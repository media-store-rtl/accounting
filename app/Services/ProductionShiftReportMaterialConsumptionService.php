<?php

namespace App\Services;

use App\Contracts\ProductionShiftReportMaterialConsumption;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ProductionShiftReportMaterialConsumptionService implements ProductionShiftReportMaterialConsumption
{
    public function consumeApprovedReport(int $companyId, int $reportId, int $reviewerId): void
    {
        $report = DB::table('production_shift_reports')
            ->where('company_id', $companyId)
            ->where('id', $reportId)
            ->lockForUpdate()
            ->first(['id', 'production_section_id', 'goods_id', 'order_id', 'work_date']);

        if (!$report) {
            throw ValidationException::withMessages(['report' => 'گزارش تولید پیدا نشد.']);
        }

        $materials = DB::table('production_shift_report_materials')
            ->where('company_id', $companyId)
            ->where('production_shift_report_id', $reportId)
            ->select('goods_id', DB::raw('SUM(quantity) as quantity'))
            ->groupBy('goods_id')
            ->orderBy('goods_id')
            ->get();

        if ($materials->isEmpty()) {
            return;
        }

        $existing = DB::table('inventory_movements')
            ->where('company_id', $companyId)
            ->where('reference_type', 'production_shift_reports')
            ->where('reference_id', $reportId)
            ->where('movement_type', 'production_consumption')
            ->where('quantity', '<', 0)
            ->select('goods_id', DB::raw('SUM(-quantity) as quantity'))
            ->groupBy('goods_id')
            ->pluck('quantity', 'goods_id');

        $expected = $materials->keyBy('goods_id');
        foreach ($existing as $goodsId => $quantity) {
            $expectedQuantity = (float) ($expected->get((int) $goodsId)->quantity ?? 0);
            if (abs((float) $quantity - $expectedQuantity) > 0.0001) {
                throw ValidationException::withMessages([
                    'materials' => 'مصرف انبار این گزارش با مقدار مواد ثبت‌شده در گزارش همخوان نیست و نیاز به اصلاح دارد.',
                ]);
            }
        }

        $productionId = null;
        if ($report->order_id !== null && DB::getSchemaBuilder()->hasTable('production_order')) {
            $productionId = DB::table('production_order as po')
                ->join('productions as p', 'p.id', '=', 'po.production_id')
                ->where('po.order_id', (int) $report->order_id)
                ->where('p.company_id', $companyId)
                ->where('p.goods_id', (int) $report->goods_id)
                ->orderByDesc('p.id')
                ->value('p.id');
            $productionId = $productionId ? (int) $productionId : null;
        }

        $fiscalYearId = DB::table('fiscal_years')
            ->where('company_id', $companyId)
            ->whereDate('starts_at', '<=', $report->work_date)
            ->whereDate('ends_at', '>=', $report->work_date)
            ->orderByDesc('id')
            ->value('id');
        $fiscalYearId = $fiscalYearId ? (int) $fiscalYearId : null;
        if ($fiscalYearId === null) {
            throw ValidationException::withMessages([
                'work_date' => 'برای تاریخ این گزارش سال مالی متناظر پیدا نشد؛ ابتدا سال مالی را اصلاح کنید. مصرف انبار ثبت نشد.',
            ]);
        }

        $stageId = DB::table('production_shift_report_stages')
            ->where('company_id', $companyId)
            ->where('production_shift_report_id', $reportId)
            ->orderBy('id')
            ->value('production_stage_id');

        foreach ($materials as $material) {
            $alreadyConsumed = (float) ($existing->get((int) $material->goods_id) ?? 0);
            $quantity = (float) $material->quantity;
            if ($alreadyConsumed + 0.0000001 >= $quantity) {
                continue;
            }

            $metadata = [
                'shift_report_id' => $reportId,
                'approved_by_user_id' => $reviewerId,
                'production_section_id' => (int) $report->production_section_id,
                // Preserve attribution even when this shift report has no linked production/order.
                'produced_goods_id' => (int) $report->goods_id,
                'work_date' => (string) $report->work_date,
                // Value only against cost layers received on or before the report date.
                'effective_occurred_at' => (string) $report->work_date . ' 23:59:59',
            ];
            if ($report->order_id !== null) {
                $metadata['order_id'] = (int) $report->order_id;
            }
            if ($productionId !== null) {
                $metadata['production_id'] = $productionId;
            }
            if ($stageId !== null) {
                $metadata['production_stage_id'] = (int) $stageId;
            }
            if ($fiscalYearId !== null) {
                $metadata['fiscal_year_id'] = $fiscalYearId;
            }

            app(InventoryService::class)->consumeFromProductionSection(
                $companyId,
                (int) $report->production_section_id,
                (int) $material->goods_id,
                $quantity - $alreadyConsumed,
                'production_shift_reports',
                $reportId,
                $metadata
            );

        }
    }
}
