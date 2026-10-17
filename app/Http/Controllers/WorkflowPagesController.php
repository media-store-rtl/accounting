<?php
namespace App\Http\Controllers;
use App\Support\CompanyAuthorization; use App\Http\Controllers\SupplyRequestController; use App\Http\Controllers\PurchaseController; use App\Http\Controllers\PurchaseReceiptController; use Illuminate\Http\Request; use Illuminate\Support\Facades\DB;
class WorkflowPagesController extends Controller {
 public function supply(Request $r){
  $cid=CompanyAuthorization::authorize($r,'supply_request.view');
  $canReviewStock=$r->user()->hasCompanyPermission($cid,'supply_request.handover.create');
  $rows=DB::table('supply_requests')->where('company_id',$cid)->latest('id')->paginate(20);
  foreach($rows as $row){
   $row->items=DB::table('supply_request_items as sri')->join('goods as g','g.id','=','sri.goods_id')
    ->where('sri.supply_request_id',$row->id)->select('sri.*','g.name as goods_name','g.code as goods_code')->get();
   if($canReviewStock){
    foreach($row->items as $item){
     $item->warehouse_quantity=app(\App\Services\InventoryBalanceService::class)->availableInWarehouses($cid,(int)$item->goods_id);
    }
   }
  }
  return view('workflows.supply',['rows'=>$rows,'canReviewStock'=>$canReviewStock,'goods'=>DB::table('goods')->where('company_id',$cid)->where('is_active',true)->get(),'years'=>DB::table('fiscal_years')->where('company_id',$cid)->where('is_closed',false)->get(),'productions'=>DB::table('productions')->where('company_id',$cid)->latest('id')->get(),'orders'=>DB::table('orders')->where('company_id',$cid)->latest('id')->get()]);
 }
 public function productionSalesShortages(Request $r){
  $cid=CompanyAuthorization::authorize($r,'production.supervise');
  $inventoryService=app(\App\Services\InventoryService::class);

  // Reconcile open production-shortage orders against current allocatable stock.
  // Orders that become fully available leave this list and return to ready_for_delivery.
  $candidates=DB::table('orders')->where('company_id',$cid)
   ->whereIn('status',['production_required','awaiting_production'])
   ->whereExists(function($q){$q->select(DB::raw(1))->from('order_items as oi')->whereColumn('oi.order_id','orders.id')->where('oi.shortage_quantity','>',0);})
   ->orderByDesc('id')->get(['id','status']);

  foreach($candidates as $candidate){
   $previousStatus=$candidate->status;
   $newStatus=DB::transaction(function()use($candidate,$cid,$inventoryService){
    $order=DB::table('orders')->where('company_id',$cid)->where('id',$candidate->id)->lockForUpdate()->first();
    if(!$order || !in_array($order->status,['production_required','awaiting_production'],true)) return null;
    $items=DB::table('order_items')->where('order_id',$order->id)->lockForUpdate()->get();
    $allReady=$items->isNotEmpty();
    foreach($items as $item){
     $stock=(float)$inventoryService->available($cid,(int)$item->goods_id);
     $reserved=DB::table('order_items as oi')->join('orders as other','other.id','=','oi.order_id')
      ->where('other.company_id',$cid)->where('oi.goods_id',$item->goods_id)
      ->whereIn('other.status',['production_required','awaiting_production','ready_for_delivery','delivery_requested'])
      ->where('other.id','<>',$order->id)->sum('oi.available_quantity');
     $available=max(0,$stock-(float)$reserved);
     $shortage=max(0,(float)$item->quantity-$available);
     DB::table('order_items')->where('id',$item->id)->update([
      'available_quantity'=>min((float)$item->quantity,$available),
      'shortage_quantity'=>$shortage,
      'fulfillment_status'=>$shortage>0?'production_required':'ready_for_delivery',
      'updated_at'=>now()
     ]);
     $allReady=$allReady&&$shortage<=0;
    }
    $status=$allReady?'ready_for_delivery':($order->production_due_at?'awaiting_production':'production_required');
    DB::table('orders')->where('id',$order->id)->where('company_id',$cid)->update(['status'=>$status,'updated_at'=>now()]);
    return $status;
   });

   if($newStatus==='ready_for_delivery' && $previousStatus!=='ready_for_delivery'){
    app(\App\Services\NotificationRecipientService::class)->send($cid,'order.ready_for_delivery',['order_id'=>$candidate->id],(int)$r->user()->id,'order.view');
   }
  }

  $rows=DB::table('orders as o')
   ->where('o.company_id',$cid)
   ->whereIn('o.status',['production_required','awaiting_production'])
   ->whereExists(function($q){$q->select(DB::raw(1))->from('order_items as oi')->whereColumn('oi.order_id','o.id')->where('oi.shortage_quantity','>',0);})
   ->select('o.id','o.number','o.status','o.ordered_at','o.requested_delivery_at','o.production_due_at')
   ->orderByDesc('o.id')->paginate(30);
  foreach($rows as $row){
   $row->items=DB::table('order_items as oi')->join('goods as g','g.id','=','oi.goods_id')
    ->where('oi.order_id',$row->id)->where('oi.shortage_quantity','>',0)
    ->select('oi.quantity','oi.available_quantity','oi.shortage_quantity','oi.fulfillment_status','g.name as goods_name','g.code as goods_code')
    ->orderBy('g.name')->get();
   $row->total_shortage=(float)$row->items->sum('shortage_quantity');
  }
  return view('production.sales-shortages',compact('rows'));
 }
 public function productionDue(Request $r){
  $cid=CompanyAuthorization::authorize($r,'production.supervise');
  $rows=DB::table('orders as o')->join('customers as c','c.id','=','o.customer_id')
   ->where('o.company_id',$cid)->whereIn('o.status',['production_required','awaiting_production'])
   ->select('o.id','o.number','o.status','o.production_due_at','o.requested_delivery_at','c.name as customer_name')
   ->orderByRaw("CASE WHEN o.production_due_at IS NULL THEN 0 ELSE 1 END")
   ->orderBy('o.production_due_at')->orderByDesc('o.id')->paginate(30);
  return view('production.due-dates',compact('rows'));
 }
 public function purchasing(Request $r){
  $cid=CompanyAuthorization::authorize($r,'purchase.view');
  $years=DB::table('fiscal_years')->where('company_id',$cid)->where('is_closed',false)->orderByDesc('starts_at')->get();
  $activeFiscalYearId=(int)$r->session()->get('fiscal_year_id');
  if(!$years->contains('id',$activeFiscalYearId)) $activeFiscalYearId=(int)($years->first()->id??0);
  return view('workflows.purchasing',[
   'rows'=>DB::table('purchases')->where('company_id',$cid)->latest('id')->paginate(20),
   'goods'=>DB::table('goods')->where('company_id',$cid)->where('purchasable',true)->get(),
   'suppliers'=>DB::table('suppliers')->where('company_id',$cid)->where('is_active',true)->get(),
   'requests'=>DB::table('supply_requests')->where('company_id',$cid)->whereIn('status',['shortage_pending','purchasing','purchased','partially_supplied'])->orderByDesc('id')->get()->each(function($row){$row->items=DB::table('supply_request_items as sri')->join('goods as g','g.id','=','sri.goods_id')->where('sri.supply_request_id',$row->id)->select('sri.*','g.name as goods_name')->get();}),
   'years'=>$years,'activeFiscalYearId'=>$activeFiscalYearId
  ]);
 }
 public function handovers(Request $r){$cid=CompanyAuthorization::authorize($r,'supply_request.handover.view');return view('workflows.handovers',['rows'=>DB::table('material_handovers')->where('company_id',$cid)->latest('id')->paginate(20),'requests'=>DB::table('supply_requests')->where('company_id',$cid)->whereIn('status',['stock_available','purchased','partially_supplied'])->get(),'locations'=>DB::table('locations')->where('company_id',$cid)->whereRaw("LOWER(TRIM(type)) IN (?, ?, ?)",['warehouse','انبار','مواد'])->where('is_active',true)->orderBy('name')->get(),'sections'=>DB::table('production_sections as ps')->join('locations as l','l.id','=','ps.location_id')->where('l.company_id',$cid)->where('l.is_active',true)->where('ps.is_active',true)->orderBy('l.name')->get(['ps.id','l.name']),'stages'=>DB::table('production_stages as s')->join('production_routes as pr','pr.id','=','s.production_route_id')->where('pr.company_id',$cid)->where('s.status','active')->whereNotNull('s.production_section_id')->orderBy('s.sequence')->get(['s.id','s.name as stage_name','s.production_section_id','pr.name as route_name']),'users'=>DB::table('company_user as cu')->join('users as u','u.id','=','cu.user_id')->where('cu.company_id',$cid)->where('cu.is_active',true)->select('u.id','u.name')->get(),'goods'=>DB::table('goods')->where('company_id',$cid)->where('is_active',true)->get()]);}
 public function storeHandover(Request $r){$cid=CompanyAuthorization::authorize($r,'supply_request.handover.create');$r->validate(['supply_request_id'=>'required|integer','warehouse_location_id'=>'required|integer','production_section_id'=>'nullable|integer','production_stage_id'=>'nullable|integer','delivered_by_user_id'=>'required|integer','received_by_user_id'=>'required|integer','handed_over_at'=>'required|date','items'=>'required|array|min:1','items.*.goods_id'=>'required|integer','items.*.quantity'=>'required|numeric|gt:0']);app(MaterialHandoverController::class)->store($r,app(\App\Services\InventoryService::class),app(\App\Services\InventoryValuationService::class));return back()->with('success','تحویل مواد ثبت شد.');}
 public function receipts(Request $r){$cid=CompanyAuthorization::authorize($r,'purchase.receipt.create');return view('workflows.receipts',['rows'=>DB::table('purchase_receipts')->where('company_id',$cid)->latest('id')->paginate(20),'purchases'=>DB::table('purchases')->where('company_id',$cid)->whereIn('status',['draft','partially_received'])->get(),'locations'=>DB::table('locations')->where('company_id',$cid)->whereRaw("LOWER(TRIM(type)) IN (?, ?, ?)",['warehouse','انبار','مواد'])->where('is_active',true)->orderBy('name')->get(),'goods'=>DB::table('goods')->where('company_id',$cid)->where('is_active',true)->get()]);}
 public function approveReceipt(Request $r,int $id){$cid=CompanyAuthorization::authorize($r,'purchase.receipt.approve');app(PurchaseReceiptController::class)->approve($r,$id);return back()->with('success','رسید تأیید شد و موجودی به‌روزرسانی شد.');}
 public function storeReceipt(Request $r){$cid=CompanyAuthorization::authorize($r,'purchase.receipt.create');$d=$r->validate(['purchase_id'=>'required|integer','warehouse_location_id'=>'required|integer','received_at'=>'required|date','notes'=>'nullable|string','items'=>'required|array|min:1','items.*.goods_id'=>'required|integer','items.*.quantity'=>'required|numeric|gt:0']);app(PurchaseReceiptController::class)->store($r);return back()->with('success','رسید خرید ثبت شد.');}
 public function labor(Request $r){$cid=CompanyAuthorization::authorize($r,'production.labor.create');return view('workflows.labor',['rows'=>DB::table('production_labor_entries')->where('company_id',$cid)->latest('id')->paginate(20),'personnel'=>DB::table('personnel')->where('account_id',$r->user()->account_id)->where('is_active',true)->get(),'runs'=>DB::table('production_operation_runs as r')->join('production_stage_runs as sr','sr.id','=','r.production_stage_run_id')->join('productions as p','p.id','=','sr.production_id')->where('p.company_id',$cid)->select('r.id','p.number')->latest('r.id')->get()]);}
 public function warehouseSupplyRequests(Request $r){
  $cid=CompanyAuthorization::authorize($r,'supply_request.handover.create');
  $rows=DB::table('supply_requests')->where('company_id',$cid)->latest('id')->paginate(30);
  foreach($rows as $row){
   $row->items=DB::table('supply_request_items as sri')->join('goods as g','g.id','=','sri.goods_id')->where('sri.supply_request_id',$row->id)->select('sri.*','g.name as goods_name','g.code as goods_code')->get();
   foreach($row->items as $item){$item->warehouse_quantity=app(\App\Services\InventoryBalanceService::class)->availableInWarehouses($cid,(int)$item->goods_id);}
  }
  return view('workflows.warehouse-supply-requests',compact('rows'));
 }
 public function rejectSupplyRequest(Request $r,int $supplyRequest){
  $cid=CompanyAuthorization::authorize($r,'supply_request.handover.create');
  $data=$r->validate(['warehouse_note'=>'required|string|max:1000']);
  $row=DB::table('supply_requests')->where('company_id',$cid)->where('id',$supplyRequest)->first();
  abort_unless($row,404); abort_unless($row->status==='checking_stock',422,'این درخواست قبلاً بررسی شده است.');
  DB::table('supply_requests')->where('id',$supplyRequest)->where('company_id',$cid)->update(['status'=>'rejected','updated_at'=>now()]);
  app(\App\Services\NotificationRecipientService::class)->send($cid,'supply.stock_rejected',['supply_request_id'=>$supplyRequest,'warehouse_note'=>$data['warehouse_note'],'needed_at'=>$row->needed_at],(int)$r->user()->id,'supply_request.create');
  return back()->with('success','درخواست رد شد و نتیجه برای تولید ارسال شد.');
 }
 public function updateSupplyPurchaseStatus(Request $r,int $supplyRequest){
  $cid=CompanyAuthorization::authorize($r,'supply.manage');
  $data=$r->validate(['status'=>'required|in:purchasing']);
  $row=DB::table('supply_requests')->where('company_id',$cid)->where('id',$supplyRequest)->lockForUpdate()->first();
  abort_unless($row,404);
  abort_unless(in_array($row->status,['shortage_pending','partially_supplied'],true),422,'فقط درخواست دارای کسری باقی‌مانده می‌تواند وارد مرحله خرید شود.');
  DB::table('supply_requests')->where('id',$supplyRequest)->where('company_id',$cid)->update(['status'=>'purchasing','updated_at'=>now()]);
  return back()->with('success','درخواست وارد مرحله «در حال خرید» شد. اکنون قیمت واحد را در همان فرم ثبت خرید وارد کنید.');
 }
 public function storeSupply(Request $r){app(SupplyRequestController::class)->store($r);return back()->with('success','درخواست تأمین ثبت شد.');}
 public function storePurchase(Request $r){app(PurchaseController::class)->store($r);return back()->with('success','خرید ثبت شد.');}
 public function storeLabor(Request $r){$cid=CompanyAuthorization::authorize($r,'production.labor.create');$d=$r->validate(['production_operation_run_id'=>'required|integer','personnel_id'=>'required|integer','measure_type'=>'required|in:hours,quantity','labor_rate_type'=>'nullable|in:regular,overtime,holiday','measure_quantity'=>'required|numeric|gt:0','worked_at'=>'required|date','notes'=>'nullable|string']);$run=DB::table('production_operation_runs as r')->join('production_stage_runs as sr','sr.id','=','r.production_stage_run_id')->join('productions as p','p.id','=','sr.production_id')->where('r.id',$d['production_operation_run_id'])->where('p.company_id',$cid)->exists();abort_unless($run,422,'عملیات تولید نامعتبر است.');app(\App\Services\LaborCostService::class)->record($cid,(int)$d['production_operation_run_id'],(int)$d['personnel_id'],$d['measure_type'],(float)$d['measure_quantity'],$d['worked_at'],(int)$r->user()->id,$d['notes']??null,$d['labor_rate_type']??'regular');return back()->with('success','کارکرد ثبت شد.');}
 public function setReorderPoint(Request $r,int $locationId,int $goodsId){
  $cid=CompanyAuthorization::authorize($r,'inventory.reorder_point.manage');
  $data=$r->validate(['reorder_point'=>['required','numeric','min:0']]);
  $location=DB::table('locations')->where('id',$locationId)->where('company_id',$cid)->where('is_active',true)->exists();
  abort_unless($location,422,'انبار انتخاب‌شده معتبر یا فعال نیست.');
  $goods=DB::table('goods')->where('id',$goodsId)->where('company_id',$cid)->where('is_active',true)->exists();
  abort_unless($goods,422,'کالای انتخاب‌شده معتبر یا فعال نیست.');
  $inventory=DB::table('inventory')->where('company_id',$cid)->where('location_id',$locationId)->where('goods_id',$goodsId)->first();
  if(!$inventory){
   $inventoryId=DB::table('inventory')->insertGetId(['company_id'=>$cid,'location_id'=>$locationId,'goods_id'=>$goodsId,'quantity'=>0,'reorder_point'=>0,'created_at'=>now(),'updated_at'=>now()]);
  }else{$inventoryId=(int)$inventory->id;}
  app(\App\Services\InventoryService::class)->setReorderPoint($cid,$inventoryId,(float)$data['reorder_point'],(int)$r->user()->id);
  return back()->with('success','نقطه سفارش ذخیره شد.');
 }
 public function inventory(Request $r){
  $cid=CompanyAuthorization::authorize($r,'inventory.view');
  $stock=DB::table('inventory_movements')->select('company_id','location_id','goods_id',DB::raw('SUM(quantity) as quantity'))->groupBy('company_id','location_id','goods_id');
  $rows=DB::table('goods as g')->crossJoin('locations as l')
   ->leftJoin('inventory as i',function($join)use($cid){$join->on('i.goods_id','=','g.id')->on('i.location_id','=','l.id')->where('i.company_id','=',$cid);})
   ->leftJoinSub($stock,'stock',function($join){$join->on('stock.company_id','=','g.company_id')->on('stock.goods_id','=','g.id')->on('stock.location_id','=','l.id');})
   ->where('g.company_id',$cid)->where('g.is_active',true)->where('l.company_id',$cid)->where('l.is_active',true)
   ->select('i.id as inventory_id','g.id as goods_id','l.id as location_id','g.name as goods_name','g.code as goods_code','l.name as location_name',DB::raw('COALESCE(stock.quantity, 0) as quantity'),DB::raw('COALESCE(i.reorder_point, 0) as reorder_point'))
   ->orderBy('g.name')->orderBy('l.name')->paginate(30);
  return view('workflows.inventory',compact('rows'));
 }
}