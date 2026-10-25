<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AuditHistoricalProductionMaterialReconciliation extends Command
{
    protected $signature = 'costing:reconcile-production-materials
        {--company= : Required company ID}
        {--from= : First report work date (YYYY-MM-DD)}
        {--to= : Last report work date inclusive (YYYY-MM-DD)}
        {--report= : Optional production shift report ID}';

    protected $description = 'Read-only historical reconciliation of production report materials against inventory movements, handovers, valuation and cost layers';

    public function handle(): int
    {
        $companyId = filter_var($this->option('company'), FILTER_VALIDATE_INT);
        if (!$companyId || $companyId < 1) {
            $this->error('Pass a valid --company=ID. This command is strictly read-only.');
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

        foreach (['production_shift_reports', 'production_shift_report_materials', 'inventory_movements', 'inventory_consumption_costs', 'inventory_cost_layers'] as $table) {
            if (!Schema::hasTable($table)) {
                $this->error("Required table {$table} is missing; no changes were made.");
                return self::FAILURE;
            }
        }

        $reports = DB::table('production_shift_reports as r')
            ->where('r.company_id', $companyId)
            ->when($from, fn ($q) => $q->whereDate('r.work_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('r.work_date', '<=', $to))
            ->when($this->option('report'), fn ($q, $id) => $q->where('r.id', (int) $id))
            ->orderBy('r.work_date')->orderBy('r.id')
            ->get(['r.id', 'r.production_section_id', 'r.goods_id', 'r.work_date', 'r.status']);

        if ($reports->isEmpty()) {
            $this->info('No production shift reports matched these filters.');
            return self::SUCCESS;
        }

        $rows = [];
        $totals = ['reported' => 0.0, 'movement' => 0.0, 'valued' => 0.0, 'cost' => 0.0, 'missing' => 0.0, 'unresolved' => 0];

        foreach ($reports as $report) {
            $materials = DB::table('production_shift_report_materials')
                ->where('company_id', $companyId)
                ->where('production_shift_report_id', $report->id)
                ->select('goods_id', DB::raw('SUM(quantity) as quantity'))
                ->groupBy('goods_id')->orderBy('goods_id')->get();

            foreach ($materials as $material) {
                $reported = (float) $material->quantity;
                $movements = DB::table('inventory_movements')
                    ->where('company_id', $companyId)
                    ->where('reference_type', 'production_shift_reports')
                    ->where('reference_id', $report->id)
                    ->where('movement_type', 'production_consumption')
                    ->where('goods_id', $material->goods_id)
                    ->where('quantity', '<', 0)
                    ->get(['id', 'quantity', 'occurred_at', 'location_id', 'metadata']);
                $movementQty = (float) $movements->sum(fn ($m) => abs((float) $m->quantity));
                $insufficientStockAtConsumption = false;
                foreach ($movements as $movement) {
                    if ((float) $movement->quantity >= 0 || empty($movement->occurred_at) || empty($movement->location_id)) {
                        continue;
                    }
                    $stockBefore = (float) DB::table('inventory_movements')
                        ->where('company_id', $companyId)
                        ->where('goods_id', $material->goods_id)
                        ->where('location_id', $movement->location_id)
                        ->where(function ($query) use ($movement) {
                            $query->where('occurred_at', '<', $movement->occurred_at)
                                ->orWhere(function ($sameTime) use ($movement) {
                                    $sameTime->where('occurred_at', '=', $movement->occurred_at)
                                        ->where('id', '<', $movement->id);
                                });
                        })
                        ->sum('quantity');
                    if ($stockBefore + 0.0001 < abs((float) $movement->quantity)) {
                        $insufficientStockAtConsumption = true;
                        break;
                    }
                }
                $movementAfterReportDate = $movements->contains(fn ($m) =>
                    !empty($m->occurred_at) && substr((string) $m->occurred_at, 0, 10) > (string) $report->work_date
                );
                $movementSignatures = $movements->map(fn ($m) => implode('|', [
                    (string) $m->quantity,
                    (string) ($m->occurred_at ?? ''),
                    (string) ($m->location_id ?? ''),
                ]));
                $hasDuplicateMovementSignature = $movementSignatures->count() !== $movementSignatures->unique()->count();

                $movementIds = $movements->pluck('id')->all();
                $valuations = $movementIds
                    ? DB::table('inventory_consumption_costs')
                        ->where('company_id', $companyId)->whereIn('inventory_movement_id', $movementIds)
                        ->get(['id', 'inventory_movement_id', 'quantity', 'total_cost', 'consumed_at', 'valuation_method'])
                    : collect();
                $valuedQty = (float) $valuations->sum(fn ($v) => (float) $v->quantity);
                $cost = (float) $valuations->sum(fn ($v) => (float) $v->total_cost);
                $fifoAllocationMismatch = false;
                $fifoInvalidLayer = false;
                if (Schema::hasTable('inventory_consumption_cost_items')) {
                    foreach ($valuations->filter(fn ($v) => strtolower((string) ($v->valuation_method ?? '')) === 'fifo') as $valuation) {
                        $invalidLayer = false;
                        $allocation = DB::table('inventory_consumption_cost_items')
                            ->where('inventory_consumption_cost_id', $valuation->id)
                            ->selectRaw('COALESCE(SUM(quantity), 0) as quantity, COALESCE(SUM(total_cost), 0) as total_cost')
                            ->first();
                        if (abs((float) $allocation->quantity - (float) $valuation->quantity) > 0.0001
                            || abs((float) $allocation->total_cost - (float) $valuation->total_cost) > 0.01) {
                            $fifoAllocationMismatch = true;
                        }
                        $invalidLayer = $invalidLayer || DB::table('inventory_consumption_cost_items as ci')
                            ->leftJoin('inventory_cost_layers as cl', 'cl.id', '=', 'ci.inventory_cost_layer_id')
                            ->leftJoin('inventory_consumption_costs as cc', 'cc.id', '=', 'ci.inventory_consumption_cost_id')
                            ->leftJoin('inventory_movements as im', 'im.id', '=', 'cc.inventory_movement_id')
                            ->where('ci.inventory_consumption_cost_id', $valuation->id)
                            ->where(function ($query) use ($companyId, $material, $valuation) {
                                $query->whereNull('cl.id')
                                    ->orWhere('cl.company_id', '!=', $companyId)
                                    ->orWhere('cl.goods_id', '!=', $material->goods_id)
                                    ->orWhere('cl.received_at', '>', $valuation->consumed_at)
                                    ->orWhereColumn('ci.unit_cost', '!=', 'cl.unit_cost')
                                    ->orWhereRaw('ABS(ci.total_cost - (ci.quantity * ci.unit_cost)) > 0.01');
                            })
                            ->exists();
                        $invalidLayer = $invalidLayer || DB::table('inventory_consumption_cost_items as ci')
                            ->join('inventory_cost_layers as cl', 'cl.id', '=', 'ci.inventory_cost_layer_id')
                            ->join('inventory_consumption_costs as cc', 'cc.id', '=', 'ci.inventory_consumption_cost_id')
                            ->join('inventory_movements as im', 'im.id', '=', 'cc.inventory_movement_id')
                            ->where('ci.inventory_consumption_cost_id', $valuation->id)
                            ->whereColumn('cl.location_id', '!=', 'im.location_id')
                            ->exists();
                        if ($invalidLayer) $fifoInvalidLayer = true;
                    }
                }

                $effectiveAt = (string) $report->work_date . ' 23:59:59';
                $layers = DB::table('inventory_cost_layers')
                    ->where('company_id', $companyId)
                    ->where('goods_id', $material->goods_id)
                    ->where('received_at', '<=', $effectiveAt)
                    ->get(['id', 'location_id', 'quantity_received', 'fifo_quantity_remaining', 'weighted_average_quantity_remaining', 'unit_cost', 'total_cost', 'received_at']);
                $historicalLayerQty = (float) $layers->sum(fn ($l) => (float) $l->quantity_received);

                $handoverQty = 0.0;
                if (Schema::hasTable('material_handovers') && Schema::hasTable('material_handover_items')) {
                    $handoverQty = (float) DB::table('material_handover_items as hi')
                        ->join('material_handovers as h', 'h.id', '=', 'hi.material_handover_id')
                        ->where('h.company_id', $companyId)
                        ->where('hi.goods_id', $material->goods_id)
                        ->where('h.handed_over_at', '<=', $effectiveAt)
                        ->when(Schema::hasColumn('material_handovers', 'production_section_id'), fn ($q) => $q->where('h.production_section_id', $report->production_section_id))
                        ->sum('hi.quantity');
                }

                $status = strtolower((string) $report->status);
                $finding = match (true) {
                    $status === 'rejected' => 'rejected_report',
                    $movementQty <= 0.0000001 && $handoverQty <= 0.0000001 => 'missing_movement_and_handover',
                    $movementQty <= 0.0000001 => 'missing_consumption_movement',
                    $movementAfterReportDate => 'movement_after_report_date',
                    $hasDuplicateMovementSignature => 'duplicate_consumption_movement_signature',
                    abs($movementQty - $reported) > 0.0001 => 'quantity_mismatch',
                    $valuations->isEmpty() => $historicalLayerQty > 0 ? 'missing_valuation' : 'no_historical_layer',
                    abs($valuedQty - $movementQty) > 0.0001 => 'partial_valuation',
                    $fifoInvalidLayer => 'fifo_allocation_invalid_layer',
                    $fifoAllocationMismatch => 'fifo_allocation_mismatch',
                    $insufficientStockAtConsumption => 'insufficient_stock_at_consumption',
                    default => 'matched',
                };

                $isUnresolved = !in_array($finding, ['matched', 'rejected_report'], true);
                $totals['reported'] += $reported;
                $totals['movement'] += $movementQty;
                $totals['valued'] += $valuedQty;
                $totals['cost'] += $cost;
                if ($movementQty <= 0.0000001 && $status !== 'rejected') $totals['missing'] += $reported;
                if ($isUnresolved) $totals['unresolved']++;

                $rows[] = [
                    $report->id,
                    $report->work_date,
                    $report->status,
                    $material->goods_id,
                    $this->format($reported),
                    $this->format($movementQty),
                    $this->format($handoverQty),
                    $this->format($valuedQty),
                    $this->format($historicalLayerQty),
                    $this->format($cost),
                    $finding,
                ];
            }
        }

        $this->table(
            ['Report', 'Work date', 'Status', 'Material', 'Reported', 'Consumption', 'Handover', 'Valued', 'Historical layer qty', 'Cost', 'Finding'],
            $rows
        );
        $this->newLine();
        $this->table(['Metric', 'Value'], [
            ['Reported material quantity', $this->format($totals['reported'])],
            ['Consumption movement quantity', $this->format($totals['movement'])],
            ['Handover quantity', $this->format($this->sumColumn($rows, 6))],
            ['Valued quantity', $this->format($totals['valued'])],
            ['Linked historical valuation cost', $this->format($totals['cost'])],
            ['Missing movement quantity (non-rejected)', $this->format($totals['missing'])],
            ['Unresolved report/material groups', (string) $totals['unresolved']],
        ]);
        $this->warn('READ-ONLY: this command creates no inventory movement, valuation, cost-layer, handover, or report records. A report quantity is not treated as proof of historical stock consumption.');

        return self::SUCCESS;
    }

    private function sumColumn(array $rows, int $index): float
    {
        return array_sum(array_map(fn ($row) => (float) $row[$index], $rows));
    }

    private function format(float $value): string
    {
        return rtrim(rtrim(number_format($value, 4, '.', ''), '0'), '.') ?: '0';
    }
}
