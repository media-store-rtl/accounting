<?php

namespace App\Http\Controllers;

use App\Services\InventoryService;
use App\Services\NotificationRecipientService;
use App\Services\InventoryValuationService;
use App\Support\CompanyAuthorization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseReceiptController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $companyId=CompanyAuthorization::authorize($request,'purchase.receipt.create');
        $data=$request->validate([
            'purchase_id'=>'nullable|integer',
            'warehouse_location_id'=>'required|integer',
            'received_at'=>'required|date',
            'notes'=>'nullable|string',
            'items'=>'required|array|min:1',
            'items.*.goods_id'=>'required|integer|distinct',
            'items.*.quantity'=>'required|numeric|gt:0',
        ]);

        $id=DB::transaction(function()use($data,$companyId,$request){
            $purchase=null;
            if (!empty($data['purchase_id'])) {
                $purchase=DB::table('purchases')->where('id',$data['purchase_id'])->where('company_id',$companyId)->lockForUpdate()->first();
                if (!$purchase || $purchase->status==='cancelled') {
                    throw ValidationException::withMessages(['purchase_id'=>'خرید انتخاب‌شده پیدا نشد یا لغو شده است. یک خرید معتبر انتخاب کنید.']);
                }
            }
            if (!DB::table('locations')->where('id',$data['warehouse_location_id'])->where('company_id',$companyId)->whereRaw("LOWER(TRIM(type)) = ?",['مواد اولیه'])->where('is_active',true)->exists()) {
                throw ValidationException::withMessages(['warehouse_location_id'=>'انبار انتخاب‌شده معتبر یا فعال نیست. یک انبار فعال از نوع «مواد اولیه» انتخاب کنید.']);
            }

            $id=DB::table('purchase_receipts')->insertGetId([
                'company_id'=>$companyId,
                'purchase_id'=>$purchase?->id,
                'warehouse_location_id'=>$data['warehouse_location_id'],
                'received_by_user_id'=>$request->user()->id,
                'received_at'=>$data['received_at'],
                'status'=>'pending',
                'notes'=>$data['notes']??null,
                'created_at'=>now(),
                'updated_at'=>now(),
            ]);

            foreach($data['items'] as $index=>$item){
                $goods=DB::table('goods')->where('id',$item['goods_id'])->where('company_id',$companyId)
                    ->where('purchasable',true)->where('is_active',true)->first();
                if (!$goods) {
                    throw ValidationException::withMessages(["items.{$index}.goods_id"=>'کالای انتخاب‌شده معتبر، فعال یا قابل خرید نیست.']);
                }
                if ($purchase) {
                    $pi=DB::table('purchase_items')->where('purchase_id',$purchase->id)->where('goods_id',$item['goods_id'])->first();
                    if (!$pi) {
                        throw ValidationException::withMessages(["items.{$index}.goods_id"=>'این کالا در اقلام خرید انتخاب‌شده وجود ندارد.']);
                    }
                    $approved=(float)DB::table('purchase_receipt_items as x')->join('purchase_receipts as pr','pr.id','=','x.purchase_receipt_id')
                        ->where('pr.purchase_id',$purchase->id)->where('pr.status','approved')->where('x.goods_id',$item['goods_id'])->sum('x.quantity');
                    $pending=(float)DB::table('purchase_receipt_items as x')->join('purchase_receipts as pr','pr.id','=','x.purchase_receipt_id')
                        ->where('pr.purchase_id',$purchase->id)->where('pr.status','pending')->where('x.goods_id',$item['goods_id'])->sum('x.quantity');
                    if ($approved+$pending+(float)$item['quantity']>(float)$pi->quantity+0.0000001) {
                        $remaining=max(0,(float)$pi->quantity-$approved-$pending);
                        throw ValidationException::withMessages(["items.{$index}.quantity"=>'مقدار رسید از باقی‌مانده خرید بیشتر است. حداکثر مقدار مجاز: '.number_format($remaining,4,'.',',').'.']);
                    }
                }
                DB::table('purchase_receipt_items')->insert([
                    'purchase_receipt_id'=>$id,
                    'goods_id'=>$item['goods_id'],
                    'quantity'=>$item['quantity'],
                    'created_at'=>now(),
                    'updated_at'=>now(),
                ]);
            }
            return $id;
        });

        return response()->json(['data'=>DB::table('purchase_receipts')->where('id',$id)->first()],201);
    }

    public function approve(Request $request,int $purchaseReceipt): JsonResponse
    {
        $sessionCompanyId=(int)$request->session()->get('company_id');
        $candidate=DB::table('purchase_receipts')->where('id',$purchaseReceipt)->where('company_id',$sessionCompanyId)->first();
        abort_unless($candidate,404);
        $companyId=CompanyAuthorization::authorize($request,$candidate->purchase_id ? 'purchase.receipt.approve' : 'purchase.create');
        $data=[];
        if (!$candidate->purchase_id) {
            $data=$request->validate([
                'supplier_id'=>'required|integer',
                'invoice_number'=>'nullable|string|max:100',
                'invoice_date'=>'nullable|date',
                'unit_prices'=>'required|array|min:1',
                'unit_prices.*'=>'required|numeric|gt:0',
            ]);
        }
        [$purchase,$receipt]=DB::transaction(function()use($companyId,$request,$purchaseReceipt,$data){
            $receipt=DB::table('purchase_receipts')->where('id',$purchaseReceipt)->where('company_id',$companyId)->lockForUpdate()->first();
            abort_unless($receipt&&$receipt->status==='pending',422,'این رسید قبلاً تعیین تکلیف شده است.');

            if (!$receipt->purchase_id) {
                $supplierId=(int)$data['supplier_id'];
                abort_unless(DB::table('suppliers')->where('id',$supplierId)->where('company_id',$companyId)->where('is_active',true)->exists(),422,'تأمین‌کننده انتخاب‌شده معتبر یا فعال نیست.');
                $receiptItems=DB::table('purchase_receipt_items')->where('purchase_receipt_id',$receipt->id)->orderBy('id')->get();
                abort_unless($receiptItems->isNotEmpty(),422,'رسید باید حداقل یک قلم کالا داشته باشد.');
                $receiptGoodsIds=$receiptItems->pluck('goods_id')->map(fn($id)=>(int)$id)->sort()->values()->all();
                $priceGoodsIds=collect(array_keys($data['unit_prices']))->map(fn($id)=>(int)$id)->sort()->values()->all();
                if ($receiptGoodsIds!==$priceGoodsIds) {
                    throw ValidationException::withMessages(['unit_prices'=>'قیمت واحد باید دقیقاً برای تمام اقلام همین رسید وارد شود.']);
                }
                $fiscalYearId=$request->session()->get('fiscal_year_id');
                $fiscalYearIsOpen=$fiscalYearId && DB::table('fiscal_years')->where('id',$fiscalYearId)->where('company_id',$companyId)->where('is_closed',false)->exists();
                if (!$fiscalYearIsOpen) {
                    $fiscalYearId=DB::table('fiscal_years')->where('company_id',$companyId)->where('is_closed',false)->orderByDesc('starts_at')->value('id');
                }
                abort_unless($fiscalYearId,422,'سال مالی باز برای ثبت خرید تعریف نشده است.');
                $purchaseId=DB::table('purchases')->insertGetId([
                    'company_id'=>$companyId,
                    'fiscal_year_id'=>$fiscalYearId,
                    'supplier_id'=>$supplierId,
                    'supply_request_id'=>null,
                    'invoice_number'=>$data['invoice_number']??null,
                    'invoice_date'=>$data['invoice_date']??null,
                    'purchased_at'=>substr((string)$receipt->received_at,0,10),
                    'subtotal'=>0,
                    'direct_cost_total'=>0,
                    'total_amount'=>0,
                    'status'=>'draft',
                    'notes'=>$receipt->notes,
                    'created_at'=>now(),
                    'updated_at'=>now(),
                ]);
                $subtotal=0.0;
                foreach($receiptItems as $receiptItem) {
                    $goods=DB::table('goods')->where('id',$receiptItem->goods_id)->where('company_id',$companyId)->where('purchasable',true)->where('is_active',true)->first();
                    abort_unless($goods,422,'یکی از کالاهای رسید دیگر قابل خرید نیست.');
                    $unitPrice=(float)$data['unit_prices'][$receiptItem->goods_id];
                    $lineTotal=(float)$receiptItem->quantity*$unitPrice;
                    $subtotal+=$lineTotal;
                    DB::table('purchase_items')->insert([
                        'purchase_id'=>$purchaseId,
                        'goods_id'=>$receiptItem->goods_id,
                        'quantity'=>$receiptItem->quantity,
                        'unit_price'=>$unitPrice,
                        'line_total'=>$lineTotal,
                        'created_at'=>now(),
                        'updated_at'=>now(),
                    ]);
                }
                DB::table('purchases')->where('id',$purchaseId)->update(['subtotal'=>$subtotal,'total_amount'=>$subtotal,'updated_at'=>now()]);
                DB::table('purchase_receipts')->where('id',$receipt->id)->update(['purchase_id'=>$purchaseId,'updated_at'=>now()]);
                $receipt=DB::table('purchase_receipts')->where('id',$receipt->id)->first();
            }

            $purchase=DB::table('purchases')->where('id',$receipt->purchase_id)->where('company_id',$companyId)->lockForUpdate()->firstOrFail();
            $hasItems=DB::table('purchase_items')->where('purchase_id',$purchase->id)->exists();
            $hasUnpricedItems=DB::table('purchase_items')->where('purchase_id',$purchase->id)->where('unit_price','<=',0)->exists();
            abort_unless($hasItems && !$hasUnpricedItems && (float)$purchase->subtotal>0,422,'تا زمانی که قیمت واحد معتبر برای تمام اقلام خرید ثبت نشده باشد، تأیید رسید و ورود کالا به انبار مجاز نیست.');
            foreach(DB::table('purchase_receipt_items')->where('purchase_receipt_id',$receipt->id)->get() as $item){
                $pi=DB::table('purchase_items')->where('purchase_id',$purchase->id)->where('goods_id',$item->goods_id)->lockForUpdate()->firstOrFail();
                $approved=(float)DB::table('purchase_receipt_items as x')->join('purchase_receipts as r','r.id','=','x.purchase_receipt_id')
                    ->where('r.purchase_id',$purchase->id)->where('r.status','approved')->where('x.goods_id',$item->goods_id)->sum('x.quantity');
                if ($approved+(float)$item->quantity>(float)$pi->quantity+0.0000001) {
                    $otherPending=DB::table('purchase_receipt_items as x')->join('purchase_receipts as r','r.id','=','x.purchase_receipt_id')
                        ->where('r.purchase_id',$purchase->id)->where('r.status','pending')->where('r.id','<>',$receipt->id)->where('x.goods_id',$item->goods_id)
                        ->select('r.id','x.quantity')->orderBy('r.id')->get();
                    $message='تأیید این رسید از مقدار خرید بیشتر می‌شود. مقدار خرید: '.number_format((float)$pi->quantity,4,'.',',')
                        .'؛ قبلاً تأییدشده: '.number_format($approved,4,'.',',')
                        .'؛ مقدار این رسید: '.number_format((float)$item->quantity,4,'.',',').'.';
                    if ($otherPending->isNotEmpty()) {
                        $message.=' رسیدهای دیگرِ در انتظار تأیید برای همین کالا: '.$otherPending->map(fn($row)=>'#'.$row->id.' ('.number_format((float)$row->quantity,4,'.',',').')')->implode('، ').'.';
                    }
                    throw ValidationException::withMessages(['receipt'=>'رسید #'.$receipt->id.' — '.$message.' رسیدهای قبلی را در جدول همین صفحه بررسی کنید.']);
                }
                app(InventoryService::class)->receive($companyId,$receipt->warehouse_location_id,$item->goods_id,(float)$item->quantity,'purchase_receipts',(int)$receipt->id,['purchase_id'=>$purchase->id,'purchase_item_id'=>$pi->id]);

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
        app(NotificationRecipientService::class)->send(
            $companyId,
            'purchase.received_financial_value',
            [
                'purchase_id' => $purchase->id,
                'purchase_total_amount' => (float) $purchase->total_amount,
                'direct_cost_total' => (float) $purchase->direct_cost_total,
                'receipt_id' => $receipt->id,
            ],
            (int) $request->user()->id,
            'finance.purchase.receive'
        );
        return response()->json(['data'=>$receipt]);
    }
}