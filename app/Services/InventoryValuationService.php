<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

final class InventoryValuationService
{
    public const FIFO = 'fifo';
    public const WEIGHTED_AVERAGE = 'weighted_average';

    public function methodForCompany(int $companyId): string
    {
        $method = DB::table('companies')->where('id', $companyId)->value('inventory_valuation_method');
        return in_array($method, [self::FIFO, self::WEIGHTED_AVERAGE], true) ? $method : self::FIFO;
    }

    public function setMethod(int $companyId, string $method): void
    {
        if (!in_array($method, [self::FIFO, self::WEIGHTED_AVERAGE], true)) {
            throw new RuntimeException('روش ارزش‌گذاری باید FIFO یا Weighted Average باشد.');
        }

        DB::table('companies')->where('id', $companyId)->update([
            'inventory_valuation_method' => $method,
            'updated_at' => now(),
        ]);
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
            // Legacy/adjusted stock may not have valuation layers; transfer the valued
            // portion only and leave consumption explicitly unvalued when necessary.
            $remaining = min($quantity, $available);
            if ($remaining <= 0.0000001) return;
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
            return ['total_cost' => (float) $existing->total_cost, 'unit_cost' => (float) $existing->unit_cost, 'method' => $existing->valuation_method];
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
                $remaining = $valuedQuantity;
                foreach ($layers as $layer) {
                    if ($remaining <= 0) break;
                    $take = min($remaining, (float) $layer->{$column});
                    $allocations[] = [$layer, $take, $average];
                    $remaining -= $take;
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