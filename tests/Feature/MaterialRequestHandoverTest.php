<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MaterialRequestHandoverTest extends TestCase
{
    use RefreshDatabase;

    private function context(): array
    {
        $account = DB::table('accounts')->insertGetId([
            'name' => 'Test Account', 'code' => 'ACC'.uniqid(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        $company = DB::table('companies')->insertGetId([
            'account_id' => $account, 'name' => 'Test Company', 'code' => 'COM'.uniqid(), 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $user = DB::table('users')->insertGetId([
            'account_id' => $account, 'name' => 'Tester', 'username' => 'u'.uniqid(), 'email' => uniqid().'@test.local',
            'password' => Hash::make('secret'), 'created_at' => now(), 'updated_at' => now(),
        ]);
        $role = DB::table('roles')->insertGetId([
            'company_id' => $company, 'name' => 'Production', 'slug' => 'production', 'is_system' => false, 'created_at' => now(), 'updated_at' => now(),
        ]);
        foreach (['supply_request.handover.create', 'supply_request.handover.view'] as $slug) {
            $permission = DB::table('permissions')->insertGetId([
                'name' => $slug, 'slug' => $slug, 'module' => 'production-supply', 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('role_permissions')->insert(['role_id' => $role, 'permission_id' => $permission]);
        }
        DB::table('company_user')->insert([
            'company_id' => $company, 'user_id' => $user, 'role_id' => $role, 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $fy = DB::table('fiscal_years')->insertGetId([
            'company_id' => $company, 'name' => '1405', 'code' => 'FY1405', 'starts_at' => '2026-03-21', 'ends_at' => '2027-03-20',
            'is_closed' => false, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $category = DB::table('goods_categories')->insertGetId([
            'company_id' => $company, 'name' => 'Raw', 'code' => 'RAW', 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $goods = DB::table('goods')->insertGetId([
            'company_id' => $company, 'category_id' => $category, 'code' => 'MAT1', 'name' => 'Material',
            'purchasable' => true, 'producible' => false, 'sellable' => false, 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $warehouse = DB::table('locations')->insertGetId([
            'company_id' => $company, 'code' => 'W1', 'name' => 'Warehouse', 'type' => 'warehouse', 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('inventory')->insert([
            'company_id' => $company, 'location_id' => $warehouse, 'goods_id' => $goods, 'quantity' => 10, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('supply_requests')->insert([
            'company_id' => $company, 'fiscal_year_id' => $fy, 'requested_by_user_id' => $user, 'requested_at' => now(),
            'needed_at' => '2026-10-10', 'status' => 'stock_available', 'notes' => null, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $requestId = DB::table('supply_requests')->latest('id')->value('id');
        DB::table('supply_request_items')->insert([
            'supply_request_id' => $requestId, 'goods_id' => $goods, 'requested_quantity' => 4, 'available_quantity' => 4,
            'shortage_quantity' => 0, 'supplied_quantity' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->actingAs(\App\Models\User::findOrFail($user))->withSession(['company_id' => $company]);
        return compact('company', 'user', 'fy', 'goods', 'warehouse');
    }

    public function test_material_handover_records_deliverer_receiver_and_decreases_inventory(): void
    {
        $c = $this->context();
        $requestId = DB::table('supply_requests')->latest('id')->value('id');
        $response = $this->postJson('/api/material-handovers', [
            'supply_request_id' => $requestId, 'warehouse_location_id' => $c['warehouse'],
            'delivered_by_user_id' => $c['user'], 'received_by_user_id' => $c['user'],
            'handed_over_at' => '2026-10-07 10:00:00', 'items' => [['goods_id' => $c['goods'], 'quantity' => 4]],
        ]);
        $response->assertCreated()->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.delivered_by_user_id', $c['user'])->assertJsonPath('data.received_by_user_id', $c['user']);
        $this->assertDatabaseHas('inventory', ['location_id' => $c['warehouse'], 'goods_id' => $c['goods'], 'quantity' => 6]);
        $this->assertDatabaseHas('inventory_movements', ['goods_id' => $c['goods'], 'location_id' => $c['warehouse'], 'movement_type' => 'material_handover', 'quantity' => -4, 'reference_type' => 'material_handovers']);
        $this->assertDatabaseHas('supply_request_items', ['supply_request_id' => $requestId, 'goods_id' => $c['goods'], 'supplied_quantity' => 4]);
        $this->assertDatabaseHas('supply_requests', ['id' => $requestId, 'status' => 'fulfilled']);
    }

    public function test_material_handover_rejects_insufficient_inventory_without_mutation(): void
    {
        $c = $this->context();
        $requestId = DB::table('supply_requests')->latest('id')->value('id');
        $this->postJson('/api/material-handovers', [
            'supply_request_id' => $requestId, 'warehouse_location_id' => $c['warehouse'],
            'delivered_by_user_id' => $c['user'], 'received_by_user_id' => $c['user'],
            'handed_over_at' => '2026-10-07 10:00:00', 'items' => [['goods_id' => $c['goods'], 'quantity' => 11]],
        ])->assertStatus(422);
        $this->assertDatabaseHas('inventory', ['location_id' => $c['warehouse'], 'goods_id' => $c['goods'], 'quantity' => 10]);
        $this->assertDatabaseCount('material_handovers', 0);
        $this->assertDatabaseHas('supply_requests', ['id' => $requestId, 'status' => 'stock_available']);
    }
}
