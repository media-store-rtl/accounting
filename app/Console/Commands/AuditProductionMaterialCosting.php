<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AuditProductionMaterialCosting extends Command
{
    protected $signature = 'costing:audit-production-materials
        {--company= : Required company ID}
        {--from= : First report work date (YYYY-MM-DD)}
        {--to= : Last report work date inclusive (YYYY-MM-DD)}
        {--report= : Optional production shift report ID}';

    protected $description = 'Read-only audit of production shift report material consumption and historical valuation';

    public function handle(): int
    {
        $companyId = filter_var($this->option('company'), FILTER_VALIDATE_INT);
        if (!$companyId || $companyId < 1) {
            $this->error('Pass a valid --company=ID. This command is read-only.');
            return self::FAILURE;
        }

        $from = $this->option('from');
        $to = $this->option('to');
        foreach (['from' => $from, 'to' => $to] as $name => $date) {
            if ($date !== null && (!is_string($date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !strtotime($date))) {
                $this->error("Invalid --{$name} date. Use YYYY-MM-DD.");
                return self::FAILURE;
            }
        }
        if ($from && $to && $from > $to) {
            $this->error('--from must be on or before --to.');
            return self::FAILURE;
        }
        if (!Schema::hasTable('production_shift_reports')
            || !Schema::hasTable('production_shift_report_materials')
            || !Schema::hasTable('inventory_movements')
            || !Schema::hasTable('inventory_consumption_costs')) {
            $this->error('Required production/inventory tables are missing; no changes were made.');
            return self::FAILURE;
        }

        $reports = DB::table('production_shift_reports as r')
            ->where('r.company_id', $companyId)
            ->when($from, fn ($q) => $q->whereDate('r.work_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('r.work_date', '<=', $to))
            ->when($this->option('report'), fn ($q, $id) => $q->where('r.id', (int) $id))
            ->orderBy('r.work_date')
            ->orderBy('r.id')
            ->get(['r.id', 'r.company_id', 'r.production_section_id', 'r.goods_id', 'r.work_date', 'r.status']);

        if ($reports->isEmpty()) {
            $this->info('No production shift reports matched these filters.');
            return self::SUCCESS;
        }

        $rows = [];
        $totals = ['reports' => 0, 'material_lines' => 0, 'reported_qty' => 0.0, 'movement_qty' => 0.0, 'valued_qty' => 0.0, 'cost' => 0.0];
        foreach ($reports as $report) {
            $totals['reports']++;
            $materials = DB::table('production_shift_report_materials')
                ->where('company_id', $companyId)
                ->where('production_shift_report_id', $report->id)
                ->select('goods_id', DB::raw('SUM(quantity) as quantity'))
                ->groupBy('goods_id')
                ->orderBy('goods_id')
                ->get();

            if ($materials->isEmpty()) {
                $rows[] = [$report->id, $report->work_date, $report->status, '-', '0', '0', '0', '0', 'no_material_lines'];
                continue;
            }

            foreach ($materials as $material) {
                $totals['material_lines']++;
                $reportedQty = (float) $material->quantity;
                $movements = DB::table('inventory_movements')
                    ->where('company_id', $companyId)
                    ->where('reference_type', 'production_shift_reports')
                    ->where('reference_id', $report->id)
                    ->where('movement_type', 'production_consumption')
                    ->where('goods_id', $material->goods_id)
                    ->where('quantity', '<', 0)
                    ->get(['id', 'quantity', 'occurred_at']);

                $movementQty = (float) $movements->sum(fn ($movement) => abs((float) $movement->quantity));
                $movementIds = $movements->pluck('id')->all();
                $valuationRows = $movementIds
                    ? DB::table('inventory_consumption_costs')
                        ->where('company_id', $companyId)
                        ->whereIn('inventory_movement_id', $movementIds)
                        ->get(['inventory_movement_id', 'quantity', 'unit_cost', 'total_cost', 'consumed_at'])
                    : collect();
                $valuedQty = (float) $valuationRows->sum(fn ($valuation) => (float) $valuation->quantity);
                $cost = (float) $valuationRows->sum(fn ($valuation) => (float) $valuation->total_cost);

                $classification = match (true) {
                    strtolower((string) $report->status) === 'rejected' => 'rejected_report',
                    $movementQty <= 0.0000001 => 'missing_movement',
                    abs($movementQty - $reportedQty) > 0.0001 => 'quantity_mismatch',
                    $valuationRows->isEmpty() => 'missing_valuation',
                    abs($valuedQty - $movementQty) > 0.0001 => 'partial_valuation',
                    default => 'matched',
                };

                $totals['reported_qty'] += $reportedQty;
                $totals['movement_qty'] += $movementQty;
                $totals['valued_qty'] += $valuedQty;
                $totals['cost'] += $cost;

                $rows[] = [
                    $report->id,
                    $report->work_date,
                    $report->status,
                    $material->goods_id,
                    $this->format($reportedQty),
                    $this->format($movementQty),
                    $this->format($valuedQty),
                    $this->format($cost),
                    $classification,
                ];
            }
        }

        $this->table(
            ['Report', 'Work date', 'Status', 'Material goods ID', 'Reported qty', 'Movement qty', 'Valued qty', 'Cost total', 'Finding'],
            $rows
        );

        $this->newLine();
        $this->line('Summary (read-only; cost totals include only valuation rows linked to matching production-consumption movements):');
        $this->table(['Metric', 'Value'], [
            ['Reports checked', (string) $totals['reports']],
            ['Report/material groups', (string) $totals['material_lines']],
            ['Reported material quantity', $this->format($totals['reported_qty'])],
            ['Movement quantity', $this->format($totals['movement_qty'])],
            ['Valued quantity', $this->format($totals['valued_qty'])],
            ['Linked valuation total cost', $this->format($totals['cost'])],
        ]);
        $this->warn('This audit does not create or modify movements, valuation rows, cost layers, or reports. A missing movement is not proof that a historical consumption should be posted.');
        return self::SUCCESS;
    }

    private function format(float $value): string
    {
        return rtrim(rtrim(number_format($value, 4, '.', ''), '0'), '.') ?: '0';
    }
}
