<?php

namespace App\Http\Controllers;

use App\Services\InventoryService;
use App\Services\NotificationRecipientService;
use App\Support\CompanyAuthorization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $companyId=CompanyAuthorization::authorize($request,'order.view');
        $orders=DB::table('orders as o')->join('customers as c','c.id','=','o.customer_id')
            ->where('o.company_id',$companyId)->select('o.*','c.name as customer_name')->latest('o.id')->paginate(20);
        return view('sales.orders.index',compact('orders'));
    }

    public function create(Request $request)
    {
        $companyId=CompanyAuthorization::authorize($request,'order.create');
        $customers=DB::table('customers')->where('company_id',$companyId)->where('is_active',true)->orderBy('name')->get();
        $goods=DB::table('goods')->where('company_id',$companyId)->where('is_active',true)->where('sellable',true)->orderBy('name')->get();
        $fiscalYear=DB::table('fiscal_years')->where('company_id',$companyId)->where('is_closed',false)->latest('id')->first();
        abort_unless($fiscalYear,422,'سال مالی باز برای ثبت سفارش وجود ندارد.');
        return view('sales.orders.create',compact('customers','goods','fiscalYear'));
    }

    public function store(Request $request): JsonResponse
    {
        $companyId=CompanyAuthorization::authorize($request,'order.create');
        $data=$request->validate([
            'fiscal_year_id'=>'required|integer','customer_id'=>'required|integer','number'=>'required|string|max:100',
            'ordered_at'=>'required|date','requested_delivery_at'=>'required|date','notes'=>'nullable|string',
            'items'=>'required|array|min:1','items.*.goods_id'=>'required|integer','items.*.quantity'=>'required|numeric|gt:0'
        ]);
        $order=DB::transaction(function()use($data,$companyId){
            abort_unless(DB::table('fiscal_years')->where('id',$data['fiscal_year_id'])->where('company_id',$companyId)->where('is_closed',false)->exists(),422,'سال مالی نامعتبر است.');
            abort_unless(DB::table('customers')->where('id',$data['customer_id'])->where('company_id',$companyId)->where('is_active',true)->exists(),422,'مشتری نامعتبر است.');
            $id=DB::table('orders')->insertGetId([
                'company_id'=>$companyId,'fiscal_year_id'=>$data['fiscal_year_id'],'customer_id'=>$data['customer_id'],'number'=>$data['number'],
                'ordered_at'=>$data['ordered_at'],'requested_delivery_at'=>$data['requested_delivery_at'],'notes'=>$data['notes']??null,
                'status'=>'checking_stock','created_at'=>now(),'updated_at'=>now()
            ]);
            $needsProduction=false;
            foreach($data['items'] as $item){
                $goods=DB::table('goods')->where('id',$item['goods_id'])->where('company_id',$companyId)->where('is_active',true)->first();
                abort_unless($goods&&$goods->sellable,422,'کالای سفارش معتبر یا قابل فروش نیست.');
                $available=(float)DB::table('inventory as i')->join('locations as l','l.id','=','i.location_id')
                    ->where('i.company_id',$companyId)->where('i.goods_id',$item['goods_id'])->where('l.company_id',$companyId)
                    ->where('l.type','warehouse')->where('l.is_active',true)->sum('i.quantity');
                $qty=(float)$item['quantity'];$shortage=max(0,$qty-$available);$needsProduction=$needsProduction||$shortage>0;
                DB::table('order_items')->insert([
                    'order_id'=>$id,'goods_id'=>$item['goods_id'],'quantity'=>$qty,'available_quantity'=>min($qty,$available),
                    'shortage_quantity'=>$shortage,'fulfillment_status'=>$shortage>0?'production_required':'ready_for_delivery',
                    'created_at'=>now(),'updated_at'=>now()
                ]);
            }
            $status=$needsProduction?'production_required':'ready_for_delivery';
            DB::table('orders')->where('id',$id)->update(['status'=>$status,'updated_at'=>now()]);
            return DB::table('orders')->where('id',$id)->first();
        });
        if ($order->status === 'production_required') {
            app(NotificationRecipientService::class)->send($companyId, 'order.production_required', ['order_id' => $order->id], (int) $request->user()->id, 'production.supervise');
        }
        return response()->json(['data'=>$order],201);
    }

    public function show(Request $request,int $order)
    {
        $companyId=CompanyAuthorization::authorize($request,'order.view');
        $row=DB::table('orders as o')->join('customers as c','c.id','=','o.customer_id')
            ->where('o.id',$order)->where('o.company_id',$companyId)->select('o.*','c.name as customer_name')->first();
        abort_unless($row,404);
        $items=DB::table('order_items as oi')->join('goods as g','g.id','=','oi.goods_id')
            ->where('oi.order_id',$order)->select('oi.*','g.name as goods_name','g.code as goods_code')->get();
        $deliveries=DB::table('delivery_requests')->where('order_id',$order)->orderByDesc('id')->get();
        if($request->expectsJson()) return response()->json(['data'=>$row,'items'=>$items,'deliveries'=>$deliveries]);
        return view('sales.orders.show',compact('row','items','deliveries'));
    }

    public function refreshFulfillment(Request $request,int $order): JsonResponse
    {
        $companyId=CompanyAuthorization::authorize($request,'order.refresh');
        $result=DB::transaction(function()use($order,$companyId){
            $row=DB::table('orders')->where('id',$order)->where('company_id',$companyId)->lockForUpdate()->first();
            abort_unless($row,404);
            $items=DB::table('order_items')->where('order_id',$order)->lockForUpdate()->get();
            $ready=true;
            foreach($items as $item){
                $available=(float)app(InventoryService::class)->available($companyId,$item->goods_id);
                $shortage=max(0,(float)$item->quantity-$available);
                DB::table('order_items')->where('id',$item->id)->update([
                    'available_quantity'=>min((float)$item->quantity,$available),'shortage_quantity'=>$shortage,
                    'fulfillment_status'=>$shortage>0?'production_required':'ready_for_delivery','updated_at'=>now()
                ]);
                $ready=$ready&&$shortage<=0;
            }
            $status=$ready?'ready_for_delivery':($row->production_due_at?'awaiting_production':'production_required');
            DB::table('orders')->where('id',$order)->update(['status'=>$status,'updated_at'=>now()]);
            return DB::table('orders')->where('id',$order)->first();
        });
        if ($result->status === 'ready_for_delivery') {
            app(NotificationRecipientService::class)->send($companyId, 'order.ready_for_delivery', ['order_id' => $order], (int) $request->user()->id, 'order.view');
        }
        return response()->json(['data'=>$result]);
    }

    public function setProductionDue(Request $request,int $order): JsonResponse
    {
        $companyId=CompanyAuthorization::authorize($request,'production.supervise');
        $data=$request->validate(['production_due_at'=>'required|date']);
        $updated=DB::table('orders')->where('id',$order)->where('company_id',$companyId)
            ->whereIn('status',['production_required','awaiting_production'])->update([
                'production_due_at'=>$data['production_due_at'],'status'=>'awaiting_production','updated_at'=>now()
            ]);
        abort_unless($updated,404);
        app(NotificationRecipientService::class)->send($companyId, 'order.production_due_set', ['order_id' => $order, 'production_due_at' => $data['production_due_at']], (int) $request->user()->id, 'order.view');
        return response()->json(['data'=>DB::table('orders')->find($order)]);
    }

    public function deliveryRequest(Request $request,int $order): JsonResponse
    {
        $companyId=CompanyAuthorization::authorize($request,'delivery_request.create');
        $data=$request->validate([
            'scheduled_at'=>'required|date','vehicle'=>'nullable|string|max:255','recipient_name'=>'required|string|max:255','notes'=>'nullable|string'
        ]);
        $requestId=DB::transaction(function()use($data,$order,$companyId,$request){
            $row=DB::table('orders')->where('id',$order)->where('company_id',$companyId)->lockForUpdate()->first();
            abort_unless($row,404); abort_unless($row->status==='ready_for_delivery',422,'سفارش هنوز آماده تحویل نیست.');
            $items=DB::table('order_items')->where('order_id',$order)->get();
            abort_unless($items->isNotEmpty(),422,'سفارش فاقد قلم است.');
            $id=DB::table('delivery_requests')->insertGetId([
                'company_id'=>$companyId,'order_id'=>$order,'customer_id'=>$row->customer_id,'requested_by_user_id'=>$request->user()->id,
                'requested_at'=>now(),'scheduled_at'=>$data['scheduled_at'],'vehicle'=>$data['vehicle']??null,
                'recipient_name'=>$data['recipient_name'],'status'=>'pending','notes'=>$data['notes']??null,'created_at'=>now(),'updated_at'=>now()
            ]);
            foreach($items as $item) DB::table('delivery_request_items')->insert([
                'delivery_request_id'=>$id,'goods_id'=>$item->goods_id,'quantity'=>$item->quantity,'created_at'=>now(),'updated_at'=>now()
            ]);
            DB::table('orders')->where('id',$order)->update(['status'=>'delivery_requested','updated_at'=>now()]);
            return $id;
        });
        app(NotificationRecipientService::class)->send($companyId, 'delivery.requested', ['delivery_request_id' => $requestId, 'order_id' => $order], (int) $request->user()->id, 'warehouse.delivery.manage');
        return response()->json(['data'=>DB::table('delivery_requests')->find($requestId)],201);
    }

}
