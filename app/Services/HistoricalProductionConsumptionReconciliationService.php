<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Safely reconciles an approved historical shift report that has no
 * production-consumption movement. The plan is read-only; apply rechecks it
 * under a transaction and records an auditable, report-linked movement.
 */
final class HistoricalProductionConsumptionReconciliationService
{
    public function preview(int $companyId, int $reportId): array
    {
        return $this->buildPlan($companyId, $reportId);
    }

    public function apply(int $companyId, int $reportId, int $actorId, string $method = 'WEB'): array
    {
        if ($actorId < 1 || !DB::table('users')->where('id', $actorId)->exists()) {
            throw new RuntimeException('کاربر مسئول ثبت سابقهٔ اصلاح معتبر نیست.');
        }

        return DB::transaction(function () use ($companyId, $reportId, $actorId, $method) {
            $report = DB::table('production_shift_reports')
                ->where('company_id', $companyId)
                ->where('id', $reportId)
                ->lockForUpdate()
                ->first();

            $plan = $this->buildPlan($companyId, $reportId, $report);
            if (!$plan['eligible']) {
                throw new RuntimeException($plan['reason']);
            }

            $inventory = app(InventoryService::class);
            foreach ($plan['materials'] as $material) {
                $metadata = [
                    'shift_report_id' => $reportId,
                    'approved_by_user_id' => $actorId,
                    'historical_reconciliation' => true,
                    'production_section_id' => (int) $report->production_section_id,
                    'produced_goods_id' => (int) $report->goods_id,
                    'work_date' => (string) $report->work_date,
                    'effective_occurred_at' => (string) $report->work_date . ' 23:59:59',
                    'fiscal_year_id' => (int) $plan['fiscal_year_id'],
                ];
                if ($report->order_id !== null) {
                    $metadata['order_id'] = (int) $report->order_id;
                }

                $stageId = DB::table('production_shift_report_stages')
                    ->where('company_id', $companyId)
                    ->where('production_shift_report_id', $reportId)
                    ->orderBy('id')
                    ->value('production_stage_id');
                if ($stageId) {
                    $metadata['production_stage_id'] = (int) $stageId;
                }

                $inventory->consumeFromProductionSection(
                    $companyId,
                    (int) $report->production_section_id,
                    (int) $material['goods_id'],
                    (float) $material['quantity'],
                    'production_shift_reports',
                    $reportId,
                    $metadata
                );
            }

            DB::table('audit_trails')->insert([
                'company_id' => $companyId,
                'user_id' => $actorId,
                'module' => 'production',
                'action' => 'production_shift_report.historical_consumption_reconciled',
                'auditable_type' => 'production_shift_reports',
                'auditable_id' => $reportId,
                'before' => json_encode(['status' => $report->status, 'work_date' => $report->work_date]),
                'after' => json_encode([
                    'consumed_goods' => array_column($plan['materials'], 'goods_id'),
                    'quantities' => array_column($plan['materials'], 'quantity'),
                ]),
                'method' => $method,
                'status_code' => 200,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $plan;
        });
    }

    private function buildPlan(int $companyId, int $reportId, ?object $report = null): array
    {
        $required = [
            'production_shift_reports', 'production_shift_report_materials',
            'production_shift_report_stages', 'production_sections', 'locations',
            'inventory_movements', 'inventory_cost_layers', 'fiscal_years',
            'users', 'audit_trails',
        ];
        foreach ($required as $table) {
            if (!Schema::hasTable($table)) {
                return ['eligible' => false, 'reason' => "جدول ضروری {$table} در دسترس نیست.", 'materials' => []];
            }
        }

        $report ??= DB::table('production_shift_reports')
            ->where('company_id', $companyId)
            ->where('id', $reportId)
            ->first();

        if (!$report) {
            return ['eligible' => false, 'reason' => 'گزارش تولید برای این شرکت پیدا نشد.', 'materials' => []];
        }
        if (!in_array($report->status, ['approved', 'received'], true)) {
            return ['eligible' => false, 'reason' => 'فقط گزارش تأییدشده یا تحویل‌شده قابل تطبیق تاریخی است.', 'materials' => []];
        }
        if (empty($report->work_date) || (string) $report->work_date > now()->toDateString()) {
            return ['eligible' => false, 'reason' => 'تاریخ گزارش نامعتبر یا در آینده است.', 'materials' => []];
        }

        $section = DB::table('production_sections as s')
            ->join('locations as l', 'l.id', '=', 's.location_id')
            ->where('s.id', $report->production_section_id)
            ->where('l.company_id', $companyId)
            ->where('l.is_active', true)
            ->select('l.id as location_id')
            ->first();
        if (!$section) {
            return ['eligible' => false, 'reason' => 'محل موجودی فعال و متعلق به قسمت تولید پیدا نشد.', 'materials' => []];
        }

        $fiscalYearId = DB::table('fiscal_years')
            ->where('company_id', $companyId)
            ->whereDate('starts_at', '<=', $report->work_date)
            ->whereDate('ends_at', '>=', $report->work_date)
            ->where('is_closed', false)
            ->orderByDesc('id')
            ->value('id');
        if (!$fiscalYearId) {
            return ['eligible' => false, 'reason' => 'برای تاریخ گزارش سال مالی باز وجود ندارد؛ دورهٔ بسته نیازمند فرآیند حسابداری جداگانه است.', 'materials' => []];
        }

        $existing = DB::table('inventory_movements')
            ->where('company_id', $companyId)
            ->where('reference_type', 'production_shift_reports')
            ->where('reference_id', $reportId)
            ->where('movement_type', 'production_consumption')
            ->where('quantity', '<', 0)
            ->exists();
        if ($existing) {
            return ['eligible' => false, 'reason' => 'برای این گزارش گردش مصرف ناقص یا کامل وجود دارد؛ از ایجاد گردش تکراری خودداری شد.', 'materials' => []];
        }

        $rows = DB::table('production_shift_report_materials')
            ->where('company_id', $companyId)
            ->where('production_shift_report_id', $reportId)
            ->select('goods_id', DB::raw('SUM(quantity) as quantity'))
            ->groupBy('goods_id')
            ->orderBy('goods_id')
            ->get();
        if ($rows->isEmpty()) {
            return ['eligible' => false, 'reason' => 'برای این گزارش ریزمواد ثبت نشده است.', 'materials' => []];
        }

        $effectiveAt = (string) $report->work_date . ' 23:59:59';

        // If any consumption issue already exists for this material in the
        // production location by the report date, an empty report allocation
        // may be a legacy-link mismatch rather than missing consumption.
        // Refuse automatic backfill instead of risking a duplicate stock issue.
        $materialsForSafety = DB::table('production_shift_report_materials')
            ->where('company_id', $companyId)
            ->where('production_shift_report_id', $reportId)
            ->pluck('goods_id')
            ->unique()
            ->all();
        $ambiguousExistingConsumption = DB::table('inventory_movements')
            ->where('company_id', $companyId)
            ->where('location_id', $section->location_id)
            ->whereIn('goods_id', $materialsForSafety)
            ->where('movement_type', 'production_consumption')
            ->where('quantity', '<', 0)
            ->where('occurred_at', '<=', $effectiveAt)
            ->exists();
        if ($ambiguousExistingConsumption) {
            return ['eligible' => false, 'reason' => 'برای یکی از مواد در همین قسمت گردش مصرف قبلی وجود دارد، اما به این گزارش تخصیص داده نشده است؛ ابتدا ارتباط گردش‌های قدیمی باید بررسی شود.', 'materials' => []];
        }

        $materials = [];
        foreach ($rows as $row) {
            $quantity = (float) $row->quantity;
            if ($quantity <= 0) {
                return ['eligible' => false, 'reason' => 'مقدار یکی از مواد معتبر نیست.', 'materials' => []];
            }

            $historicalStock = (float) DB::table('inventory_movements')
                ->where('company_id', $companyId)
                ->where('goods_id', $row->goods_id)
                ->where('location_id', $section->location_id)
                ->where('occurred_at', '<=', $effectiveAt)
                ->sum('quantity');
            $currentStock = (float) DB::table('inventory_movements')
                ->where('company_id', $companyId)
                ->where('goods_id', $row->goods_id)
                ->where('location_id', $section->location_id)
                ->sum('quantity');

            $laterIssue = DB::table('inventory_movements')
                ->where('company_id', $companyId)
                ->where('goods_id', $row->goods_id)
                ->where('location_id', $section->location_id)
                ->where('occurred_at', '>', $effectiveAt)
                ->where('quantity', '<', 0)
                ->exists();
            if ($laterIssue) {
                return ['eligible' => false, 'reason' => "برای مادهٔ {$row->goods_id} خروج انبارِ بعد از تاریخ گزارش وجود دارد؛ ثبت با تاریخ گذشته می‌تواند FIFO را مخدوش کند.", 'materials' => []];
            }
            if ($historicalStock + 0.0001 < $quantity) {
                return ['eligible' => false, 'reason' => "موجودی دفتری مادهٔ {$row->goods_id} در تاریخ گزارش کافی نبوده است.", 'materials' => []];
            }
            if ($currentStock + 0.0001 < $quantity) {
                return ['eligible' => false, 'reason' => "موجودی فعلی قسمت برای مادهٔ {$row->goods_id} کافی نیست؛ ثبت باعث منفی شدن موجودی می‌شود.", 'materials' => []];
            }

            $eligibleLayers = (float) DB::table('inventory_cost_layers')
                ->where('company_id', $companyId)
                ->where('goods_id', $row->goods_id)
                ->where('location_id', $section->location_id)
                ->where('received_at', '<=', $effectiveAt)
                ->where('fifo_quantity_remaining', '>', 0)
                ->sum('fifo_quantity_remaining');
            if ($eligibleLayers + 0.0001 < $quantity) {
                return ['eligible' => false, 'reason' => "لایه‌های بهای FIFO مادهٔ {$row->goods_id} در تاریخ گزارش برای کل مقدار کافی نیستند.", 'materials' => []];
            }

            $materials[] = [
                'goods_id' => (int) $row->goods_id,
                'quantity' => $quantity,
                'historical_stock' => $historicalStock,
                'current_stock' => $currentStock,
                'eligible_layers' => $eligibleLayers,
            ];
        }

        return [
            'eligible' => true,
            'reason' => null,
            'materials' => $materials,
            'fiscal_year_id' => (int) $fiscalYearId,
        ];
    }
}
