<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

final class CostingReportService
{
    public function generate(int $companyId, ?int $fiscalYearId = null, ?int $orderId = null, ?int $goodsId = null): array
    {
        $fiscalYearId ??= DB::table('fiscal_years')->where('company_id', $companyId)->orderByDesc('id')->value('id');

        if ($fiscalYearId !== null) {
            abort_unless(
                DB::table('fiscal_years')->where('id', $fiscalYearId)->where('company_id', $companyId)->exists(),
                422,
                'سال مالی متعلق به شرکت نیست.'
            );
        }

        if ($orderId !== null) {
            abort_unless(
                DB::table('orders')->where('id', $orderId)->where('company_id', $companyId)->exists(),
                422,
                'سفارش متعلق به شرکت نیست.'
            );
        }

        if ($goodsId !== null) {
            abort_unless(
                DB::table('goods')->where('id', $goodsId)->where('company_id', $companyId)->exists(),
                422,
                'کالا متعلق به شرکت نیست.'
            );
        }

        $rows = [];
        $this->materials($rows, $companyId, $fiscalYearId, $orderId, $goodsId);
        $this->labor($rows, $companyId, $fiscalYearId, $orderId, $goodsId);

        $ids = array_values(array_unique(array_filter(array_column($rows, 'goods_id'))));
        $goods = $ids
            ? DB::table('goods')->where('company_id', $companyId)->whereIn('id', $ids)->get(['id', 'code', 'name'])->keyBy('id')
            : collect();

        $valuationMethods = [];

        foreach ($rows as $key => &$row) {
            $row = $this->normalizeRow($row);
            $goodsItem = $goods->get($row['goods_id']);

            $row['goods_code'] = $goodsItem?->code;
            $row['goods_name'] = $goodsItem?->name;
            $row['valuation_methods'] = array_keys($row['valuation_methods']);
            $row['material_cost'] = round($row['material_cost'], 4);
            $row['labor_cost'] = round($row['labor_cost'], 4);
            $row['total_cost'] = round($row['material_cost'] + $row['labor_cost'], 4);
            $row['unvalued_material_quantity'] = round($row['unvalued_material_quantity'], 4);

            foreach ($row['valuation_methods'] as $method) {
                $valuationMethods[$method] = true;
            }
        }
        unset($row);

        usort(
            $rows,
            fn ($a, $b) => [$a['order_id'] ?? PHP_INT_MAX, $a['goods_name'] ?? ''] <=> [$b['order_id'] ?? PHP_INT_MAX, $b['goods_name'] ?? '']
        );

        return [
            'fiscal_year_id' => $fiscalYearId,
            'rows' => array_values($rows),
            'totals' => [
                'material_cost' => round(array_sum(array_column($rows, 'material_cost')), 4),
                'labor_cost' => round(array_sum(array_column($rows, 'labor_cost')), 4),
                'total_cost' => round(array_sum(array_column($rows, 'total_cost')), 4),
                'unvalued_material_quantity' => round(array_sum(array_column($rows, 'unvalued_material_quantity')), 4),
            ],
            'valuation_methods' => array_keys($valuationMethods),
        ];
    }

