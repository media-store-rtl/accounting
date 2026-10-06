<?php

namespace App\Http\Controllers;

use App\Support\CompanyAuthorization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $companyId=CompanyAuthorization::authorize($request,'purchase.create');
        $data=$request->validate([
            'fiscal_year_id'=>'required|integer','supplier_id'=>'required|integer','supply_request_id'=>'required|integer',
            'invoice_number'=>'nullable|string|max:100','invoice_date'=>'nullable|date','purchased_at'=>'required|date','notes'=>'nullable|string',
            'items'=>'required|array|min:1','items.*.goods_id'=>'required|integer','items.*.quantity'=>'required|numeric|gt:0','items.*.unit_price'=>'required|numeric|gte:0',
            'direct_costs'=>'nullable|array','direct_costs.*.type'=>'required|string|max:50','direct_costs.*.description'=>'nullable|string','direct_costs.*.amount'=>'required|numeric|gt:0'
        ]);
        $purchase=DB::transaction(function()use($data,$companyId){
            abort_unless(DB::table('suppliers')->where('id',$data['supplier_id'])->where('company_id',$companyId)->where('is_active',true)->exists(),422,'تأمین‌کننده نامعتبر است.');
            $sr=DB::table('supply_requests')->where('id',$data['supply_request_id'])->where('company_id',$companyId)->lockForUpdate()->first();
            abort_unless($sr&&in_array($sr->status,['shortage_pending','partially_supplied'],true),422,'درخواست تأمین آماده خرید نیست.');
            abort_unless(DB::table('fiscal_years')->where('id',$data['fiscal_year_id'])->where('company_id',$companyId)->exists(),422,'سال مالی نامعتبر است.');
            $id=DB::table('purchases')->insertGetId([
                'company_id'=>$companyId,'fiscal_year_id'=>$data['fiscal_year_id'],'supplier_id'=>$data['supplier_id'],'supply_request_id'=>$sr->id,
                'invoice_number'=>$data['invoice_number']??null,'invoice_date'=>$data['invoice_date']??null,'purchased_at'=>$data['purchased_at'],
                'subtotal'=>0,'direct_cost_total'=>0,'total_amount'=>0,'status'=>'draft','notes'=>$data['notes']??null,'created_at'=>now(),'updated_at'=>now()
            ]);
            $subtotal=0;
            foreach($data['items'] as $item){
                $ri=DB::table('supply_request_items')->where('supply_request_id',$sr->id)->where('goods_id',$item['goods_id'])->lockForUpdate()->first();
                abort_unless($ri,422,'قلم خرید خارج از درخواست تأمین است.');
                $remaining=(float)$ri->shortage_quantity-(float)$ri->supplied_quantity;$qty=(float)$item['quantity'];
                abort_unless($qty<=$remaining+0.0000001,422,'مقدار خرید بیش از کسری تأمین است.');
                abort_unless(DB::table('goods')->where('id',$item['goods_id'])->where('company_id',$companyId)->where('purchasable',true)->exists(),422,'کالا قابل خرید نیست.');
                $line=$qty*(float)$item['unit_price'];$subtotal+=$line;
                DB::table('purchase_items')->insert(['purchase_id'=>$id,'goods_id'=>$item['goods_id'],'quantity'=>$qty,'unit_price'=>$item['unit_price'],'line_total'=>$line,'created_at'=>now(),'updated_at'=>now()]);
            }
            $direct=0;
            foreach($data['direct_costs']??[] as $cost){$direct+=(float)$cost['amount'];DB::table('purchase_direct_costs')->insert(['purchase_id'=>$id,'type'=>$cost['type'],'description'=>$cost['description']??null,'amount'=>$cost['amount'],'created_at'=>now(),'updated_at'=>now()]);}
            DB::table('purchases')->where('id',$id)->update(['subtotal'=>$subtotal,'direct_cost_total'=>$direct,'total_amount'=>$subtotal+$direct,'updated_at'=>now()]);
            return DB::table('purchases')->where('id',$id)->first();
        });
        return response()->json(['data'=>$purchase],201);
    }

    public function show(Request $request,int $purchase): JsonResponse
    {
        $companyId=CompanyAuthorization::authorize($request,'purchase.view');
        $row=DB::table('purchases')->where('id',$purchase)->where('company_id',$companyId)->first();
        abort_unless($row,404);
        return response()->json(['data'=>$row,'items'=>DB::table('purchase_items')->where('purchase_id',$purchase)->get(),'direct_costs'=>DB::table('purchase_direct_costs')->where('purchase_id',$purchase)->get()]);
    }
}