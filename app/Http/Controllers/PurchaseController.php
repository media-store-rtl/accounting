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
            'fiscal_year_id'=>'nullable|integer','supplier_id'=>'nullable|integer','supply_request_id'=>'nullable|integer',
            'invoice_number'=>'nullable|string|max:100','invoice_date'=>'nullable|date','purchased_at'=>'required|date','notes'=>'nullable|string',
            'items'=>'required|array|min:1','items.*.goods_id'=>'required|integer','items.*.quantity'=>'required|numeric|gt:0','items.*.unit_price'=>'required|numeric|gt:0',
            'direct_costs'=>'nullable|array','direct_costs.*.type'=>'required|string|max:50','direct_costs.*.description'=>'nullable|string','direct_costs.*.amount'=>'required|numeric|gt:0'
        ]);
        $fiscalYearId=$data['fiscal_year_id']??$request->session()->get('fiscal_year_id');
        if (!$fiscalYearId) {
            $fiscalYearId=DB::table('fiscal_years')->where('company_id',$companyId)->where('is_closed',false)->orderByDesc('starts_at')->value('id');
        }
        $supplierId=$data['supplier_id']??null;
        $purchase=DB::transaction(function()use($data,$companyId,$fiscalYearId,$supplierId){
            if ($supplierId !== null) {
                abort_unless(DB::table('suppliers')->where('id',$supplierId)->where('company_id',$companyId)->where('is_active',true)->exists(),422,'تأمین‌کننده نامعتبر است.');
            }
            $sr=null;
            if (!empty($data['supply_request_id'])) {
                $sr=DB::table('supply_requests')->where('id',$data['supply_request_id'])->where('company_id',$companyId)->lockForUpdate()->first();
                abort_unless($sr && $sr->status === 'purchasing',422,'ابتدا درخواست را وارد مرحله «در حال خرید» کنید.');
            }
            abort_unless($fiscalYearId && DB::table('fiscal_years')->where('id',$fiscalYearId)->where('company_id',$companyId)->where('is_closed',false)->exists(),422,'سال مالی فعال نامعتبر است یا تعریف نشده است.');
            $id=DB::table('purchases')->insertGetId([
                'company_id'=>$companyId,'fiscal_year_id'=>$fiscalYearId,'supplier_id'=>$supplierId,'supply_request_id'=>$sr?->id,
                'invoice_number'=>$data['invoice_number']??null,'invoice_date'=>$data['invoice_date']??null,'purchased_at'=>$data['purchased_at'],
                'subtotal'=>0,'direct_cost_total'=>0,'total_amount'=>0,'status'=>'draft','notes'=>$data['notes']??null,'created_at'=>now(),'updated_at'=>now()
            ]);
            $subtotal=0;
            foreach($data['items'] as $item){
                $qty=(float)$item['quantity'];
                abort_unless(DB::table('goods')->where('id',$item['goods_id'])->where('company_id',$companyId)->where('purchasable',true)->where('is_active',true)->exists(),422,'کالا قابل خرید نیست.');
                // Purchase lines are independent of supply-request lines. A purchase may be
                // created without a request, and a linked purchase may include extra goods
                // or quantities beyond the original request when procurement requires it.
                $line=$qty*(float)$item['unit_price'];$subtotal+=$line;
                DB::table('purchase_items')->insert(['purchase_id'=>$id,'goods_id'=>$item['goods_id'],'quantity'=>$qty,'unit_price'=>$item['unit_price'],'line_total'=>$line,'created_at'=>now(),'updated_at'=>now()]);
            }
            $direct=0;
            foreach($data['direct_costs']??[] as $cost){$direct+=(float)$cost['amount'];DB::table('purchase_direct_costs')->insert(['purchase_id'=>$id,'type'=>$cost['type'],'description'=>$cost['description']??null,'amount'=>$cost['amount'],'created_at'=>now(),'updated_at'=>now()]);}
            DB::table('purchases')->where('id',$id)->update(['subtotal'=>$subtotal,'direct_cost_total'=>$direct,'total_amount'=>$subtotal+$direct,'updated_at'=>now()]);
            if ($sr) {
                DB::table('supply_requests')->where('id',$sr->id)->where('company_id',$companyId)->where('status','purchasing')->update(['status'=>'purchased','updated_at'=>now()]);
            }
            return [
                'purchase' => DB::table('purchases')->where('id',$id)->first(),
                'supply_request' => $sr,
            ];
        });
        $purchase = $purchase['purchase'];
        if (!empty($purchase->supply_request_id)) {
            $supplyRequest = DB::table('supply_requests')->where('id',$purchase->supply_request_id)->where('company_id',$companyId)->first();
            if ($supplyRequest && $supplyRequest->status === 'purchased') {
                app(\App\Services\NotificationRecipientService::class)->send($companyId,'supply.purchased',['supply_request_id'=>$supplyRequest->id,'needed_at'=>$supplyRequest->needed_at],(int)$request->user()->id,'supply_request.create');
            }
        }
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