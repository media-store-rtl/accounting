<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProductionOutputWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function context(): array
    {
        $account = DB::table('accounts')->insertGetId(['name' => 'Output Test', 'code' => 'ACC'.uniqid(), 'created_at' => now(), 'updated_at' => now()]);
        $user = DB::table('users')->insertGetId(['account_id' => $account, 'name' => 'Output User', 'username' => 'output'.uniqid(), 'email' => uniqid().'@test.local', 'password' => Hash::make('secret'), 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        $company = DB::table('companies')->insertGetId(['account_id' => $account, 'name' => 'Output Company', 'code' => 'COM'.uniqid(), 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        $role = DB::table('roles')->insertGetId(['company_id' => $company, 'name' => 'Production', 'slug' => 'production', 'is_system' => false, 'created_at' => now(), 'updated_at' => now()]);
        foreach (['production.output.view', 'production.output.create', 'production.output.confirm', 'production.output.reject'] as $slug) {
            $permission = DB::table('permissions')->where('slug', $slug)->value('id');
            DB::table('role_permissions')->insert(['role_id' => $role, 'permission_id' => $permission]);
        }
        DB::table('company_user')->insert(['company_id' => $company, 'user_id' => $user, 'role_id' => $role, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        $fy = DB::table('fiscal_years')->insertGetId(['company_id' => $company, 'name' => '1405', 'code' => 'FY'.uniqid(), 'starts_at' => '2026-03-21', 'ends_at' => '2027-03-20', 'is_closed' => false, 'created_at' => now(), 'updated_at' => now()]);
        $category = DB::table('goods_categories')->insertGetId(['company_id' => $company, 'name' => 'Finished', 'code' => 'FG'.uniqid(), 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        $goods = DB::table('goods')->insertGetId(['company_id' => $company, 'category_id' => $category, 'code' => 'FG1', 'name' => 'Finished Product', 'purchasable' => false, 'producible' => true, 'sellable' => true, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        $warehouse = DB::table('locations')->insertGetId(['company_id' => $company, 'code' => 'W1', 'name' => 'Finished Warehouse', 'type' => 'warehouse', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        $customer = DB::table('customers')->insertGetId(['company_id' => $company, 'name' => 'Customer', 'code' => 'CU'.uniqid(), 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
        $order = DB::table('orders')->insertGetId(['company_id' => $company, 'fiscal_year_id' => $fy, 'customer_id' => $customer, 'number' => 'O'.uniqid(), 'ordered_at' => '2026-10-07', 'requested_delivery_at' => '2026-10-15', 'status' => 'confirmed', 'created_at' => now(), 'updated_at' => now()]);
        $route = DB::table('production_routes')->insertGetId(['company_id' => $company, 'goods_id' => $goods, 'code' => 'R'.uniqid(), 'name' => 'Standard Route', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
        $production = DB::table('productions')->insertGetId(['company_id' => $company, 'fiscal_year_id' => $fy, 'goods_id' => $goods, 'production_route_id' => $route, 'number' => 'P'.uniqid(), 'planned_quantity' => 10, 'produced_quantity' => 0, 'rejected_quantity' => 0, 'status' => 'completed', 'completed_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
        DB::table('production_order')->insert(['production_id' => $production, 'order_id' => $order, 'created_at' => now(), 'updated_at' => now()]);
        $this->actingAs(User::findOrFail($user))->withSession(['company_id' => $company]);
        return compact('company', 'user', 'fy', 'goods', 'warehouse', 'order', 'production');
    }

    public function test_completed_production_output_reaches_finished_inventory_only_after_confirmation(): void
    {
        $c = $this->context();

        $this->get('/production/outputs')->assertOk()->assertSee('ورود کالای ساخته‌شده');

        $this->postJson('/api/production/outputs', [
            'production_id' => $c['production'], 'order_id' => $c['order'], 'warehouse_location_id' => $c['warehouse'],
            'quantity' => 6, 'produced_at' => '2026-10-07 10:00:00',
        ])->assertCreated()->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseMissing('inventory', ['location_id' => $c['warehouse'], 'goods_id' => $c['goods'], 'quantity' => 6]);

        $outputId = DB::table('production_outputs')->where('production_id', $c['production'])->value('id');
        $this->postJson('/api/production/outputs/'.$outputId.'/confirm', ['received_at' => '2026-10-07 11:00:00'])
            ->assertOk()->assertJsonPath('data.status', 'confirmed');

        $this->assertDatabaseHas('inventory', ['location_id' => $c['warehouse'], 'goods_id' => $c['goods'], 'quantity' => 6]);
        $this->assertDatabaseHas('inventory_movements', [
            'goods_id' => $c['goods'], 'location_id' => $c['warehouse'], 'quantity' => 6,
            'movement_type' => 'finished_goods_receipt', 'reference_type' => 'production_outputs', 'reference_id' => $outputId,
        ]);
        $this->assertDatabaseHas('production_outputs', ['id' => $outputId, 'production_id' => $c['production'], 'order_id' => $c['order'], 'status' => 'confirmed']);
        $this->assertDatabaseHas('finished_goods_receipts', ['production_output_id' => $outputId, 'status' => 'approved']);
        $this->assertDatabaseHas('productions', ['id' => $c['production'], 'produced_quantity' => 6]);
    }

    public function test_zero_negative_and_excess_output_quantities_are_rejected(): void
    {
        $c = $this->context();
        foreach ([0, -1, 11] as $quantity) {
            $this->postJson('/api/production/outputs', [
                'production_id' => $c['production'], 'order_id' => $c['order'], 'warehouse_location_id' => $c['warehouse'],
                'quantity' => $quantity, 'produced_at' => '2026-10-07 10:00:00',
            ])->assertStatus(422);
        }
        $this->assertDatabaseCount('production_outputs', 0);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_completed_production_and_server_permission_are_required(): void
    {
        $c = $this->context();
        DB::table('productions')->where('id', $c['production'])->update(['status' => 'in_progress']);
        $this->postJson('/api/production/outputs', [
            'production_id' => $c['production'], 'order_id' => $c['order'], 'warehouse_location_id' => $c['warehouse'],
            'quantity' => 1, 'produced_at' => '2026-10-07 10:00:00',
        ])->assertStatus(422);

        DB::table('productions')->where('id', $c['production'])->update(['status' => 'completed']);
        DB::table('role_permissions')->delete();
        $this->postJson('/api/production/outputs', [
            'production_id' => $c['production'], 'order_id' => $c['order'], 'warehouse_location_id' => $c['warehouse'],
            'quantity' => 1, 'produced_at' => '2026-10-07 10:00:00',
        ])->assertForbidden();
    }
}