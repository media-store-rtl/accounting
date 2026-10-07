<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Notifications\WorkflowNotification;
use App\Support\CompanyAuthorization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Illuminate\Pagination\LengthAwarePaginator;

class DeliveryController extends Controller
{
    public function index(Request $request)
    {
        $companyId=CompanyAuthorization::authorize($request,'delivery_request.view');
        if (! Schema::hasTable('delivery_requests')) {
            $deliveries = new LengthAwarePaginator(
                collect(),
                0,
                20,
                1,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        } else {
            $deliveries=DB::table('delivery_requests as d')->join('orders as o','o.id','=','d.order_id')->join('customers as c','c.id','=','d.customer_id')
                ->where('d.company_id',$companyId)->select('d.*','o.number as order_number','c.name as customer_name')->latest('d.id')->paginate(20);
        }
        return view('sales.deliveries.index',compact('deliveries'));
    }

    public function issue(Request $request,int $delivery): \Illuminate\Http\JsonResponse
    {
        $companyId=CompanyAuthorization::authorize($request,'delivery_request.issue');
        DB::transaction(function()use($companyId,$delivery,$request){
            $d=DB::table('delivery_requests')->where('id',$delivery)->where('company_id',$companyId)->lockForUpdate()->first();
            abort_unless($d,404); abort_unless($d->status==='pending',422,'درخواست تحویل در وضعیت قابل خروج نیست.');
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
        $data=$request->validate(['received_by'=>'required|string|max:255','handover_note'=>'nullable|string']);
        DB::transaction(function()use($companyId,$delivery,$request,$data){
            $d=DB::table('delivery_requests')->where('id',$delivery)->where('company_id',$companyId)->first();
            abort_unless($d,404); abort_unless($d->status==='issued',422,'ابتدا باید خروج انبار ثبت شود.');
            $deliveryRow=DB::table('deliveries')->where('delivery_request_id',$delivery)->lockForUpdate()->first();
            abort_unless($deliveryRow,422,'رکورد تحویل فیزیکی یافت نشد.');
            DB::table('deliveries')->where('id',$deliveryRow->id)->update([
                'status'=>'handed_over','handed_over_at'=>now(),'handed_over_by_user_id'=>$request->user()->id,
                'received_by'=>$data['received_by'],'handover_note'=>$data['handover_note']??null,'updated_at'=>now()
            ]);
            DB::table('delivery_requests')->where('id',$delivery)->update(['status'=>'completed','updated_at'=>now()]);
            DB::table('orders')->where('id',$d->order_id)->update(['status'=>'completed','updated_at'=>now()]);
        });
        return response()->json(['data'=>DB::table('delivery_requests')->find($delivery)]);
    }
}
