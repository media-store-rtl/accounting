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

    public function refreshAllProductionReports(Request $request, InventoryValuationService $valuationService)
    {
        $companyId = CompanyAuthorization::authorize($request, 'costing.report.view');
        $reportIds = DB::table('production_shift_reports')
            ->where('company_id', $companyId)
            ->whereIn('status', ['approved', 'received'])
            ->whereExists(function ($query) use ($companyId) {
                $query->selectRaw('1')->from('production_shift_report_materials as rm')
                    ->whereColumn('rm.production_shift_report_id', 'production_shift_reports.id')
                    ->where('rm.company_id', $companyId);
            })
            ->orderBy('work_date')->orderBy('id')->pluck('id');
        $succeeded = 0; $failed = 0; $partial = 0; $details = [];
        foreach ($reportIds as $reportId) {
            // Reuse the company-scoped, idempotent single-report valuation path.
            $response = $this->valueProductionReport($request, $valuationService, (int) $reportId);
            $payload = method_exists($response, 'getData') ? $response->getData(true) : [];
            $message = (string) ($payload['message'] ?? '');
            if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300 && !empty($payload['success'])) {
                $succeeded++;
                if (str_contains($message, 'بخشی') || str_contains($message, 'هنوز بخشی بدون بها')) {
                    $partial++;
                    $details[] = 'گزارش #' . (int) $reportId . ': ' . $message;
                }
            } else {
                $failed++;
                $details[] = 'گزارش #' . (int) $reportId . ': ' . ($message !== '' ? $message : 'ارزش‌گذاری انجام نشد.');
            }
        }

        $processed = $reportIds->count();
        $complete = $processed > 0 && $failed === 0 && $partial === 0;
        $success = $processed > 0 && $failed === 0;
        $summary = $processed === 0
            ? 'هیچ گزارش تولید واجد شرایطی برای بروزرسانی پیدا نشد.'
            : ($failed > 0
                ? 'بروزرسانی پایان یافت، اما تعدادی از گزارش‌ها ناموفق بودند؛ جزئیات را بررسی کنید.'
                : ($partial > 0
                    ? 'بروزرسانی انجام شد، اما تعدادی از گزارش‌ها هنوز بخشی بدون بها دارند.'
                    : 'بروزرسانی همه گزارش‌ها با موفقیت کامل شد.'));

        return response()->json([
            'success' => $success,
            'complete' => $complete,
            'processed' => $processed,
            'succeeded' => $succeeded,
            'failed' => $failed,
            'partial' => $partial,
            'message' => $summary,
            'details' => array_slice($details, 0, 5),
        ]);
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
            return $this->valuationResponse($request, 'برای این گزارش مادهٔ مصرفی ثبت نشده است.', false, 422);
        }

        // Resolve the same production-consumption pool used by CostingReportService.
        // Consumption movements are often linked to supply requests/production runs,
        // not directly to the shift report id.
        $allocation = $this->productionReportConsumptionMovements($companyId, $reportId);
        $movements = $allocation['movements'];

        if ($movements->isEmpty()) {
            return $this->valuationResponse($request, 'برای این گزارش مصرف قابل تخصیص از انبار پیدا نشد؛ ارتباط قسمت تولید، ماده، سفارش و تاریخ را بررسی کنید.', false, 422);
        }

        foreach ($materials as $goodsId => $material) {
            if (abs((float) ($allocation['allocated_by_goods'][$goodsId] ?? 0) - (float) $material->quantity) > 0.0001) {
                return $this->valuationResponse($request, 'مقدار مصرف قابل تخصیص به گزارش با ریز مواد همخوان نیست؛ برای جلوگیری از ارزش‌گذاری مصرف اشتباه، اقدامی انجام نشد.', false, 422);
            }
        }

        $fiscalYearId = DB::table('fiscal_years')
            ->where('company_id', $companyId)
            ->whereDate('starts_at', '<=', $report->work_date)
            ->whereDate('ends_at', '>=', $report->work_date)
            ->orderByDesc('id')
            ->value('id');

        if (!$fiscalYearId) {
            return $this->valuationResponse($request, 'برای تاریخ گزارش سال مالی متناظر پیدا نشد؛ ریالی‌سازی انجام نشد.', false, 422);
        }

        $result = DB::transaction(function () use ($movements, $fiscalYearId, $valuationService) {
            $refreshed = 0;
            $unvalued = 0;

            foreach ($movements as $movement) {
                // Revisit every movement, including fully valued ones. Existing
                // rial amounts are recalculated from their linked receipt layers;
                // only any still-missing quantity may be topped up.
                $value = $valuationService->valueInventoryMovement((int) $movement->id, (int) $fiscalYearId);
                if (!empty($value['valued']) && (float) ($value['valued_quantity'] ?? 0) > 0) {
                    $refreshed++;
                    if ((float) ($value['unvalued_quantity'] ?? 0) > 0.0001) {
                        $unvalued++;
                    }
                } else {
                    $unvalued++;
                }
            }

            return compact('refreshed', 'unvalued');
        });

        if ($result['refreshed'] === 0 && $result['unvalued'] > 0) {
            return $this->valuationResponse($request, 'برای مصرف این گزارش هنوز بهای معتبر قابل ردیابی وجود ندارد؛ هیچ بهای ساختگی ثبت نشد.', false, 422);
        }

        if ($result['unvalued'] > 0) {
            return $this->valuationResponse($request, 'مبالغ ریالی '.$result['refreshed'].' حرکت بروزرسانی شد؛ '.$result['unvalued'].' حرکت هنوز بخشی بدون بها دارد و پس از ثبت بهای معتبر دوباره قابل بروزرسانی است.', true);
        }

        return $this->valuationResponse($request, 'مبالغ ریالی همه '.$result['refreshed'].' حرکت مصرف دوباره محاسبه و بروزرسانی شد؛ موجودی انبار دوباره کسر نشد.', true);
    }


    private function valuationResponse(Request $request, string $message, bool $success, int $status = 200)
    {
        if ($request->expectsJson()) {
            return response()->json(['success' => $success, 'message' => $message], $status);
        }

        return back()->with($success ? 'success' : 'error', $message);
    }

    /**
     * Allocate consumption movements to approved/received shift reports using
     * the same section + material + order + date ordering as the costing report.
     *
     * A movement may contribute to more than one report; valuation remains at
     * movement level, while allocation is used only to identify relevant moves.
     */
    private function productionReportConsumptionMovements(int $companyId, int $reportId): array
    {
        $usageRows = DB::table('production_shift_report_materials as rm')
            ->join('production_shift_reports as r', 'r.id', '=', 'rm.production_shift_report_id')
            ->where('r.company_id', $companyId)
            ->whereIn('r.status', ['approved', 'received'])
            ->orderBy('r.work_date')->orderBy('r.id')->orderBy('rm.id')
            ->get([
                'rm.id as material_row_id', 'rm.production_shift_report_id as report_id',
                'rm.goods_id as material_id', 'rm.quantity',
                'r.production_section_id', 'r.order_id', 'r.work_date',
            ]);

        $consumptionRows = DB::table('inventory_movements as m')
            ->join('locations as l', function ($join) use ($companyId) {
                $join->on('l.id', '=', 'm.location_id')->where('l.company_id', '=', $companyId);
            })
            ->leftJoin('production_sections as ps', 'ps.location_id', '=', 'l.id')
            ->where('m.company_id', $companyId)
            ->where('m.movement_type', 'production_consumption')
            ->where('m.quantity', '<', 0)
            ->orderBy('m.occurred_at')->orderBy('m.id')
            ->get([
                'm.id', 'm.goods_id', 'm.quantity', 'm.location_id',
                'm.occurred_at', 'm.metadata', 'm.reference_type', 'm.reference_id',
                'ps.id as location_section_id',
            ]);

        $metadataByMovement = [];
        $supplyIds = [];
        $productionIds = [];
        foreach ($consumptionRows as $movement) {
            $metadata = json_decode((string) $movement->metadata, true) ?: [];
            $metadataByMovement[(int) $movement->id] = $metadata;
            if (!empty($metadata['supply_request_id'])) $supplyIds[] = (int) $metadata['supply_request_id'];
            if (!empty($metadata['production_id'])) $productionIds[] = (int) $metadata['production_id'];
        }

        $supplies = empty($supplyIds) ? collect() : DB::table('supply_requests')
            ->where('company_id', $companyId)->whereIn('id', array_unique($supplyIds))
            ->get(['id', 'production_section_id', 'order_id'])->keyBy('id');
        $productionOrders = empty($productionIds) ? collect() : DB::table('production_order')
            ->whereIn('production_id', array_unique($productionIds))
            ->orderBy('order_id')->get(['production_id', 'order_id'])
            ->groupBy('production_id')->map(fn ($links) => $links->first());

        $pools = [];
        $usageReportIds = [];
        $directMovements = [];
        foreach ($usageRows as $usage) {
            $usageReportIds[(int) $usage->report_id] = true;
        }

        foreach ($consumptionRows as $movement) {
            $metadata = $metadataByMovement[(int) $movement->id] ?? [];
            $supply = $supplies->get((int) ($metadata['supply_request_id'] ?? 0));
            $productionOrder = $productionOrders->get((int) ($metadata['production_id'] ?? 0));
            $sectionId = (int) ($supply->production_section_id ?? $metadata['production_section_id'] ?? $movement->location_section_id ?? 0);
            $orderId = (int) ($supply->order_id ?? $productionOrder->order_id ?? $metadata['order_id'] ?? 0);

            // Prefer the authoritative report link created when an approved shift report
            // consumes its own materials. Do not let an earlier report in the same
            // section/material/order pool consume that movement first.
            $directReportId = (int) ($metadata['shift_report_id'] ?? 0);
            if ($directReportId <= 0 && (string) $movement->reference_type === 'production_shift_reports') {
                $directReportId = (int) $movement->reference_id;
            }
            if ($directReportId > 0 && isset($usageReportIds[$directReportId])) {
                $directMovements[$directReportId][(int) $movement->goods_id][] = [
                    'movement' => $movement,
                    'remaining' => abs((float) $movement->quantity),
                    'date' => substr((string) $movement->occurred_at, 0, 10),
                ];
                continue;
            }

            if ($sectionId <= 0) continue;
            $key = $sectionId . ':' . (int) $movement->goods_id . ':' . $orderId;
            $pools[$key][] = [
                'movement' => $movement,
                'remaining' => abs((float) $movement->quantity),
                'date' => substr((string) $movement->occurred_at, 0, 10),
            ];
        }

        $allocatedByGoods = [];
        $targetMovementIds = [];
        $remainingByUsageRow = [];

        // Allocate report-linked movements first, independently of the shared legacy
        // pool. This prevents historical pool ordering from stealing an explicit link.
        foreach ($usageRows as $usage) {
            $rowId = (int) $usage->material_row_id;
            $remainingUsage = max(0.0, (float) $usage->quantity);
            $reportIdForUsage = (int) $usage->report_id;
            $materialId = (int) $usage->material_id;

            if (!empty($directMovements[$reportIdForUsage][$materialId])) {
                foreach ($directMovements[$reportIdForUsage][$materialId] as &$direct) {
                    if ($remainingUsage <= 0.0000001) break;
                    if ($direct['remaining'] <= 0.0000001) continue;
                    $allocated = min($remainingUsage, $direct['remaining']);
                    $direct['remaining'] -= $allocated;
                    $remainingUsage -= $allocated;
                    if ($reportIdForUsage === $reportId) {
                        $allocatedByGoods[$materialId] = ($allocatedByGoods[$materialId] ?? 0.0) + $allocated;
                        $targetMovementIds[(int) $direct['movement']->id] = true;
                    }
                }
                unset($direct);
            }
            $remainingByUsageRow[$rowId] = $remainingUsage;
        }

        // Preserve section + material + order + date allocation for legacy movements
        // that do not identify a shift report directly.
        foreach ($usageRows as $usage) {
            $rowId = (int) $usage->material_row_id;
            $remainingUsage = (float) ($remainingByUsageRow[$rowId] ?? max(0.0, (float) $usage->quantity));
            if ($remainingUsage <= 0.0000001) continue;

            $key = (int) $usage->production_section_id . ':' . (int) $usage->material_id . ':' . (int) ($usage->order_id ?? 0);
            if (empty($pools[$key])) continue;
            foreach ($pools[$key] as &$pool) {
                if ($remainingUsage <= 0.0000001) break;
                if ($pool['remaining'] <= 0.0000001 || $pool['date'] > substr((string) $usage->work_date, 0, 10)) continue;
                $allocated = min($remainingUsage, $pool['remaining']);
                $pool['remaining'] -= $allocated;
                $remainingUsage -= $allocated;
                if ((int) $usage->report_id === $reportId) {
                    $goodsId = (int) $usage->material_id;
                    $allocatedByGoods[$goodsId] = ($allocatedByGoods[$goodsId] ?? 0.0) + $allocated;
                    $targetMovementIds[(int) $pool['movement']->id] = true;
                }
            }
            unset($pool);
        }

        $movementIds = array_keys($targetMovementIds);
        $movements = $movementIds
            ? $consumptionRows->whereIn('id', $movementIds)->values()
            : collect();

        return [
            'movements' => $movements,
            'allocated_by_goods' => $allocatedByGoods,
        ];
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
