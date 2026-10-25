<?php

namespace App\\Console\\Commands;

use Illuminate\\Console\\Command;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Schema;

/**
 * Read-only diagnostic for failed production-report material valuation.
 *
 * It identifies explicit report links and legacy pool candidates without
 * creating, updating, or deleting inventory/costing records.
 */
class DiagnoseProductionConsumptionCandidates extends Command
{
    protected $signature = 'costing:diagnose-production-consumption
        {--company= : Required company ID}
        {--report= : Optional production shift report ID}
        {--from= : First report work date (YYYY-MM-DD)}
        {--to= : Last report work date inclusive (YYYY-MM-DD)}';

    protected $description = 'Read-only diagnostic of direct and legacy inventory-consumption candidates for production reports';

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
            if ($date !== null && (!is_string($date) || !preg_match('/^\\d{4}-\\d{2}-\\d{2}$/', $date) || !strtotime($date))) {
                $this->error("Invalid --{$name} date. Use YYYY-MM-DD.");
                return self::FAILURE;
            }
        }
        if ($from && $to && $from > $to) {
            $this->error('--from must be on or before --to.');
            return self::FAILURE;
        }

        foreach (['production_shift_reports', 'production_shift_report_materials', 'inventory_movements', 'locations', 'production_sections'] as $table) {
            if (!Schema::hasTable($table)) {
                $this->error("Required table {$table} is missing; no changes were made.");
                return self::FAILURE;
            }
        }

        $reports = DB::table('production_shift_reports as r')
            ->where('r.company_id', $companyId)
            ->when($this->option('report'), fn ($q, $id) => $q->where('r.id', (int) $id))
            ->when($from, fn ($q) => $q->whereDate('r.work_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('r.work_date', '<=', $to))
            ->orderBy('r.work_date')->orderBy('r.id')
            ->get(['r.id', 'r.production_section_id', 'r.order_id', 'r.work_date', 'r.status']);

        if ($reports->isEmpty()) {
            $this->info('No production shift reports matched these filters.');
            return self::SUCCESS;
        }

        $consumptions = DB::table('inventory_movements as m')
            ->leftJoin('locations as l', function ($join) use ($companyId) {
                $join->on('l.id', '=', 'm.location_id')->where('l.company_id', '=', $companyId);
            })
            ->leftJoin('production_sections as ps', 'ps.location_id', '=', 'l.id')
            ->where('m.company_id', $companyId)
            ->where('m.movement_type', 'production_consumption')
            ->where('m.quantity', '<', 0)
            ->orderBy('m.occurred_at')->orderBy('m.id')
            ->get([
                'm.id', 'm.goods_id', 'm.quantity', 'm.location_id',
                'm.reference_type', 'm.reference_id', 'm.occurred_at',
                'm.metadata', 'ps.id as location_section_id',
            ]);

        $metadataById = [];
        $supplyIds = [];
        $productionIds = [];
        foreach ($consumptions as $movement) {
            $metadata = json_decode((string) $movement->metadata, true);
            $metadata = is_array($metadata) ? $metadata : [];
            $metadataById[(int) $movement->id] = $metadata;
            if (!empty($metadata['supply_request_id'])) $supplyIds[] = (int) $metadata['supply_request_id'];
            if (!empty($metadata['production_id'])) $productionIds[] = (int) $metadata['production_id'];
        }

        $supplies = Schema::hasTable('supply_requests') && $supplyIds
            ? DB::table('supply_requests')->where('company_id', $companyId)->whereIn('id', array_unique($supplyIds))
                ->get(['id', 'production_section_id', 'order_id'])->keyBy('id')
            : collect();
        $productionOrders = Schema::hasTable('production_order') && $productionIds
            ? DB::table('production_order')->whereIn('production_id', array_unique($productionIds))
                ->orderBy('order_id')->get(['production_id', 'order_id'])->groupBy('production_id')
                ->map(fn ($rows) => $rows->first())
            : collect();

        $movementFacts = [];
        foreach ($consumptions as $movement) {
            $metadata = $metadataById[(int) $movement->id] ?? [];
            $supply = $supplies->get((int) ($metadata['supply_request_id'] ?? 0));
            $productionOrder = $productionOrders->get((int) ($metadata['production_id'] ?? 0));
            $directReportId = (int) ($metadata['shift_report_id'] ?? 0);
            if ($directReportId <= 0 && (string) $movement->reference_type === 'production_shift_reports') {
                $directReportId = (int) $movement->reference_id;
            }
            $movementFacts[(int) $movement->id] = [
                'id' => (int) $movement->id,
                'goods_id' => (int) $movement->goods_id,
                'quantity' => abs((float) $movement->quantity),
                'date' => substr((string) $movement->occurred_at, 0, 10),
                'section_id' => (int) ($supply->production_section_id ?? $metadata['production_section_id'] ?? $movement->location_section_id ?? 0),
                'order_id' => (int) ($supply->order_id ?? $productionOrder->order_id ?? $metadata['order_id'] ?? 0),
                'direct_report_id' => $directReportId,
                'reference' => (string) $movement->reference_type . ':' . (string) $movement->reference_id,
            ];
        }

        $rows = [];
        foreach ($reports as $report) {
            $materials = DB::table('production_shift_report_materials')
                ->where('company_id', $companyId)
                ->where('production_shift_report_id', $report->id)
                ->select('goods_id', DB::raw('SUM(quantity) as quantity'))
                ->groupBy('goods_id')->orderBy('goods_id')->get();

            foreach ($materials as $material) {
                $goodsId = (int) $material->goods_id;
                $direct = [];
                $legacy = [];
                foreach ($movementFacts as $fact) {
                    if ($fact['goods_id'] !== $goodsId || $fact['date'] === '' || $fact['date'] > (string) $report->work_date) continue;
                    if ($fact['direct_report_id'] === (int) $report->id) {
                        $direct[] = $fact;
                        continue;
                    }
                    if ($fact['direct_report_id'] > 0) continue;
                    if ($fact['section_id'] === (int) $report->production_section_id
                        && $fact['order_id'] === (int) ($report->order_id ?? 0)) {
                        $legacy[] = $fact;
                    }
                }

                $rows[] = [
                    $report->id,
                    $report->work_date,
                    $report->status,
                    $report->production_section_id,
                    $report->order_id ?? 0,
                    $goodsId,
                    $this->format((float) $material->quantity),
                    count($direct),
                    $this->describe($direct),
                    count($legacy),
                    $this->describe($legacy),
                    $direct ? 'direct_link_found' : ($legacy ? 'legacy_pool_candidates_need_allocation_review' : 'no_candidate_movement_found'),
                ];
            }
        }

        $this->table(
            ['Report', 'Work date', 'Status', 'Section', 'Order', 'Material', 'Reported qty', 'Direct #', 'Direct movement IDs (qty/date/ref)', 'Legacy #', 'Legacy IDs (qty/date/ref)', 'Diagnostic'],
            $rows
        );
        $this->newLine();
        $this->warn('READ-ONLY DIAGNOSTIC ONLY: candidate matches are not proof of actual consumption. No inventory movements, valuations, cost layers, handovers, or reports were changed.');
        return self::SUCCESS;
    }

    private function describe(array $facts): string
    {
        if (!$facts) return '-';
        return implode('; ', array_map(fn ($fact) =>
            $fact['id'] . ' (' . $this->format($fact['quantity']) . ' @ ' . ($fact['date'] ?: 'no-date') . ', ' . $fact['reference'] . ')',
            $facts
        ));
    }

    private function format(float $value): string
    {
        return rtrim(rtrim(number_format($value, 4, '.', ''), '0'), '.') ?: '0';
    }
}
