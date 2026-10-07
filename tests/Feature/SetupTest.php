<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Company;
use App\Models\SubscriptionEntitlement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SetupTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(bool $active = true): array
    {
        $account = Account::create([
            'name' => 'حساب آزمایشی',
            'code' => 'ACC-TEST-'.uniqid(),
            'is_active' => true,
        ]);

        $company = Company::create([
            'account_id' => $account->id,
            'name' => 'شرکت آزمایشی',
            'code' => 'COMP-TEST-'.uniqid(),
            'is_active' => true,
        ]);

        SubscriptionEntitlement::create([
            'company_id' => $company->id,
            'external_subscription_id' => 'sub-'.uniqid(),
            'status' => $active ? 'active' : 'expired',
            'starts_at' => now()->subDay(),
            'expires_at' => $active ? now()->addMonth() : now()->subDay(),
            'last_verified_at' => now(),
        ]);

        $user = User::create([
            'account_id' => $account->id,
            'name' => 'مدیر آزمایشی',
            'username' => 'tester-'.uniqid(),
            'email' => uniqid().'@example.test',
            'password' => 'password',
        ]);

        $company->users()->attach($user->id, ['is_active' => true]);

        return [$user, $company];
    }

    public function test_dashboard_exposes_subscription_status(): void
    {
        [$user] = $this->makeUser();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('فعال')
            ->assertSee('href="/company"')
            ->assertSee('href="/fiscal-years"');
    }

    public function test_dashboard_sets_active_company_and_fiscal_year(): void
    {
        [$user, $company] = $this->makeUser();

        $this->actingAs($user)->post('/fiscal-years', [
            'name' => 'سال فعال',
            'starts_at' => '2026-03-21',
            'ends_at' => '2027-03-20',
        ]);

        $fiscalYear = $company->fiscalYears()->first();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSessionHas('company_id', $company->id)
            ->assertSessionHas('fiscal_year_id', $fiscalYear->id);
    }

    public function test_active_subscription_can_create_fiscal_year_and_data_is_persisted(): void
    {
        [$user, $company] = $this->makeUser();

        $response = $this->actingAs($user)->post('/fiscal-years', [
            'name' => 'سال مالی ۱۴۰۵',
            'starts_at' => '2026-03-21',
            'ends_at' => '2027-03-20',
        ]);

        $response->assertRedirect('/fiscal-years');

        $this->assertDatabaseHas('fiscal_years', [
            'company_id' => $company->id,
            'name' => 'سال مالی ۱۴۰۵',
            'starts_at' => '2026-03-21',
            'ends_at' => '2027-03-20',
        ]);

        $this->assertFalse(Schema::hasColumn('fiscal_years', 'currency'));
    }

    public function test_expired_subscription_cannot_create_fiscal_year_but_existing_data_is_visible(): void
    {
        [$user] = $this->makeUser(false);

        $this->actingAs($user)
            ->get('/fiscal-years/create')
            ->assertRedirect('/dashboard');

        $this->actingAs($user)
            ->get('/fiscal-years')
            ->assertOk()
            ->assertSee('منقضی / غیرفعال');
    }

    public function test_a_subscription_can_create_only_one_fiscal_year_and_renewal_does_not_create_another(): void
    {
        [$user, $company] = $this->makeUser();

        $this->actingAs($user)->post('/fiscal-years', [
            'name' => 'سال اول',
            'starts_at' => '2026-03-21',
            'ends_at' => '2027-03-20',
        ])->assertRedirect('/fiscal-years');

        $this->actingAs($user)->post('/fiscal-years', [
            'name' => 'سال دوم',
            'starts_at' => '2027-03-21',
            'ends_at' => '2028-03-20',
        ])->assertStatus(422);

        $this->assertDatabaseCount('fiscal_years', 1);
    }

    public function test_a_new_subscription_can_create_its_own_fiscal_year(): void
    {
        [$user, $company] = $this->makeUser();

        $this->actingAs($user)->post('/fiscal-years', [
            'name' => 'سال اول',
            'starts_at' => '2026-03-21',
            'ends_at' => '2027-03-20',
        ])->assertRedirect('/fiscal-years');

        $firstSubscription = $company->subscriptionEntitlement;
        $firstSubscription->update([
            'status' => 'expired',
            'expires_at' => now()->subDay(),
        ]);

        $company->subscriptionEntitlements()->create([
            'external_subscription_id' => 'sub-new-'.uniqid(),
            'status' => 'active',
            'starts_at' => now(),
            'expires_at' => now()->addYear(),
            'last_verified_at' => now(),
        ]);

        $this->actingAs($user)->post('/fiscal-years', [
            'name' => 'سال دوم',
            'starts_at' => '2027-03-21',
            'ends_at' => '2028-03-20',
        ])->assertRedirect('/fiscal-years');

        $this->assertDatabaseCount('fiscal_years', 2);
    }

    public function test_overlapping_fiscal_years_are_rejected(): void
    {
        [$user, $company] = $this->makeUser();

        $this->actingAs($user)->post('/fiscal-years', [
            'name' => 'سال اول',
            'starts_at' => '2026-03-21',
            'ends_at' => '2027-03-20',
        ])->assertRedirect('/fiscal-years');

        $company->subscriptionEntitlement->update([
            'status' => 'expired',
            'expires_at' => now()->subDay(),
        ]);

        $company->subscriptionEntitlements()->create([
            'external_subscription_id' => 'sub-overlap-'.uniqid(),
            'status' => 'active',
            'starts_at' => now(),
            'expires_at' => now()->addYear(),
            'last_verified_at' => now(),
        ]);

        $this->actingAs($user)->post('/fiscal-years', [
            'name' => 'سال هم‌پوشان',
            'starts_at' => '2026-06-01',
            'ends_at' => '2027-05-31',
        ])->assertStatus(422);
    }

    public function test_fiscal_year_can_be_updated_and_activated(): void
    {
        [$user, $company] = $this->makeUser();

        $this->actingAs($user)->post('/fiscal-years', [
            'name' => 'سال اول',
            'starts_at' => '2026-03-21',
            'ends_at' => '2027-03-20',
        ])->assertRedirect('/fiscal-years');

        $fiscalYear = $company->fiscalYears()->first();

        $this->actingAs($user)
            ->put("/fiscal-years/{$fiscalYear->id}", [
                'name' => 'سال اول ویرایش‌شده',
                'starts_at' => '2026-04-01',
                'ends_at' => '2027-03-31',
            ])
            ->assertRedirect('/fiscal-years');

        $this->assertDatabaseHas('fiscal_years', [
            'id' => $fiscalYear->id,
            'name' => 'سال اول ویرایش‌شده',
            'starts_at' => '2026-04-01',
            'ends_at' => '2027-03-31',
        ]);

        $this->actingAs($user)
            ->post("/fiscal-years/{$fiscalYear->id}/activate")
            ->assertRedirect('/dashboard')
            ->assertSessionHas('fiscal_year_id', $fiscalYear->id);
    }

    public function test_open_fiscal_year_can_be_deleted_but_closed_one_cannot(): void
    {
        [$user, $company] = $this->makeUser();

        $this->actingAs($user)->post('/fiscal-years', [
            'name' => 'سال قابل حذف',
            'starts_at' => '2026-03-21',
            'ends_at' => '2027-03-20',
        ]);

        $fiscalYear = $company->fiscalYears()->first();

        $this->actingAs($user)
            ->delete("/fiscal-years/{$fiscalYear->id}")
            ->assertRedirect('/fiscal-years');

        $this->assertDatabaseMissing('fiscal_years', ['id' => $fiscalYear->id]);
    }

    public function test_fiscal_year_validation_rejects_missing_name_and_invalid_date_range(): void
    {
        [$user] = $this->makeUser();

        $this->actingAs($user)
            ->post('/fiscal-years', [
                'starts_at' => '2026-03-21',
                'ends_at' => '2026-03-20',
            ])
            ->assertSessionHasErrors(['name', 'ends_at']);
    }

    public function test_closed_fiscal_year_cannot_be_updated_or_activated(): void
    {
        [$user, $company] = $this->makeUser();

        $this->actingAs($user)->post('/fiscal-years', [
            'name' => 'سال بسته',
            'starts_at' => '2026-03-21',
            'ends_at' => '2027-03-20',
        ]);

        $fiscalYear = $company->fiscalYears()->first();
        $fiscalYear->update(['is_closed' => true]);

        $this->actingAs($user)
            ->put("/fiscal-years/{$fiscalYear->id}", [
                'name' => 'نباید تغییر کند',
                'starts_at' => '2026-03-21',
                'ends_at' => '2027-03-20',
            ])
            ->assertStatus(422);

        $this->actingAs($user)
            ->post("/fiscal-years/{$fiscalYear->id}/activate")
            ->assertStatus(422);
    }

    public function test_company_information_is_saved(): void
    {
        [$user, $company] = $this->makeUser();

        $this->actingAs($user)
            ->put('/company', [
                'name' => 'شرکت به‌روزشده',
                'commercial_name' => 'برند آزمایشی',
                'entity_type' => 'شرکت',
                'national_id' => '1234567890',
                'registration_number' => '1234',
                'economic_code' => '5678',
                'phone' => '02112345678',
                'mobile' => '09120000000',
                'email' => 'company@example.test',
                'website' => 'https://example.test',
                'province' => 'تهران',
                'city' => 'تهران',
                'address' => 'آدرس آزمایشی',
                'postal_code' => '1234567890',
            ])
            ->assertRedirect('/company');

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'name' => 'شرکت به‌روزشده',
            'commercial_name' => 'برند آزمایشی',
            'national_id' => '1234567890',
            'city' => 'تهران',
        ]);
    }
}
