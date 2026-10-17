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
        foreach (['supply_request.handover.create', 'supply_request.handover.view', 'location.create'] as $slug) {
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
        $inventoryId = DB::table('inventory')->insertGetId([
            'company_id' => $company, 'location_id' => $warehouse, 'goods_id' => $goods, 'quantity' => 10, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('inventory_movements')->insert(['company_id'=>$company,'location_id'=>$warehouse,'goods_id'=>$goods,'quantity'=>10,'movement_type'=>'opening_balance','reference_type'=>'inventory','reference_id'=>$inventoryId,'occurred_at'=>now(),'metadata'=>json_encode(['source'=>'test opening balance']),'created_at'=>now(),'updated_at'=>now()]);
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

    public function test_location_form_uses_supported_location_types(): void
    {
        $this->context();

        $this->get('/master/locations/create')
            ->assertOk()
            ->assertSee('name="type"', false)
            ->assertSee('value="مواد اولیه"', false)
            ->assertSee('value="مواد"', false)
            ->assertSee('value="محصول"', false)
            ->assertSee('value="ضایعات"', false);
    }

    public function test_location_form_rejects_unrecognized_types(): void
    {
        $this->context();

        $this->from('/master/locations/create')->post('/master/locations', [
            'code' => 'BADTYPE',
            'name' => 'Invalid Location',
            'type' => 'random-type',
        ])->assertRedirect('/master/locations/create')->assertSessionHasErrors('type');

        $this->assertDatabaseMissing('locations', ['code' => 'BADTYPE']);
    }

    public function test_material_handover_page_lists_material_location_type(): void
    {
        $c = $this->context();
        DB::table('locations')->where('id', $c['warehouse'])->update(['type' => 'مواد']);

        $this->get('/handovers')
            ->assertOk()
            ->assertSee('انتخاب تاریخ شمسی')
            ->assertSee('jquery@3.7.1/dist/jquery.min.js', false)
            ->assertSee('persian-date@1.1.0/dist/persian-date.min.js', false)
            ->assertSee('persian-datepicker@1.2.0/dist/js/persian-datepicker.min.js', false)
            ->assertSee('persian-datepicker@1.2.0/dist/css/persian-datepicker.min.css', false)
            ->assertSee('Warehouse')
            ->assertDontSee('هیچ انبار فعال برای این شرکت پیدا نشد');
    }

    public function test_material_handover_form_includes_production_section_and_stage_fields(): void
    {
        $this->context();

        $this->get('/handovers')
            ->assertOk()
            ->assertSee('name="production_section_id"', false)
            ->assertSee('name="production_stage_id"', false)
            ->assertSee('قسمت تولید')
            ->assertSee('مرحله تولید');
    }

    public function test_handover_lists_named_production_sections_separately_from_warehouse_locations(): void
    {
        $c = $this->context();
        $sectionLocation = DB::table('locations')->insertGetId([
            'company_id' => $c['company'], 'code' => 'CELL1', 'name' => 'Production Cell Location',
            'type' => 'مواد', 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $sectionId = DB::table('production_sections')->insertGetId([
            'name' => 'Cutting Section', 'location_id' => $sectionLocation, 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('locations')->insert([
            'company_id' => $c['company'], 'code' => 'RAW2', 'name' => 'Raw Warehouse',
            'type' => 'مواد اولیه', 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $routeId = DB::table('production_routes')->insertGetId([
            'company_id' => $c['company'], 'goods_id' => $c['goods'], 'code' => 'ROUTE1',
            'name' => 'Cutting Route', 'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('production_stages')->insert([
            'production_route_id' => $routeId, 'production_section_id' => $sectionId,
            'code' => 'STAGE1', 'name' => 'Cutting Stage', 'sequence' => 1, 'status' => 'active',
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->get('/handovers')
            ->assertOk()
            ->assertSee('Cutting Section')
            ->assertSee('Cutting Stage')
            ->assertSee('Raw Warehouse')
            ->assertDontSee('Production Cell Location')
            ->assertDontSee('Cutting Stage — Cutting Route');
    }

    public function test_material_handover_accepts_material_location_type(): void
    {
        $c = $this->context();
        DB::table('locations')->where('id', $c['warehouse'])->update(['type' => 'مواد']);
        $requestId = DB::table('supply_requests')->latest('id')->value('id');

        $response = $this->postJson('/api/material-handovers', [
            'supply_request_id' => $requestId,
            'warehouse_location_id' => $c['warehouse'],
            'delivered_by_user_id' => $c['user'],
            'received_by_user_id' => $c['user'],
            'handed_over_at' => '2026-10-16 10:00:00',
            'items' => [['goods_id' => $c['goods'], 'quantity' => 4]],
        ]);
        $response->assertCreated()->assertJsonPath('data.status', 'completed');
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
        $this->assertDatabaseHas('inventory', ['location_id' => $c['warehouse'], 'goods_id' => $c['goods'], 'quantity' => 10]);
        $this->assertSame(6.0,(float)DB::table('inventory_movements')->where('company_id',$c['company'])->where('location_id',$c['warehouse'])->where('goods_id',$c['goods'])->sum('quantity'));
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
