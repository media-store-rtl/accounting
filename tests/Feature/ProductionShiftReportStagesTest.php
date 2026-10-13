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
        foreach (['production.operation.create', 'production.operation.view', 'production.operation.review', 'production.output.receive'] as $slug) {
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

        $this->get(route('production.shift-reports.show', $reportId))
            ->assertOk()
            ->assertSee('ویرایش اطلاعات این گزارش');
        $this->get(route('production.shift-reports.show', $reportId))
            ->assertSee('۲۰')
            ->assertDontSee('20.0000');

        $this->put(route('production.shift-reports.update', $reportId), [
            'production_section_id' => $section,
            'goods_id' => $goods,
            'personnel_id' => $worker,
            'worked_hours' => 7.5,
            'overtime_hours' => 1,
            'holiday_hours' => 0,
            'work_date' => '2026-10-13',
            'produced_quantity' => 18,
            'scrap_quantity' => 2,
            'notes' => 'اصلاح‌شده توسط سرپرست',
            'stages' => [['production_stage_id' => $stageTwo]],
            'materials' => [[
                'goods_id' => $goods,
                'quantity' => 4,
                'scrap_quantity' => 1,
            ]],
        ])->assertRedirect(route('production.shift-reports.show', $reportId))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('production_shift_reports', [
            'id' => $reportId,
            'work_date' => '2026-10-13',
            'produced_quantity' => 18,
            'scrap_quantity' => 2,
            'worked_hours' => 7.5,
            'overtime_hours' => 1,
            'notes' => 'اصلاح‌شده توسط سرپرست',
            'status' => 'pending',
            'reviewed_by_user_id' => null,
        ]);
        $this->assertSame(1, DB::table('production_shift_report_stages')
            ->where('production_shift_report_id', $reportId)->count());
        $this->assertDatabaseHas('production_shift_report_stages', [
            'production_shift_report_id' => $reportId,
            'production_stage_id' => $stageTwo,
        ]);
        $this->assertDatabaseHas('production_shift_report_materials', [
            'production_shift_report_id' => $reportId,
            'goods_id' => $goods,
            'quantity' => 4,
            'scrap_quantity' => 1,
        ]);
        $this->assertDatabaseHas('audit_trails', [
            'company_id' => $company,
            'auditable_type' => 'production_shift_reports',
            'auditable_id' => $reportId,
            'action' => 'production_shift_report.update',
        ]);

        // Leaving every stage checkbox untouched should preserve the existing stage list.
        $this->put(route('production.shift-reports.update', $reportId), [
            'production_section_id' => $section,
            'goods_id' => $goods,
            'personnel_id' => $worker,
            'worked_hours' => 7.5,
            'work_date' => '2026-10-13',
            'produced_quantity' => 18,
            'scrap_quantity' => 2,
        ])->assertRedirect(route('production.shift-reports.show', $reportId))
            ->assertSessionHas('success');
        $this->assertSame(1, DB::table('production_shift_report_stages')
            ->where('production_shift_report_id', $reportId)->count());

        DB::table('production_shift_reports')->where('id', $reportId)->update(['status' => 'approved']);
        $this->put(route('production.shift-reports.update', $reportId), [
            'production_section_id' => $section,
            'goods_id' => $goods,
            'personnel_id' => $worker,
            'worked_hours' => 8,
            'work_date' => '2026-10-13',
            'produced_quantity' => 18,
            'scrap_quantity' => 2,
            'stages' => [['production_stage_id' => $stageTwo]],
        ])->assertRedirect(route('production.shift-reports.show', $reportId))
            ->assertSessionHas('success');
        $this->assertDatabaseHas('production_shift_reports', ['id' => $reportId, 'status' => 'pending']);

        DB::table('production_shift_reports')->where('id', $reportId)->update(['status' => 'pending']);
        $this->from(route('production.shift-reports.show', $reportId))
            ->post(route('production.shift-reports.review', $reportId), [
                'approve' => '0',
                'review_notes' => '',
            ])->assertSessionHasErrors('review_notes');
        $this->assertDatabaseHas('production_shift_reports', [
            'id' => $reportId,
            'status' => 'pending',
        ]);

        $this->from(route('production.shift-reports.show', $reportId))
            ->post(route('production.shift-reports.review', $reportId), [
                'approve' => '0',
                'review_notes' => 'مقدار تولید با برگه کار مطابقت ندارد',
            ])->assertSessionHas('success');
        $this->assertDatabaseHas('production_shift_reports', [
            'id' => $reportId,
            'status' => 'rejected',
            'review_notes' => 'مقدار تولید با برگه کار مطابقت ندارد',
        ]);
        $this->get(route('production.shift-reports.show', $reportId))
            ->assertSee('ردشده')
            ->assertSee('مقدار تولید با برگه کار مطابقت ندارد');

        $warehouse = DB::table('locations')->insertGetId([
            'company_id' => $company, 'code' => 'WH' . uniqid(), 'name' => 'Finished Goods Warehouse',
            'type' => 'warehouse', 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        // Approving an intermediate good must not transfer it to the finished-goods warehouse.
        DB::table('production_shift_reports')->where('id', $reportId)->update(['status' => 'pending']);
        DB::table('goods')->where('id', $goods)->update(['sellable' => false]);
        $this->post(route('production.shift-reports.review', $reportId), [
            'approve' => '1',
            'review_notes' => '',
        ])->assertRedirect(route('production.shift-reports.show', $reportId))
            ->assertSessionHas('success');
        $this->assertDatabaseHas('production_shift_reports', ['id' => $reportId, 'status' => 'approved']);
        $this->assertDatabaseMissing('inventory_movements', [
            'reference_type' => 'production_shift_reports',
            'reference_id' => $reportId,
        ]);

        // Approving a sellable finished good transfers its full good quantity in the same action.
        DB::table('production_shift_reports')->where('id', $reportId)->update(['status' => 'pending']);
        DB::table('goods')->where('id', $goods)->update(['sellable' => true]);
        $this->post(route('production.shift-reports.review', $reportId), [
            'approve' => '1',
            'review_notes' => '',
            'warehouse_location_id' => $warehouse,
            'warehouse_quantity' => 16,
            'receipt_token' => (string) \Illuminate\Support\Str::uuid(),
        ])->assertRedirect(route('production.shift-reports.show', $reportId))
            ->assertSessionHas('success');
        $this->assertDatabaseHas('inventory_movements', [
            'company_id' => $company,
            'location_id' => $warehouse,
            'reference_type' => 'production_shift_reports',
            'reference_id' => $reportId,
            'movement_type' => 'finished_goods_receipt',
            'quantity' => 16,
        ]);
        $this->assertDatabaseHas('production_shift_reports', ['id' => $reportId, 'status' => 'received', 'warehouse_quantity' => 16]);

        // Rejecting a report must not require warehouse-transfer fields, even for sellable goods.
        DB::table('production_shift_reports')->where('id', $reportId)->update(['status' => 'pending']);
        $this->post(route('production.shift-reports.review', $reportId), [
            'approve' => '0',
            'review_notes' => 'اطلاعات گزارش نیاز به اصلاح دارد.',
        ])->assertRedirect(route('production.shift-reports.show', $reportId))
            ->assertSessionHas('success');
        $this->assertDatabaseHas('production_shift_reports', ['id' => $reportId, 'status' => 'rejected']);

        // A supervisor without warehouse-receipt permission must still be able to approve the report.
        DB::table('production_shift_reports')->where('id', $reportId)->update(['status' => 'pending']);
        $receivePermissionId = DB::table('permissions')->where('slug', 'production.output.receive')->value('id');
        DB::table('role_permissions')->where('role_id', $role)->where('permission_id', $receivePermissionId)->delete();

        $this->get(route('production.shift-reports.show', $reportId))
            ->assertOk()
            ->assertSee('تأیید گزارش')
            ->assertSee('انتقال محصول نهایی به انبار باید بعداً');

        $this->post(route('production.shift-reports.review', $reportId), [
            'approve' => '1',
            'review_notes' => '',
        ])->assertRedirect(route('production.shift-reports.show', $reportId))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('production_shift_reports', ['id' => $reportId, 'status' => 'approved']);
        $this->assertDatabaseMissing('inventory_movements', [
            'reference_type' => 'production_shift_reports',
            'reference_id' => $reportId,
        ]);
    }

}
