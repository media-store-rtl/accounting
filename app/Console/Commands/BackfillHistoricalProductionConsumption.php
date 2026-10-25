<?php

namespace App\Console\Commands;

use App\Services\InventoryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

final class BackfillHistoricalProductionConsumption extends Command
{
    protected $signature = 'costing:backfill-production-consumption
        {--company= : Required company ID}
        {--report= : One approved/received shift report ID}
        {--actor= : User ID responsible for this explicitly approved reconciliation}
        {--apply : Write missing consumption only after all safety checks pass}';

    protected $description = 'Explicitly reconcile one historical shift report; dry-run by default and refuses ambiguous stock history';

    public function handle(): int
    {
        $companyId = filter_var($this->option('company'), FILTER_VALIDATE_INT);
        $reportId = filter_var($this->option('report'), FILTER_VALIDATE_INT);
        $actorId = filter_var($this->option('actor'), FILTER_VALIDATE_INT);

        if (!$companyId || $companyId < 1 || !$reportId || $reportId < 1) {
            $this->error('Pass valid --company=ID and --report=ID. No changes were made.');
            return self::FAILURE;
        }

        if (!$this->option('apply')) {
            $this->warn('DRY RUN ONLY: no records were changed. Re-run with --apply and an explicit --actor=USER_ID only after reviewing the report.');
        } elseif (!$actorId || $actorId < 1) {
            $this->error('Applying requires --actor=USER_ID for audit attribution. No changes were made.');
            return self::FAILURE;
        }

        $required = [
            'production_shift_reports', 'production_shift_report_materials',
            'production_shift_report_stages', 'production_sections', 'locations',
            'inventory_movements', 'inventory_cost_layers', 'fiscal_years',
            'users', 'audit_trails',
        ];
        foreach ($required as $table) {
            if (!Schema::hasTable($table)) {
                $this->error("Required table {$table} is missing; no changes were made.");
                return self::FAILURE;
            }
        }

        if ($this->option('apply') && !DB::table('users')->where('id', $actorId)->exists()) {
            $this->error('The audit actor user does not exist. No changes were made.');
            return self::FAILURE;
        }

        try {
            if ($this->option('apply')) {
                $result = DB::transaction(function () use ($companyId, $reportId, $actorId) {
                    $report = DB::table('production_shift_reports')
                        ->where('company_id', $companyId)->where('id', $reportId)
                        ->lockForUpdate()->first();
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
                        if ($report->order_id !== null) $metadata['order_id'] = (int) $report->order_id;
                        $stageId = DB::table('production_shift_report_stages')
                            ->where('company_id', $companyId)->where('production_shift_report_id', $reportId)
                            ->orderBy('id')->value('production_stage_id');
                        if ($stageId) $metadata['production_stage_id'] = (int) $stageId;

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
                        'after' => json_encode(['consumed_goods' => array_column($plan['materials'], 'goods_id'), 'quantities' => array_column($plan['materials'], 'quantity')]),
                        'method' => 'CLI',
                        'status_code' => 200,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    return $plan;
                });
            } else {
                $report = DB::table('production_shift_reports')
                    ->where('company_id', $companyId)->where('id', $reportId)->first();
                $result = $this->buildPlan($companyId, $reportId, $report);
            }
        } catch (RuntimeException $exception) {
            $this->error('NOT ELIGIBLE: ' . $exception->getMessage());
            return self::FAILURE;
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->error('NOT ELIGIBLE: ' . collect($exception->errors())->flatten()->implode(' '));
            return self::FAILURE;
        }

        if (!$result['eligible']) {
            $this->error('NOT ELIGIBLE: ' . $result['reason']);
            return self::FAILURE;
        }

        $this->table(['Material ID', 'Missing quantity', 'Historical stock', 'Current stock', 'Eligible cost layers'], array_map(
            fn ($material) => [
                $material['goods_id'],
                $this->format($material['quantity']),
                $this->format($material['historical_stock']),
                $this->format($material['current_stock']),
                $this->format($material['eligible_layers']),
            ],
            $result['materials']
        ));

        if ($this->option('apply')) {
            $this->info('Historical consumption was recorded with report linkage and an audit-trail entry.');
        } else {
            $this->info('Eligible in dry-run. No records were changed. Use --apply --actor=USER_ID only after independent review.');
        }

        return self::SUCCESS;
    }

