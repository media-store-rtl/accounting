<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\WorkflowNotification;
use App\Support\CompanyAuthorization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class DeliveryController extends Controller
{
    public function index(Request $request)
    {
        $companyId=CompanyAuthorization::authorize($request,'delivery_request.view');
        $deliveries=DB::table('delivery_requests as d')
            ->join('orders as o','o.id','=','d.order_id')
            ->join('customers as c','c.id','=','d.customer_id')
            ->leftJoin('deliveries as physical','physical.delivery_request_id','=','d.id')
            ->where('d.company_id',$companyId)
            ->select(
                'd.*','o.number as order_number','c.name as customer_name',
                'physical.driver_name','physical.driver_phone','physical.vehicle_plate',
                'physical.received_by','physical.handover_note',
                'physical.handed_over_at'
            )
            ->latest('d.id')->paginate(20);

        $eligibleOrders = DB::table('orders as o')
            ->join('customers as c','c.id','=','o.customer_id')
            ->where('o.company_id',$companyId)
            ->where('o.status','ready_for_delivery')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('delivery_requests as d')
                    ->whereColumn('d.order_id','o.id');
            })
            ->select('o.id','o.number','c.name as customer_name')
            ->latest('o.id')->get();

        $canCreateDeliveryRequest = $request->user()->hasCompanyPermission($companyId, 'delivery_request.create');

        return view('sales.deliveries.index',compact('deliveries','eligibleOrders','canCreateDeliveryRequest'));
    }

    public function receipt(Request $request,int $delivery): \Illuminate\Http\JsonResponse
    {
        $companyId=CompanyAuthorization::authorize($request,'delivery_request.view');
        $receipt=DB::table('deliveries as physical')
            ->join('delivery_requests as d','d.id','=','physical.delivery_request_id')
            ->where('d.company_id',$companyId)
            ->where('d.id',$delivery)
            ->whereNotNull('physical.recipient_signature')
            ->select('physical.received_by','physical.driver_name','physical.driver_phone','physical.vehicle_plate','physical.recipient_signature','physical.handed_over_at')
            ->first();
        abort_unless($receipt,404,'رسید تحویل برای این سفارش ثبت نشده است.');
        return response()->json(['data'=>$receipt]);
    }

    public function issue(Request $request,int $delivery): \Illuminate\Http\JsonResponse
    {
        $companyId=CompanyAuthorization::authorize($request,'delivery_request.issue');
        DB::transaction(function()use($companyId,$delivery,$request){
            DB::table('companies')->where('id',$companyId)->lockForUpdate()->first();
            $d=DB::table('delivery_requests')->where('id',$delivery)->where('company_id',$companyId)->lockForUpdate()->first();
            abort_unless($d,404); abort_unless($d->status==='pending' && $d->sales_approved_at !== null,422,'ابتدا باید مجوز خروج فروش صادر شود.');
            $items=DB::table('delivery_request_items')->where('delivery_request_id',$delivery)->get();
            foreach($items as $item){
                $rows=DB::table('inventory')->join('locations','locations.id','=','inventory.location_id')
                    ->where('inventory.company_id',$companyId)->where('inventory.goods_id',$item->goods_id)->where('locations.company_id',$companyId)
                    ->where('locations.type','warehouse')->where('locations.is_active',true)->where('inventory.quantity','>',0)
                    ->select('inventory.*')->lockForUpdate()->get();
                $remaining=(float)$item->quantity;
                foreach($rows as $row){
                    if($remaining<=0) break;
                    $take=min($remaining,(float)$row->quantity);
                    DB::table('inventory')->where('id',$row->id)->update(['quantity'=>DB::raw('quantity - '.(float)$take),'updated_at'=>now()]);
                    DB::table('inventory_movements')->insert([
                        'company_id'=>$companyId,'goods_id'=>$item->goods_id,'location_id'=>$row->location_id,'quantity'=>-$take,
                        'movement_type'=>'delivery_issue','reference_type'=>'delivery_request','reference_id'=>$delivery,'occurred_at'=>now(),
                        'metadata'=>json_encode(['order_id'=>$d->order_id]),'created_at'=>now(),'updated_at'=>now()
                    ]);
                    $remaining-=$take;
                }
                abort_unless($remaining<=0,422,'موجودی کافی برای خروج کالا وجود ندارد.');
            }
            $deliveryId=DB::table('deliveries')->insertGetId([
                'company_id'=>$companyId,'delivery_request_id'=>$delivery,'issued_by_user_id'=>$request->user()->id,'issued_at'=>now(),
                'status'=>'issued','created_at'=>now(),'updated_at'=>now()
            ]);
            DB::table('delivery_requests')->where('id',$delivery)->update(['status'=>'issued','updated_at'=>now()]);
            DB::table('orders')->where('id',$d->order_id)->update(['status'=>'issued','updated_at'=>now()]);
            DB::table('deliveries')->where('id',$deliveryId)->update(['metadata'=>json_encode(['recipient_name'=>$d->recipient_name])]);
        });
        return response()->json(['data'=>DB::table('delivery_requests')->find($delivery)]);
    }

    public function handover(Request $request,int $delivery): \Illuminate\Http\JsonResponse
    {
        $companyId=CompanyAuthorization::authorize($request,'delivery_request.handover');
        $data=$request->validate([
            'received_by'=>'required|string|max:255',
            'vehicle_plate'=>'required|string|max:30',
            'driver_name'=>'required|string|max:255',
            'driver_phone'=>'nullable|string|max:30',
            'recipient_signature'=>'required|string|max:700000',
            'handover_note'=>'nullable|string|max:2000',
        ]);
        $signaturePrefix='data:image/png;base64,';
        abort_unless(str_starts_with($data['recipient_signature'],$signaturePrefix),422,'فرمت تصویر امضا معتبر نیست.');
        $signaturePayload=substr($data['recipient_signature'],strlen($signaturePrefix));
        $signatureBytes=base64_decode($signaturePayload,true);
        abort_if($signatureBytes===false || !str_starts_with($signatureBytes, "\x89PNG\r\n\x1a\n"),422,'تصویر امضا معتبر نیست.');

        DB::transaction(function()use($companyId,$delivery,$request,$data){
            $d=DB::table('delivery_requests')->where('id',$delivery)->where('company_id',$companyId)->first();
            abort_unless($d,404); abort_unless($d->status==='issued',422,'ابتدا باید خروج انبار ثبت شود.');
            $deliveryRow=DB::table('deliveries')->where('delivery_request_id',$delivery)->lockForUpdate()->first();
            abort_unless($deliveryRow,422,'رکورد تحویل فیزیکی یافت نشد.');
            DB::table('deliveries')->where('id',$deliveryRow->id)->update([
                'status'=>'handed_over','handed_over_at'=>now(),'handed_over_by_user_id'=>$request->user()->id,
                'received_by'=>$data['received_by'],'vehicle_plate'=>$data['vehicle_plate'],
                'driver_name'=>$data['driver_name'],'driver_phone'=>$data['driver_phone']??null,
                'recipient_signature'=>$data['recipient_signature'],
                'handover_note'=>$data['handover_note']??null,'updated_at'=>now()
            ]);
            DB::table('delivery_requests')->where('id',$delivery)->update(['status'=>'completed','updated_at'=>now()]);
            DB::table('orders')->where('id',$d->order_id)->update(['status'=>'completed','updated_at'=>now()]);
        });
        return response()->json(['data'=>DB::table('delivery_requests')->find($delivery)]);
    }
}
