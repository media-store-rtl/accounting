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
            'quantity_remaining' => $quantity,
            'unit_cost' => $unitCost,
            'total_cost' => $quantity * $unitCost,
            'received_at' => $receivedAt,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
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
        if ($quantity <= 0) {
            throw new RuntimeException('مقدار مصرف باید بیشتر از صفر باشد.');
        }

        return DB::transaction(function () use (
            $companyId, $locationId, $goodsId, $fiscalYearId,
            $quantity, $inventoryMovementId, $consumedAt
        ) {
            $layers = DB::table('inventory_cost_layers')
                ->where('company_id', $companyId)
                ->where('location_id', $locationId)
                ->where('goods_id', $goodsId)
                ->where('quantity_remaining', '>', 0)
                ->lockForUpdate()
                ->get();

            $available = (float) $layers->sum('quantity_remaining');

            // Existing stock may predate valuation layers. Keep operational inventory
            // backward-compatible; new receipts will always create layers.
            if ($available + 0.0000001 < $quantity) {
                return null;
            }

            $method = $this->methodForCompany($companyId);
            $allocations = [];
            $totalCost = 0.0;

            if ($method === self::FIFO) {
                $remaining = $quantity;
                foreach ($layers->sortBy(fn ($layer) => [$layer->received_at, $layer->id]) as $layer) {
                    if ($remaining <= 0) {
                        break;
                    }

                    $take = min($remaining, (float) $layer->quantity_remaining);
                    $unitCost = (float) $layer->unit_cost;
                    $allocations[] = [$layer, $take, $unitCost];
                    $totalCost += $take * $unitCost;
                    $remaining -= $take;
                }
            } else {
                $value = 0.0;
                foreach ($layers as $layer) {
                    $value += (float) $layer->quantity_remaining * (float) $layer->unit_cost;
                }
                $average = $available > 0 ? $value / $available : 0.0;
                $remaining = $quantity;

                foreach ($layers as $layer) {
                    if ($remaining <= 0) {
                        break;
                    }
                    $take = min($remaining, (float) $layer->quantity_remaining);
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
                'inventory_movement_id' => $inventoryMovementId,
                'valuation_method' => $method,
                'quantity' => $quantity,
                'unit_cost' => $quantity > 0 ? $totalCost / $quantity : 0,
                'total_cost' => $totalCost,
                'consumed_at' => $consumedAt,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($allocations as [$layer, $take, $unitCost]) {
                DB::table('inventory_cost_layers')->where('id', $layer->id)->update([
                    'quantity_remaining' => DB::raw('quantity_remaining - '.(float) $take),
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

            return $totalCost;
        });
    }
}