<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WarehouseDeliveryActionVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_users_with_issue_permission_see_warehouse_exit_action(): void
    {
        $account = DB::table('accounts')->insertGetId([
            'name' => 'Test Account', 'code' => 'ACC'.uniqid(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        $company = DB::table('companies')->insertGetId([
            'account_id' => $account, 'name' => 'Test Company', 'code' => 'COM'.uniqid(),
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $viewPermission = DB::table('permissions')->where('slug', 'delivery_request.view')->first();
        if (!$viewPermission) {
            $viewPermission = (object) ['id' => DB::table('permissions')->insertGetId([
                'name' => 'delivery_request.view', 'slug' => 'delivery_request.view', 'module' => 'delivery',
                'created_at' => now(), 'updated_at' => now(),
            ])];
        }
        $issuePermission = DB::table('permissions')->where('slug', 'delivery_request.issue')->first();
        if (!$issuePermission) {
            $issuePermission = (object) ['id' => DB::table('permissions')->insertGetId([
                'name' => 'delivery_request.issue', 'slug' => 'delivery_request.issue', 'module' => 'delivery',
                'created_at' => now(), 'updated_at' => now(),
            ])];
        }

        $warehouseRole = DB::table('roles')->insertGetId([
            'company_id' => $company, 'name' => 'Warehouse', 'slug' => 'warehouse-test-'.uniqid(),
            'is_system' => false, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $salesRole = DB::table('roles')->insertGetId([
            'company_id' => $company, 'name' => 'Sales', 'slug' => 'sales-test-'.uniqid(),
            'is_system' => false, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('role_permissions')->insert([
            ['role_id' => $warehouseRole, 'permission_id' => $viewPermission->id],
            ['role_id' => $warehouseRole, 'permission_id' => $issuePermission->id],
            ['role_id' => $salesRole, 'permission_id' => $viewPermission->id],
        ]);

        $warehouseUser = DB::table('users')->insertGetId([
            'account_id' => $account, 'name' => 'Warehouse User', 'username' => 'warehouse'.uniqid(),
            'email' => uniqid().'@test.local', 'password' => bcrypt('secret'), 'created_at' => now(), 'updated_at' => now(),
        ]);
        $salesUser = DB::table('users')->insertGetId([
            'account_id' => $account, 'name' => 'Sales User', 'username' => 'sales'.uniqid(),
            'email' => uniqid().'@test.local', 'password' => bcrypt('secret'), 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('company_user')->insert([
            ['company_id' => $company, 'user_id' => $warehouseUser, 'role_id' => $warehouseRole, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['company_id' => $company, 'user_id' => $salesUser, 'role_id' => $salesRole, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $customer = DB::table('customers')->insertGetId([
            'company_id' => $company, 'name' => 'Customer', 'code' => 'CU'.uniqid(), 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $order = DB::table('orders')->insertGetId([
            'company_id' => $company, 'customer_id' => $customer, 'number' => 'SO-'.uniqid(),
            'status' => 'delivery_requested', 'requested_delivery_at' => '2026-10-20',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $delivery = DB::table('delivery_requests')->insertGetId([
            'company_id' => $company, 'order_id' => $order, 'customer_id' => $customer,
            'requested_by_user_id' => $salesUser, 'requested_at' => now(), 'scheduled_at' => now(),
            'recipient_name' => 'Receiver', 'status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs(User::findOrFail($warehouseUser))->withSession(['company_id' => $company]);
        $this->get('/sales/deliveries')->assertOk()->assertSee('ثبت خروج از انبار');

        $this->actingAs(User::findOrFail($salesUser))->withSession(['company_id' => $company]);
        $this->get('/sales/deliveries')->assertOk()->assertDontSee('ثبت خروج از انبار');
    }
}