    private function buildPlan(int $companyId, int $reportId, ?object $report): array
    {
        if (!$report) return ['eligible' => false, 'reason' => 'Report not found for this company.', 'materials' => []];
        if (!in_array($report->status, ['approved', 'received'], true)) {
            return ['eligible' => false, 'reason' => 'Only approved/received reports may be reconciled; rejected/pending reports are never auto-consumed.', 'materials' => []];
        }
        if (empty($report->work_date) || (string) $report->work_date > now()->toDateString()) {
            return ['eligible' => false, 'reason' => 'Report work date is missing or in the future.', 'materials' => []];
        }

        $section = DB::table('production_sections as s')
            ->join('locations as l', 'l.id', '=', 's.location_id')
            ->where('s.id', $report->production_section_id)
            ->where('l.company_id', $companyId)
            ->where('l.is_active', true)
            ->select('l.id as location_id')
            ->first();
        if (!$section) return ['eligible' => false, 'reason' => 'No active inventory location belongs to this production section/company.', 'materials' => []];

        $fiscalYearId = DB::table('fiscal_years')
            ->where('company_id', $companyId)
            ->whereDate('starts_at', '<=', $report->work_date)
            ->whereDate('ends_at', '>=', $report->work_date)
            ->orderByDesc('id')->value('id');
        if (!$fiscalYearId) return ['eligible' => false, 'reason' => 'No fiscal year covers the report work date.', 'materials' => []];

        $existing = DB::table('inventory_movements')
            ->where('company_id', $companyId)->where('reference_type', 'production_shift_reports')
            ->where('reference_id', $reportId)->where('movement_type', 'production_consumption')
            ->where('quantity', '<', 0)->exists();
        if ($existing) return ['eligible' => false, 'reason' => 'A consumption movement already exists for this report; partial/duplicate history requires manual review.', 'materials' => []];

        $rows = DB::table('production_shift_report_materials')
            ->where('company_id', $companyId)->where('production_shift_report_id', $reportId)
            ->select('goods_id', DB::raw('SUM(quantity) as quantity'))
            ->groupBy('goods_id')->orderBy('goods_id')->get();
        if ($rows->isEmpty()) return ['eligible' => false, 'reason' => 'No material lines exist on the report.', 'materials' => []];

        $effectiveAt = (string) $report->work_date . ' 23:59:59';
        $materials = [];
        foreach ($rows as $row) {
            $quantity = (float) $row->quantity;
            if ($quantity <= 0) return ['eligible' => false, 'reason' => 'A material line has a non-positive quantity.', 'materials' => []];

            $historicalStock = (float) DB::table('inventory_movements')
                ->where('company_id', $companyId)->where('goods_id', $row->goods_id)
                ->where('location_id', $section->location_id)->where('occurred_at', '<=', $effectiveAt)->sum('quantity');
            $currentStock = (float) DB::table('inventory_movements')
                ->where('company_id', $companyId)->where('goods_id', $row->goods_id)
                ->where('location_id', $section->location_id)->sum('quantity');

            $laterIssue = DB::table('inventory_movements')
                ->where('company_id', $companyId)->where('goods_id', $row->goods_id)
                ->where('location_id', $section->location_id)->where('occurred_at', '>', $effectiveAt)
                ->where('quantity', '<', 0)->exists();
            if ($laterIssue) {
                return ['eligible' => false, 'reason' => "Material {$row->goods_id} has later stock issues; backdating could distort the historical FIFO trail.", 'materials' => []];
            }
            if ($historicalStock + 0.0001 < $quantity) {
                return ['eligible' => false, 'reason' => "Material {$row->goods_id} had insufficient ledger stock on the report date; no historical consumption can be inferred.", 'materials' => []];
            }
            if ($currentStock + 0.0001 < $quantity) {
                return ['eligible' => false, 'reason' => "Material {$row->goods_id} has insufficient current section stock; writing a backdated issue would make inventory negative.", 'materials' => []];
            }

            $eligibleLayers = (float) DB::table('inventory_cost_layers')
                ->where('company_id', $companyId)->where('goods_id', $row->goods_id)
                ->where('location_id', $section->location_id)->where('received_at', '<=', $effectiveAt)
                ->where('fifo_quantity_remaining', '>', 0)->sum('fifo_quantity_remaining');
            if ($eligibleLayers + 0.0001 < $quantity) {
                return ['eligible' => false, 'reason' => "Material {$row->goods_id} lacks enough remaining FIFO cost layers received by the report date.", 'materials' => []];
            }

            $materials[] = [
                'goods_id' => (int) $row->goods_id,
                'quantity' => $quantity,
                'historical_stock' => $historicalStock,
                'current_stock' => $currentStock,
                'eligible_layers' => $eligibleLayers,
            ];
        }

        return ['eligible' => true, 'reason' => null, 'materials' => $materials, 'fiscal_year_id' => (int) $fiscalYearId];
    }

    private function format(float $value): string
    {
        return rtrim(rtrim(number_format($value, 4, '.', ''), '0'), '.') ?: '0';
    }
}
