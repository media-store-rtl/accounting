<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Role;
use App\Models\SubscriptionEntitlement;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StorefrontSubscriptionController extends Controller
{
    public function sync(Request $request): JsonResponse
    {
        $expectedToken = (string) config('services.storefront.token');

        if ($expectedToken === '' || ! hash_equals($expectedToken, (string) $request->bearerToken())) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $data = $request->validate([
            'external_user_id' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'external_company_id' => ['required', 'string', 'max:100'],
            'company_name' => ['required', 'string', 'max:255'],
            'external_subscription_id' => ['required', 'string', 'max:100'],
            'status' => ['required', 'string', 'max:30'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
            'max_users' => ['nullable', 'integer', 'min:1'],
            'plan_id' => ['nullable', 'integer'],
            'plan_name' => ['nullable', 'string', 'max:255'],
        ]);

        $result = DB::transaction(function () use ($data) {
            $user = User::where('external_user_id', $data['external_user_id'])->first();

            if (! $user) {
                $user = User::where('email', $data['email'])->first();
            }

            if (! $user) {
                $user = User::create([
                    'external_user_id' => $data['external_user_id'],
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => Str::random(64),
                ]);
            } else {
                $user->forceFill([
                    'external_user_id' => $data['external_user_id'],
                    'name' => $data['name'],
                    'email' => $data['email'],
                ])->save();
            }

            $company = Company::where('external_company_id', $data['external_company_id'])->first();

            if (! $company) {
                $company = Company::create([
                    'external_company_id' => $data['external_company_id'],
                    'name' => $data['company_name'],
                    'code' => 'WEB-' . $data['external_company_id'],
                    'is_active' => true,
                ]);
            } else {
                $company->forceFill([
                    'name' => $data['company_name'],
                    'is_active' => $data['status'] === 'active',
                ])->save();
            }

            $role = Role::firstOrCreate(
                ['company_id' => $company->id, 'slug' => 'owner'],
                [
                    'name' => 'Owner',
                    'description' => 'Company owner provisioned from the storefront.',
                    'is_system' => true,
                ]
            );

            $company->users()->syncWithoutDetaching([
                $user->id => [
                    'role_id' => $role->id,
                    'is_active' => $data['status'] === 'active',
                ],
            ]);

            $entitlement = SubscriptionEntitlement::updateOrCreate(
                ['company_id' => $company->id],
                [
                    'external_subscription_id' => $data['external_subscription_id'],
                    'status' => $data['status'],
                    'starts_at' => $data['starts_at'] ?? null,
                    'expires_at' => $data['expires_at'] ?? null,
                    'last_verified_at' => now(),
                    'metadata' => [
                        'external_user_id' => $data['external_user_id'],
                        'max_users' => $data['max_users'] ?? 1,
                        'plan_id' => $data['plan_id'] ?? null,
                        'plan_name' => $data['plan_name'] ?? null,
                    ],
                ]
            );

            return compact('user', 'company', 'entitlement');
        });

        return response()->json([
            'ok' => true,
            'user_id' => $result['user']->id,
            'company_id' => $result['company']->id,
            'entitlement_id' => $result['entitlement']->id,
            'status' => $result['entitlement']->status,
            'expires_at' => $result['entitlement']->expires_at,
        ]);
    }
}
