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
        $fiscalYearId = DB::table('fiscal_years')->insertGetId([
            'company_id' => $company, 'name' => 'سال آزمایشی', 'code' => 'FY'.uniqid(),
            'starts_at' => '2026-01-01', 'ends_at' => '2026-12-31',
            'is_closed' => false, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $supplierId = DB::table('suppliers')->insertGetId([
            'company_id' => $company, 'name' => 'تأمین‌کننده آزمایشی', 'code' => 'SUP'.uniqid(),
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $purchaseId = DB::table('purchases')->insertGetId([
            'company_id' => $company, 'fiscal_year_id' => $fiscalYearId, 'supplier_id' => $supplierId,
            'invoice_number' => null, 'invoice_date' => null, 'purchased_at' => '2026-10-01',
            'subtotal' => 200, 'direct_cost_total' => 0, 'total_amount' => 200,
            'status' => 'received', 'notes' => null, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $receiptId = DB::table('purchase_receipts')->insertGetId([
            'company_id' => $company, 'purchase_id' => $purchaseId,
            'warehouse_location_id' => DB::table('locations')->where('company_id', $company)->where('code', 'W4')->value('id'),
            'received_by_user_id' => $userId, 'received_at' => '2026-10-02 10:00:00',
            'approved_at' => '2026-10-02 10:05:00', 'approved_by_user_id' => $userId,
            'status' => 'approved', 'notes' => null, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('purchase_receipt_items')->insert([
            'purchase_receipt_id' => $receiptId, 'goods_id' => $goodsId, 'quantity' => 2,
            'created_at' => now(), 'updated_at' => now(),
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
            ->assertSee('کیلوگرم (kg)')
            ->assertSee('2.0000')
            ->assertDontSee('Inactive Warehouse')
            ->assertDontSee('Other Company Warehouse');
    }
}
