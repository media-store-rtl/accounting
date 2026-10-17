<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PurchaseReceiptWarehouseOptionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_receipts_page_lists_active_warehouses_ignoring_case_and_outer_spaces(): void
    {
        $account = DB::table('accounts')->insertGetId([
            'name' => 'Receipt Account', 'code' => 'RA'.uniqid(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $company = DB::table('companies')->insertGetId([
            'account_id' => $account, 'name' => 'Receipt Company', 'code' => 'RC'.uniqid(),
            'is_active' => true, 'settings' => json_encode([]), 'inventory_valuation_method' => 'fifo',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $userId = DB::table('users')->insertGetId([
            'account_id' => $account, 'name' => 'Receipt User', 'username' => 'ru'.uniqid(),
            'email' => uniqid().'@test.local', 'password' => Hash::make('secret'),
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $role = DB::table('roles')->insertGetId([
            'company_id' => $company, 'name' => 'Receipt Role', 'slug' => 'rr'.uniqid(),
            'is_system' => false, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $permission = DB::table('permissions')->insertGetId([
            'name' => 'Create purchase receipt', 'slug' => 'purchase.receipt.create', 'module' => 'purchase',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('role_permissions')->insert(['role_id' => $role, 'permission_id' => $permission]);
        DB::table('company_user')->insert([
            'company_id' => $company, 'user_id' => $userId, 'role_id' => $role, 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        DB::table('locations')->insert([
            ['company_id' => $company, 'code' => 'W1', 'name' => 'Warehouse Uppercase', 'type' => 'مواد اولیه', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['company_id' => $company, 'code' => 'W2', 'name' => 'Warehouse Spaces', 'type' => ' مواد اولیه ', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['company_id' => $company, 'code' => 'W4', 'name' => 'انبار مواد اولیه', 'type' => 'مواد اولیه', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['company_id' => $company, 'code' => 'W5', 'name' => 'انبار1', 'type' => 'مواد اولیه', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['company_id' => $company, 'code' => 'W3', 'name' => 'Inactive Warehouse', 'type' => 'مواد اولیه', 'is_active' => false, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $categoryId = DB::table('goods_categories')->insertGetId([
            'company_id' => $company, 'name' => 'مواد اولیه', 'code' => 'CAT'.uniqid(),
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $unitId = DB::table('units')->insertGetId([
            'company_id' => $company, 'name' => 'کیلوگرم', 'code' => 'KG'.uniqid(),
            'symbol' => 'kg', 'unit_type' => 'weight', 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $goodsId = DB::table('goods')->insertGetId([
            'company_id' => $company, 'category_id' => $categoryId, 'code' => 'MAT-001',
            'name' => 'مواد آزمایشی', 'purchasable' => true, 'producible' => false,
            'sellable' => false, 'is_active' => true, 'attributes' => json_encode([]),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('goods_units')->insert([
            'goods_id' => $goodsId, 'unit_id' => $unitId, 'conversion_factor' => 1,
            'is_base' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $otherAccount = DB::table('accounts')->insertGetId([
            'name' => 'Other Account', 'code' => 'OA'.uniqid(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        $otherCompany = DB::table('companies')->insertGetId([
            'account_id' => $otherAccount, 'name' => 'Other Company', 'code' => 'OC'.uniqid(),
            'is_active' => true, 'settings' => json_encode([]), 'inventory_valuation_method' => 'fifo',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('locations')->insert([
            'company_id' => $otherCompany, 'code' => 'OW1', 'name' => 'Other Company Warehouse',
            'type' => 'مواد اولیه', 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs(User::findOrFail($userId))->withSession(['company_id' => $company])
            ->get('/receipts')
            ->assertOk()
            ->assertSee('Warehouse Uppercase')
            ->assertSee('Warehouse Spaces')
            ->assertSee('انبار مواد اولیه')
            ->assertSee('انبار1')
            ->assertSee('data-unit-label="کیلوگرم (kg)"', false)
            ->assertSee('واحد انتخابی کالا')
            ->assertDontSee('Inactive Warehouse')
            ->assertDontSee('Other Company Warehouse');
    }
}
