<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\SubscriptionEntitlement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_without_permission_cannot_reach_protected_user_url(): void
    {
        [$user] = $this->makeUserWithCompany(false);

        $this->actingAs($user)->get('/settings/users')->assertForbidden();
    }

    public function test_role_permission_allows_protected_url(): void
    {
        [$user, $company, $account] = $this->makeUserWithCompany(true);
        $account->update(['owner_user_id' => null]);
        $user->refresh();

        $this->actingAs($user)->get('/settings/users')->assertOk();
    }

    public function test_user_creation_is_blocked_when_plan_limit_is_reached(): void
    {
        [$owner, $company] = $this->makeUserWithCompany(true);

        SubscriptionEntitlement::create([
            'company_id' => $company->id,
            'status' => 'active',
            'max_users' => 1,
            'starts_at' => now()->subDay(),
            'expires_at' => now()->addDay(),
        ]);

        $this->actingAs($owner)->post('/settings/users', [
            'code' => 'P-2',
            'first_name' => 'کاربر',
            'last_name' => 'دوم',
            'email' => 'second@example.test',
            'username' => 'second',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertStatus(422);
    }

    private function makeUserWithCompany(bool $withPermission): array
    {
        $account = Account::create(['name' => 'Test', 'code' => 'TEST-'.uniqid()]);
        $company = Company::create(['account_id' => $account->id, 'name' => 'Test Co', 'code' => 'MAIN']);
        $user = User::create([
            'account_id' => $account->id,
            'name' => 'Tester',
            'username' => 'tester',
            'email' => uniqid().'@example.test',
            'password' => 'password123',
            'is_active' => true,
        ]);
        $account->update(['owner_user_id' => $user->id]);
        $company->users()->attach($user->id, ['is_active' => true]);

        if (! $withPermission) {
            $account->update(['owner_user_id' => null]);
        } else {
            $permission = Permission::create(['name' => 'View Users', 'slug' => 'user.view', 'module' => 'users']);
            $role = Role::create(['company_id' => $company->id, 'name' => 'Viewer', 'slug' => 'viewer']);
            $role->permissions()->attach($permission->id);
            $company->users()->updateExistingPivot($user->id, ['role_id' => $role->id]);
        }

        return [$user->fresh(), $company->fresh(), $account->fresh()];
    }
}