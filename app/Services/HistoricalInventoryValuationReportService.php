<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Read-only historical valuation replay for reporting.
 *
 * This service deliberately does not write inventory movements, consumption cost
 * rows, or mutate cost-layer balances. It reconstructs a reporting view from
 * receipt cost layers and dated inventory movements.
 */
final class HistoricalInventoryValuationReportService
{
    /**
     * @return array<int, array{total_cost: float, unit_cost: float, valued_quantity: float, unvalued_quantity: float, method: string}>
     */
    public function calculate(int $companyId, string $method): array
    {
        if (!in_array($method, [InventoryValuationService::FIFO, InventoryValuationService::WEIGHTED_AVERAGE], true)) {
            abort(422, 'روش گزارش ارزش‌گذاری معتبر نیست.');
        }

        $layers = DB::table('inventory_cost_layers')
            ->where('company_id', $companyId)
            ->orderBy('received_at')->orderBy('id')
            ->get(['id', 'goods_id', 'location_id', 'quantity_received', 'unit_cost', 'received_at']);

        $movements = DB::table('inventory_movements')
            ->where('company_id', $companyId)
            ->orderBy('occurred_at')->orderBy('id')
            ->get(['id', 'goods_id', 'location_id', 'quantity', 'movement_type', 'reference_type', 'reference_id', 'occurred_at', 'metadata']);

        $events = [];
        foreach ($layers as $layer) {
            $events[] = [
                'at' => (string) $layer->received_at,
                'priority' => 0,
                'kind' => 'receipt_layer',
                'id' => (int) $layer->id,
                'row' => $layer,
            ];
        }
        foreach ($movements as $movement) {
            $events[] = [
                'at' => (string) $movement->occurred_at,
                'priority' => 1,
                'kind' => 'movement',
                'id' => (int) $movement->id,
                'row' => $movement,
            ];
        }
        usort($events, fn ($a, $b) => [$a['at'], $a['priority'], $a['id']] <=> [$b['at'], $b['priority'], $b['id']]);

        // Stock is tracked by goods/location. Null unit cost means the historical
        // movement added stock for which the ledger has no reliable acquisition cost.
        $stock = [];
        $transferSlices = [];
        $result = [];

        foreach ($events as $event) {
            $row = $event['row'];
            $goodsId = (int) $row->goods_id;
            $locationId = (int) $row->location_id;
            $key = $goodsId . ':' . $locationId;

            if ($event['kind'] === 'receipt_layer') {
                $quantity = (float) $row->quantity_received;
                if ($quantity > 0.0000001) {
                    $stock[$key][] = [
                        'quantity' => $quantity,
                        'unit_cost' => (float) $row->unit_cost,
                        'at' => (string) $row->received_at,
                        'layer_id' => (int) $row->id,
                    ];
                }
                continue;
            }

            $quantity = (float) $row->quantity;
            if (abs($quantity) <= 0.0000001) continue;
            $type = (string) $row->movement_type;
            $meta = json_decode((string) $row->metadata, true) ?: [];

            // The receipt cost layers already represent purchase receipts. Do not
            // count the corresponding positive movement a second time.
            if ($quantity > 0) {
                if (in_array($type, ['purchase_receipt', 'finished_goods_receipt'], true)) {
                    continue;
                }

                if ($type === 'production_material_receipt') {
                    $transferKey = $this->transferKey($row);
                    $slices = $transferSlices[$transferKey] ?? [];
                    $moved = array_sum(array_column($slices, 'quantity'));
                    if ($moved + 0.0001 >= $quantity) {
                        $remaining = $quantity;
                        foreach ($slices as $slice) {
                            if ($remaining <= 0.0000001) break;
                            $take = min($remaining, (float) $slice['quantity']);
                            $stock[$key][] = [
                                'quantity' => $take,
                                'unit_cost' => $slice['unit_cost'],
                                'at' => (string) $row->occurred_at,
                                'layer_id' => $slice['layer_id'] ?? null,
                            ];
                            $remaining -= $take;
                        }
                        unset($transferSlices[$transferKey]);
                        continue;
                    }
                }

                // An adjustment or receipt without a linked cost layer must remain
                // explicitly unvalued rather than receiving an invented unit cost.
                $stock[$key][] = [
                    'quantity' => $quantity,
                    'unit_cost' => null,
                    'at' => (string) $row->occurred_at,
                    'layer_id' => null,
                ];
                continue;
            }

            $needed = abs($quantity);
            $available = $stock[$key] ?? [];
            $valuedQuantity = 0.0;
            $totalCost = 0.0;
            $allocations = [];

            if ($method === InventoryValuationService::FIFO) {
                $remaining = $needed;
                foreach ($available as $index => $layer) {
                    if ($remaining <= 0.0000001) break;
                    if ((string) $layer['at'] > (string) $row->occurred_at) continue;
                    $take = min($remaining, (float) $layer['quantity']);
                    if ($take <= 0.0000001) continue;
                    $unitCost = $layer['unit_cost'];
                    if ($unitCost === null) {
                        // FIFO cannot jump over an unvalued earlier layer and claim
                        // that later known-cost stock was consumed first.
                        break;
                    }
                    $allocations[] = ['index' => $index, 'quantity' => $take, 'unit_cost' => (float) $unitCost];
                    $valuedQuantity += $take;
                    $totalCost += $take * (float) $unitCost;
                    $remaining -= $take;
                }
            } else {
                $eligible = [];
                $allAvailable = 0.0;
                $knownAvailable = 0.0;
                $knownValue = 0.0;
                foreach ($available as $index => $layer) {
                    if ((string) $layer['at'] > (string) $row->occurred_at) continue;
                    $q = (float) $layer['quantity'];
                    if ($q <= 0.0000001) continue;
                    $eligible[$index] = $layer;
                    $allAvailable += $q;
                    if ($layer['unit_cost'] !== null) {
                        $knownAvailable += $q;
                        $knownValue += $q * (float) $layer['unit_cost'];
                    }
                }
                // If any on-hand stock lacks cost provenance, the weighted average
                // is unknowable; do not value a partial quantity at a misleading rate.
                if ($allAvailable > 0.0000001 && abs($allAvailable - $knownAvailable) <= 0.0001) {
                    $average = $knownValue / $knownAvailable;
                    $valuedQuantity = min($needed, $knownAvailable);
                    $totalCost = $valuedQuantity * $average;
                    $remaining = $valuedQuantity;
                    foreach ($eligible as $index => $layer) {
                        if ($remaining <= 0.0000001) break;
                        $take = min($remaining, (float) $layer['quantity']);
                        if ($take <= 0.0000001) continue;
                        $allocations[] = ['index' => $index, 'quantity' => $take, 'unit_cost' => $average];
                        $remaining -= $take;
                    }
                }
            }

            // Apply the physical quantity movement to virtual stock, independently
            // of whether its cost is known. For FIFO, preserve the sequence; for
            // average, consume quantities proportionally across available layers.
            if ($method === InventoryValuationService::FIFO) {
                $remainingToRemove = $needed;
                foreach ($available as $index => &$layer) {
                    if ($remainingToRemove <= 0.0000001) break;
                    if ((string) $layer['at'] > (string) $row->occurred_at) continue;
                    $take = min($remainingToRemove, (float) $layer['quantity']);
                    $layer['quantity'] -= $take;
                    $remainingToRemove -= $take;
                }
                unset($layer);
            } else {
                $remainingToRemove = $needed;
                foreach ($available as $index => &$layer) {
                    if ($remainingToRemove <= 0.0000001) break;
                    if ((string) $layer['at'] > (string) $row->occurred_at) continue;
                    $take = min($remainingToRemove, (float) $layer['quantity']);
                    $layer['quantity'] -= $take;
                    $remainingToRemove -= $take;
                }
                unset($layer);
            }
            $stock[$key] = array_values(array_filter($available, fn ($layer) => (float) $layer['quantity'] > 0.0000001));

            if ($type === 'production_material_transfer_out') {
                $transferSlices[$this->transferKey($row)] = array_map(fn ($allocation) => [
                    'quantity' => $allocation['quantity'],
                    'unit_cost' => $allocation['unit_cost'],
                    'layer_id' => $available[$allocation['index']]['layer_id'] ?? null,
                ], $allocations);
            }

            $movementId = (int) $row->id;
            $result[$movementId] = [
                'total_cost' => round($totalCost, 4),
                'unit_cost' => $valuedQuantity > 0.0000001 ? round($totalCost / $valuedQuantity, 6) : 0.0,
                'valued_quantity' => round($valuedQuantity, 4),
                'unvalued_quantity' => round(max(0.0, $needed - $valuedQuantity), 4),
                'method' => $method,
                'slices' => array_map(fn ($allocation) => ['quantity' => round((float) $allocation['quantity'], 4), 'unit_cost' => round((float) $allocation['unit_cost'], 6)], $allocations),
            ];
        }

        return $result;
    }

    private function transferKey(object $movement): string
    {
        return (string) $movement->reference_type . ':' . (int) $movement->reference_id . ':' . (int) $movement->goods_id;
    }
}
