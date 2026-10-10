<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Permission;
use App\Models\Personnel;
use App\Models\Role;
use App\Models\SubscriptionEntitlement;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AccountingSsoController extends Controller
{
    public function start(): RedirectResponse
    {
        $url = rtrim((string) config('services.web2022.url'), '/');
        abort_if($url === '', 503, 'Web2022 SSO is not configured.');
        return redirect()->away($url . '/accounting/sso/start');
    }

    public function logout(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user, 401, 'کاربر وارد نشده است.');

        $web2022Url = rtrim((string) config('services.web2022.url'), '/');
        $secret = (string) config('services.accounting.sso_secret');
        if ($web2022Url !== '' && $secret !== '' && $request->session()->get('auth_source') === 'web2022' && $user->web2022_user_id) {
            $timestamp = now()->timestamp;
            $nonce = bin2hex(random_bytes(16));
            $payload = (int)$user->web2022_user_id.'|'.$timestamp.'|'.$nonce;
            $signature = hash_hmac('sha256',$payload,$secret);
            $token = rtrim(strtr(base64_encode($payload.'|'.$signature), '+/','-_'),'=');
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->away($web2022Url.'/accounting/sso/logout?token='.rawurlencode($token));
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('logout.success');
    }

    public function callback(Request $request): RedirectResponse
    {
        $token = (string) $request->query('token');
        abort_if($token === '' || strlen($token) < 32, 401, 'Invalid SSO token.');

        $url = rtrim((string) config('services.web2022.url'), '/');
        $secret = (string) config('services.web2022.sso_secret');
        abort_if($url === '' || $secret === '', 503, 'Web2022 SSO is not configured.');

        try {
            $response = Http::acceptJson()->timeout(10)
                ->withHeaders(['X-Accounting-SSO-Secret' => $secret])
                ->post($url . '/accounting/sso/exchange', ['token' => $token]);
        } catch (ConnectionException) {
            abort(503, 'Unable to connect to Web2022.');
        }

        abort_if($response->failed(), $response->status() === 401 ? 401 : 503, 'SSO authentication failed.');

        $payload = $response->json();
        abort_unless(is_array($payload) && ! empty($payload['user_id']) && ! empty($payload['email']) && ! empty($payload['subscription_id']), 401, 'Invalid SSO response.');

        [$user, $isNewUser, $initialPassword] = DB::transaction(function () use ($payload) {
            $catalog = [
                ['user.view', 'مشاهده کاربران', 'users'], ['user.create', 'ایجاد کاربر', 'users'],
                ['user.update', 'ویرایش کاربر', 'users'], ['user.activate', 'فعال‌سازی کاربر', 'users'],
                ['user.deactivate', 'غیرفعال‌سازی کاربر', 'users'], ['user.access.manage', 'مدیریت دسترسی کاربر', 'users'],
                ['personnel.view', 'مشاهده پرسنل', 'personnel'], ['personnel.create', 'ایجاد پرسنل', 'personnel'],
                ['personnel.update', 'ویرایش پرسنل', 'personnel'], ['personnel.deactivate', 'غیرفعال‌سازی پرسنل', 'personnel'],
                ['role.view', 'مشاهده نقش‌ها', 'access'], ['role.create', 'ایجاد نقش', 'access'],
                ['role.update', 'ویرایش نقش', 'access'], ['role.delete', 'حذف نقش', 'access'],
                ['fiscal_year.view', 'مشاهده سال‌های مالی', 'fiscal_year'], ['fiscal_year.create', 'تعریف سال مالی', 'fiscal_year'],
                ['fiscal_year.update', 'ویرایش سال مالی', 'fiscal_year'], ['fiscal_year.close', 'بستن سال مالی', 'fiscal_year'],
            ];
            foreach ($catalog as [$slug, $name, $module]) {
                Permission::updateOrCreate(['slug' => $slug], ['name' => $name, 'module' => $module]);
            }

            $user = User::where('web2022_user_id', (int) $payload['user_id'])->first()
                ?? User::where('email', (string) $payload['email'])->first();
            $isNew = ! $user;
            $password = null;

            if (! $user) {
                $password = Str::random(20);
                $account = Account::firstOrCreate(
                    ['code' => 'WEB2022-' . (int) $payload['user_id']],
                    ['name' => (string) ($payload['company_name'] ?? 'حساب Accounting'), 'is_active' => true]
                );
                $company = $account->company()->firstOrCreate(
                    ['account_id' => $account->id],
                    ['name' => (string) ($payload['company_name'] ?? 'مجموعه'), 'code' => 'MAIN', 'is_active' => true]
                );

                $user = User::create([
                    'account_id' => $account->id,
                    'name' => (string) ($payload['name'] ?? $payload['email']),
                    'username' => $this->uniqueUsername((string) ($payload['username'] ?? $payload['email']), $account->id),
                    'email' => (string) $payload['email'],
                    'password' => $password,
                    'web2022_user_id' => (int) $payload['user_id'],
                    'web2022_subscription_id' => (string) $payload['subscription_id'],
                    'is_active' => true,
                ]);
                $account->update(['owner_user_id' => $user->id]);

                Personnel::create([
                    'account_id' => $account->id, 'user_id' => $user->id,
                    'code' => 'OWNER-'.$user->id, 'name' => $user->name,
                    'first_name' => $payload['first_name'] ?? $user->name,
                    'last_name' => $payload['last_name'] ?? null,
                    'email' => $user->email, 'is_active' => true,
                ]);

                $role = Role::firstOrCreate(
                    ['company_id' => $company->id, 'slug' => 'owner'],
                    ['name' => 'مالک حساب', 'description' => 'نقش سیستمی مالک حساب', 'is_system' => true]
                );
                $role->permissions()->sync(Permission::pluck('id'));
                $company->users()->attach($user->id, ['role_id' => $role->id, 'is_active' => true]);
            } else {
                abort_if($user->web2022_user_id !== null && (int) $user->web2022_user_id !== (int) $payload['user_id'], 409, 'This Accounting account is linked to another Web2022 user.');
                $user->update([
                    'name' => (string) ($payload['name'] ?? $user->name),
                    'email' => (string) $payload['email'],
                    'web2022_user_id' => (int) $payload['user_id'],
                    'web2022_subscription_id' => (string) $payload['subscription_id'],
                ]);
                if (! $user->personnel()->exists()) {
                    Personnel::create([
                        'account_id' => $user->account_id, 'user_id' => $user->id,
                        'code' => 'P-'.$user->id, 'name' => $user->name,
                        'email' => $user->email, 'is_active' => true,
                    ]);
                }
            }

            $company = $user->currentCompany();
            if ($company) {
                $entitlement = SubscriptionEntitlement::firstOrNew(['company_id' => $company->id]);
                $entitlement->external_subscription_id = (string) $payload['subscription_id'];
                // Web2022 may send generic field names (status, starts_at, expires_at)
                // or subscription-prefixed names. Accept both so an active plan is not
                // incorrectly stored as pending just because the field names differ.
                $incomingStatus = $payload['subscription_status'] ?? $payload['status'] ?? $entitlement->status ?? 'pending';
                $entitlement->status = strtolower(trim((string) $incomingStatus));
                $entitlement->max_users = isset($payload['max_users']) ? (int) $payload['max_users'] : $entitlement->max_users;
                $entitlement->starts_at = array_key_exists('subscription_starts_at', $payload)
                    ? $payload['subscription_starts_at']
                    : (array_key_exists('starts_at', $payload) ? $payload['starts_at'] : $entitlement->starts_at);
                $entitlement->expires_at = array_key_exists('subscription_expires_at', $payload)
                    ? $payload['subscription_expires_at']
                    : (array_key_exists('expires_at', $payload) ? $payload['expires_at'] : $entitlement->expires_at);
                $entitlement->last_verified_at = now();
                $metadata = is_array($entitlement->metadata) ? $entitlement->metadata : [];
                if (isset($payload['max_users'])) $metadata['max_users'] = (int) $payload['max_users'];
                $entitlement->metadata = $metadata;
                $entitlement->save();
            }

            return [$user, $isNew, $password];
        });

        abort_unless($user->is_active, 403, 'کاربر غیرفعال است.');
        abort_unless($user->companies()->wherePivot('is_active', true)->exists(), 403, 'عضویت کاربر غیرفعال است.');

        if ($isNewUser && $initialPassword !== null) {
            Mail::raw(
                "سلام،\n\nحساب شما در My Medimo Accounting با موفقیت ایجاد شد.\n\nایمیل ورود:\n{$user->email}\n\nرمز عبور اولیه:\n{$initialPassword}\n",
                fn ($message) => $message->to($user->email)->subject('اطلاعات ورود به My Medimo Accounting')
            );
        }

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('auth_source', 'web2022');
        $request->session()->put('web2022_user_id', (int) $payload['user_id']);
        if ($company = $user->currentCompany()) {
            $request->session()->put('company_id', $company->id);
        }

        return redirect()->intended('/dashboard');
    }

    private function uniqueUsername(string $candidate, int $accountId): string
    {
        $base = Str::of($candidate)->before('@')->replaceMatches('/[^A-Za-z0-9_-]/', '-')->trim('-')->value() ?: 'user';
        $username = $base;
        $i = 1;
        while (User::where('account_id', $accountId)->where('username', $username)->exists()) {
            $username = $base.'-'.($i++);
        }
        return $username;
    }
}