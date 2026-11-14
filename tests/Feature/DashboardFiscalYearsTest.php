<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Company;
use App\Models\FiscalYear;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardFiscalYearsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_lists_only_fiscal_years_for_the_users_selected_company(): void
    {
        $account = Account::create(['name' => 'حساب آزمایشی', 'code' => 'DASHBOARD-FY-ACCOUNT']);
        $company = Company::create([
            'account_id' => $account->id,
            'name' => 'شرکت مجاز',
            'code' => 'ALLOWED',
            'is_active' => true,
        ]);
        $otherCompany = Company::create([
            'account_id' => $account->id,
            'name' => 'شرکت دیگر',
            'code' => 'OTHER',
            'is_active' => true,
        ]);

        $user = User::create([
            'account_id' => $account->id,
            'name' => 'کاربر آزمایشی',
            'username' => 'dashboard-fiscal-year-user',
            'email' => 'dashboard-fiscal-year@example.test',
            'password' => 'password123',
            'web2022_user_id' => 98765,
            'is_active' => true,
        ]);

        $permission = Permission::create([
            'name' => 'مشاهده سال مالی',
            'slug' => 'fiscal_year.view',
            'module' => 'fiscal_years',
        ]);
        $role = Role::create([
            'company_id' => $company->id,
            'name' => 'مشاهده‌گر',
            'slug' => 'dashboard-fiscal-year-viewer',
        ]);
        $role->permissions()->attach($permission->id);
        $company->users()->attach($user->id, [
            'role_id' => $role->id,
            'is_active' => true,
        ]);

        $visibleYear = FiscalYear::create([
            'company_id' => $company->id,
            'name' => 'سال مالی قابل مشاهده',
            'code' => 'FY-VISIBLE',
            'starts_at' => '2025-03-21',
            'ends_at' => '2026-03-20',
            'is_closed' => false,
        ]);
        SubscriptionEntitlement::create([
            'company_id' => $company->id,
            'external_subscription_id' => 'WEB-SUB-98765',
            'status' => 'active',
            'starts_at' => '2025-03-21 00:00:00',
            'expires_at' => '2027-03-20 23:59:59',
            'last_verified_at' => '2026-11-13 10:00:00',
            'metadata' => ['plan_name' => 'پلن حرفه‌ای'],
        ]);

        FiscalYear::create([
            'company_id' => $otherCompany->id,
            'name' => 'سال مالی شرکت دیگر',
            'code' => 'FY-OTHER',
            'starts_at' => '2025-03-21',
            'ends_at' => '2026-03-20',
            'is_closed' => false,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['company_id' => $company->id])
            ->get('/dashboard');

        $response->assertOk()
            ->assertSee('سوابق سال‌های مالی')
            ->assertSee('اطلاعات مالک و اشتراک')
            ->assertSee('پلن حرفه‌ای')
            ->assertSee('WEB-SUB-98765')
            ->assertSee('2027/03/20')
            ->assertSee('سال‌های مالی')
            ->assertSee('subscription-dashboard')
            ->assertSee('شرکت مجاز')
            ->assertSee('98765', false)
            ->assertSee($visibleYear->name)
            ->assertDontSee('سال مالی شرکت دیگر');
    }

    public function test_dashboard_does_not_select_a_company_with_inactive_membership(): void
    {
        $account = Account::create(['name' => 'حساب دوم', 'code' => 'DASHBOARD-FY-ACCOUNT-2']);
        $activeCompany = Company::create([
            'account_id' => $account->id,
            'name' => 'شرکت فعال کاربر',
            'code' => 'ACTIVE',
            'is_active' => true,
        ]);
        $inactiveCompany = Company::create([
            'account_id' => $account->id,
            'name' => 'شرکت با عضویت غیرفعال',
            'code' => 'INACTIVE',
            'is_active' => true,
        ]);
        $user = User::create([
            'account_id' => $account->id,
            'name' => 'کاربر دوم',
            'username' => 'dashboard-fiscal-year-user-2',
            'email' => 'dashboard-fiscal-year-2@example.test',
            'password' => 'password123',
            'is_active' => true,
        ]);

        $permission = Permission::create([
            'name' => 'مشاهده سال مالی',
            'slug' => 'fiscal_year.view',
            'module' => 'fiscal_years',
        ]);
        $role = Role::create([
            'company_id' => $activeCompany->id,
            'name' => 'مشاهده‌گر',
            'slug' => 'dashboard-fiscal-year-viewer-2',
        ]);
        $role->permissions()->attach($permission->id);
        $activeCompany->users()->attach($user->id, [
            'role_id' => $role->id,
            'is_active' => true,
        ]);
        $inactiveCompany->users()->attach($user->id, [
            'role_id' => null,
            'is_active' => false,
        ]);

        FiscalYear::create([
            'company_id' => $activeCompany->id,
            'name' => 'سال شرکت فعال',
            'code' => 'FY-ACTIVE',
            'starts_at' => '2025-03-21',
            'ends_at' => '2026-03-20',
            'is_closed' => false,
        ]);
        FiscalYear::create([
            'company_id' => $inactiveCompany->id,
            'name' => 'سال شرکت غیرفعال',
            'code' => 'FY-INACTIVE',
            'starts_at' => '2025-03-21',
            'ends_at' => '2026-03-20',
            'is_closed' => false,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['company_id' => $inactiveCompany->id])
            ->get('/dashboard');

        $response->assertOk()
            ->assertSee('شرکت فعال کاربر')
            ->assertSee('سال شرکت فعال')
            ->assertDontSee('شرکت با عضویت غیرفعال')
            ->assertDontSee('سال شرکت غیرفعال');
    }
}
