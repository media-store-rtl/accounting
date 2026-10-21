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
  return view('workflows.supply', compact('rows', 'canReviewStock'));
 }

 public function createSupply(Request $r){
  $cid=CompanyAuthorization::authorize($r,'supply_request.create');
  $years=DB::table('fiscal_years')->where('company_id',$cid)->where('is_closed',false)->orderByDesc('starts_at')->get();
  $activeFiscalYearId=(int)$r->session()->get('fiscal_year_id');
  if(!$years->contains('id',$activeFiscalYearId)){
   $activeFiscalYearId=(int)($years->first()->id??0);
  }

  return view('workflows.supply-create',[
   'goods'=>DB::table('goods')->where('company_id',$cid)->where('is_active',true)->orderBy('name')->get(),
   'years'=>$years,
   'activeFiscalYearId'=>$activeFiscalYearId,
   'productionStages'=>DB::table('production_stages as s')->join('production_routes as pr','pr.id','=','s.production_route_id')->where('pr.company_id',$cid)->where('s.status','active')->orderBy('s.sequence')->orderBy('s.name')->get(['s.id','s.name']),
   'orders'=>DB::table('orders as o')->join('customers as c','c.id','=','o.customer_id')->where('o.company_id',$cid)->orderByDesc('o.id')->get(['o.id','o.number','c.name as customer_name']),
   'sections'=>DB::table('production_sections as s')->join('locations as l','l.id','=','s.location_id')->where('l.company_id',$cid)->where('l.is_active',true)->where('s.is_active',true)->orderBy('l.name')->get(['s.id','l.name as name']),
  ]);
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
  $canCreatePurchase=$r->user()->hasCompanyPermission($cid,'purchase.create');
  $rows=DB::table('purchases')->leftJoin('suppliers','suppliers.id','=','purchases.supplier_id')->where('purchases.company_id',$cid)->select('purchases.*','suppliers.name as supplier_name')->latest('purchases.id')->paginate(20);
  foreach($rows as $row){
   $row->items=DB::table('purchase_items as pi')->join('goods as g','g.id','=','pi.goods_id')->where('pi.purchase_id',$row->id)->select('pi.quantity','g.name as goods_name')->orderBy('g.name')->get();
   $row->has_receipt=DB::table('purchase_receipts')->where('purchase_id',$row->id)->exists();
  }
  return view('workflows.purchasing',[
   'rows'=>$rows,
   'suppliers'=>DB::table('suppliers')->where('company_id',$cid)->where('is_active',true)->get(),
   'requests'=>DB::table('supply_requests')->where('company_id',$cid)->whereIn('status',['shortage_pending','purchasing','purchased','partially_supplied'])->orderByDesc('id')->get()->each(function($row){$row->items=DB::table('supply_request_items as sri')->join('goods as g','g.id','=','sri.goods_id')->where('sri.supply_request_id',$row->id)->select('sri.*','g.name as goods_name')->get();}),
   'canCreatePurchase'=>$canCreatePurchase
  ]);
 }
 public function createPurchasing(Request $r){
  $cid=CompanyAuthorization::authorize($r,'purchase.create');
  return view('workflows.purchasing-create',[
   'goods'=>DB::table('goods')->where('company_id',$cid)->where('purchasable',true)->where('is_active',true)->orderBy('name')->get(),
   'suppliers'=>DB::table('suppliers')->where('company_id',$cid)->where('is_active',true)->orderBy('name')->get(),
   'requests'=>DB::table('supply_requests')->where('company_id',$cid)->where('status','purchasing')->orderByDesc('id')->get(),
  ]);
 }
 public function handovers(Request $r){$cid=CompanyAuthorization::authorize($r,'supply_request.handover.view');return view('workflows.handovers',['rows'=>DB::table('material_handovers')->where('company_id',$cid)->latest('id')->paginate(20),'requests'=>DB::table('supply_requests')->where('company_id',$cid)->whereIn('status',['stock_available','purchased','partially_supplied'])->get(),'locations'=>DB::table('locations')->where('company_id',$cid)->whereRaw("LOWER(TRIM(type)) = ?",['مواد اولیه'])->where('is_active',true)->orderBy('name')->get(),'sections'=>DB::table('production_sections as ps')->where('ps.company_id',$cid)->where('ps.is_active',true)->orderBy('ps.name')->get(['ps.id','ps.name']),'stages'=>DB::table('production_stages as s')->join('production_routes as pr','pr.id','=','s.production_route_id')->where('pr.company_id',$cid)->where('s.status','active')->whereNotNull('s.production_section_id')->orderBy('s.sequence')->get(['s.id','s.name as stage_name','s.production_section_id']),'users'=>DB::table('company_user as cu')->join('users as u','u.id','=','cu.user_id')->where('cu.company_id',$cid)->where('cu.is_active',true)->select('u.id','u.name')->get(),'goods'=>DB::table('goods')->where('company_id',$cid)->where('is_active',true)->get()]);}
 public function storeHandover(Request $r){$cid=CompanyAuthorization::authorize($r,'supply_request.handover.create');$r->validate(['supply_request_id'=>'nullable|integer','warehouse_location_id'=>'required|integer','production_section_id'=>'nullable|integer','production_stage_id'=>'nullable|integer','delivered_by_user_id'=>'required|integer','received_by_user_id'=>'required|integer','handed_over_at'=>'required|date','items'=>'required|array|min:1','items.*.goods_id'=>'required|integer','items.*.quantity'=>'required|numeric|gt:0']);app(MaterialHandoverController::class)->store($r,app(\App\Services\InventoryService::class),app(\App\Services\InventoryValuationService::class));return back()->with('success','تحویل مواد ثبت شد.');}
 public function receipts(Request $r){
  $cid=(int)$r->session()->get('company_id');
  $canCreateReceipt=$cid>0 && $r->user()->hasCompanyPermission($cid,'purchase.receipt.create');
  $canCreatePurchase=$cid>0 && $r->user()->hasCompanyPermission($cid,'purchase.create');
  $canApproveExisting=$cid>0 && $r->user()->hasCompanyPermission($cid,'purchase.receipt.approve');
  abort_unless($canCreateReceipt || $canCreatePurchase || $canApproveExisting,403);
  $rows=DB::table('purchase_receipts')->where('company_id',$cid)->latest('id')->paginate(20);
  foreach($rows as $row){
   $row->items=DB::table('purchase_receipt_items as x')->join('goods as g','g.id','=','x.goods_id')
    ->where('x.purchase_receipt_id',$row->id)->select('x.id','x.goods_id','g.name as goods_name','x.quantity')->orderBy('g.name')->get();
  }
  return view('workflows.receipts',[
   'rows'=>$rows,
   'canCreateReceipt'=>$canCreateReceipt,
   'canCreatePurchase'=>$canCreatePurchase,
   'canApproveExisting'=>$canApproveExisting,
   'suppliers'=>DB::table('suppliers')->where('company_id',$cid)->where('is_active',true)->orderBy('name')->get(),
   'locations'=>DB::table('locations')->where('company_id',$cid)->whereRaw("LOWER(TRIM(type)) = ?",['مواد اولیه'])->where('is_active',true)->orderBy('name')->get(),
   'goods'=>DB::table('goods')->where('company_id',$cid)->where('is_active',true)->where('purchasable',true)->orderBy('name')->get(),
  ]);
 }
 public function editReceipt(Request $r,int $id){
  $cid=CompanyAuthorization::authorize($r,'purchase.receipt.create');
  $receipt=DB::table('purchase_receipts')->where('company_id',$cid)->where('id',$id)->first();
  abort_unless($receipt,404);
  if($receipt->status!=='pending'){
   return redirect()->route('workflows.receipts')->with('error','فقط رسیدهای در انتظار تأیید قابل ویرایش هستند. رسید تأییدشده برای حفظ سوابق موجودی و بهای تمام‌شده قفل است.');
  }
  $receipt->items=DB::table('purchase_receipt_items as x')->join('goods as g','g.id','=','x.goods_id')
   ->where('x.purchase_receipt_id',$receipt->id)->select('x.id','x.goods_id','x.quantity','g.name as goods_name')->orderBy('g.name')->get();
  return view('workflows.receipts-edit',[
   'receipt'=>$receipt,
   'locations'=>DB::table('locations')->where('company_id',$cid)->whereRaw("LOWER(TRIM(type)) = ?",['مواد اولیه'])->where('is_active',true)->orderBy('name')->get(),
  ]);
 }
 public function updateReceipt(Request $r,int $id){
  $cid=CompanyAuthorization::authorize($r,'purchase.receipt.create');
  $data=$r->validate([
   'warehouse_location_id'=>'required|integer',
   'received_at'=>'required|date',
   'notes'=>'nullable|string',
   'items'=>'required|array|min:1',
   'items.*.id'=>'required|integer',
   'items.*.goods_id'=>'required|integer',
   'items.*.quantity'=>'required|numeric|gt:0',
  ]);
  DB::transaction(function()use($data,$cid,$id){
   $receipt=DB::table('purchase_receipts')->where('company_id',$cid)->where('id',$id)->lockForUpdate()->first();
   abort_unless($receipt,404);
   if($receipt->status!=='pending'){
    throw \Illuminate\Validation\ValidationException::withMessages(['receipt'=>'فقط رسیدهای در انتظار تأیید قابل ویرایش هستند؛ رسید تأییدشده را نمی‌توان تغییر داد.']);
   }
   $purchase=$receipt->purchase_id ? DB::table('purchases')->where('company_id',$cid)->where('id',$receipt->purchase_id)->lockForUpdate()->first() : null;
   if ($receipt->purchase_id) abort_unless($purchase,404);
   if(!DB::table('locations')->where('id',$data['warehouse_location_id'])->where('company_id',$cid)->whereRaw("LOWER(TRIM(type)) = ?",['مواد اولیه'])->where('is_active',true)->exists()){
    throw \Illuminate\Validation\ValidationException::withMessages(['warehouse_location_id'=>'انبار انتخاب‌شده معتبر یا فعال نیست. یک انبار فعال از نوع «مواد اولیه» انتخاب کنید.']);
   }
   $existing=DB::table('purchase_receipt_items')->where('purchase_receipt_id',$receipt->id)->orderBy('id')->get(['id','goods_id']);
   $existingIds=$existing->pluck('id')->map(fn($value)=>(int)$value)->all();
   $submittedIds=collect($data['items'])->pluck('id')->map(fn($value)=>(int)$value)->all();
   if(count($existingIds)!==count($submittedIds)||array_diff($existingIds,$submittedIds)||array_diff($submittedIds,$existingIds)){
    throw \Illuminate\Validation\ValidationException::withMessages(['items'=>'فهرست اقلام رسید تغییر کرده است؛ صفحه را تازه‌سازی کنید.']);
   }
   foreach($data['items'] as $index=>$item){
    $receiptItem=$existing->firstWhere('id',(int)$item['id']);
    if(!$receiptItem||(int)$receiptItem->goods_id!==(int)$item['goods_id']){
     throw \Illuminate\Validation\ValidationException::withMessages(["items.{$index}.goods_id"=>'کالای این قلم با رسید ثبت‌شده مطابقت ندارد.']);
    }
    if ($purchase) {
     $purchaseItem=DB::table('purchase_items')->where('purchase_id',$purchase->id)->where('goods_id',$receiptItem->goods_id)->first();
     if(!$purchaseItem){
      throw \Illuminate\Validation\ValidationException::withMessages(["items.{$index}.goods_id"=>'این کالا دیگر در اقلام خرید وجود ندارد.']);
     }
     $reservedByOthers=(float)DB::table('purchase_receipt_items as x')
      ->join('purchase_receipts as pr','pr.id','=','x.purchase_receipt_id')
      ->where('pr.purchase_id',$purchase->id)->where('pr.id','<>',$receipt->id)
      ->whereIn('pr.status',['pending','approved'])->where('x.goods_id',$receiptItem->goods_id)->sum('x.quantity');
     if($reservedByOthers+(float)$item['quantity']>(float)$purchaseItem->quantity+0.0000001){
      $remaining=max(0,(float)$purchaseItem->quantity-$reservedByOthers);
      throw \Illuminate\Validation\ValidationException::withMessages(["items.{$index}.quantity"=>'مقدار این قلم از باقی‌مانده خرید بیشتر است. حداکثر مقدار مجاز: '.number_format($remaining,4,'.',',').'.']);
     }
    } else {
     $goods=DB::table('goods')->where('id',$receiptItem->goods_id)->where('company_id',$cid)->where('purchasable',true)->where('is_active',true)->exists();
     if (!$goods) throw \Illuminate\Validation\ValidationException::withMessages(["items.{$index}.goods_id"=>'کالای رسید معتبر یا قابل خرید نیست.']);
    }
    DB::table('purchase_receipt_items')->where('id',$receiptItem->id)->where('purchase_receipt_id',$receipt->id)->update([
     'quantity'=>$item['quantity'],'updated_at'=>now(),
    ]);
   }
   DB::table('purchase_receipts')->where('id',$receipt->id)->where('company_id',$cid)->update([
    'warehouse_location_id'=>$data['warehouse_location_id'],
    'received_at'=>$data['received_at'],
    'notes'=>$data['notes']??null,
    'updated_at'=>now(),
   ]);
  });
  return redirect()->route('workflows.receipts')->with('success','رسید انبار به‌روزرسانی شد.');
 }
 public function approveReceipt(Request $r,int $id){app(PurchaseReceiptController::class)->approve($r,$id);return back()->with('success','اطلاعات خرید ثبت شد و رسید نهایی شد؛ موجودی و بهای کالا به‌روزرسانی شد.');}
 public function storeReceipt(Request $r){CompanyAuthorization::authorize($r,'purchase.receipt.create');$r->validate(['warehouse_location_id'=>'required|integer','received_at'=>'required|date','notes'=>'nullable|string','items'=>'required|array|min:1','items.*.goods_id'=>'required|integer|distinct','items.*.quantity'=>'required|numeric|gt:0']);app(PurchaseReceiptController::class)->store($r);return back()->with('success','رسید اولیه انبار ثبت شد؛ برای تکمیل خرید و ثبت موجودی، تدارکات باید قیمت‌ها را وارد و رسید را نهایی کند.');}
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
 public function editPurchase(Request $r,int $purchase){
  $cid=CompanyAuthorization::authorize($r,'purchase.create');
  $row=DB::table('purchases')->where('id',$purchase)->where('company_id',$cid)->first();
  abort_unless($row,404);
  abort_if($row->status==='cancelled',422,'خرید لغوشده قابل ویرایش نیست.');
  if(DB::table('purchase_receipts')->where('purchase_id',$purchase)->exists()){
   return redirect()->route('workflows.purchasing')->with('error','این خرید رسید انبار دارد؛ برای حفظ سوابق موجودی و بهای تمام‌شده، ویرایش آن غیرفعال است.');
  }
  $row->items=DB::table('purchase_items as pi')->join('goods as g','g.id','=','pi.goods_id')->where('pi.purchase_id',$purchase)->select('pi.*','g.name as goods_name')->orderBy('pi.id')->get();
  $row->direct_costs=DB::table('purchase_direct_costs')->where('purchase_id',$purchase)->orderBy('id')->get();
  return view('workflows.purchasing-edit',['purchase'=>$row,'suppliers'=>DB::table('suppliers')->where('company_id',$cid)->where('is_active',true)->orderBy('name')->get()]);
 }
 public function updatePurchase(Request $r,int $purchase){
  $cid=CompanyAuthorization::authorize($r,'purchase.create');
  if(DB::table('purchase_receipts')->where('purchase_id',$purchase)->exists()){
   return redirect()->route('workflows.purchasing')->with('error','این خرید رسید انبار دارد؛ برای حفظ سوابق موجودی و بهای تمام‌شده، ویرایش آن غیرفعال است.');
  }
  $data=$r->validate([
   'supplier_id'=>'nullable|integer','invoice_number'=>'nullable|string|max:100','invoice_date'=>'nullable|date','purchased_at'=>'required|date','notes'=>'nullable|string',
   'items'=>'required|array|min:1','items.*.id'=>'required|integer','items.*.goods_id'=>'required|integer','items.*.quantity'=>'required|numeric|gt:0','items.*.unit_price'=>'required|numeric|gt:0',
   'direct_costs'=>'nullable|array','direct_costs.*.type'=>'required|string|max:50','direct_costs.*.description'=>'nullable|string','direct_costs.*.amount'=>'required|numeric|gt:0'
  ]);
  DB::transaction(function()use($data,$cid,$purchase){
   $row=DB::table('purchases')->where('id',$purchase)->where('company_id',$cid)->lockForUpdate()->first();
   abort_unless($row,404);
   abort_if($row->status==='cancelled',422,'خرید لغوشده قابل ویرایش نیست.');
   if(DB::table('purchase_receipts')->where('purchase_id',$purchase)->lockForUpdate()->exists()){
    throw \Illuminate\Validation\ValidationException::withMessages(['purchase'=>'این خرید رسید انبار دارد؛ برای حفظ سوابق موجودی و بهای تمام‌شده، ویرایش آن غیرفعال است.']);
   }
   if(!empty($data['supplier_id'])) abort_unless(DB::table('suppliers')->where('id',$data['supplier_id'])->where('company_id',$cid)->where('is_active',true)->exists(),422,'تأمین‌کننده نامعتبر است.');
   $existing=DB::table('purchase_items')->where('purchase_id',$purchase)->orderBy('id')->pluck('id')->map(fn($id)=>(int)$id)->all();
   $submitted=collect($data['items'])->pluck('id')->map(fn($id)=>(int)$id)->all();
   abort_unless(count($existing)===count($submitted) && empty(array_diff($existing,$submitted)) && empty(array_diff($submitted,$existing)),422,'فهرست اقلام خرید تغییر کرده است؛ صفحه را تازه‌سازی کنید.');
   $subtotal=0;
   foreach($data['items'] as $item){
    abort_unless(DB::table('goods')->where('id',$item['goods_id'])->where('company_id',$cid)->where('purchasable',true)->where('is_active',true)->exists(),422,'کالا قابل خرید نیست.');
    $line=(float)$item['quantity']*(float)$item['unit_price']; $subtotal+=$line;
    DB::table('purchase_items')->where('id',$item['id'])->where('purchase_id',$purchase)->update(['goods_id'=>$item['goods_id'],'quantity'=>$item['quantity'],'unit_price'=>$item['unit_price'],'line_total'=>$line,'updated_at'=>now()]);
   }
   DB::table('purchase_direct_costs')->where('purchase_id',$purchase)->delete();
   $direct=0;
   foreach($data['direct_costs']??[] as $cost){$direct+=(float)$cost['amount'];DB::table('purchase_direct_costs')->insert(['purchase_id'=>$purchase,'type'=>$cost['type'],'description'=>$cost['description']??null,'amount'=>$cost['amount'],'created_at'=>now(),'updated_at'=>now()]);}
   DB::table('purchases')->where('id',$purchase)->where('company_id',$cid)->update(['supplier_id'=>$data['supplier_id']??null,'invoice_number'=>$data['invoice_number']??null,'invoice_date'=>$data['invoice_date']??null,'purchased_at'=>$data['purchased_at'],'notes'=>$data['notes']??null,'subtotal'=>$subtotal,'direct_cost_total'=>$direct,'total_amount'=>$subtotal+$direct,'updated_at'=>now()]);
  });
  return redirect()->route('workflows.purchasing')->with('success','اطلاعات خرید به‌روزرسانی شد.');
 }
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