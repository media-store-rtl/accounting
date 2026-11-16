<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\FarnamSiteContent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FarnamContentManagementTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        $account = Account::create(['name' => 'حساب فرنام', 'code' => 'FARNAM-CMS-TEST']);
        $owner = User::create([
            'account_id' => $account->id,
            'name' => 'مدیر فرنام',
            'username' => 'farnam-cms-owner',
            'email' => 'farnam-cms-owner@example.test',
            'password' => 'password123',
            'is_active' => true,
        ]);
        $account->update(['owner_user_id' => $owner->id]);

        return $owner;
    }

    public function test_configured_site_owner_can_open_farnam_content_editor(): void
    {
        $owner = $this->owner();
        config(['services.farnam.site_admin_user_id' => $owner->id]);

        $this->actingAs($owner)
            ->get('/farnam-admin')
            ->assertOk()
            ->assertSee('مدیریت محتوای وب‌سایت فرنام')
            ->assertSee('افزودن خدمت');
    }

    public function test_non_owner_cannot_access_farnam_content_editor(): void
    {
        $account = Account::create(['name' => 'حساب عضو', 'code' => 'FARNAM-CMS-MEMBER']);
        $member = User::create([
            'account_id' => $account->id,
            'name' => 'عضو',
            'username' => 'farnam-cms-member',
            'email' => 'farnam-cms-member@example.test',
            'password' => 'password123',
            'is_active' => true,
        ]);

        $this->actingAs($member)->get('/farnam-admin')->assertForbidden();
    }


    public function test_another_account_owner_cannot_access_farnam_site_admin(): void
    {
        $siteOwner = $this->owner();
        config(['services.farnam.site_admin_user_id' => $siteOwner->id]);

        $otherAccount = Account::create(['name' => 'حساب مشتری دیگر', 'code' => 'FARNAM-CMS-OTHER']);
        $otherOwner = User::create([
            'account_id' => $otherAccount->id,
            'name' => 'مالک مشتری دیگر',
            'username' => 'farnam-cms-other-owner',
            'email' => 'farnam-cms-other-owner@example.test',
            'password' => 'password123',
            'is_active' => true,
        ]);
        $otherAccount->update(['owner_user_id' => $otherOwner->id]);

        $this->actingAs($otherOwner)->get('/farnam-admin')->assertForbidden();
    }

    public function test_owner_can_update_homepage_copy_and_services_and_public_page_uses_them(): void
    {
        $owner = $this->owner();
        config(['services.farnam.site_admin_user_id' => $owner->id]);
        $payload = [
            'hero_eyebrow' => 'معرفی تازه فرنام',
            'hero_title' => 'عنوان جدید صفحه اصلی',
            'hero_copy' => 'توضیح تازه خدمات فرنام',
            'services_title' => 'خدمات تازه',
            'services_intro' => 'شرح خدمات تازه',
            'about_title' => 'درباره تازه فرنام',
            'about_copy' => 'متن تازه درباره مجموعه',
            'contact_title' => 'تماس تازه',
            'contact_copy' => 'برای تماس با ما اقدام کنید',
            'phone' => '09130000000',
            'services' => [
                ['title' => 'خدمت تازه', 'description' => 'شرح خدمت تازه'],
                ['title' => 'خدمت دوم', 'description' => 'شرح خدمت دوم'],
            ],
        ];

        $this->actingAs($owner)
            ->put('/farnam-admin', $payload)
            ->assertRedirect('/farnam-admin')
            ->assertSessionHas('status');

        $this->assertSame('عنوان جدید صفحه اصلی', FarnamSiteContent::query()->firstOrFail()->content['hero_title']);

        auth()->logout();

        $this->get('/')
            ->assertOk()
            ->assertSee('عنوان جدید صفحه اصلی')
            ->assertSee('خدمت تازه')
            ->assertSee('شرح خدمت دوم')
            ->assertSee('09130000000')
            ->assertDontSee('ساماندهی فرآیندهای حسابداری');
    }
}
