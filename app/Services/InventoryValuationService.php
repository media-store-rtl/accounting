<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

final class InventoryValuationService
{
    public const FIFO = 'fifo';
    public const WEIGHTED_AVERAGE = 'weighted_average';

    public static function isSupported(string $method): bool
    {
        return in_array($method, [self::FIFO, self::WEIGHTED_AVERAGE], true);
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
        if ($quantity <= 0) {
            throw new RuntimeException('مقدار لایه ارزش‌گذاری باید بیشتر از صفر باشد.');
        }

        $item = DB::table('purchase_items')->where('id', $purchaseItemId)->where('purchase_id', $purchaseId)->first();
        abort_unless($item, 422, 'قلم خرید نامعتبر است.');

        $purchase = DB::table('purchases')->where('id', $purchaseId)->where('company_id', $companyId)->first();
        abort_unless($purchase, 422, 'خرید نامعتبر است.');

        $subtotal = (float) $purchase->subtotal;
        $directCost = (float) $purchase->direct_cost_total;
        $lineTotal = (float) $item->line_total;
        $allocatedDirectCost = $subtotal > 0 ? $directCost * ($lineTotal / $subtotal) : 0.0;
        $unitCost = ((float) $item->unit_price) + ($allocatedDirectCost / (float) $item->quantity);

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

    public function consume(int $companyId, int $locationId, int $goodsId, int $fiscalYearId, float $quantity, int $movementId, string $consumedAt): array
    {
        return $this->valueInventoryMovement($movementId, $fiscalYearId);
    }

    public function valueInventoryMovement(int $movementId, ?int $fiscalYearId = null): array
    {
        $movement = DB::table('inventory_movements')->where('id', $movementId)->lockForUpdate()->first();
        abort_unless($movement, 404, 'حرکت موجودی پیدا نشد.');
        abort_unless((float) $movement->quantity < 0, 422, 'فقط خروج موجودی قابل ارزش‌گذاری است.');

        $existing = DB::table('inventory_consumption_costs')->where('inventory_movement_id', $movementId)->first();
        if ($existing) {
            return ['id' => $existing->id, 'quantity' => (float) $existing->quantity, 'unit_cost' => (float) $existing->unit_cost, 'total_cost' => (float) $existing->total_cost, 'valuation_method' => $existing->valuation_method];
        }

        $fiscalYearId ??= $this->resolveFiscalYearId($movement);
        abort_unless($fiscalYearId, 422, 'سال مالی برای ارزش‌گذاری مشخص نیست.');

        $method = DB::table('companies')->where('id', $movement->company_id)->value('inventory_valuation_method') ?: self::FIFO;
        abort_unless(self::isSupported($method), 422, 'روش ارزش‌گذاری موجودی نامعتبر است.');

        $quantity = abs((float) $movement->quantity);
        $layers = DB::table('inventory_cost_layers')
            ->where('company_id', $movement->company_id)
            ->where('goods_id', $movement->goods_id)
            ->where('location_id', $movement->location_id)
            ->when($method === self::FIFO, fn ($q) => $q->where('fifo_quantity_remaining', '>', 0), fn ($q) => $q->where('weighted_average_quantity_remaining', '>', 0))
            ->orderBy('received_at')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $available = 0.0;
        $value = 0.0;
        foreach ($layers as $layer) {
            $remaining = $method === self::FIFO ? (float) $layer->fifo_quantity_remaining : (float) $layer->weighted_average_quantity_remaining;
            $available += $remaining;
            $value += $remaining * (float) $layer->unit_cost;
        }
        abort_unless($available + 0.0000001 >= $quantity, 422, 'لایه ارزش‌گذاری موجودی کافی نیست.');

        $average = $available > 0 ? $value / $available : 0.0;
        $items = [];
        $left = $quantity;

        foreach ($layers as $layer) {
            if ($left <= 0.0000001) {
                break;
            }

            $remaining = $method === self::FIFO ? (float) $layer->fifo_quantity_remaining : (float) $layer->weighted_average_quantity_remaining;
            if ($remaining <= 0) {
                continue;
            }

            $take = $method === self::FIFO
                ? min($left, $remaining)
                : min($left, $remaining * ($quantity / $available));

            if ($method === self::WEIGHTED_AVERAGE && $left - $take < 0.0000001) {
                $take = $left;
            }

            $unitCost = $method === self::WEIGHTED_AVERAGE ? $average : (float) $layer->unit_cost;
            $items[] = [
                'layer_id' => $layer->id,
                'quantity' => $take,
                'unit_cost' => $unitCost,
                'total_cost' => $take * $unitCost,
            ];

            $column = $method === self::FIFO ? 'fifo_quantity_remaining' : 'weighted_average_quantity_remaining';
            DB::table('inventory_cost_layers')->where('id', $layer->id)->update([
                $column => max(0, $remaining - $take),
                'updated_at' => now(),
            ]);
            $left -= $take;
        }

        abort_unless($left <= 0.0000001, 422, 'مقدار مصرف با لایه‌های ارزش‌گذاری تطبیق ندارد.');

        $totalCost = array_sum(array_column($items, 'total_cost'));
        $costId = DB::table('inventory_consumption_costs')->insertGetId([
            'company_id' => $movement->company_id,
            'fiscal_year_id' => $fiscalYearId,
            'goods_id' => $movement->goods_id,
            'location_id' => $movement->location_id,
            'inventory_movement_id' => $movementId,
            'valuation_method' => $method,
            'quantity' => $quantity,
            'unit_cost' => $quantity > 0 ? $totalCost / $quantity : 0,
            'total_cost' => $totalCost,
            'consumed_at' => $movement->occurred_at,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($items as $item) {
            DB::table('inventory_consumption_cost_items')->insert([
                'inventory_consumption_cost_id' => $costId,
                'inventory_cost_layer_id' => $item['layer_id'],
                'quantity' => $item['quantity'],
                'unit_cost' => $item['unit_cost'],
                'total_cost' => $item['total_cost'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return ['id' => $costId, 'quantity' => $quantity, 'unit_cost' => $quantity > 0 ? $totalCost / $quantity : 0, 'total_cost' => $totalCost, 'valuation_method' => $method];
    }

    private function resolveFiscalYearId(object $movement): ?int
    {
        $metadata = json_decode((string) $movement->metadata, true) ?: [];
        $candidates = [];
        if (!empty($metadata['supply_request_id'])) {
            $candidates[] = DB::table('supply_requests')->where('id', $metadata['supply_request_id'])->value('fiscal_year_id');
        }
        if (!empty($metadata['order_id'])) {
            $candidates[] = DB::table('orders')->where('id', $metadata['order_id'])->value('fiscal_year_id');
        }
        foreach ($candidates as $id) {
            if ($id) {
                return (int) $id;
            }
        }
        return null;
    }
}
