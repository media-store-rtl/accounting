<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GoodsMeasurementUnitTest extends TestCase
{
    use RefreshDatabase;

    private function prepareGoodsForm(): array
    {
        $now = now();
        $accountId = DB::table('accounts')->insertGetId([
            'name' => 'Unit Test Account',
            'code' => 'UTA'.uniqid(),
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $companyId = DB::table('companies')->insertGetId([
            'account_id' => $accountId,
            'name' => 'Unit Test Company',
            'code' => 'UTC'.uniqid(),
            'is_active' => true,
            'settings' => json_encode([]),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $userId = DB::table('users')->insertGetId([
            'account_id' => $accountId,
            'name' => 'Unit Tester',
            'username' => 'ut'.uniqid(),
            'email' => uniqid().'@test.local',
            'password' => bcrypt('secret'),
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $roleId = DB::table('roles')->insertGetId([
            'company_id' => $companyId,
            'name' => 'Unit Test Role',
            'slug' => 'unit-test-'.uniqid(),
            'is_system' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        foreach (['goods.create', 'goods.update', 'goods.view'] as $slug) {
            $permissionId = DB::table('permissions')->insertGetId([
                'name' => $slug,
                'slug' => $slug,
                'module' => 'goods',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('role_permissions')->insert([
                'role_id' => $roleId,
                'permission_id' => $permissionId,
            ]);
        }
        DB::table('company_user')->insert([
            'company_id' => $companyId,
            'user_id' => $userId,
            'role_id' => $roleId,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $categoryId = DB::table('goods_categories')->insertGetId([
            'company_id' => $companyId,
            'name' => 'مواد اولیه',
            'code' => 'CAT'.uniqid(),
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $unitId = DB::table('units')->insertGetId([
            'company_id' => $companyId,
            'name' => 'کیلوگرم',
            'code' => 'KG'.uniqid(),
            'symbol' => 'kg',
            'unit_type' => 'weight',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->actingAs(User::findOrFail($userId))->withSession(['company_id' => $companyId]);

        return compact('companyId', 'categoryId', 'unitId', 'accountId');
    }

    public function test_goods_store_and_edit_persist_the_base_measurement_unit(): void
    {
        $ids = $this->prepareGoodsForm();
        $payload = [
            'code' => 'MAT-001',
            'name' => 'مواد آزمایشی',
            'category_id' => $ids['categoryId'],
            'unit_id' => $ids['unitId'],
            'purchasable' => '1',
            'producible' => '0',
            'sellable' => '0',
            'description' => 'test',
        ];

        $this->post(route('master.store', 'goods'), $payload)
            ->assertRedirect(route('master.index', 'goods'));

        $goodsId = (int) DB::table('goods')->where('company_id', $ids['companyId'])->where('code', 'MAT-001')->value('id');
        $this->assertGreaterThan(0, $goodsId);
        $this->assertDatabaseHas('goods_units', [
            'goods_id' => $goodsId,
            'unit_id' => $ids['unitId'],
            'conversion_factor' => 1,
            'is_base' => true,
        ]);

        $secondUnitId = DB::table('units')->insertGetId([
            'company_id' => $ids['companyId'],
            'name' => 'گرم',
            'code' => 'GR'.uniqid(),
            'symbol' => 'g',
            'unit_type' => 'weight',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $payload['unit_id'] = $secondUnitId;
        $this->put(route('master.update', ['goods', $goodsId]), $payload)
            ->assertRedirect(route('master.index', 'goods'));

        $this->assertDatabaseHas('goods_units', [
            'goods_id' => $goodsId,
            'unit_id' => $ids['unitId'],
            'is_base' => false,
        ]);
        $this->assertDatabaseHas('goods_units', [
            'goods_id' => $goodsId,
            'unit_id' => $secondUnitId,
            'conversion_factor' => 1,
            'is_base' => true,
        ]);

        $this->get(route('master.index', 'goods'))
            ->assertOk()
            ->assertSee('گرم')
            ->assertSee('(g)');
    }

    public function test_goods_reject_a_measurement_unit_from_another_company(): void
    {
        $ids = $this->prepareGoodsForm();
        $otherAccountId = DB::table('accounts')->insertGetId([
            'name' => 'Other Unit Account',
            'code' => 'OUA'.uniqid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $otherCompanyId = DB::table('companies')->insertGetId([
            'account_id' => $otherAccountId,
            'name' => 'Other Unit Company',
            'code' => 'OUC'.uniqid(),
            'is_active' => true,
            'settings' => json_encode([]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $foreignUnitId = DB::table('units')->insertGetId([
            'company_id' => $otherCompanyId,
            'name' => 'Foreign Unit',
            'code' => 'FU'.uniqid(),
            'symbol' => 'fu',
            'unit_type' => 'count',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->post(route('master.store', 'goods'), [
            'code' => 'MAT-FOREIGN',
            'name' => 'Invalid Unit Good',
            'category_id' => $ids['categoryId'],
            'unit_id' => $foreignUnitId,
        ])->assertStatus(422);

        $this->assertDatabaseMissing('goods', [
            'company_id' => $ids['companyId'],
            'code' => 'MAT-FOREIGN',
        ]);
    }
}
