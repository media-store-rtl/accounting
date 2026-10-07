<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\WorkflowNotification;
use App\Services\InventoryService;
use App\Services\InventoryValuationService;
use App\Support\CompanyAuthorization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class PurchaseReceiptController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $companyId=CompanyAuthorization::authorize($request,'purchase.receipt.create');
        $data=$request->validate([
            'purchase_id'=>'required|integer','warehouse_location_id'=>'required|integer','received_at'=>'required|date','notes'=>'nullable|string',
            'items'=>'required|array|min:1','items.*.goods_id'=>'required|integer','items.*.quantity'=>'required|numeric|gt:0'
        ]);
        $id=DB::transaction(function()use($data,$companyId,$request){
            $purchase=DB::table('purchases')->where('id',$data['purchase_id'])->where('company_id',$companyId)->lockForUpdate()->first();
            abort_unless($purchase&&$purchase->status!=='cancelled',422,'خرید معتبر نیست.');
            abort_unless(DB::table('locations')->where('id',$data['warehouse_location_id'])->where('company_id',$companyId)->where('type','warehouse')->where('is_active',true)->exists(),422,'انبار معتبر نیست.');
            $id=DB::table('purchase_receipts')->insertGetId([
                'company_id'=>$companyId,'purchase_id'=>$purchase->id,'warehouse_location_id'=>$data['warehouse_location_id'],'received_by_user_id'=>$request->user()->id,
                'received_at'=>$data['received_at'],'status'=>'pending','notes'=>$data['notes']??null,'created_at'=>now(),'updated_at'=>now()
            ]);
            foreach($data['items'] as $item){
                $pi=DB::table('purchase_items')->where('purchase_id',$purchase->id)->where('goods_id',$item['goods_id'])->first();
                abort_unless($pi,422,'قلم دریافت خارج از خرید است.');
                $approved=(float)DB::table('purchase_receipt_items as x')->join('purchase_receipts as r','r.id','=','x.purchase_receipt_id')
                    ->where('r.purchase_id',$purchase->id)->where('r.status','approved')->where('x.goods_id',$item['goods_id'])->sum('x.quantity');
                abort_unless($approved+(float)$item['quantity']<=(float)$pi->quantity+0.0000001,422,'مقدار دریافت بیش از خرید است.');
                DB::table('purchase_receipt_items')->insert(['purchase_receipt_id'=>$id,'goods_id'=>$item['goods_id'],'quantity'=>$item['quantity'],'created_at'=>now(),'updated_at'=>now()]);
            }
            return $id;
        });
        return response()->json(['data'=>DB::table('purchase_receipts')->where('id',$id)->first()],201);
    }

    public function approve(Request $request,int $purchaseReceipt): JsonResponse
    {
        $companyId=CompanyAuthorization::authorize($request,'purchase.receipt.approve');
        [$purchase,$receipt]=DB::transaction(function()use($companyId,$request,$purchaseReceipt){
            $receipt=DB::table('purchase_receipts')->where('id',$purchaseReceipt)->where('company_id',$companyId)->lockForUpdate()->first();
            abort_unless($receipt&&$receipt->status==='pending',422,'این رسید قبلاً تعیین تکلیف شده است.');
            $purchase=DB::table('purchases')->where('id',$receipt->purchase_id)->where('company_id',$companyId)->lockForUpdate()->firstOrFail();
            foreach(DB::table('purchase_receipt_items')->where('purchase_receipt_id',$receipt->id)->get() as $item){
                $pi=DB::table('purchase_items')->where('purchase_id',$purchase->id)->where('goods_id',$item->goods_id)->lockForUpdate()->firstOrFail();
                $approved=(float)DB::table('purchase_receipt_items as x')->join('purchase_receipts as r','r.id','=','x.purchase_receipt_id')
                    ->where('r.purchase_id',$purchase->id)->where('r.status','approved')->where('x.goods_id',$item->goods_id)->sum('x.quantity');
                abort_unless($approved+(float)$item->quantity<=(float)$pi->quantity+0.0000001,422,'مقدار دریافت بیش از خرید است.');
                app(InventoryService::class)->receive($companyId,$receipt->warehouse_location_id,$item->goods_id,(float)$item->quantity,'purchase_receipts',(int)$receipt->id,['purchase_id'=>$purchase->id,'purchase_item_id'=>$pi->id]);

                $directCostPerUnit = 0.0;
                if ((float)$purchase->subtotal > 0 && (float)$purchase->direct_cost_total > 0) {
                    $directCostPerUnit = ((float)$purchase->direct_cost_total * (float)$pi->line_total / (float)$purchase->subtotal) / (float)$pi->quantity;
                }
                app(InventoryValuationService::class)->recordReceipt(
                    $companyId, (int)$receipt->warehouse_location_id, (int)$item->goods_id,
                    (int)$purchase->fiscal_year_id, (int)$purchase->id, (int)$pi->id,
                    (int)$receipt->id, (int)$item->id, (float)$item->quantity,
                    (float)$pi->unit_price + $directCostPerUnit, $receipt->received_at
                );
                $receiptItemId=DB::table('purchase_receipt_items')->where('purchase_receipt_id',$receipt->id)->where('goods_id',$item->goods_id)->value('id');
                app(\App\Services\InventoryValuationService::class)->registerPurchaseReceipt(
                    $companyId,(int)$purchase->id,(int)$pi->id,(int)$receipt->id,(int)$receiptItemId,
                    (int)$receipt->warehouse_location_id,(int)$item->goods_id,(int)$purchase->fiscal_year_id,(float)$item->quantity,(string)$receipt->received_at
                );
            }
            DB::table('purchase_receipts')->where('id',$receipt->id)->update(['status'=>'approved','approved_at'=>now(),'approved_by_user_id'=>$request->user()->id,'updated_at'=>now()]);
            $complete=true;
            foreach(DB::table('purchase_items')->where('purchase_id',$purchase->id)->get() as $pi){
                $received=(float)DB::table('purchase_receipt_items as x')->join('purchase_receipts as r','r.id','=','x.purchase_receipt_id')
                    ->where('r.purchase_id',$purchase->id)->where('r.status','approved')->where('x.goods_id',$pi->goods_id)->sum('x.quantity');
                if($received+0.0000001<(float)$pi->quantity){$complete=false;break;}
            }
            DB::table('purchases')->where('id',$purchase->id)->update(['status'=>$complete?'received':'partially_received','updated_at'=>now()]);
            if($purchase->supply_request_id){
                foreach(DB::table('supply_request_items')->where('supply_request_id',$purchase->supply_request_id)->lockForUpdate()->get() as $sri){
                    $supplied=(float)DB::table('purchase_receipt_items as x')->join('purchase_receipts as r','r.id','=','x.purchase_receipt_id')->join('purchases as p','p.id','=','r.purchase_id')
                        ->where('p.supply_request_id',$purchase->supply_request_id)->where('r.status','approved')->where('x.goods_id',$sri->goods_id)->sum('x.quantity');
                    DB::table('supply_request_items')->where('id',$sri->id)->update(['supplied_quantity'=>$supplied,'updated_at'=>now()]);
                }
                $remaining=DB::table('supply_request_items')->where('supply_request_id',$purchase->supply_request_id)->whereColumn('supplied_quantity','<','shortage_quantity')->exists();
                DB::table('supply_requests')->where('id',$purchase->supply_request_id)->update(['status'=>$remaining?'partially_supplied':'fulfilled','updated_at'=>now()]);
            }
            return [$purchase,DB::table('purchase_receipts')->where('id',$receipt->id)->first()];
        });
        $ids=DB::table('company_user as cu')->join('role_permissions as rp','rp.role_id','=','cu.role_id')->join('permissions as p','p.id','=','rp.permission_id')
            ->where('cu.company_id',$companyId)->where('cu.is_active',true)->where('p.slug','finance.purchase.receive')->pluck('cu.user_id');
        Notification::send(User::whereIn('id',$ids)->get(),new WorkflowNotification('purchase.received_financial_value',[
            'purchase_id'=>$purchase->id,'purchase_total_amount'=>(float)$purchase->total_amount,'direct_cost_total'=>(float)$purchase->direct_cost_total,'receipt_id'=>$receipt->id,'company_id'=>$companyId
        ]));
        return response()->json(['data'=>$receipt]);
    }
}