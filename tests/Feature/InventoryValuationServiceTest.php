<?php

namespace Tests\Feature;

use App\Services\InventoryValuationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InventoryValuationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_partial_consumption_is_valued_and_can_be_topped_up_later(): void
    {
        $account = DB::table('accounts')->insertGetId([
            'name' => 'Partial Valuation Account',
            'code' => 'PVA'.uniqid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $company = DB::table('companies')->insertGetId([
            'account_id' => $account,
            'name' => 'Partial Valuation Company',
            'code' => 'PVC'.uniqid(),
            'is_active' => true,
            'settings' => json_encode([]),
            'inventory_valuation_method' => 'fifo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $fy = DB::table('fiscal_years')->insertGetId([
            'company_id' => $company,
            'name' => '1405',
            'code' => 'PVFY'.uniqid(),
            'starts_at' => '2026-03-21',
            'ends_at' => '2027-03-20',
            'is_closed' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $supplier = DB::table('suppliers')->insertGetId([
            'company_id' => $company,
            'code' => 'PVS'.uniqid(),
            'name' => 'Partial Valuation Supplier',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $category = DB::table('goods_categories')->insertGetId([
            'company_id' => $company,
            'name' => 'Partial Valuation Category',
            'code' => 'PVCAT'.uniqid(),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $goods = DB::table('goods')->insertGetId([
            'company_id' => $company,
            'category_id' => $category,
            'code' => 'PVM'.uniqid(),
            'name' => 'Partial Valuation Material',
            'purchasable' => true,
            'producible' => false,
            'sellable' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $location = DB::table('locations')->insertGetId([
            'company_id' => $company,
            'code' => 'PVL'.uniqid(),
            'name' => 'Partial Valuation Warehouse',
            'type' => 'warehouse',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $purchase = DB::table('purchases')->insertGetId([
            'company_id' => $company,
            'fiscal_year_id' => $fy,
            'supplier_id' => $supplier,
            'invoice_number' => 'PVINV-1',
            'invoice_date' => '2026-04-01',
            'purchased_at' => '2026-04-01',
            'subtotal' => 20,
            'direct_cost_total' => 0,
            'total_amount' => 20,
            'status' => 'received',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $purchaseItem = DB::table('purchase_items')->insertGetId([
            'purchase_id' => $purchase,
            'goods_id' => $goods,
            'quantity' => 2,
            'unit_price' => 10,
            'line_total' => 20,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $receiver = DB::table('users')->insertGetId([
            'account_id' => $account,
            'name' => 'Warehouse Receiver',
            'username' => 'pvreceiver'.uniqid(),
            'email' => uniqid().'@test.local',
            'password' => bcrypt('secret'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $receipt = DB::table('purchase_receipts')->insertGetId([
            'company_id' => $company,
            'purchase_id' => $purchase,
            'warehouse_location_id' => $location,
            'received_at' => '2026-04-01 09:00:00',
            'received_by_user_id' => $receiver,
            'approved_at' => '2026-04-01 09:00:00',
            'approved_by_user_id' => null,
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $receiptItem = DB::table('purchase_receipt_items')->insertGetId([
            'purchase_receipt_id' => $receipt,
            'goods_id' => $goods,
            'quantity' => 2,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('inventory_cost_layers')->insert([
            'company_id' => $company,
            'fiscal_year_id' => $fy,
            'goods_id' => $goods,
            'location_id' => $location,
            'purchase_id' => $purchase,
            'purchase_item_id' => $purchaseItem,
            'purchase_receipt_id' => $receipt,
            'purchase_receipt_item_id' => $receiptItem,
            'quantity_received' => 2,
            'fifo_quantity_remaining' => 2,
            'weighted_average_quantity_remaining' => 2,
            'unit_cost' => 10,
            'total_cost' => 20,
            'received_at' => '2026-04-01 09:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $movement = DB::table('inventory_movements')->insertGetId([
            'company_id' => $company,
            'goods_id' => $goods,
            'location_id' => $location,
            'quantity' => -5,
            'movement_type' => 'production_consumption',
            'reference_type' => 'production_shift_reports',
            'reference_id' => 1,
            'occurred_at' => '2026-04-02 09:00:00',
            'metadata' => json_encode(['fiscal_year_id' => $fy]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = app(InventoryValuationService::class);
        $first = $service->valueInventoryMovement($movement, $fy);

        $this->assertTrue($first['valued']);
        $this->assertSame(2.0, (float) $first['valued_quantity']);
        $this->assertSame(3.0, (float) $first['unvalued_quantity']);
        $this->assertDatabaseHas('inventory_consumption_costs', [
            'inventory_movement_id' => $movement,
            'quantity' => 2,
            'total_cost' => 20,
        ]);

        DB::table('inventory_cost_layers')->insert([
            'company_id' => $company,
            'fiscal_year_id' => $fy,
            'goods_id' => $goods,
            'location_id' => $location,
            'purchase_id' => $purchase,
            'purchase_item_id' => $purchaseItem,
            'purchase_receipt_id' => $receipt,
            'purchase_receipt_item_id' => $receiptItem,
            'quantity_received' => 3,
            'fifo_quantity_remaining' => 3,
            'weighted_average_quantity_remaining' => 3,
            'unit_cost' => 12,
            'total_cost' => 36,
            'received_at' => '2026-04-02 08:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $second = $service->valueInventoryMovement($movement, $fy);

        $this->assertTrue($second['valued']);
        $this->assertSame(5.0, (float) $second['valued_quantity']);
        $this->assertSame(0.0, (float) $second['unvalued_quantity']);
        $this->assertDatabaseHas('inventory_consumption_costs', [
            'inventory_movement_id' => $movement,
            'quantity' => 5,
            'total_cost' => 56,
        ]);
        $this->assertSame(2, DB::table('inventory_consumption_cost_items')->where('inventory_consumption_cost_id', DB::table('inventory_consumption_costs')->where('inventory_movement_id', $movement)->value('id'))->count());
    }
}
