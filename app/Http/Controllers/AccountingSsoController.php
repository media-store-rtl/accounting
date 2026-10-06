<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Company;
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
        $web2022Url = rtrim((string) config('services.web2022.url'), '/');

        if ($web2022Url === '') {
            abort(503, 'Web2022 SSO is not configured.');
        }

        return redirect()->away($web2022Url . '/accounting/sso/start');
    }

    public function callback(Request $request): RedirectResponse
    {
        $token = (string) $request->query('token');

        if ($token === '' || strlen($token) < 32) {
            abort(401, 'Invalid SSO token.');
        }

        $web2022Url = rtrim((string) config('services.web2022.url'), '/');
        $secret = (string) config('services.web2022.sso_secret');

        if ($web2022Url === '' || $secret === '') {
            abort(503, 'Web2022 SSO is not configured.');
        }

        try {
            $response = Http::acceptJson()
                ->timeout(10)
                ->withHeaders([
                    'X-Accounting-SSO-Secret' => $secret,
                ])
                ->post($web2022Url . '/accounting/sso/exchange', [
                    'token' => $token,
                ]);
        } catch (ConnectionException) {
            abort(503, 'Unable to connect to Web2022.');
        }

        if ($response->failed()) {
            abort($response->status() === 401 ? 401 : 503, 'SSO authentication failed.');
        }

        $payload = $response->json();

        if (
            ! is_array($payload)
            || empty($payload['user_id'])
            || empty($payload['email'])
            || empty($payload['subscription_id'])
        ) {
            abort(401, 'Invalid SSO response.');
        }

        $user = User::query()
            ->where('web2022_user_id', (int) $payload['user_id'])
            ->first();

        if (! $user) {
            $user = User::query()
                ->where('email', (string) $payload['email'])
                ->first();
        }

        $isNewUser = ! $user;
        $initialPassword = null;

        $user = DB::transaction(function () use ($user, $payload, $isNewUser, &$initialPassword) {
            if ($isNewUser) {
                $account = Account::create([
                    'name' => (string) ($payload['name'] ?? $payload['email']),
                    'code' => 'ACC-'.Str::upper(Str::random(12)),
                    'is_active' => true,
                ]);

                Company::create([
                    'account_id' => $account->id,
                    'name' => (string) ($payload['name'] ?? 'مجموعه جدید'),
                    'code' => 'COMP-'.Str::upper(Str::random(10)),
                    'is_active' => true,
                ]);

                $user = new User();
                $user->account_id = $account->id;
                $user->username = (string) $payload['email'];
                $initialPassword = Str::random(20);
                $user->password = $initialPassword;
            } elseif (! $user->account_id) {
                abort(409, 'Accounting user is not linked to an account.');
            }

            if (
                $user->web2022_user_id !== null
                && (int) $user->web2022_user_id !== (int) $payload['user_id']
            ) {
                abort(409, 'This Accounting account is linked to another Web2022 user.');
            }

            if ($user->username === null || $user->username === '') {
                $user->username = (string) $payload['email'];
            }

            $user->name = (string) ($payload['name'] ?? $payload['email']);
            $user->email = (string) $payload['email'];
            $user->web2022_user_id = (int) $payload['user_id'];
            $user->web2022_subscription_id = (string) $payload['subscription_id'];
            $user->save();

            $company = $user->account->company;

            if (! $company) {
                $company = Company::create([
                    'account_id' => $user->account_id,
                    'name' => (string) ($payload['name'] ?? 'مجموعه جدید'),
                    'code' => 'COMP-'.Str::upper(Str::random(10)),
                    'is_active' => true,
                ]);
            }

            SubscriptionEntitlement::updateOrCreate(
                [
                    'company_id' => $company->id,
                    'external_subscription_id' => (string) $payload['subscription_id'],
                ],
                [
                    'status' => (string) ($payload['subscription_status'] ?? 'active'),
                    'starts_at' => $payload['subscription_starts_at'] ?? null,
                    'expires_at' => $payload['subscription_expires_at'] ?? ($payload['expires_at'] ?? null),
                    'last_verified_at' => now(),
                    'metadata' => $payload,
                ]
            );

            return $user;
        });

        if ($isNewUser && $initialPassword !== null) {
            Mail::raw(
                "سلام،

حساب شما در My Medimo Accounting با موفقیت ایجاد شد.

ایمیل ورود:
{$user->email}

رمز عبور اولیه:
{$initialPassword}

این رمز را نزد خود نگه دارید و در اولین فرصت آن را تغییر دهید.

با احترام
My Medimo",
                function ($message) use ($user) {
                    $message
                        ->to($user->email)
                        ->subject('اطلاعات ورود به My Medimo Accounting');
                }
            );
        }

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('auth_source', 'web2022');
        $request->session()->put('web2022_user_id', (int) $payload['user_id']);

        return redirect()->intended('/dashboard');
    }
}
