<?php

namespace Tests\\Feature;

use App\\Models\\User;
use Illuminate\\Foundation\\Testing\\RefreshDatabase;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Hash;
use Tests\\TestCase;

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
            ['company_id' => $company, 'code' => 'W1', 'name' => 'Warehouse Uppercase', 'type' => 'WAREHOUSE', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['company_id' => $company, 'code' => 'W2', 'name' => 'Warehouse Spaces', 'type' => ' warehouse ', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['company_id' => $company, 'code' => 'W3', 'name' => 'Inactive Warehouse', 'type' => 'warehouse', 'is_active' => false, 'created_at' => now(), 'updated_at' => now()],
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
            'type' => 'warehouse', 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs(User::findOrFail($userId))->withSession(['company_id' => $company])
            ->get('/receipts')
            ->assertOk()
            ->assertSee('Warehouse Uppercase')
            ->assertSee('Warehouse Spaces')
            ->assertDontSee('Inactive Warehouse')
            ->assertDontSee('Other Company Warehouse');
    }
}
