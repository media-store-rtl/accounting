<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

final class InventoryValuationService
{
    public const FIFO = 'fifo';
    public const WEIGHTED_AVERAGE = 'weighted_average';

    /**
     * Fixed application-wide inventory valuation policy.
     * Historical consumption-cost rows retain their stored valuation method.
     */
    public function methodForCompany(int $companyId): string
    {
        return self::FIFO;
    }

    public function recordReceipt(
        int $companyId,
        int $locationId,
        int $goodsId,
        int $fiscalYearId,
        int $purchaseId,
        int $purchaseItemId,
        int $purchaseReceiptId,
        int $purchaseReceiptItemId,
        float $quantity,
        float $unitCost,
        string $receivedAt
    ): void {
        if ($quantity <= 0 || $unitCost < 0) {
            throw new RuntimeException('مقدار یا بهای واحد ورودی نامعتبر است.');
        }

        DB::table('inventory_cost_layers')->insert([
            'company_id' => $companyId,
            'fiscal_year_id' => $fiscalYearId,
            'goods_id' => $goodsId,
            'location_id' => $locationId,
            'purchase_id' => $purchaseId,
            'purchase_item_id' => $purchaseItemId,
            'purchase_receipt_id' => $purchaseReceiptId,
            'purchase_receipt_item_id' => $purchaseReceiptItemId,
            'quantity_received' => $quantity,
            'fifo_quantity_remaining' => $quantity,
            'weighted_average_quantity_remaining' => $quantity,
            'unit_cost' => $unitCost,
            'total_cost' => $quantity * $unitCost,
            'received_at' => $receivedAt,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function registerPurchaseReceipt(
        int $companyId,
        int $purchaseId,
        int $purchaseItemId,
        int $purchaseReceiptId,
        int $purchaseReceiptItemId,
        int $locationId,
        int $goodsId,
        int $fiscalYearId,
        float $quantity,
        string $receivedAt
    ): void {
        $unitCost = (float) DB::table('purchase_items')->where('id', $purchaseItemId)->value('unit_price');
        $purchase = DB::table('purchases')->where('id', $purchaseId)->first();
        $item = DB::table('purchase_items')->where('id', $purchaseItemId)->first();
        if ($purchase && $item && (float) $purchase->subtotal > 0 && (float) $purchase->direct_cost_total > 0) {
            $unitCost += ((float) $purchase->direct_cost_total * (float) $item->line_total / (float) $purchase->subtotal) / (float) $item->quantity;
        }

        $this->recordReceipt(
            $companyId, $locationId, $goodsId, $fiscalYearId, $purchaseId, $purchaseItemId,
            $purchaseReceiptId, $purchaseReceiptItemId, $quantity, $unitCost, $receivedAt
        );
    }

    /** Move existing cost-layer balances between locations without recognizing consumption. */
    public function transferLayers(int $companyId, int $goodsId, int $fromLocationId, int $toLocationId, float $quantity): void
    {
        if ($quantity <= 0) {
            throw new RuntimeException('مقدار انتقال برای ارزش‌گذاری معتبر نیست.');
        }

        DB::transaction(function () use ($companyId, $goodsId, $fromLocationId, $toLocationId, $quantity) {
            $method = $this->methodForCompany($companyId);
            $column = $method === self::FIFO ? 'fifo_quantity_remaining' : 'weighted_average_quantity_remaining';
            $layers = DB::table('inventory_cost_layers')
                ->where('company_id', $companyId)->where('goods_id', $goodsId)
                ->where('location_id', $fromLocationId)->where($column, '>', 0)
                ->orderBy('received_at')->orderBy('id')->lockForUpdate()->get();
            $available = (float) $layers->sum($column);
            if ($available + 0.0000001 < $quantity) {
                $unvaluedQuantity = max(0.0, $quantity - $available);

                throw ValidationException::withMessages([
                    'items' => 'انتقال انجام نشد؛ موجودی انبار مبدأ برای این کالا لایه بهای ریالی کافی ندارد. ابتدا ورود یا ارزش‌گذاری موجودی را تکمیل کنید. مقدار فاقد بهای قابل انتقال: '.$unvaluedQuantity,
                ]);
            }

            // A production-section transfer must move the full quantity and its full
            // cost basis together. Never silently create an unvalued balance at the
            // destination when source valuation layers are missing or incomplete.
            $remaining = $quantity;
            foreach ($layers as $layer) {
                if ($remaining <= 0.0000001) break;
                $take = min($remaining, (float) $layer->{$column});
                DB::table('inventory_cost_layers')->where('id', $layer->id)->update([
                    'fifo_quantity_remaining' => DB::raw('fifo_quantity_remaining - '.(float) $take),
                    'weighted_average_quantity_remaining' => DB::raw('weighted_average_quantity_remaining - '.(float) $take),
                    'updated_at' => now(),
                ]);
                DB::table('inventory_cost_layers')->insert([
                    'company_id' => $layer->company_id,
                    'fiscal_year_id' => $layer->fiscal_year_id,
                    'goods_id' => $layer->goods_id,
                    'location_id' => $toLocationId,
                    'purchase_id' => $layer->purchase_id,
                    'purchase_item_id' => $layer->purchase_item_id,
                    'purchase_receipt_id' => $layer->purchase_receipt_id,
                    'purchase_receipt_item_id' => $layer->purchase_receipt_item_id,
                    'quantity_received' => $take,
                    'fifo_quantity_remaining' => $take,
                    'weighted_average_quantity_remaining' => $take,
                    'unit_cost' => $layer->unit_cost,
                    'total_cost' => $take * (float) $layer->unit_cost,
                    'received_at' => $layer->received_at,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $remaining -= $take;
            }
        });
    }

    public function valueInventoryMovement(int $movementId, int $fiscalYearId): array
    {
        $movement = DB::table('inventory_movements')->where('id', $movementId)->lockForUpdate()->firstOrFail();
        if ((float) $movement->quantity >= 0) {
            return ['total_cost' => 0.0, 'unit_cost' => 0.0, 'method' => $this->methodForCompany((int) $movement->company_id)];
        }

        $quantity = abs((float) $movement->quantity);
        $companyId = (int) $movement->company_id;
        $locationId = (int) $movement->location_id;
        $goodsId = (int) $movement->goods_id;
        $method = $this->methodForCompany($companyId);

        $existing = DB::table('inventory_consumption_costs')->where('inventory_movement_id', $movementId)->first();
        if ($existing) {
            // Recalculate the rial amounts from the already-linked receipt layers.
            // Keep the consumed quantities and layer balances untouched; this is a
            // monetary refresh, not a second inventory consumption.
            $this->refreshExistingCostAmounts((int) $existing->id);
            return $this->topUpExistingCost($movement, $fiscalYearId, $quantity, (int) $existing->id);
        }

        return DB::transaction(function () use ($movementId, $fiscalYearId, $quantity, $companyId, $locationId, $goodsId, $method, $movement) {
            $column = $method === self::FIFO ? 'fifo_quantity_remaining' : 'weighted_average_quantity_remaining';
            $layers = DB::table('inventory_cost_layers')
                ->where('company_id', $companyId)
                ->where('location_id', $locationId)
                ->where('goods_id', $goodsId)
                // A movement must never be valued from a receipt that occurred after it.
                // For backdated movements, later receipts are deliberately left unallocated;
                // a full historical replay is required to reconstruct already-consumed layers.
                ->where('received_at', '<=', $movement->occurred_at)
                ->where($column, '>', 0)
                ->when($method === self::FIFO, fn ($query) => $query->orderBy('received_at')->orderBy('id'))
                ->lockForUpdate()
                ->get();

            $available = (float) $layers->sum($column);
            // Inventory can contain a mix of valued receipt layers and legacy or
            // adjusted stock without layers. Value the traceable portion instead
            // of discarding its cost merely because the full movement is not covered.
            $valuedQuantity = min($quantity, $available);
            if ($valuedQuantity <= 0.0000001) {
                return [
                    'total_cost' => 0.0, 'unit_cost' => 0.0, 'method' => $method,
                    'valued' => false, 'valued_quantity' => 0.0, 'unvalued_quantity' => $quantity,
                ];
            }

            $allocations = [];
            $totalCost = 0.0;

            if ($method === self::FIFO) {
                $remaining = $valuedQuantity;
                foreach ($layers as $layer) {
                    if ($remaining <= 0) break;
                    $take = min($remaining, (float) $layer->{$column});
                    $unitCost = (float) $layer->unit_cost;
                    $allocations[] = [$layer, $take, $unitCost];
                    $totalCost += $take * $unitCost;
                    $remaining -= $take;
                }
            } else {
                $value = 0.0;
                foreach ($layers as $layer) {
                    $value += (float) $layer->{$column} * (float) $layer->unit_cost;
                }
                $average = $available > 0 ? $value / $available : 0.0;
                // Weighted-average consumption removes the same fraction from each
                // contributing layer. Removing FIFO quantities here would distort
                // the remaining pool's next average by leaving its original costs
                // concentrated in the wrong quantities.
                $fraction = $available > 0 ? min(1.0, $valuedQuantity / $available) : 0.0;
                foreach ($layers as $layer) {
                    $take = (float) $layer->{$column} * $fraction;
                    if ($take <= 0.0000001) continue;
                    $allocations[] = [$layer, $take, $average];
                }
                $totalCost = $valuedQuantity * $average;
            }

            $costId = DB::table('inventory_consumption_costs')->insertGetId([
                'company_id' => $companyId,
                'fiscal_year_id' => $fiscalYearId,
                'goods_id' => $goodsId,
                'location_id' => $locationId,
                'inventory_movement_id' => $movementId,
                'valuation_method' => $method,
                'quantity' => $valuedQuantity,
                'unit_cost' => $totalCost / $valuedQuantity,
                'total_cost' => $totalCost,
                'consumed_at' => $movement->occurred_at,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($allocations as [$layer, $take, $unitCost]) {
                DB::table('inventory_cost_layers')->where('id', $layer->id)->update([
                    $column => DB::raw($column.' - '.(float) $take),
                    'updated_at' => now(),
                ]);
                DB::table('inventory_consumption_cost_items')->insert([
                    'inventory_consumption_cost_id' => $costId,
                    'inventory_cost_layer_id' => $layer->id,
                    'quantity' => $take,
                    'unit_cost' => $unitCost,
                    'total_cost' => $take * $unitCost,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return [
                'total_cost' => $totalCost,
                'unit_cost' => $totalCost / $valuedQuantity,
                'method' => $method,
                'valued' => true,
                'valued_quantity' => $valuedQuantity,
                'unvalued_quantity' => max(0.0, $quantity - $valuedQuantity),
            ];
        });
    }

    /**
     * Refresh stored rial amounts using the receipt-layer prices already attached
     * to a consumption cost. This deliberately does not alter quantities or layer
     * balances, so rerunning a costing refresh cannot deduct inventory again.
     */
    private function refreshExistingCostAmounts(int $costId): void
    {
        DB::transaction(function () use ($costId) {
            $cost = DB::table('inventory_consumption_costs')
                ->where('id', $costId)->lockForUpdate()->first();
            if (!$cost) return;

            $items = DB::table('inventory_consumption_cost_items as item')
                ->join('inventory_cost_layers as layer', 'layer.id', '=', 'item.inventory_cost_layer_id')
                ->where('item.inventory_consumption_cost_id', $costId)
                ->lockForUpdate()
                ->get(['item.id', 'item.quantity', 'layer.unit_cost']);

            // Legacy cost rows without layer-level details cannot be safely
            // reconstructed; retain their existing documented amount.
            if ($items->isEmpty()) return;

            $weightedAverage = null;
            if ($cost->valuation_method === self::WEIGHTED_AVERAGE) {
                $quantity = (float) $items->sum('quantity');
                $weightedAverage = $quantity > 0
                    ? $items->sum(fn ($item) => (float) $item->quantity * (float) $item->unit_cost) / $quantity
                    : 0.0;
            }

            $totalCost = 0.0;
            foreach ($items as $item) {
                // FIFO keeps the actual receipt-layer price; weighted average
                // recomputes one common average from the same linked quantities.
                $unitCost = $weightedAverage ?? (float) $item->unit_cost;
                $lineTotal = (float) $item->quantity * $unitCost;
                DB::table('inventory_consumption_cost_items')
                    ->where('id', $item->id)
                    ->update([
                        'unit_cost' => $unitCost,
                        'total_cost' => $lineTotal,
                        'updated_at' => now(),
                    ]);
                $totalCost += $lineTotal;
            }

            $quantity = (float) $items->sum('quantity');
            DB::table('inventory_consumption_costs')->where('id', $costId)->update([
                'quantity' => $quantity,
                'unit_cost' => $quantity > 0 ? $totalCost / $quantity : 0,
                'total_cost' => $totalCost,
                'updated_at' => now(),
            ]);
        });
    }

    /**
     * Complete a previously partial valuation from remaining layers that existed
     * by the movement timestamp. Existing allocations are immutable; only the
     * missing quantity is allocated and appended to the existing cost record.
     */
    private function topUpExistingCost(object $movement, int $fiscalYearId, float $quantity, int $costId): array
    {
        return DB::transaction(function () use ($movement, $fiscalYearId, $quantity, $costId) {
            $cost = DB::table('inventory_consumption_costs')->where('id', $costId)->lockForUpdate()->first();
            if (!$cost) {
                throw new RuntimeException('ردیف بهای مصرف برای تکمیل پیدا نشد.');
            }

            $existingQuantity = (float) $cost->quantity;
            $missingQuantity = max(0.0, $quantity - $existingQuantity);
            if ($missingQuantity <= 0.0000001) {
                return [
                    'total_cost' => (float) $cost->total_cost,
                    'unit_cost' => (float) $cost->unit_cost,
                    'method' => $cost->valuation_method,
                    'valued' => true,
                    'valued_quantity' => $existingQuantity,
                    'unvalued_quantity' => 0.0,
                ];
            }

            $method = $cost->valuation_method ?: $this->methodForCompany((int) $movement->company_id);
            $column = $method === self::FIFO ? 'fifo_quantity_remaining' : 'weighted_average_quantity_remaining';
            $layers = DB::table('inventory_cost_layers')
                ->where('company_id', (int) $movement->company_id)
                ->where('location_id', (int) $movement->location_id)
                ->where('goods_id', (int) $movement->goods_id)
                ->where('received_at', '<=', $movement->occurred_at)
                ->where($column, '>', 0)
                ->when($method === self::FIFO, fn ($query) => $query->orderBy('received_at')->orderBy('id'))
                ->lockForUpdate()
                ->get();

            $available = (float) $layers->sum($column);
            $valuedQuantity = min($missingQuantity, $available);
            if ($valuedQuantity <= 0.0000001) {
                return [
                    'total_cost' => (float) $cost->total_cost,
                    'unit_cost' => (float) $cost->unit_cost,
                    'method' => $method,
                    'valued' => true,
                    'valued_quantity' => $existingQuantity,
                    'unvalued_quantity' => $missingQuantity,
                ];
            }

            $allocations = [];
            $additionalCost = 0.0;
            if ($method === self::FIFO) {
                $remaining = $valuedQuantity;
                foreach ($layers as $layer) {
                    if ($remaining <= 0.0000001) break;
                    $take = min($remaining, (float) $layer->{$column});
                    $unitCost = (float) $layer->unit_cost;
                    $allocations[] = [$layer, $take, $unitCost];
                    $additionalCost += $take * $unitCost;
                    $remaining -= $take;
                }
            } else {
                $layerValue = 0.0;
                foreach ($layers as $layer) {
                    $layerValue += (float) $layer->{$column} * (float) $layer->unit_cost;
                }
                $average = $available > 0 ? $layerValue / $available : 0.0;
                $fraction = $available > 0 ? min(1.0, $valuedQuantity / $available) : 0.0;
                foreach ($layers as $layer) {
                    $take = (float) $layer->{$column} * $fraction;
                    if ($take <= 0.0000001) continue;
                    $allocations[] = [$layer, $take, $average];
                }
                $additionalCost = $valuedQuantity * $average;
            }

            foreach ($allocations as [$layer, $take, $unitCost]) {
                DB::table('inventory_cost_layers')->where('id', $layer->id)->update([
                    $column => DB::raw($column.' - '.(float) $take),
                    'updated_at' => now(),
                ]);
                DB::table('inventory_consumption_cost_items')->insert([
                    'inventory_consumption_cost_id' => $costId,
                    'inventory_cost_layer_id' => $layer->id,
                    'quantity' => $take,
                    'unit_cost' => $unitCost,
                    'total_cost' => $take * $unitCost,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $newQuantity = $existingQuantity + $valuedQuantity;
            $newTotalCost = (float) $cost->total_cost + $additionalCost;
            $newUnitCost = $newQuantity > 0 ? $newTotalCost / $newQuantity : 0.0;
            DB::table('inventory_consumption_costs')->where('id', $costId)->update([
                'quantity' => $newQuantity,
                'total_cost' => $newTotalCost,
                'unit_cost' => $newUnitCost,
                'updated_at' => now(),
            ]);

            return [
                'total_cost' => $newTotalCost,
                'unit_cost' => $newUnitCost,
                'method' => $method,
                'valued' => true,
                'valued_quantity' => $newQuantity,
                'unvalued_quantity' => max(0.0, $quantity - $newQuantity),
            ];
        });
    }

    public function consume(
        int $companyId,
        int $locationId,
        int $goodsId,
        int $fiscalYearId,
        float $quantity,
        int $inventoryMovementId,
        string $consumedAt
    ): ?float {
        $result = $this->valueInventoryMovement($inventoryMovementId, $fiscalYearId);
        return ($result['valued'] ?? true) ? (float) $result['total_cost'] : null;
    }
}