<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Personnel;
use App\Models\Role;
use App\Models\SubscriptionEntitlement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class UserAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_user_and_personnel_with_role(): void
    {
        [$owner, $company] = $this->ownerContext();
        $role = Role::create(['company_id' => $company->id, 'name' => 'Operator', 'slug' => 'operator']);
        SubscriptionEntitlement::create(['company_id' => $company->id, 'status' => 'active', 'max_users' => 2]);

        $response = $this->actingAs($owner)->withSession(['company_id' => $company->id])->post('/settings/users', [
            'code' => 'P-2',
            'first_name' => 'Ali',
            'last_name' => 'Ahmadi',
            'national_id' => '001',
            'job_title' => 'Operator',
            'phone' => '021',
            'mobile' => '0912',
            'email' => 'ali@example.test',
            'username' => 'ali',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role_id' => $role->id,
        ]);

        $response->assertRedirectToRoute('users.index');
        $user = User::where('email', 'ali@example.test')->firstOrFail();

        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertDatabaseHas('personnel', [
            'user_id' => $user->id, 'code' => 'P-2',
            'first_name' => 'Ali', 'last_name' => 'Ahmadi',
        ]);
        $this->assertDatabaseHas('company_user', [
            'company_id' => $company->id, 'user_id' => $user->id,
            'role_id' => $role->id, 'is_active' => true,
        ]);
    }

    public function test_owner_can_edit_user_and_personnel_together(): void
    {
        [$owner, $company] = $this->ownerContext();
        $target = $this->createUser($owner->account_id, $company, 'old@example.test', 'old', 'P-2');

        $response = $this->actingAs($owner)->withSession(['company_id' => $company->id])
            ->put('/settings/users/'.$target->id, [
                'code' => 'P-22', 'first_name' => 'Sara', 'last_name' => 'Karimi',
                'national_id' => '002', 'job_title' => 'Manager', 'phone' => '0212',
                'mobile' => '0913', 'email' => 'sara@example.test', 'username' => 'sara',
                'password' => '', 'password_confirmation' => '',
            ]);

        $response->assertRedirectToRoute('users.index');
        $target->refresh()->load('personnel');

        $this->assertSame('Sara Karimi', $target->name);
        $this->assertSame('sara@example.test', $target->email);
        $this->assertSame('P-22', $target->personnel->code);
        $this->assertSame('Manager', $target->personnel->job_title);
    }

    public function test_owner_can_deactivate_and_reactivate_user_and_personnel(): void
    {
        [$owner, $company] = $this->ownerContext();
        SubscriptionEntitlement::create(['company_id' => $company->id, 'status' => 'active', 'max_users' => 2]);
        $target = $this->createUser($owner->account_id, $company, 'target@example.test', 'target', 'P-2');

        $this->actingAs($owner)->withSession(['company_id' => $company->id])
            ->post('/settings/users/'.$target->id.'/deactivate')->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $target->id, 'is_active' => false]);
        $this->assertDatabaseHas('personnel', ['user_id' => $target->id, 'is_active' => false]);
        $this->assertDatabaseHas('company_user', ['company_id' => $company->id, 'user_id' => $target->id, 'is_active' => false]);

        $this->actingAs($owner)->withSession(['company_id' => $company->id])
            ->post('/settings/users/'.$target->id.'/activate')->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $target->id, 'is_active' => true]);
        $this->assertDatabaseHas('personnel', ['user_id' => $target->id, 'is_active' => true]);
        $this->assertDatabaseHas('company_user', ['company_id' => $company->id, 'user_id' => $target->id, 'is_active' => true]);
    }

    public function test_authorized_user_can_edit_personnel_and_identity_remains_linked(): void
    {
        [$owner, $company] = $this->ownerContext();
        [$actor] = $this->memberWithPermission($owner->account_id, $company, 'personnel.update');

        $response = $this->actingAs($actor)->withSession(['company_id' => $company->id])
            ->put('/settings/personnel/'.$actor->personnel->id, [
                'code' => 'P-9', 'first_name' => 'Reza', 'last_name' => 'Moradi',
                'national_id' => '009', 'job_title' => 'Supervisor', 'phone' => '0219',
                'mobile' => '0919', 'email' => 'reza@example.test', 'employment_type' => 'full-time',
            ]);

        $response->assertRedirectToRoute('personnel.index');
        $actor->refresh()->load('personnel');

        $this->assertSame('Reza Moradi', $actor->name);
        $this->assertSame('reza@example.test', $actor->email);
        $this->assertSame('P-9', $actor->personnel->code);
    }

    public function test_personnel_deactivation_deactivates_linked_user_and_company_membership(): void
    {
        [$owner, $company] = $this->ownerContext();
        [$actor] = $this->memberWithPermission($owner->account_id, $company, 'personnel.deactivate');
        $target = $this->createUser($owner->account_id, $company, 'target@example.test', 'target', 'P-3');

        $this->actingAs($actor)->withSession(['company_id' => $company->id])
            ->post('/settings/personnel/'.$target->personnel->id.'/deactivate')->assertRedirect();

        $this->assertDatabaseHas('personnel', ['id' => $target->personnel->id, 'is_active' => false]);
        $this->assertDatabaseHas('users', ['id' => $target->id, 'is_active' => false]);
        $this->assertDatabaseHas('company_user', ['company_id' => $company->id, 'user_id' => $target->id, 'is_active' => false]);
    }

    public function test_owner_can_create_and_update_role_with_permissions(): void
    {
        [$owner, $company] = $this->ownerContext();
        $view = Permission::create(['name' => 'View Personnel', 'slug' => 'personnel.view', 'module' => 'personnel']);
        $update = Permission::create(['name' => 'Update Personnel', 'slug' => 'personnel.update', 'module' => 'personnel']);

        $this->actingAs($owner)->withSession(['company_id' => $company->id])
            ->post('/settings/roles', [
                'name' => 'Personnel Manager', 'slug' => 'personnel-manager',
                'permissions' => [$view->id],
            ])->assertRedirect();

        $role = Role::where('company_id', $company->id)->where('slug', 'personnel-manager')->firstOrFail();
        $this->assertTrue($role->permissions->contains($view));

        $this->actingAs($owner)->withSession(['company_id' => $company->id])
            ->put('/settings/roles/'.$role->id, [
                'name' => 'Personnel Editor', 'slug' => 'personnel-editor',
                'permissions' => [$update->id],
            ])->assertRedirect();

        $role->refresh()->load('permissions');
        $this->assertSame('Personnel Editor', $role->name);
        $this->assertTrue($role->permissions->contains($update));
        $this->assertFalse($role->permissions->contains($view));
    }

    public function test_user_without_permission_is_rejected_by_server(): void
    {
        [$owner, $company] = $this->ownerContext();
        [$actor] = $this->memberWithPermission($owner->account_id, $company, 'personnel.update');

        $this->actingAs($actor)->withSession(['company_id' => $company->id])
            ->get('/settings/personnel')->assertForbidden();
    }

    public function test_user_with_permission_is_allowed_by_server(): void
    {
        [$owner, $company] = $this->ownerContext();
        [$actor] = $this->memberWithPermission($owner->account_id, $company, 'personnel.view');

        $this->actingAs($actor)->withSession(['company_id' => $company->id])
            ->get('/settings/personnel')->assertOk();
    }

    public function test_inactive_user_is_rejected_even_when_role_has_permission(): void
    {
        [$owner, $company] = $this->ownerContext();
        [$actor] = $this->memberWithPermission($owner->account_id, $company, 'personnel.view');
        $actor->update(['is_active' => false]);

        $this->actingAs($actor)->withSession(['company_id' => $company->id])
            ->get('/settings/personnel')->assertForbidden();
    }

    public function test_user_creation_is_blocked_at_subscription_limit(): void
    {
        [$owner, $company] = $this->ownerContext();
        SubscriptionEntitlement::create(['company_id' => $company->id, 'status' => 'active', 'max_users' => 1]);

        $this->actingAs($owner)->withSession(['company_id' => $company->id])
            ->post('/settings/users', [
                'code' => 'P-2', 'first_name' => 'Second', 'last_name' => 'User',
                'email' => 'second@example.test', 'username' => 'second',
                'password' => 'password123', 'password_confirmation' => 'password123',
            ])->assertStatus(422);

        $this->assertDatabaseMissing('users', ['email' => 'second@example.test']);
        $this->assertDatabaseMissing('personnel', ['code' => 'P-2']);
    }

    public function test_permission_is_denied_for_inactive_company(): void
    {
        [$owner, $company] = $this->ownerContext();
        [$actor] = $this->memberWithPermission($owner->account_id, $company, 'personnel.view');
        $company->update(['is_active' => false]);

        $this->assertFalse($actor->fresh()->hasCompanyPermission($company->id, 'personnel.view'));
    }

    private function ownerContext(): array
    {
        $account = Account::create(['name' => 'Test Account', 'code' => 'ACC-'.Str::lower(Str::random(8))]);
        $company = Company::create(['account_id' => $account->id, 'name' => 'Test Company', 'code' => 'MAIN']);
        $owner = $this->createUser($account->id, $company, 'owner@example.test', 'owner', 'P-1');
        $account->update(['owner_user_id' => $owner->id]);

        return [$owner->fresh(), $company->fresh()];
    }

    private function memberWithPermission(int $accountId, Company $company, string $slug): array
    {
        $actor = $this->createUser(
            $accountId, $company,
            Str::lower(Str::random(8)).'@example.test',
            'member-'.Str::lower(Str::random(8)),
            'P-'.random_int(10, 9999)
        );
        $permission = Permission::create([
            'name' => $slug, 'slug' => $slug, 'module' => Str::before($slug, '.'),
        ]);
        $role = Role::create([
            'company_id' => $company->id, 'name' => 'Role '.$slug,
            'slug' => 'role-'.Str::lower(Str::random(8)),
        ]);
        $role->permissions()->attach($permission->id);
        $company->users()->updateExistingPivot($actor->id, ['role_id' => $role->id]);

        return [$actor->fresh('personnel'), $permission];
    }

    private function createUser(int $accountId, Company $company, string $email, string $username, string $personnelCode): User
    {
        $user = User::create([
            'account_id' => $accountId, 'name' => 'Test User',
            'username' => $username, 'email' => $email,
            'password' => 'password123', 'is_active' => true,
        ]);
        Personnel::create([
            'account_id' => $accountId, 'user_id' => $user->id,
            'code' => $personnelCode, 'name' => $user->name,
            'first_name' => 'Test', 'last_name' => 'User',
            'email' => $user->email, 'is_active' => true,
        ]);
        $company->users()->attach($user->id, ['is_active' => true]);

        return $user->fresh('personnel');
    }
}
