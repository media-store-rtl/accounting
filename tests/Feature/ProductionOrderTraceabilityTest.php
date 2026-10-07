<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProductionOrderTraceabilityTest extends TestCase
{
    use RefreshDatabase;

    private function context(): array
    {
        $account = DB::table('accounts')->insertGetId([
            'name' => 'Test Account',
            'code' => 'ACC'.uniqid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $company = DB::table('companies')->insertGetId([
            'account_id' => $account,
            'name' => 'Test Company',
            'code' => 'COM'.uniqid(),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = DB::table('users')->insertGetId([
            'account_id' => $account,
            'name' => 'Tester',
            'username' => 'u'.uniqid(),
            'email' => uniqid().'@test.local',
            'password' => Hash::make('secret'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $role = DB::table('roles')->insertGetId([
            'company_id' => $company,
            'name' => 'Production',
            'slug' => 'production',
            'is_system' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach (['production.create', 'production.view'] as $slug) {
            $permission = DB::table('permissions')->insertGetId([
                'name' => $slug,
                'slug' => $slug,
                'module' => 'production',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('role_permissions')->insert([
                'role_id' => $role,
                'permission_id' => $permission,
            ]);
        }

        DB::table('company_user')->insert([
            'company_id' => $company,
            'user_id' => $user,
            'role_id' => $role,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $fy = DB::table('fiscal_years')->insertGetId([
            'company_id' => $company,
            'name' => '1405',
            'code' => 'FY'.uniqid(),
            'starts_at' => '2026-03-21',
            'ends_at' => '2027-03-20',
            'is_closed' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $category = DB::table('goods_categories')->insertGetId([
            'company_id' => $company,
            'name' => 'Finished',
            'code' => 'FG'.uniqid(),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $goods = DB::table('goods')->insertGetId([
            'company_id' => $company,
            'category_id' => $category,
            'code' => 'G'.uniqid(),
            'name' => 'Finished Good',
            'purchasable' => false,
            'producible' => true,
            'sellable' => true,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $customer = DB::table('customers')->insertGetId([
            'company_id' => $company,
            'name' => 'Customer',
            'code' => 'C'.uniqid(),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $order = DB::table('orders')->insertGetId([
            'company_id' => $company,
            'fiscal_year_id' => $fy,
            'customer_id' => $customer,
            'number' => 'O'.uniqid(),
            'ordered_at' => '2026-10-07',
            'requested_delivery_at' => '2026-10-20',
            'status' => 'production_required',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $item = DB::table('order_items')->insertGetId([
            'order_id' => $order,
            'goods_id' => $goods,
            'quantity' => 10,
            'available_quantity' => 2,
            'shortage_quantity' => 8,
            'fulfillment_status' => 'production_required',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $route = DB::table('production_routes')->insertGetId([
            'company_id' => $company,
            'goods_id' => $goods,
            'code' => 'R'.uniqid(),
            'name' => 'Default Route',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs(\App\Models\User::findOrFail($user))
            ->withSession(['company_id' => $company]);

        return compact('company', 'user', 'fy', 'goods', 'order', 'item', 'route');
    }

    public function test_creates_production_order_with_exact_order_traceability(): void
    {
        $c = $this->context();

        $response = $this->postJson('/api/production-orders', [
            'order_id' => $c['order'],
            'order_item_id' => $c['item'],
            'production_route_id' => $c['route'],
            'number' => 'PO-1',
            'planned_quantity' => 8,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.order_id', $c['order'])
            ->assertJsonPath('data.order_item_id', $c['item'])
            ->assertJsonPath('data.goods_id', $c['goods'])
            ->assertJsonPath('data.production_route_id', $c['route']);

        $productionId = $response->json('data.id');

        $this->assertDatabaseHas('productions', [
            'id' => $productionId,
            'company_id' => $c['company'],
            'fiscal_year_id' => $c['fy'],
            'order_id' => $c['order'],
            'order_item_id' => $c['item'],
            'goods_id' => $c['goods'],
            'production_route_id' => $c['route'],
            'planned_quantity' => 8,
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $c['order'],
            'status' => 'in_production',
        ]);

        $show = $this->getJson('/api/production-orders/'.$productionId);
        $show->assertOk()
            ->assertJsonPath('data.id', $productionId)
            ->assertJsonPath('data.order.id', $c['order'])
            ->assertJsonPath('data.order_item.id', $c['item'])
            ->assertJsonPath('data.production_route.id', $c['route']);
    }

    public function test_rejects_order_item_that_does_not_belong_to_order(): void
    {
        $c = $this->context();

        $otherItem = DB::table('order_items')->insertGetId([
            'order_id' => DB::table('orders')->insertGetId([
                'company_id' => $c['company'],
                'fiscal_year_id' => $c['fy'],
                'customer_id' => DB::table('customers')->where('company_id', $c['company'])->value('id'),
                'number' => 'O'.uniqid(),
                'ordered_at' => '2026-10-07',
                'requested_delivery_at' => '2026-10-20',
                'status' => 'production_required',
                'created_at' => now(),
                'updated_at' => now(),
            ]),
            'goods_id' => $c['goods'],
            'quantity' => 5,
            'available_quantity' => 0,
            'shortage_quantity' => 5,
            'fulfillment_status' => 'production_required',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->postJson('/api/production-orders', [
            'order_id' => $c['order'],
            'order_item_id' => $otherItem,
            'production_route_id' => $c['route'],
            'number' => 'PO-INVALID',
            'planned_quantity' => 5,
        ])->assertUnprocessable();

        $this->assertDatabaseCount('productions', 0);
    }

    public function test_rejects_planned_quantity_above_order_shortage(): void
    {
        $c = $this->context();

        $this->postJson('/api/production-orders', [
            'order_id' => $c['order'],
            'order_item_id' => $c['item'],
            'production_route_id' => $c['route'],
            'number' => 'PO-OVER',
            'planned_quantity' => 9,
        ])->assertUnprocessable();

        $this->assertDatabaseCount('productions', 0);
    }
}
