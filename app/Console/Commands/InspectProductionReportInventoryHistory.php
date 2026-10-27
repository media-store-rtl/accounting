<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Read-only evidence dump for investigating a production report before any
 * historical stock or FIFO valuation repair is attempted.
 */
final class InspectProductionReportInventoryHistory extends Command
{
    protected $signature = 'costing:inspect-production-report
        {--company= : Required company ID}
        {--report= : Required production shift report ID}';

    protected $description = 'Read-only inventory chronology and FIFO-layer evidence for one production report';

    public function handle(): int
    {
        $companyId = filter_var($this->option('company'), FILTER_VALIDATE_INT);
        $reportId = filter_var($this->option('report'), FILTER_VALIDATE_INT);
        if (!$companyId || $companyId < 1 || !$reportId || $reportId < 1) {
            $this->error('Pass valid --company=ID and --report=ID. This command is read-only.');
            return self::FAILURE;
        }

        foreach (['production_shift_reports', 'production_shift_report_materials', 'inventory_movements', 'locations', 'inventory_cost_layers'] as $table) {
            if (!Schema::hasTable($table)) {
                $this->error("Required table {$table} is missing. No changes were made.");
                return self::FAILURE;
            }
        }

        $report = DB::table('production_shift_reports')
            ->where('company_id', $companyId)->where('id', $reportId)
            ->first(['id', 'work_date', 'status', 'production_section_id', 'order_id', 'goods_id']);
        if (!$report) {
            $this->error('Report was not found for this company. No changes were made.');
            return self::FAILURE;
        }

        $this->info(sprintf(
            'Report %d | date=%s | status=%s | section=%s | order=%s',
            $report->id,
            $report->work_date,
            $report->status,
            $report->production_section_id ?? 'null',
            $report->order_id ?? 'null'
        ));

        $materials = DB::table('production_shift_report_materials')
            ->where('company_id', $companyId)->where('production_shift_report_id', $reportId)
            ->select('goods_id', DB::raw('SUM(quantity) as quantity'))
            ->groupBy('goods_id')->orderBy('goods_id')->get();

        if ($materials->isEmpty()) {
            $this->warn('No material rows are attached to this report.');
            return self::SUCCESS;
        }

        foreach ($materials as $material) {
            $goodsId = (int) $material->goods_id;
            $this->newLine();
            $this->info(sprintf('Material %d | reported quantity=%s', $goodsId, $this->format((float) $material->quantity)));

            $movements = DB::table('inventory_movements as m')
                ->leftJoin('locations as l', 'l.id', '=', 'm.location_id')
                ->where('m.company_id', $companyId)->where('m.goods_id', $goodsId)
                ->orderBy('m.occurred_at')->orderBy('m.id')
                ->get([
                    'm.id', 'm.location_id', 'l.name as location_name', 'm.quantity',
                    'm.movement_type', 'm.reference_type', 'm.reference_id',
                    'm.occurred_at', 'm.metadata',
                ]);

            $this->table(
                ['Movement', 'Location', 'Qty', 'Type', 'Reference', 'Date/time', 'Metadata'],
                $movements->map(fn ($m) => [
                    $m->id,
                    ($m->location_name ?? 'unknown') . ' (#' . ($m->location_id ?? '?') . ')',
                    $this->format((float) $m->quantity),
                    $m->movement_type,
                    ($m->reference_type ?? '-') . ':' . ($m->reference_id ?? '-'),
                    $m->occurred_at ?? '-',
                    $this->compactMetadata($m->metadata),
                ])->all()
            );

            $layers = DB::table('inventory_cost_layers as cl')
                ->leftJoin('locations as l', 'l.id', '=', 'cl.location_id')
                ->where('cl.company_id', $companyId)->where('cl.goods_id', $goodsId)
                ->orderBy('cl.received_at')->orderBy('cl.id')
                ->get([
                    'cl.id', 'cl.location_id', 'l.name as location_name', 'cl.received_at',
                    'cl.quantity_received', 'cl.fifo_quantity_remaining', 'cl.unit_cost', 'cl.total_cost',
                ]);

            $this->info('Cost layers (all dates, for chronology review):');
            $this->table(
                ['Layer', 'Location', 'Received at', 'Received qty', 'FIFO remaining', 'Unit cost', 'Total cost'],
                $layers->map(fn ($l) => [
                    $l->id,
                    ($l->location_name ?? 'unknown') . ' (#' . ($l->location_id ?? '?') . ')',
                    $l->received_at ?? '-',
                    $this->format((float) $l->quantity_received),
                    $this->format((float) $l->fifo_quantity_remaining),
                    $this->format((float) $l->unit_cost),
                    $this->format((float) $l->total_cost),
                ])->all()
            );

            if (Schema::hasTable('inventory_consumption_costs')) {
                $costs = DB::table('inventory_consumption_costs as c')
                    ->join('inventory_movements as m', 'm.id', '=', 'c.inventory_movement_id')
                    ->where('c.company_id', $companyId)->where('c.goods_id', $goodsId)
                    ->orderBy('m.occurred_at')->orderBy('m.id')
                    ->get([
                        'c.id', 'c.inventory_movement_id', 'c.quantity', 'c.unit_cost',
                        'c.total_cost', 'c.valuation_method', 'c.consumed_at', 'm.location_id',
                    ]);
                $this->info('Existing consumption valuations:');
                $this->table(
                    ['Cost row', 'Movement', 'Location', 'Qty valued', 'Unit cost', 'Total', 'Method', 'Consumed at'],
                    $costs->map(fn ($c) => [
                        $c->id, $c->inventory_movement_id, $c->location_id,
                        $this->format((float) $c->quantity), $this->format((float) $c->unit_cost),
                        $this->format((float) $c->total_cost), $c->valuation_method, $c->consumed_at,
                    ])->all()
                );
            }
        }

        $this->warn('READ-ONLY: no inventory movements, FIFO balances, cost allocations, or report rows were changed.');
        return self::SUCCESS;
    }

    private function compactMetadata($metadata): string
    {
        if (!$metadata) return '-';
        $decoded = json_decode((string) $metadata, true);
        if (!is_array($decoded)) return substr((string) $metadata, 0, 160);
        $keys = ['shift_report_id', 'production_section_id', 'order_id', 'production_id', 'supply_request_id', 'work_date', 'effective_occurred_at'];
        $selected = array_intersect_key($decoded, array_flip($keys));
        return $selected ? json_encode($selected, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '-';
    }

    private function format(float $value): string
    {
        return rtrim(rtrim(number_format($value, 4, '.', ''), '0'), '.') ?: '0';
    }
}
