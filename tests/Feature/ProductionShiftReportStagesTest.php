<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProductionShiftReportStagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_shift_report_stores_multiple_independently_selected_stages(): void
    {
        $account = DB::table('accounts')->insertGetId([
            'name' => 'Stage Report Account', 'code' => 'SRA' . uniqid(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $company = DB::table('companies')->insertGetId([
            'account_id' => $account, 'name' => 'Stage Report Company', 'code' => 'SRC' . uniqid(),
            'is_active' => true, 'settings' => json_encode([]),
            'inventory_valuation_method' => 'fifo', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $user = DB::table('users')->insertGetId([
            'account_id' => $account, 'name' => 'Report Worker', 'username' => 'srw' . uniqid(),
            'email' => uniqid() . '@test.local', 'password' => Hash::make('secret'),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $role = DB::table('roles')->insertGetId([
            'company_id' => $company, 'name' => 'Production', 'slug' => 'production-test',
            'is_system' => false, 'created_at' => now(), 'updated_at' => now(),
        ]);
        foreach (['production.operation.create', 'production.operation.view', 'production.operation.review'] as $slug) {
            $permission = DB::table('permissions')->where('slug', $slug)->first();
            $permissionId = $permission?->id ?? DB::table('permissions')->insertGetId([
                'name' => $slug, 'slug' => $slug, 'module' => 'production',
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('role_permissions')->insertOrIgnore([
                'role_id' => $role, 'permission_id' => $permissionId,
            ]);
        }
        DB::table('company_user')->insert([
            'company_id' => $company, 'user_id' => $user, 'role_id' => $role,
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $worker = DB::table('personnel')->insertGetId([
            'account_id' => $account, 'user_id' => $user, 'code' => 'W' . uniqid(),
            'name' => 'Report Worker', 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $sectionLocation = DB::table('locations')->insertGetId([
            'company_id' => $company, 'code' => 'SEC' . uniqid(), 'name' => 'Cutting',
            'type' => 'production', 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $section = DB::table('production_sections')->insertGetId([
            'location_id' => $sectionLocation, 'supervisor_personnel_id' => $worker,
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $category = DB::table('goods_categories')->insertGetId([
            'company_id' => $company, 'name' => 'Products', 'code' => 'CAT' . uniqid(),
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $goods = DB::table('goods')->insertGetId([
            'company_id' => $company, 'category_id' => $category, 'code' => 'G' . uniqid(),
            'name' => 'Finished Product', 'producible' => true, 'sellable' => true,
            'purchasable' => false, 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $route = DB::table('production_routes')->insertGetId([
            'company_id' => $company, 'goods_id' => $goods, 'code' => 'R' . uniqid(),
            'name' => 'Default Route', 'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $stageOne = DB::table('production_stages')->insertGetId([
            'production_route_id' => $route, 'code' => 'ST1' . uniqid(), 'name' => 'Initial Cut',
            'sequence' => 1, 'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $stageTwo = DB::table('production_stages')->insertGetId([
            'production_route_id' => $route, 'code' => 'ST2' . uniqid(), 'name' => 'Final Cut',
            'sequence' => 2, 'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs(User::findOrFail($user))->withSession(['company_id' => $company]);

        $this->post(route('production.shift-reports.store'), [
            'production_section_id' => $section,
            'stages' => [
                ['production_stage_id' => $stageOne],
                ['production_stage_id' => $stageTwo],
            ],
            'goods_id' => $goods,
            'personnel_id' => $worker,
            'worked_hours' => 8,
            'overtime_hours' => 0,
            'holiday_hours' => 0,
            'work_date' => '2026-10-12',
            'produced_quantity' => 20,
            'scrap_quantity' => 0,
        ])->assertRedirect(route('production.shift-reports.index'));

        $reportId = DB::table('production_shift_reports')->where('company_id', $company)->value('id');
        $this->assertNotNull($reportId);
        $this->assertDatabaseHas('production_shift_report_stages', [
            'company_id' => $company,
            'production_shift_report_id' => $reportId,
            'production_stage_id' => $stageOne,
        ]);
        $this->assertDatabaseHas('production_shift_report_stages', [
            'company_id' => $company,
            'production_shift_report_id' => $reportId,
            'production_stage_id' => $stageTwo,
        ]);
        $this->assertSame(2, DB::table('production_shift_report_stages')
            ->where('production_shift_report_id', $reportId)->count());
    }

}
