<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\WorkflowNotification;
use App\Support\CompanyAuthorization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class SupplyRequestController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $companyId=CompanyAuthorization::authorize($request,'supply_request.create');
        $data=$request->validate([
            'fiscal_year_id'=>'required|integer','production_id'=>'nullable|integer','order_id'=>'nullable|integer','needed_at'=>'required|date',
            'notes'=>'nullable|string','items'=>'required|array|min:1','items.*.goods_id'=>'required|integer','items.*.quantity'=>'required|numeric|gt:0'
        ]);
        abort_unless(!empty($data['production_id'])||!empty($data['order_id']),422,'مرجع تولید یا سفارش الزامی است.');
        $model=DB::transaction(function()use($data,$companyId,$request){
            abort_unless(DB::table('fiscal_years')->where('id',$data['fiscal_year_id'])->where('company_id',$companyId)->exists(),422,'سال مالی نامعتبر است.');
            foreach(['production_id'=>'productions','order_id'=>'orders'] as $key=>$table){
                if(!empty($data[$key])) abort_unless(DB::table($table)->where('id',$data[$key])->where('company_id',$companyId)->exists(),422,'مرجع انتخاب‌شده نامعتبر است.');
            }
            $id=DB::table('supply_requests')->insertGetId([
                'company_id'=>$companyId,'fiscal_year_id'=>$data['fiscal_year_id'],'production_id'=>$data['production_id']??null,'order_id'=>$data['order_id']??null,
                'requested_by_user_id'=>$request->user()->id,'requested_at'=>now(),'needed_at'=>$data['needed_at'],'status'=>'checking_stock','notes'=>$data['notes']??null,
                'created_at'=>now(),'updated_at'=>now()
            ]);
            $shortage=false;
            foreach($data['items'] as $item){
                abort_unless(DB::table('goods')->where('id',$item['goods_id'])->where('company_id',$companyId)->where('is_active',true)->exists(),422,'کالا نامعتبر است.');
                $available=(float)DB::table('inventory as i')->join('locations as l','l.id','=','i.location_id')->where('i.company_id',$companyId)->where('i.goods_id',$item['goods_id'])
                    ->where('l.company_id',$companyId)->where('l.type','warehouse')->where('l.is_active',true)->sum('i.quantity');
                $qty=(float)$item['quantity'];$missing=max(0,$qty-$available);$shortage=$shortage||$missing>0;
                DB::table('supply_request_items')->insert([
                    'supply_request_id'=>$id,'goods_id'=>$item['goods_id'],'requested_quantity'=>$qty,'available_quantity'=>min($qty,$available),'shortage_quantity'=>$missing,
                    'supplied_quantity'=>0,'created_at'=>now(),'updated_at'=>now()
                ]);
            }
            DB::table('supply_requests')->where('id',$id)->update(['status'=>$shortage?'shortage_pending':'stock_available','updated_at'=>now()]);
            return DB::table('supply_requests')->where('id',$id)->first();
        });
        if($model->status==='shortage_pending'){
            $ids=DB::table('company_user as cu')->join('role_permissions as rp','rp.role_id','=','cu.role_id')->join('permissions as p','p.id','=','rp.permission_id')
                ->where('cu.company_id',$companyId)->where('cu.is_active',true)->where('p.slug','supply.manage')->pluck('cu.user_id');
            Notification::send(User::whereIn('id',$ids)->get(),new WorkflowNotification('supply.shortage',['supply_request_id'=>$model->id,'company_id'=>$companyId]));
        }
        return response()->json(['data'=>$model],201);
    }

    public function show(Request $request,int $supplyRequest): JsonResponse
    {
        $companyId=CompanyAuthorization::authorize($request,'supply_request.view');
        $row=DB::table('supply_requests')->where('id',$supplyRequest)->where('company_id',$companyId)->first();
        abort_unless($row,404);
        return response()->json(['data'=>$row,'items'=>DB::table('supply_request_items')->where('supply_request_id',$supplyRequest)->get()]);
    }
}