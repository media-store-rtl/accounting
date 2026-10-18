<?php

namespace Tests\Feature;

use App\Services\HistoricalInventoryValuationReportService;
use App\Services\InventoryValuationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CostingValuationAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(string $method = 'fifo'): array
    {
        $now = now();
        $account = DB::table('accounts')->insertGetId([
            'name' => 'Valuation Acceptance Account', 'code' => 'VAA'.uniqid(),
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $company = DB::table('companies')->insertGetId([
            'account_id' => $account, 'name' => 'Valuation Acceptance Company',
            'code' => 'VAC'.uniqid(), 'is_active' => true, 'settings' => json_encode([]),
            'inventory_valuation_method' => $method, 'created_at' => $now, 'updated_at' => $now,
        ]);
        $fy = DB::table('fiscal_years')->insertGetId([
            'company_id' => $company, 'name' => '1405', 'code' => 'VFY'.uniqid(),
            'starts_at' => '2026-03-21', 'ends_at' => '2027-03-20',
            'is_closed' => false, 'created_at' => $now, 'updated_at' => $now,
        ]);
        $supplier = DB::table('suppliers')->insertGetId([
            'company_id' => $company, 'code' => 'VS'.uniqid(), 'name' => 'Valuation Supplier',
            'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
        ]);
        $user = DB::table('users')->insertGetId([
            'account_id' => $account, 'name' => 'Valuation User', 'username' => 'vu'.uniqid(),
            'email' => uniqid().'@test.local', 'password' => bcrypt('secret'),
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $category = DB::table('goods_categories')->insertGetId([
            'company_id' => $company, 'name' => 'Raw Materials', 'code' => 'VGC'.uniqid(),
            'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
        ]);
        $goods = DB::table('goods')->insertGetId([
            'company_id' => $company, 'category_id' => $category, 'code' => 'VG'.uniqid(),
            'name' => 'Valuation Material', 'purchasable' => true, 'producible' => false,
            'sellable' => false, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
        ]);
        $from = DB::table('locations')->insertGetId([
            'company_id' => $company, 'code' => 'VLF'.uniqid(), 'name' => 'Source Warehouse',
            'type' => 'warehouse', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
        ]);
        $to = DB::table('locations')->insertGetId([
            'company_id' => $company, 'code' => 'VLT'.uniqid(), 'name' => 'Production Location',
            'type' => 'production', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now,
        ]);

        return compact('company', 'fy', 'goods', 'from', 'to', 'supplier', 'user');
    }

    private function layer(array $f, float $quantity, float $unitCost, string $receivedAt, ?int $location = null): int
    {
        $now = now();
        $purchase = DB::table('purchases')->insertGetId([
            'company_id' => $f['company'], 'fiscal_year_id' => $f['fy'], 'supplier_id' => $f['supplier'],
            'invoice_number' => 'VPI-'.uniqid(), 'invoice_date' => substr($receivedAt, 0, 10),
            'purchased_at' => substr($receivedAt, 0, 10), 'subtotal' => $quantity * $unitCost,
            'direct_cost_total' => 0, 'total_amount' => $quantity * $unitCost, 'status' => 'received',
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $purchaseItem = DB::table('purchase_items')->insertGetId([
            'purchase_id' => $purchase, 'goods_id' => $f['goods'], 'quantity' => $quantity,
            'unit_price' => $unitCost, 'line_total' => $quantity * $unitCost,
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $receipt = DB::table('purchase_receipts')->insertGetId([
            'company_id' => $f['company'], 'purchase_id' => $purchase,
            'warehouse_location_id' => $location ?? $f['from'], 'received_by_user_id' => $f['user'],
            'received_at' => $receivedAt, 'approved_at' => $receivedAt,
            'approved_by_user_id' => $f['user'], 'status' => 'approved',
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $receiptItem = DB::table('purchase_receipt_items')->insertGetId([
            'purchase_receipt_id' => $receipt, 'goods_id' => $f['goods'], 'quantity' => $quantity,
            'created_at' => $now, 'updated_at' => $now,
        ]);

        return DB::table('inventory_cost_layers')->insertGetId([
            'company_id' => $f['company'], 'fiscal_year_id' => $f['fy'], 'goods_id' => $f['goods'],
            'location_id' => $location ?? $f['from'], 'purchase_id' => $purchase, 'purchase_item_id' => $purchaseItem,
            'purchase_receipt_id' => $receipt, 'purchase_receipt_item_id' => $receiptItem,
            'quantity_received' => $quantity, 'fifo_quantity_remaining' => $quantity,
            'weighted_average_quantity_remaining' => $quantity, 'unit_cost' => $unitCost,
            'total_cost' => $quantity * $unitCost, 'received_at' => $receivedAt,
            'created_at' => $now, 'updated_at' => $now,
        ]);
    }

    private function movement(array $f, float $quantity, string $occurredAt, ?int $location = null): int
    {
        $now = now();
        return DB::table('inventory_movements')->insertGetId([
            'company_id' => $f['company'], 'goods_id' => $f['goods'],
            'location_id' => $location ?? $f['from'], 'quantity' => $quantity,
            'movement_type' => 'production_consumption', 'reference_type' => 'production_shift_reports',
            'reference_id' => 9001, 'occurred_at' => $occurredAt, 'metadata' => json_encode(['fiscal_year_id' => $f['fy']]),
            'created_at' => $now, 'updated_at' => $now,
        ]);
    }

    public function test_fifo_and_weighted_average_calculate_expected_cost_for_the_same_receipts(): void
    {
        $f = $this->fixture();
        $this->layer($f, 10, 100, '2026-04-01 09:00:00');
        $this->layer($f, 10, 200, '2026-04-02 09:00:00');
        $movement = $this->movement($f, -12, '2026-04-03 09:00:00');

        $fifo = app(HistoricalInventoryValuationReportService::class)->calculate($f['company'], 'fifo');
        $average = app(HistoricalInventoryValuationReportService::class)->calculate($f['company'], 'weighted_average');

        $this->assertSame(1400.0, $fifo[$movement]['total_cost']);
        $this->assertSame(1800.0, $average[$movement]['total_cost']);
        $this->assertSame(12.0, $fifo[$movement]['valued_quantity']);
        $this->assertSame(12.0, $average[$movement]['valued_quantity']);
        $this->assertSame(0, DB::table('inventory_consumption_costs')->where('company_id', $f['company'])->count());
        $this->assertSame(20.0, (float) DB::table('inventory_cost_layers')->where('company_id', $f['company'])->sum('fifo_quantity_remaining'));
        $this->assertSame(20.0, (float) DB::table('inventory_cost_layers')->where('company_id', $f['company'])->sum('weighted_average_quantity_remaining'));
    }

    public function test_repeated_weighted_average_consumption_keeps_remaining_pool_rate_stable(): void
    {
        $f = $this->fixture('weighted_average');
        $this->layer($f, 100, 10, '2026-04-01 09:00:00');
        $this->layer($f, 100, 15, '2026-04-02 09:00:00');
        $first = $this->movement($f, -120, '2026-04-03 09:00:00');
        $second = $this->movement($f, -20, '2026-04-04 09:00:00');
        $service = app(InventoryValuationService::class);

        $firstResult = $service->valueInventoryMovement($first, $f['fy']);
        $secondResult = $service->valueInventoryMovement($second, $f['fy']);

        $this->assertEqualsWithDelta(1500.0, $firstResult['total_cost'], 0.0001);
        $this->assertEqualsWithDelta(250.0, $secondResult['total_cost'], 0.0001);
        $this->assertEqualsWithDelta(12.5, $secondResult['unit_cost'], 0.0001);
        $remaining = (float) DB::table('inventory_cost_layers')->where('company_id', $f['company'])->sum('weighted_average_quantity_remaining');
        $this->assertEqualsWithDelta(60.0, $remaining, 0.0001);
        $this->assertSame(2, DB::table('inventory_consumption_costs')->where('company_id', $f['company'])->count());
        $this->assertSame(140.0, (float) DB::table('inventory_movements')->where('company_id', $f['company'])->sum(DB::raw('ABS(quantity)')));
    }

    public function test_transfer_moves_valued_layer_quantities_between_locations_without_consumption_or_movement_mutation(): void
    {
        $f = $this->fixture();
        $this->layer($f, 10, 20, '2026-04-01 09:00:00');
        $movementCountBefore = DB::table('inventory_movements')->where('company_id', $f['company'])->count();
        $costCountBefore = DB::table('inventory_consumption_costs')->where('company_id', $f['company'])->count();

        app(InventoryValuationService::class)->transferLayers($f['company'], $f['goods'], $f['from'], $f['to'], 6);

        $sourceQty = (float) DB::table('inventory_cost_layers')->where('company_id', $f['company'])->where('location_id', $f['from'])->sum('fifo_quantity_remaining');
        $destinationQty = (float) DB::table('inventory_cost_layers')->where('company_id', $f['company'])->where('location_id', $f['to'])->sum('fifo_quantity_remaining');
        $totalValue = (float) DB::table('inventory_cost_layers')->where('company_id', $f['company'])->selectRaw('SUM(fifo_quantity_remaining * unit_cost) as v')->value('v');

        $this->assertEqualsWithDelta(4.0, $sourceQty, 0.0001);
        $this->assertEqualsWithDelta(6.0, $destinationQty, 0.0001);
        $this->assertEqualsWithDelta(200.0, $totalValue, 0.0001);
        $this->assertSame($movementCountBefore, DB::table('inventory_movements')->where('company_id', $f['company'])->count());
        $this->assertSame($costCountBefore, DB::table('inventory_consumption_costs')->where('company_id', $f['company'])->count());
    }

    public function test_partial_consumption_values_only_traceable_quantity_and_keeps_remainder_explicitly_unvalued(): void
    {
        $f = $this->fixture();
        $this->layer($f, 4, 10, '2026-04-01 09:00:00');
        $movement = $this->movement($f, -6, '2026-04-02 09:00:00');
        $movementCountBefore = DB::table('inventory_movements')->where('company_id', $f['company'])->count();

        $result = app(InventoryValuationService::class)->valueInventoryMovement($movement, $f['fy']);

        $this->assertTrue($result['valued']);
        $this->assertEqualsWithDelta(4.0, $result['valued_quantity'], 0.0001);
        $this->assertEqualsWithDelta(2.0, $result['unvalued_quantity'], 0.0001);
        $this->assertEqualsWithDelta(40.0, $result['total_cost'], 0.0001);
        $this->assertDatabaseHas('inventory_consumption_costs', [
            'inventory_movement_id' => $movement, 'quantity' => 4, 'total_cost' => 40,
        ]);
        $this->assertSame($movementCountBefore, DB::table('inventory_movements')->where('company_id', $f['company'])->count());
        $this->assertSame(1, DB::table('inventory_consumption_cost_items')->whereIn('inventory_consumption_cost_id', function ($query) use ($movement) {
            $query->select('id')->from('inventory_consumption_costs')->where('inventory_movement_id', $movement);
        })->count());

        $repeat = app(InventoryValuationService::class)->valueInventoryMovement($movement, $f['fy']);
        $this->assertEqualsWithDelta(40.0, $repeat['total_cost'], 0.0001);
        $this->assertSame(1, DB::table('inventory_consumption_costs')->where('inventory_movement_id', $movement)->count());
        $this->assertSame($movementCountBefore, DB::table('inventory_movements')->where('company_id', $f['company'])->count());
    }

    public function test_historical_replay_is_read_only_even_when_movement_is_partially_unvalued(): void
    {
        $f = $this->fixture();
        $this->layer($f, 3, 12, '2026-04-01 09:00:00');
        $movement = $this->movement($f, -5, '2026-04-02 09:00:00');
        $layersBefore = DB::table('inventory_cost_layers')->where('company_id', $f['company'])->orderBy('id')->get()->toArray();
        $movementsBefore = DB::table('inventory_movements')->where('company_id', $f['company'])->orderBy('id')->get()->toArray();

        $result = app(HistoricalInventoryValuationReportService::class)->calculate($f['company'], 'fifo');

        $this->assertEqualsWithDelta(36.0, $result[$movement]['total_cost'], 0.0001);
        $this->assertEqualsWithDelta(3.0, $result[$movement]['valued_quantity'], 0.0001);
        $this->assertEqualsWithDelta(2.0, $result[$movement]['unvalued_quantity'], 0.0001);
        $this->assertSame(0, DB::table('inventory_consumption_costs')->where('company_id', $f['company'])->count());
        $this->assertEquals($layersBefore, DB::table('inventory_cost_layers')->where('company_id', $f['company'])->orderBy('id')->get()->toArray());
        $this->assertEquals($movementsBefore, DB::table('inventory_movements')->where('company_id', $f['company'])->orderBy('id')->get()->toArray());
    }
}