    private function materials(array &$rows, int $companyId, ?int $fy, ?int $orderId, ?int $goodsId): void
    {
        $costs = DB::table('inventory_consumption_costs as c')
            ->join('inventory_movements as m', 'm.id', '=', 'c.inventory_movement_id')
            ->where('c.company_id', $companyId)
            ->when($fy, fn ($q) => $q->where('c.fiscal_year_id', $fy))
            ->when($goodsId, fn ($q) => $q->where('c.goods_id', $goodsId))
            ->get(['c.goods_id', 'c.total_cost', 'c.valuation_method', 'm.metadata']);

        $unvaluedMovements = DB::table('inventory_movements as m')
            ->where('m.company_id', $companyId)
            ->where('m.quantity', '<', 0)
            ->where('m.movement_type', 'material_handover')
            ->whereNotExists(function ($q) {
                $q->selectRaw('1')
                    ->from('inventory_consumption_costs as vc')
                    ->whereColumn('vc.inventory_movement_id', 'm.id');
            })
            ->when($goodsId, fn ($q) => $q->where('m.goods_id', $goodsId))
            ->get(['m.goods_id', 'm.quantity', 'm.metadata']);

        $supplyIds = $costs
            ->map(fn ($c) => (int) (json_decode((string) $c->metadata, true)['supply_request_id'] ?? 0))
            ->merge($unvaluedMovements->map(fn ($m) => (int) (json_decode((string) $m->metadata, true)['supply_request_id'] ?? 0)))
            ->filter()
            ->unique()
            ->values();

        $supplies = $supplyIds->isEmpty()
            ? collect()
            : DB::table('supply_requests')
                ->where('company_id', $companyId)
                ->whereIn('id', $supplyIds)
                ->get(['id', 'order_id', 'production_id', 'fiscal_year_id'])
                ->keyBy('id');

        $prodIds = $supplies->pluck('production_id')->filter()->unique()->values();
        $links = $prodIds->isEmpty()
            ? collect()
            : DB::table('production_order')
                ->whereIn('production_id', $prodIds)
                ->get(['production_id', 'order_id'])
                ->groupBy('production_id');

        foreach ($costs as $cost) {
            $meta = json_decode((string) $cost->metadata, true) ?: [];
            $supply = $supplies->get((int) ($meta['supply_request_id'] ?? 0));
            $resolvedOrderId = $supply?->order_id;

            if ($resolvedOrderId === null && $supply?->production_id) {
                $resolvedOrderId = $links->get((int) $supply->production_id)?->first()?->order_id;
            }

            if ($orderId !== null && (int) $resolvedOrderId !== $orderId) {
                continue;
            }

            $key = $this->key($resolvedOrderId, (int) $cost->goods_id);
            $this->row($rows, $key, $resolvedOrderId, (int) $cost->goods_id);
            $rows[$key]['material_cost'] += (float) $cost->total_cost;
            $rows[$key]['valuation_methods'][(string) $cost->valuation_method] = true;
        }

        foreach ($unvaluedMovements as $movement) {
            $meta = json_decode((string) $movement->metadata, true) ?: [];
            $supply = $supplies->get((int) ($meta['supply_request_id'] ?? 0));

            if (!$supply) {
                continue;
            }

            if ($fy !== null && (int) $supply->fiscal_year_id !== $fy) {
                continue;
            }

            $resolvedOrderId = $supply->order_id;
            if ($resolvedOrderId === null && $supply->production_id) {
                $resolvedOrderId = $links->get((int) $supply->production_id)?->first()?->order_id;
            }

            if ($orderId !== null && (int) $resolvedOrderId !== $orderId) {
                continue;
            }

            $key = $this->key($resolvedOrderId, (int) $movement->goods_id);
            $this->row($rows, $key, $resolvedOrderId, (int) $movement->goods_id);
            $rows[$key]['unvalued_material_quantity'] += abs((float) $movement->quantity);
        }
    }

    private function labor(array &$rows, int $companyId, ?int $fy, ?int $orderId, ?int $goodsId): void
    {
        $costs = DB::table('production_labor_costs')
            ->where('company_id', $companyId)
            ->when($fy, fn ($q) => $q->where('fiscal_year_id', $fy))
            ->get(['production_id', 'total_cost']);

        $productionIds = $costs->pluck('production_id')->unique()->values();
        if ($productionIds->isEmpty()) {
            return;
        }

        $productionGoods = DB::table('productions')
            ->whereIn('id', $productionIds)
            ->get(['id', 'goods_id'])
            ->keyBy('id');

        $links = DB::table('production_order')
            ->whereIn('production_id', $productionIds)
            ->get(['production_id', 'order_id'])
            ->groupBy('production_id');

        foreach ($costs as $cost) {
            $production = $productionGoods->get((int) $cost->production_id);
            if (!$production || ($goodsId !== null && (int) $production->goods_id !== $goodsId)) {
                continue;
            }

            foreach ($links->get((int) $cost->production_id, collect()) as $link) {
                if ($orderId !== null && (int) $link->order_id !== $orderId) {
                    continue;
                }

                $key = $this->key((int) $link->order_id, (int) $production->goods_id);
                $this->row($rows, $key, (int) $link->order_id, (int) $production->goods_id);
                $rows[$key]['labor_cost'] += (float) $cost->total_cost;
            }
        }
    }

    private function key(?int $orderId, int $goodsId): string
    {
        return ($orderId ?? 0) . ':' . $goodsId;
    }

    private function row(array &$rows, string $key, ?int $orderId, int $goodsId): void
    {
        if (isset($rows[$key])) {
            return;
        }

        $rows[$key] = $this->normalizeRow([
            'order_id' => $orderId,
            'goods_id' => $goodsId,
        ]);
    }

    private function normalizeRow(array $row): array
    {
        $methods = $row['valuation_methods'] ?? [];

        if (!is_array($methods)) {
            $methods = [$methods];
        }

        return array_merge([
            'order_id' => null,
            'goods_id' => null,
            'material_cost' => 0.0,
            'labor_cost' => 0.0,
            'total_cost' => 0.0,
            'unvalued_material_quantity' => 0.0,
            'valuation_methods' => [],
        ], $row, [
            'valuation_methods' => array_fill_keys(array_filter(array_map('strval', $methods), fn ($method) => $method !== ''), true),
        ]);
    }
}
