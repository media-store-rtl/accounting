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
        $statusGroups = [
            'needs_supply' => ['production_required', 'awaiting_production'],
            'ready' => ['ready_for_delivery'],
            'delivery' => ['delivery_requested', 'issued'],
            'completed' => ['completed'],
        ];
        $selectedStatus = (string) $request->query('status', '');
        $ordersQuery = DB::table('orders as o')->join('customers as c','c.id','=','o.customer_id')
            ->where('o.company_id',$companyId)->select('o.*','c.name as customer_name')
            ->addSelect(['delivery_request_id' => DB::table('delivery_requests')->select('id')->whereColumn('order_id','o.id')->latest('id')->limit(1)])
            ->addSelect(['delivery_request_status' => DB::table('delivery_requests')->select('status')->whereColumn('order_id','o.id')->latest('id')->limit(1)])
            ->addSelect(['delivery_request_sales_approved_at' => DB::table('delivery_requests')->select('sales_approved_at')->whereColumn('order_id','o.id')->latest('id')->limit(1)]);
        if (isset($statusGroups[$selectedStatus])) {
            $ordersQuery->whereIn('o.status', $statusGroups[$selectedStatus]);
        }
        $orders = $ordersQuery->latest('o.id')->paginate(20)->withQueryString();
        $statusCounts = DB::table('orders')->where('company_id',$companyId)
            ->select('status', DB::raw('COUNT(*) as total'))->groupBy('status')->pluck('total','status');
        $salesCounts = [
            'needs_supply' => (int) ($statusCounts['production_required'] ?? 0) + (int) ($statusCounts['awaiting_production'] ?? 0),
            'ready' => (int) ($statusCounts['ready_for_delivery'] ?? 0),
            'delivery' => (int) ($statusCounts['delivery_requested'] ?? 0) + (int) ($statusCounts['issued'] ?? 0),
            'completed' => (int) ($statusCounts['completed'] ?? 0),
        ];
        $canApproveDeliveryRelease = $request->user()->hasCompanyPermission($companyId, 'delivery_request.sales_approve');
        return view('sales.orders.index',compact('orders','salesCounts','selectedStatus','canApproveDeliveryRelease'));
    }

    public function create(Request $request)
    {
        $companyId=CompanyAuthorization::authorize($request,'order.create');
        $customers=DB::table('customers')->where('company_id',$companyId)->where('is_active',true)->orderBy('name')->get();
        $goods=DB::table('goods')->where('company_id',$companyId)->where('is_active',true)->where('sellable',true)->orderBy('name')->get();
        $fiscalYear=DB::table('fiscal_years')->where('company_id',$companyId)->where('is_closed',false)->latest('id')->first();
        abort_unless($fiscalYear,422,'سال مالی باز برای ثبت سفارش وجود ندارد.');
        $nextOrderNumber = $this->nextOrderNumber($companyId);
        return view('sales.orders.create',compact('customers','goods','fiscalYear','nextOrderNumber'));
    }

    public function store(Request $request): JsonResponse
    {
        $companyId=CompanyAuthorization::authorize($request,'order.create');
        $data=$request->validate([
            'fiscal_year_id'=>'required|integer','customer_id'=>'required|integer',
            'ordered_at'=>'nullable|date','requested_delivery_at'=>'required|date','notes'=>'nullable|string',
            'items'=>'required|array|min:1','items.*.goods_id'=>'required|integer|distinct','items.*.quantity'=>'required|numeric|gt:0'
        ], ['items.*.goods_id.distinct'=>'هر کالا را فقط یک‌بار در سفارش وارد کنید.']);
        // Registration date is always assigned by the server; ignore any client-supplied value.
        $data['ordered_at'] = now()->toDateString();
        $order=DB::transaction(function()use($data,$companyId){
            DB::table('companies')->where('id',$companyId)->lockForUpdate()->first();
            $number = (string) $this->nextOrderNumber($companyId);
            abort_unless(DB::table('fiscal_years')->where('id',$data['fiscal_year_id'])->where('company_id',$companyId)->where('is_closed',false)->exists(),422,'سال مالی نامعتبر است.');
            abort_unless(DB::table('customers')->where('id',$data['customer_id'])->where('company_id',$companyId)->where('is_active',true)->exists(),422,'مشتری نامعتبر است.');
            $id=DB::table('orders')->insertGetId([
                'company_id'=>$companyId,'fiscal_year_id'=>$data['fiscal_year_id'],'customer_id'=>$data['customer_id'],'number'=>$number,
                'ordered_at'=>$data['ordered_at'],'requested_delivery_at'=>$data['requested_delivery_at'],'notes'=>$data['notes']??null,
                'status'=>'checking_stock','created_at'=>now(),'updated_at'=>now()
            ]);
            $needsProduction=false;
            foreach($data['items'] as $item){
                $goods=DB::table('goods')->where('id',$item['goods_id'])->where('company_id',$companyId)->where('is_active',true)->first();
                abort_unless($goods&&$goods->sellable,422,'کالای سفارش معتبر یا قابل فروش نیست.');
                $available=$this->availableForNewOrder($companyId,(int)$item['goods_id']);
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

    public function cancel(Request $request, int $order)
    {
        $companyId = CompanyAuthorization::authorize($request, 'order.create');

        DB::transaction(function () use ($order, $companyId) {
            $row = DB::table('orders')->where('id', $order)->where('company_id', $companyId)->lockForUpdate()->first();
            abort_unless($row, 404);
            abort_unless(in_array($row->status, ['checking_stock', 'production_required', 'awaiting_production', 'ready_for_delivery'], true), 422, 'این سفارش در وضعیت فعلی قابل لغو نیست.');
            $hasDelivery = DB::table('delivery_requests')->where('order_id', $order)->exists();
            abort_if($hasDelivery, 422, 'برای این سفارش سابقه درخواست تحویل وجود دارد؛ لغو از این مسیر مجاز نیست.');
            DB::table('orders')->where('id', $order)->where('company_id', $companyId)->update(['status' => 'cancelled', 'updated_at' => now()]);
        });

        if ($request->expectsJson()) return response()->json(['data' => ['id' => $order, 'status' => 'cancelled']]);
        return redirect()->route('sales.orders.index')->with('success', 'سفارش با موفقیت لغو شد.');
    }

    public function refreshFulfillment(Request $request,int $order): JsonResponse
    {
        $companyId=CompanyAuthorization::authorize($request,'order.refresh');
        $result=DB::transaction(function()use($order,$companyId){
            DB::table('companies')->where('id',$companyId)->lockForUpdate()->first();
            $row=DB::table('orders')->where('id',$order)->where('company_id',$companyId)->lockForUpdate()->first();
            abort_unless($row,404);
            $items=DB::table('order_items')->where('order_id',$order)->lockForUpdate()->get();
            $ready=true;
            foreach($items as $item){
                $available=$this->availableForNewOrder($companyId,(int)$item->goods_id,(int)$order);
                $shortage=max(0,(float)$item->quantity-$available);
                DB::table('order_items')->where('id',$item->id)->update([
                    'available_quantity'=>min((float)$item->quantity,$available),'shortage_quantity'=>$shortage,
                    'fulfillment_status'=>$shortage>0?'production_required':'ready_for_delivery','updated_at'=>now()
                ]);
                $ready=$ready&&$shortage<=0;
            }
            $status=$ready?'ready_for_delivery':($row->production_due_at?'awaiting_production':'production_required');
            DB::table('orders')->where('id',$order)->update(['status'=>$status,'updated_at'=>now()]);
            return ['order' => DB::table('orders')->where('id',$order)->first(), 'was_ready' => $row->status === 'ready_for_delivery'];
        });
        $resultOrder = $result['order'];
        if ($resultOrder->status === 'ready_for_delivery') {
            if (! $result['was_ready']) {
                app(NotificationRecipientService::class)->send($companyId, 'order.ready_for_delivery', ['order_id' => $order], (int) $request->user()->id, 'order.view');
            }
        } else {
            // A recheck can confirm an existing shortage (including orders already awaiting production).
            // Create the production-required notification once per order, rather than on every button click.
            $hasShortage = DB::table('order_items')
                ->where('order_id', $order)
                ->where('shortage_quantity', '>', 0)
                ->exists();

            if ($hasShortage) {
                $alreadyNotified = DB::table('notifications')
                    ->where('type', 'order.production_required')
                    ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.order_id')) = ?", [(string) $order])
                    ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.company_id')) = ?", [(string) $companyId])
                    ->exists();

                if (! $alreadyNotified) {
                    app(NotificationRecipientService::class)->send(
                        $companyId,
                        'order.production_required',
                        ['order_id' => $order],
                        (int) $request->user()->id,
                        'production.supervise'
                    );
                }
            }
        }
        return response()->json(['data'=>$resultOrder]);
    }


    /** Return the next numeric order number within the current company. */
    private function nextOrderNumber(int $companyId): int
    {
        $numbers = DB::table('orders')
            ->where('company_id', $companyId)
            ->pluck('number')
            ->filter(fn ($number) => preg_match('/^\\d+$/', (string) $number) === 1)
            ->map(fn ($number) => (int) $number);

        return (int) ($numbers->max() ?? 0) + 1;
    }

    /**
     * Stock not already allocated to other open orders.
     * available_quantity is the allocated amount while an order is awaiting production,
     * ready for delivery, or has a pending delivery request.
     */
    private function availableForNewOrder(int $companyId, int $goodsId, ?int $excludeOrderId = null): float
    {
        $stock = (float) app(InventoryService::class)->available($companyId, $goodsId);
        $reserved = DB::table('order_items as oi')
            ->join('orders as o', 'o.id', '=', 'oi.order_id')
            ->where('o.company_id', $companyId)
            ->where('oi.goods_id', $goodsId)
            ->whereIn('o.status', ['production_required', 'awaiting_production', 'ready_for_delivery', 'delivery_requested'])
            ->when($excludeOrderId, fn ($query) => $query->where('o.id', '<>', $excludeOrderId))
            ->sum('oi.available_quantity');

        return max(0, $stock - (float) $reserved);
    }

    public function setProductionDue(Request $request,int $order): JsonResponse
    {
        $companyId=CompanyAuthorization::authorize($request,'production.supervise');
        $data=$request->validate(['production_due_at'=>'required|date']);

        DB::transaction(function () use ($order, $companyId, $data) {
            $row=DB::table('orders')->where('id',$order)->where('company_id',$companyId)->lockForUpdate()->first();
            abort_unless($row,404);
            abort_unless($row->status === 'production_required' && $row->production_due_at === null,422,'موعد تولید این سفارش قبلاً ثبت شده و قابل تغییر نیست.');

            DB::table('orders')->where('id',$order)->where('company_id',$companyId)
                ->where('status','production_required')->whereNull('production_due_at')->update([
                    'production_due_at'=>$data['production_due_at'],'status'=>'awaiting_production','updated_at'=>now()
                ]);
        });

        app(NotificationRecipientService::class)->send($companyId, 'order.production_due_set', ['order_id' => $order, 'production_due_at' => $data['production_due_at']], (int) $request->user()->id, 'order.view');
        return response()->json(['data'=>DB::table('orders')->find($order)]);
    }

    public function approveDeliveryRelease(Request $request, int $delivery): JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $companyId = CompanyAuthorization::authorize($request, 'delivery_request.sales_approve');

        DB::transaction(function () use ($companyId, $delivery, $request) {
            $row = DB::table('delivery_requests')
                ->where('id', $delivery)
                ->where('company_id', $companyId)
                ->lockForUpdate()
                ->first();

            abort_unless($row, 404);
            abort_unless(
                $row->status === 'awaiting_sales_approval' || ($row->status === 'pending' && $row->sales_approved_at === null),
                422,
                'این درخواست قبلاً تأیید شده یا در وضعیت مجاز نیست.'
            );

            DB::table('delivery_requests')->where('id', $delivery)->update([
                'status' => 'pending',
                'sales_approved_by_user_id' => $request->user()->id,
                'sales_approved_at' => now(),
                'updated_at' => now(),
            ]);
        });

        if ($request->expectsJson()) {
            return response()->json(['data' => DB::table('delivery_requests')->find($delivery)]);
        }

        return redirect()->route('sales.orders.index')->with('success', 'مجوز خروج سفارش صادر شد؛ انبار می‌تواند خروج کالا را ثبت کند.');
    }

    public function deliveryRequest(Request $request,int $order): JsonResponse
    {
        $companyId=CompanyAuthorization::authorize($request,'delivery_request.create');
        $data=$request->validate([
            'scheduled_at'=>'required|date',
            'vehicle'=>'nullable|string|max:255',
            'recipient_name'=>'required|string|max:255',
            'recipient_code'=>'nullable|string|max:100',
            'driver_name'=>'nullable|string|max:255',
            'waybill_number'=>'nullable|string|max:100',
            'vehicle_number'=>'nullable|string|max:100',
            'vehicle_type'=>'nullable|string|max:100',
            'notes'=>'nullable|string'
        ]);
        $requestId=DB::transaction(function()use($data,$order,$companyId,$request){
            $row=DB::table('orders')->where('id',$order)->where('company_id',$companyId)->lockForUpdate()->first();
            abort_unless($row,404); abort_unless($row->status==='ready_for_delivery',422,'سفارش هنوز آماده تحویل نیست.');
            $items=DB::table('order_items')->where('order_id',$order)->get();
            abort_unless($items->isNotEmpty(),422,'سفارش فاقد قلم است.');
            $id=DB::table('delivery_requests')->insertGetId([
                'company_id'=>$companyId,'order_id'=>$order,'customer_id'=>$row->customer_id,'requested_by_user_id'=>$request->user()->id,
                'requested_at'=>now(),'scheduled_at'=>$data['scheduled_at'],'vehicle'=>$data['vehicle']??null,
                'recipient_name'=>$data['recipient_name'],'recipient_code'=>$data['recipient_code']??null,
                'driver_name'=>$data['driver_name']??null,'waybill_number'=>$data['waybill_number']??null,
                'vehicle_number'=>$data['vehicle_number']??null,'vehicle_type'=>$data['vehicle_type']??null,
                'status'=>'awaiting_sales_approval','notes'=>$data['notes']??null,'created_at'=>now(),'updated_at'=>now()
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
