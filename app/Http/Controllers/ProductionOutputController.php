<?php

namespace App\Http\Controllers;

use App\Services\ProductionOutputService;
use App\Services\InventoryService;
use App\Support\CompanyAuthorization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class ProductionOutputController extends Controller
{
    public function index(Request $request)
    {
        $companyId = CompanyAuthorization::authorize($request, 'production.output.view');
        $rows = DB::table('production_outputs as po')
            ->join('productions as p','p.id','=','po.production_id')
            ->join('goods as g','g.id','=','po.goods_id')
            ->join('locations as l','l.id','=','po.warehouse_location_id')
            ->where('po.company_id',$companyId)
            ->select('po.*','p.number as production_number','g.name as goods_name','l.name as location_name')
            ->latest('po.id')->paginate(20);

        // Include quantities received directly from production shift reports.
        // These are already posted to inventory, so they are displayed separately
        // and must not be processed again through production_outputs.
        $shiftRows = DB::table('production_shift_reports as r')
            ->join('goods as g', 'g.id', '=', 'r.goods_id')
            ->leftJoin('production_sections as s', 's.id', '=', 'r.production_section_id')
            ->leftJoin('locations as section_location', 'section_location.id', '=', 's.location_id')
            ->leftJoin('locations as l', 'l.id', '=', 'r.warehouse_location_id')
            ->where('r.company_id', $companyId)->whereIn('r.status', ['approved', 'received'])->where('g.sellable', true)->whereNotNull('r.warehouse_requested_at')
            ->select('r.id as report_id', 'r.work_date', 'r.status', 'r.produced_quantity', 'r.scrap_quantity',
                'r.warehouse_quantity as quantity', 'r.warehouse_location_id', 'r.reviewed_by_user_id',
                'g.name as goods_name', 'section_location.name as section_name', 'l.name as location_name')
            ->latest('r.reviewed_at')->latest('r.id')->paginate(20, ['*'], 'shift_page');
        $shiftWarehouses = DB::table('locations')->where('company_id', $companyId)->where('is_active', true)
            ->where(function ($query) { $query->whereRaw('LOWER(TRIM(type)) = ?', ['warehouse'])->orWhereIn(DB::raw('TRIM(type)'), ['مواد', 'محصول', 'ضایعات']); })
            ->orderBy('name')->get(['id', 'name']);
        return view('production.outputs.index', compact('rows', 'shiftRows', 'shiftWarehouses'));
    }


    public function receiveShiftReport(Request $request, int $report, InventoryService $inventory, ProductionOutputService $productionOutputService)
    {
        $companyId = CompanyAuthorization::authorize($request, 'production.output.receive');
        $data = $request->validate(['warehouse_location_id' => ['required', 'integer']]);
        DB::transaction(function () use ($companyId, $report, $data, $request, $inventory, $productionOutputService) {
            $row = DB::table('production_shift_reports')->where('company_id', $companyId)->where('id', $report)->lockForUpdate()->first();
            if (!$row || $row->status !== 'approved') {
                throw \Illuminate\Validation\ValidationException::withMessages(['report' => 'این گزارش آماده دریافت نیست یا قبلاً تحویل کامل شده است.']);
            }
            if (empty($row->warehouse_requested_at)) {
                throw \Illuminate\Validation\ValidationException::withMessages(['report' => 'ابتدا باید درخواست تحویل این گزارش جداگانه ثبت و برای انبار ارسال شود.']);
            }
            if ((int) $row->reviewed_by_user_id === (int) $request->user()->id) {
                throw \Illuminate\Validation\ValidationException::withMessages(['report' => 'تأیید نهایی انبار باید توسط کاربری غیر از تأییدکننده گزارش تولید انجام شود.']);
            }
            $goods = DB::table('goods')->where('company_id', $companyId)->where('id', $row->goods_id)->first(['sellable', 'producible']);
            if (!$goods || !(bool) $goods->sellable || !(bool) $goods->producible) {
                throw \Illuminate\Validation\ValidationException::withMessages(['report' => 'فقط کالای نهایی قابل‌فروش می‌تواند از این مسیر وارد انبار شود.']);
            }
            $available = max(0, (float) $row->produced_quantity - (float) ($row->scrap_quantity ?? 0));
            $alreadyReceived = (float) ($row->warehouse_quantity ?? 0);
            $remaining = $available - $alreadyReceived;
            if ($remaining <= 0.0000001) {
                throw \Illuminate\Validation\ValidationException::withMessages(['report' => 'مقدار آماده تحویل باقی نمانده است.']);
            }
            if ($alreadyReceived > 0 && (int) $row->warehouse_location_id !== (int) $data['warehouse_location_id']) {
                throw \Illuminate\Validation\ValidationException::withMessages(['warehouse_location_id' => 'برای تکمیل تحویل این گزارش باید همان انبار قبلی انتخاب شود.']);
            }
            $warehouse = DB::table('locations')->where('id', (int) $data['warehouse_location_id'])->where('company_id', $companyId)->where('is_active', true)
                ->where(function ($query) { $query->whereRaw('LOWER(TRIM(type)) = ?', ['warehouse'])->orWhereIn(DB::raw('TRIM(type)'), ['مواد', 'محصول', 'ضایعات']); })->exists();
            if (!$warehouse) throw \Illuminate\Validation\ValidationException::withMessages(['warehouse_location_id' => 'انبار مقصد معتبر نیست.']);
            $inventory->receiveFinishedGoods($companyId, (int) $data['warehouse_location_id'], (int) $row->goods_id, $remaining,
                'production_shift_reports', $report, ['production_section_id' => (int) $row->production_section_id, 'shift_name' => $row->shift_name,
                    'work_date' => $row->work_date, 'reported_by_user_id' => (int) $row->reported_by_user_id,
                    'reviewed_by_user_id' => (int) $row->reviewed_by_user_id, 'received_by_user_id' => (int) $request->user()->id]);
            DB::table('production_shift_reports')->where('company_id', $companyId)->where('id', $report)->update([
                'warehouse_location_id' => (int) $data['warehouse_location_id'], 'warehouse_quantity' => $available,
                'status' => 'received', 'warehouse_received_at' => now(), 'updated_at' => now(),
            ]);
            // Shift-report receipts use this controller path rather than ProductionOutputService::receive;
            // refresh order allocations here as well so sales are notified when the final shortage clears.
            $productionOutputService->refreshOrderShortagesAfterReceipt($companyId, (int) $row->goods_id, (int) $request->user()->id);
            DB::table('audit_trails')->insert([
                'company_id' => $companyId, 'user_id' => (int) $request->user()->id, 'module' => 'production',
                'action' => 'production_shift_report.warehouse_receive', 'auditable_type' => 'production_shift_reports', 'auditable_id' => $report,
                'before' => json_encode((array) $row),
                'after' => json_encode((array) DB::table('production_shift_reports')->where('company_id', $companyId)->where('id', $report)->first()),
                'method' => 'SERVICE', 'status_code' => 200, 'created_at' => now(), 'updated_at' => now(),
            ]);
        });
        return back()->with('success', 'تحویل کامل تأیید شد و موجودی انبار به مقدار آماده‌شده افزایش یافت.');
    }

    public function create(Request $request)
    {
        $companyId = CompanyAuthorization::authorize($request, 'production.output.create');
        $productions = DB::table('productions as p')->join('goods as g','g.id','=','p.goods_id')
            ->where('p.company_id',$companyId)->where('p.status','completed')
            ->orderByDesc('p.id')->get(['p.id','p.number','p.goods_id','g.code as goods_code','g.name as goods_name']);
        $locations = DB::table('locations')->where('company_id',$companyId)->where('type','warehouse')->where('is_active',true)->orderBy('name')->get();
        return view('production.outputs.create', compact('productions','locations'));
    }

    public function store(Request $request, ProductionOutputService $service)
    {
        $companyId = CompanyAuthorization::authorize($request, 'production.output.create');
        $data = $request->validate([
            'production_id'=>['required','integer'],
            'order_id'=>['nullable','integer'],
            'warehouse_location_id'=>['required','integer'],
            'quantity'=>['required','numeric','gt:0'],
            'produced_at'=>['required','date'],
            'notes'=>['nullable','string'],
        ]);
        $service->create($companyId,(int)$data['production_id'],$data['order_id']??null,(int)$data['warehouse_location_id'],(float)$data['quantity'],(int)$request->user()->id,$data['produced_at'],$data['notes']??null);
        return redirect()->route('production.outputs.index')->with('success','خروجی تولید ثبت شد و برای تأیید انبار ارسال شد.');
    }

    public function confirm(Request $request, int $output, ProductionOutputService $service)
    {
        $companyId = CompanyAuthorization::authorize($request, 'production.output.confirm');
        $service->approve($companyId,$output,(int)$request->user()->id);
        return back()->with('success','خروجی تولید تأیید شد و وارد موجودی شد.');
    }

    public function reject(Request $request, int $output, ProductionOutputService $service)
    {
        $companyId = CompanyAuthorization::authorize($request, 'production.output.confirm');
        $data = $request->validate(['rejection_reason'=>['required','string','max:1000']]);
        $service->reject($companyId,$output,(int)$request->user()->id,$data['rejection_reason']);
        return back()->with('success','خروجی تولید رد شد.');
    }

    public function receive(Request $request, int $output, ProductionOutputService $service)
    {
        $companyId = CompanyAuthorization::authorize($request, 'production.output.receive');
        $service->receive($companyId,$output,(int)$request->user()->id);
        return back()->with('success','دریافت کالای ساخته‌شده ثبت شد.');
    }
}
