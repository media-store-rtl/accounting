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

        $current = $this->methodForCompany($companyId);
        if ($current !== $method && DB::table('inventory_consumption_costs')->where('company_id', $companyId)->exists()) {
            throw new RuntimeException('تغییر روش ارزش‌گذاری پس از ثبت مصرف ارزش‌گذاری‌شده نیازمند فرآیند تغییر سیاست حسابداری و بازمحاسبه است.');
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
                ->where($column, '>', 0)
                ->lockForUpdate()
                ->get();

            $available = (float) $layers->sum($column);
            if ($available + 0.0000001 < $quantity) {
                return ['total_cost' => 0.0, 'unit_cost' => 0.0, 'method' => $method, 'valued' => false];
            }

            $allocations = [];
            $totalCost = 0.0;

            if ($method === self::FIFO) {
                $remaining = $quantity;
                foreach ($layers->sortBy(fn ($layer) => [$layer->received_at, $layer->id]) as $layer) {
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
                $remaining = $quantity;
                foreach ($layers as $layer) {
                    if ($remaining <= 0) break;
                    $take = min($remaining, (float) $layer->{$column});
                    $allocations[] = [$layer, $take, $average];
                    $remaining -= $take;
                }
                $totalCost = $quantity * $average;
            }

            $costId = DB::table('inventory_consumption_costs')->insertGetId([
                'company_id' => $companyId,
                'fiscal_year_id' => $fiscalYearId,
                'goods_id' => $goodsId,
                'location_id' => $locationId,
                'inventory_movement_id' => $movementId,
                'valuation_method' => $method,
                'quantity' => $quantity,
                'unit_cost' => $totalCost / $quantity,
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

            return ['total_cost' => $totalCost, 'unit_cost' => $totalCost / $quantity, 'method' => $method, 'valued' => true];
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