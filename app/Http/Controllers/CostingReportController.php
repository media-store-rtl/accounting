<?php

namespace App\Http\Controllers;

use App\Services\CostCalculationService;
use App\Services\CostingReportService;
use App\Services\InventoryValuationService;
use App\Support\CompanyAuthorization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class CostingReportController extends Controller
{
    public function calculate(Request $request, CostCalculationService $service)
    {
        $companyId=CompanyAuthorization::authorize($request,'costing.report.view');
        $data=$request->validate(['fiscal_year_id'=>'nullable|integer','order_id'=>'nullable|integer','goods_id'=>'nullable|integer']);
        $result=$service->calculate($companyId,(int)$request->user()->id,$data['fiscal_year_id']??null,$data['order_id']??null,$data['goods_id']??null);
        return $request->expectsJson() ? response()->json(['data'=>$result]) : back()->with('success','محاسبه بهای تمام‌شده ثبت و قابل ردیابی شد.');
    }

    public function valueProductionReport(Request $request, InventoryValuationService $valuationService, int $reportId)
    {
        $companyId = CompanyAuthorization::authorize($request, 'costing.report.view');
        $report = DB::table('production_shift_reports')
            ->where('company_id', $companyId)
            ->where('id', $reportId)
            ->whereIn('status', ['approved', 'received'])
            ->first(['id', 'work_date', 'status']);

        abort_unless($report, 404, 'گزارش تولید تأییدشده پیدا نشد.');

        $materials = DB::table('production_shift_report_materials')
            ->where('company_id', $companyId)
            ->where('production_shift_report_id', $reportId)
            ->select('goods_id', DB::raw('SUM(quantity) as quantity'))
            ->groupBy('goods_id')
            ->get()
            ->keyBy('goods_id');

        if ($materials->isEmpty()) {
            return back()->with('error', 'برای این گزارش مادهٔ مصرفی ثبت نشده است.');
        }

        $movements = DB::table('inventory_movements')
            ->where('company_id', $companyId)
            ->where('reference_type', 'production_shift_reports')
            ->where('reference_id', $reportId)
            ->where('movement_type', 'production_consumption')
            ->where('quantity', '<', 0)
            ->orderBy('id')
            ->get(['id', 'goods_id', 'location_id', 'quantity', 'occurred_at']);

        if ($movements->isEmpty()) {
            return back()->with('error', 'برای این گزارش حوالهٔ مصرف انبار ثبت نشده است؛ برای جلوگیری از ساختن مصرف تاریخی یا کسر دوبارهٔ موجودی، ریالی‌سازی انجام نشد.');
        }

        $consumedByGoods = $movements->groupBy('goods_id')->map(fn ($rows) => $rows->sum(fn ($row) => abs((float) $row->quantity)));
        foreach ($materials as $goodsId => $material) {
            if (abs((float) ($consumedByGoods->get($goodsId) ?? 0) - (float) $material->quantity) > 0.0001) {
                return back()->with('error', 'مقدار حوالهٔ مصرف انبار با مواد گزارش تولید همخوان نیست؛ ابتدا مغایرت را بررسی کنید.');
            }
        }
        foreach ($consumedByGoods as $goodsId => $quantity) {
            if (!$materials->has($goodsId) || abs((float) $quantity - (float) $materials->get($goodsId)->quantity) > 0.0001) {
                return back()->with('error', 'حوالهٔ مصرف شامل ماده‌ای خارج از ریز مواد گزارش است؛ ابتدا مغایرت را بررسی کنید.');
            }
        }

        $fiscalYearId = DB::table('fiscal_years')
            ->where('company_id', $companyId)
            ->whereDate('starts_at', '<=', $report->work_date)
            ->whereDate('ends_at', '>=', $report->work_date)
            ->orderByDesc('id')
            ->value('id');

        if (!$fiscalYearId) {
            return back()->with('error', 'برای تاریخ گزارش سال مالی متناظر پیدا نشد؛ ریالی‌سازی انجام نشد.');
        }

        $result = DB::transaction(function () use ($movements, $fiscalYearId, $valuationService, $companyId) {
            $valued = 0;
            $alreadyValued = 0;
            $unvalued = 0;

            foreach ($movements as $movement) {
                $existing = DB::table('inventory_consumption_costs')
                    ->where('inventory_movement_id', $movement->id)
                    ->first(['id', 'quantity']);
                if ($existing) {
                    if ((float) $existing->quantity + 0.0001 >= abs((float) $movement->quantity)) {
                        $alreadyValued++;
                        continue;
                    }

                    // Complete only the missing quantity from remaining eligible layers.
                    // The service appends allocations to the existing row without
                    // changing its historical cost items or consuming layers twice.
                    $value = $valuationService->valueInventoryMovement((int) $movement->id, (int) $fiscalYearId);
                    if ((float) ($value['unvalued_quantity'] ?? 0) <= 0.0001) {
                        $valued++;
                    } else {
                        $unvalued++;
                    }
                    continue;
                }

                // Avoid creating a permanent partial cost row from this action:
                // the ledger schema permits one cost row per movement, so a partial
                // row cannot safely be topped up later after new receipts arrive.
                $method = $valuationService->methodForCompany($companyId);
                $layerColumn = $method === InventoryValuationService::FIFO ? 'fifo_quantity_remaining' : 'weighted_average_quantity_remaining';
                $available = (float) DB::table('inventory_cost_layers')
                    ->where('company_id', $companyId)
                    ->where('goods_id', $movement->goods_id)
                    ->where('location_id', $movement->location_id)
                    ->where('received_at', '<=', $movement->occurred_at)
                    ->sum($layerColumn);
                if ($available + 0.0001 < abs((float) $movement->quantity)) {
                    $unvalued++;
                    continue;
                }

                $value = $valuationService->valueInventoryMovement((int) $movement->id, (int) $fiscalYearId);
                if (!empty($value['valued']) && (float) ($value['valued_quantity'] ?? 0) > 0) {
                    $valued++;
                    if ((float) ($value['unvalued_quantity'] ?? 0) > 0.0001) {
                        $unvalued++;
                    }
                } else {
                    $unvalued++;
                }
            }

            return compact('valued', 'alreadyValued', 'unvalued');
        });

        if ($result['unvalued'] > 0) {
            return back()->with('error', 'بخشی از مصرف هنوز بهای معتبر ندارد؛ موجودی لایه‌ایِ قابل ردیابی در تاریخ مصرف کافی نیست. هیچ بهای ساختگی ثبت نشد.');
        }

        return back()->with('success', 'ریالی‌سازی ردیف بررسی شد: '.$result['valued'].' حرکت ارزش‌گذاری شد و '.$result['alreadyValued'].' حرکت از قبل ارزش‌گذاری شده بود؛ موجودی دوباره کسر نشد.');
    }

    public function index(Request $request, CostingReportService $service, InventoryValuationService $valuationService)
    {
        $companyId=CompanyAuthorization::authorize($request,'costing.report.view');
        $data=$request->validate([
            'fiscal_year_id'=>'nullable|integer',
            'order_id'=>'nullable|integer',
            'goods_id'=>'nullable|integer',
            'start_date'=>'nullable|date',
            'end_date'=>'nullable|date|after_or_equal:start_date',
            'personnel_id'=>'nullable|integer',
            'production_section_id'=>'nullable|integer',
            'production_stage_id'=>'nullable|integer',
            'valuation_method'=>'nullable|in:fifo,weighted_average',
        ]);
        $personnelId=$data['personnel_id']??null;
        $productionSectionId=$data['production_section_id']??null;
        $productionStageId=$data['production_stage_id']??null;
        // This selection affects only the read-only historical report calculation.
        // New inventory consumption remains fixed to FIFO in InventoryValuationService.
        $valuationMethod=$data['valuation_method']??$valuationService->methodForCompany($companyId);
        if($personnelId!==null) abort_unless(DB::table('personnel')->where('id',$personnelId)->where('account_id',$request->user()->account_id)->exists(),422,'کارگر انتخاب‌شده معتبر نیست.');
        if($productionSectionId!==null) abort_unless(DB::table('production_sections as ps')->join('locations as l','l.id','=','ps.location_id')->where('ps.id',$productionSectionId)->where('l.company_id',$companyId)->exists(),422,'قسمت تولید انتخاب‌شده معتبر نیست.');
        if($productionStageId!==null) abort_unless(DB::table('production_stages as st')->join('production_routes as pr','pr.id','=','st.production_route_id')->where('st.id',$productionStageId)->where('pr.company_id',$companyId)->exists(),422,'مرحله تولید انتخاب‌شده معتبر نیست.');
        $report=$service->generate($companyId,$data['fiscal_year_id']??null,$data['order_id']??null,$data['goods_id']??null,$data['start_date']??null,$data['end_date']??null,$personnelId,$productionSectionId,$productionStageId,$valuationMethod);

        if($request->expectsJson()) return response()->json(['data'=>$report]);

        $fiscalYears=DB::table('fiscal_years')->where('company_id',$companyId)->orderByDesc('id')->get();
        $goods=DB::table('goods')->where('company_id',$companyId)->where('is_active',true)->orderBy('name')->get(['id','code','name']);
        $personnel=DB::table('personnel')->where('account_id',$request->user()->account_id)->where('is_active',true)->orderBy('name')->get(['id','name','code']);
        $productionSections=DB::table('production_sections as ps')->join('locations as l','l.id','=','ps.location_id')->where('l.company_id',$companyId)->orderBy('l.name')->get(['ps.id','l.name']);
        $productionStages=DB::table('production_stages as st')->join('production_routes as pr','pr.id','=','st.production_route_id')->where('pr.company_id',$companyId)->orderBy('pr.name')->orderBy('st.sequence')->get(['st.id','st.name','pr.name as route_name']);
        return view('reports.costing',compact('report','fiscalYears','goods','personnel','productionSections','productionStages','valuationMethod'));
    }
}
