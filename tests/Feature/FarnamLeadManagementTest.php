<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\FarnamLead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FarnamLeadManagementTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        $account = Account::create(['name' => 'حساب فرنام', 'code' => 'FARNAM-LEADS-TEST']);
        $owner = User::create([
            'account_id' => $account->id,
            'name' => 'مدیر فرنام',
            'username' => 'farnam-leads-owner',
            'email' => 'farnam-leads-owner@example.test',
            'password' => 'password123',
            'is_active' => true,
        ]);
        $account->update(['owner_user_id' => $owner->id]);

        return $owner;
    }

    public function test_guest_can_submit_consultation_request_and_it_is_saved_as_new_lead(): void
    {
        $this->post('/farnam-leads', [
            'name' => 'مریم رضایی',
            'phone' => '09120000000',
            'business_name' => 'شرکت نمونه',
            'business_type' => 'production',
            'service_interest' => 'industrial_costing',
            'message' => 'برای محاسبه بهای تمام‌شده نیاز به بررسی داریم.',
            'source' => 'instagram',
            'source_detail' => 'campaign-november',
            'consent' => '1',
        ])->assertRedirect('/#request-consultation')
            ->assertSessionHas('lead_status');

        $this->assertDatabaseHas('farnam_leads', [
            'name' => 'مریم رضایی',
            'phone' => '09120000000',
            'business_type' => 'production',
            'service_interest' => 'industrial_costing',
            'status' => 'new',
            'source' => 'instagram',
            'source_detail' => 'campaign-november',
        ]);
    }

    public function test_consultation_request_requires_consent(): void
    {
        $this->from('/')->post('/farnam-leads', [
            'name' => 'مریم رضایی',
            'phone' => '09120000000',
            'business_type' => 'services',
            'service_interest' => 'accounting',
        ])->assertSessionHasErrors('consent');

        $this->assertDatabaseCount('farnam_leads', 0);
    }

    public function test_business_name_and_need_description_are_required(): void
    {
        $this->from('/')->post('/farnam-leads', [
            'name' => 'مریم رضایی',
            'phone' => '09120000000',
            'business_type' => 'services',
            'service_interest' => 'accounting',
            'consent' => '1',
        ])->assertSessionHasErrors(['business_name', 'message']);

        $this->assertDatabaseCount('farnam_leads', 0);
    }

    public function test_acquisition_source_and_campaign_are_saved(): void
    {
        $this->post('/farnam-leads', [
            'name' => 'مریم رضایی',
            'phone' => '09120000000',
            'business_name' => 'شرکت نمونه',
            'business_type' => 'services',
            'service_interest' => 'finance',
            'message' => 'برای بررسی جریان نقدی درخواست جلسه داریم.',
            'source' => 'linkedin',
            'source_detail' => 'founder-outreach',
            'consent' => '1',
        ])->assertRedirect('/#request-consultation');

        $this->assertDatabaseHas('farnam_leads', [
            'business_name' => 'شرکت نمونه',
            'source' => 'linkedin',
            'source_detail' => 'founder-outreach',
        ]);
    }

    public function test_only_account_owner_can_manage_leads_and_update_status(): void
    {
        $lead = FarnamLead::query()->create([
            'name' => 'مشتری آزمایشی',
            'phone' => '09120000000',
            'business_type' => 'commerce',
            'service_interest' => 'tax',
            'status' => 'new',
            'source' => 'website',
            'consent_at' => now(),
        ]);

        $owner = $this->owner();
        config(['services.farnam.site_admin_user_id' => $owner->id]);
        $this->actingAs($owner)->get('/farnam-admin/leads')
            ->assertOk()
            ->assertSee('مشتری آزمایشی');

        $this->actingAs($owner)->patch('/farnam-admin/leads/' . $lead->id, [
            'status' => 'negotiation',
            'internal_notes' => 'پیشنهاد همکاری ارسال شد.',
        ])->assertRedirect();

        $this->assertDatabaseHas('farnam_leads', [
            'id' => $lead->id,
            'status' => 'proposal',
            'internal_notes' => 'پیشنهاد همکاری ارسال شد.',
        ]);

        $memberAccount = Account::create(['name' => 'حساب عضو', 'code' => 'FARNAM-LEADS-MEMBER']);
        $member = User::create([
            'account_id' => $memberAccount->id,
            'name' => 'عضو',
            'username' => 'farnam-leads-member',
            'email' => 'farnam-leads-member@example.test',
            'password' => 'password123',
            'is_active' => true,
        ]);

        $this->actingAs($member)->get('/farnam-admin/leads')->assertForbidden();
    }
}
