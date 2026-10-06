<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\WorkflowNotification;
use App\Support\CompanyAuthorization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class OrderController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $companyId=CompanyAuthorization::authorize($request,'order.create');
        $data=$request->validate([
            'fiscal_year_id'=>'required|integer','customer_id'=>'required|integer','number'=>'required|string|max:100',
            'ordered_at'=>'required|date','requested_delivery_at'=>'required|date','notes'=>'nullable|string',
            'items'=>'required|array|min:1','items.*.goods_id'=>'required|integer','items.*.quantity'=>'required|numeric|gt:0'
        ]);
        $order=DB::transaction(function()use($data,$companyId){
            abort_unless(DB::table('fiscal_years')->where('id',$data['fiscal_year_id'])->where('company_id',$companyId)->exists(),422,'سال مالی نامعتبر است.');
            abort_unless(DB::table('customers')->where('id',$data['customer_id'])->where('company_id',$companyId)->where('is_active',true)->exists(),422,'مشتری نامعتبر است.');
            $id=DB::table('orders')->insertGetId([
                'company_id'=>$companyId,'fiscal_year_id'=>$data['fiscal_year_id'],'customer_id'=>$data['customer_id'],'number'=>$data['number'],
                'ordered_at'=>$data['ordered_at'],'requested_delivery_at'=>$data['requested_delivery_at'],'notes'=>$data['notes']??null,'status'=>'checking_stock','created_at'=>now(),'updated_at'=>now()
            ]);
            $needsProduction=false;
            foreach($data['items'] as $item){
                $goods=DB::table('goods')->where('id',$item['goods_id'])->where('company_id',$companyId)->where('is_active',true)->first();
                abort_unless($goods&&$goods->sellable,422,'کالای سفارش معتبر یا قابل فروش نیست.');
                $available=(float)DB::table('inventory as i')->join('locations as l','l.id','=','i.location_id')->where('i.company_id',$companyId)->where('i.goods_id',$item['goods_id'])
                    ->where('l.company_id',$companyId)->where('l.type','warehouse')->where('l.is_active',true)->sum('i.quantity');
                $qty=(float)$item['quantity'];$shortage=max(0,$qty-$available);$needsProduction=$needsProduction||$shortage>0;
                DB::table('order_items')->insert([
                    'order_id'=>$id,'goods_id'=>$item['goods_id'],'quantity'=>$qty,'available_quantity'=>min($qty,$available),'shortage_quantity'=>$shortage,
                    'fulfillment_status'=>$shortage>0?'production_required':'ready_for_delivery','created_at'=>now(),'updated_at'=>now()
                ]);
            }
            DB::table('orders')->where('id',$id)->update(['status'=>$needsProduction?'production_required':'ready_for_delivery','updated_at'=>now()]);
            return DB::table('orders')->where('id',$id)->first();
        });
        if($order->status==='production_required'){
            $ids=DB::table('company_user as cu')->join('role_permissions as rp','rp.role_id','=','cu.role_id')->join('permissions as p','p.id','=','rp.permission_id')
                ->where('cu.company_id',$companyId)->where('cu.is_active',true)->where('p.slug','production.supervise')->pluck('cu.user_id');
            Notification::send(User::whereIn('id',$ids)->get(),new WorkflowNotification('order.production_required',['order_id'=>$order->id,'company_id'=>$companyId]));
        }
        return response()->json(['data'=>$order],201);
    }

    public function show(Request $request,int $order): JsonResponse
    {
        $companyId=CompanyAuthorization::authorize($request,'order.view');
        $row=DB::table('orders')->where('id',$order)->where('company_id',$companyId)->first();
        abort_unless($row,404);
        return response()->json(['data'=>$row,'items'=>DB::table('order_items')->where('order_id',$order)->get()]);
    }
}