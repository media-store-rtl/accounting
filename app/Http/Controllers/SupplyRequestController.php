<?php

namespace App\Http\Controllers;

use App\Services\NotificationRecipientService;
use App\Support\CompanyAuthorization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplyRequestController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $companyId=CompanyAuthorization::authorize($request,'supply_request.create');
        $data=$request->validate([
            'fiscal_year_id'=>'required|integer','production_id'=>'nullable|integer','production_stage_id'=>'nullable|required_without_all:production_stage_run_id,production_section_id,production_id,order_id|integer','production_stage_run_id'=>'nullable|integer','order_id'=>'nullable|integer','production_section_id'=>'nullable|integer','needed_at'=>'required|date',
            'notes'=>'nullable|string','items'=>'required|array|min:1','items.*.goods_id'=>'required|integer','items.*.quantity'=>'required|numeric|gt:0'
        ], [
            'production_stage_id.required_without_all' => 'مرحله تولید، قسمت تولید یا سفارش باید مشخص شود.',
        ]);
        $model=DB::transaction(function()use($data,$companyId,$request){
            abort_unless(DB::table('fiscal_years')->where('id',$data['fiscal_year_id'])->where('company_id',$companyId)->exists(),422,'سال مالی نامعتبر است.');
            if(!empty($data['production_section_id'])) abort_unless(DB::table('production_sections as s')->join('locations as l','l.id','=','s.location_id')->where('s.id',$data['production_section_id'])->where('l.company_id',$companyId)->where('l.is_active',true)->exists(),422,'قسمت تولید نامعتبر است.');
            $stageDefinition=null;
            if(!empty($data['production_stage_id'])){
                $stageDefinition=DB::table('production_stages as s')->join('production_routes as pr','pr.id','=','s.production_route_id')->where('s.id',$data['production_stage_id'])->where('pr.company_id',$companyId)->where('s.status','active')->first(['s.id']);
                abort_unless($stageDefinition,422,'مرحله تولید انتخاب‌شده معتبر نیست.');
            }
            $stageRun=null;
            if(!empty($data['production_stage_run_id'])){
                $stageRun=DB::table('production_stage_runs as sr')->join('productions as p','p.id','=','sr.production_id')->join('production_stages as s','s.id','=','sr.production_stage_id')->join('production_routes as pr','pr.id','=','s.production_route_id')->where('sr.id',$data['production_stage_run_id'])->where('p.company_id',$companyId)->where('s.status','active')->first(['sr.id','p.id as production_id']);
                abort_unless($stageRun,422,'مرحله تولید انتخاب‌شده معتبر نیست.');
                if(!empty($data['production_id'])) abort_unless((int)$data['production_id']===(int)$stageRun->production_id,422,'مرحله انتخاب‌شده متعلق به دستور تولید دیگری است.');
            }
            foreach(['production_id'=>'productions','order_id'=>'orders'] as $key=>$table){
                if(!empty($data[$key])) abort_unless(DB::table($table)->where('id',$data[$key])->where('company_id',$companyId)->exists(),422,'مرجع انتخاب‌شده نامعتبر است.');
            }
            $id=DB::table('supply_requests')->insertGetId([
                'company_id'=>$companyId,'fiscal_year_id'=>$data['fiscal_year_id'],'production_id'=>$stageRun? (int)$stageRun->production_id : ($data['production_id']??null),'production_stage_id'=>$data['production_stage_id']??null,'production_stage_run_id'=>$data['production_stage_run_id']??null,'order_id'=>$data['order_id']??null,'production_section_id'=>$data['production_section_id']??null,
                'requested_by_user_id'=>$request->user()->id,'requested_at'=>now(),'needed_at'=>$data['needed_at'],'status'=>'checking_stock','notes'=>$data['notes']??null,
                'created_at'=>now(),'updated_at'=>now()
            ]);
            foreach($data['items'] as $item){
                abort_unless(DB::table('goods')->where('id',$item['goods_id'])->where('company_id',$companyId)->where('is_active',true)->exists(),422,'کالا نامعتبر است.');
                $qty=(float)$item['quantity'];
                DB::table('supply_request_items')->insert([
                    'supply_request_id'=>$id,'goods_id'=>$item['goods_id'],'requested_quantity'=>$qty,'available_quantity'=>0,'shortage_quantity'=>0,
                    'supplied_quantity'=>0,'created_at'=>now(),'updated_at'=>now()
                ]);
            }
            DB::table('supply_requests')->where('id',$id)->update(['status'=>'checking_stock','updated_at'=>now()]);
            return DB::table('supply_requests')->where('id',$id)->first();
        });
        app(NotificationRecipientService::class)->send(
            $companyId,
            'supply.stock_check_required',
            ['supply_request_id' => $model->id],
            (int) $request->user()->id,
            'supply_request.handover.create'
        );
        return response()->json(['data'=>$model],201);
    }

    public function reviewStock(Request $request, int $supplyRequest)
    {
        $companyId = CompanyAuthorization::authorize($request, 'supply_request.handover.create');
        $result = DB::transaction(function () use ($companyId, $supplyRequest, $request) {
            $row = DB::table('supply_requests')->where('id', $supplyRequest)->where('company_id', $companyId)->lockForUpdate()->first();
            abort_unless($row, 404);
            abort_unless($row->status === 'checking_stock', 422, 'این درخواست قبلاً بررسی شده یا در وضعیت بررسی انبار نیست.');

            $items = DB::table('supply_request_items')->where('supply_request_id', $row->id)->lockForUpdate()->get();
            abort_unless($items->isNotEmpty(), 422, 'اقلام درخواست پیدا نشدند.');
            $hasShortage = false;
            foreach ($items as $item) {
                $available = app(\App\Services\InventoryBalanceService::class)
                    ->availableInWarehouses($companyId, (int) $item->goods_id);
                $usable = min((float) $item->requested_quantity, max(0, $available));
                $shortage = max(0, (float) $item->requested_quantity - $usable);
                $hasShortage = $hasShortage || $shortage > 0;
                DB::table('supply_request_items')->where('id', $item->id)->update([
                    'available_quantity' => $usable, 'shortage_quantity' => $shortage, 'updated_at' => now(),
                ]);
            }
            $status = $hasShortage ? 'shortage_pending' : 'stock_available';
            DB::table('supply_requests')->where('id', $row->id)->update(['status' => $status, 'updated_at' => now()]);
            return ['status' => $status, 'items' => DB::table('supply_request_items as sri')
                ->join('goods as g', 'g.id', '=', 'sri.goods_id')
                ->where('sri.supply_request_id', $row->id)
                ->select('sri.*', 'g.name as goods_name', 'g.code as goods_code')->get(), 'row' => $row];
        });

        if ($result['status'] === 'shortage_pending') {
            $first = $result['items']->first();
            app(NotificationRecipientService::class)->send($companyId, 'supply.shortage', [
                'supply_request_id' => $supplyRequest,
                'production_id' => $result['row']->production_id,
                'order_id' => $result['row']->order_id,
                'needed_at' => $result['row']->needed_at,
                'goods_id' => $first?->goods_id,
                'goods_name' => $first?->goods_name,
                'requested_quantity' => $first?->requested_quantity,
                'available_quantity' => $first?->available_quantity,
                'shortage_quantity' => $first?->shortage_quantity,
                'warehouse_note' => $request->input('warehouse_note'),
            ], (int) $request->user()->id, 'supply.manage');
        } else {
            app(NotificationRecipientService::class)->send($companyId, 'supply.stock_available', [
                'supply_request_id' => $supplyRequest,
                'production_id' => $result['row']->production_id,
                'order_id' => $result['row']->order_id,
                'needed_at' => $result['row']->needed_at,
            ], (int) $request->user()->id, 'supply_request.create');
        }

        return back()->with('success', $result['status'] === 'shortage_pending'
            ? 'بررسی انبار ثبت شد؛ کسری برای پیگیری به تدارکات ارجاع شد.'
            : 'بررسی انبار ثبت شد؛ موجودی برای تحویل کافی است.');
    }

    public function show(Request $request,int $supplyRequest): JsonResponse
    {
        $companyId=CompanyAuthorization::authorize($request,'supply_request.view');
        $row=DB::table('supply_requests')->where('id',$supplyRequest)->where('company_id',$companyId)->first();
        abort_unless($row,404);
        return response()->json(['data'=>$row,'items'=>DB::table('supply_request_items')->where('supply_request_id',$supplyRequest)->get()]);
    }
}