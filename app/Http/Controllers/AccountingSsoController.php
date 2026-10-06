<?php

namespace App\Http\Controllers;

use App\Models\AccountingSubscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            || ! isset($payload['expires_at'])
        ) {
            abort(401, 'Invalid SSO response.');
        }

        try {
            $startsAt = isset($payload['starts_at'])
                ? Carbon::parse($payload['starts_at'])
                : now();
            $expiresAt = Carbon::parse($payload['expires_at']);
        } catch (\Throwable) {
            abort(401, 'Invalid subscription dates.');
        }

        if ($expiresAt->lte($startsAt)) {
            abort(401, 'Invalid subscription period.');
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

        if ($isNewUser) {
            $user = new User();
            $initialPassword = Str::random(20);
            $user->password = $initialPassword;
        }

        if (
            $user->web2022_user_id !== null
            && (int) $user->web2022_user_id !== (int) $payload['user_id']
        ) {
            abort(409, 'This Accounting account is linked to another Web2022 user.');
        }

        $user->name = (string) ($payload['name'] ?? $payload['email']);
        $user->email = (string) $payload['email'];
        $user->web2022_user_id = (int) $payload['user_id'];
        $user->web2022_subscription_id = (string) $payload['subscription_id'];
        $user->save();

        $status = $startsAt->lte(now()) && $expiresAt->gt(now())
            ? 'active'
            : ($startsAt->gt(now()) ? 'pending' : 'expired');

        AccountingSubscription::updateOrCreate(
            ['user_id' => $user->id],
            [
                'external_subscription_id' => (string) $payload['subscription_id'],
                'plan_id' => isset($payload['plan_id']) ? (int) $payload['plan_id'] : null,
                'status' => $status,
                'max_users' => isset($payload['max_users']) ? (int) $payload['max_users'] : null,
                'starts_at' => $startsAt,
                'expires_at' => $expiresAt,
                'last_synced_at' => now(),
            ]
        );

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

    public function logout(Request $request): RedirectResponse
    {
        $authSource = (string) $request->session()->get('auth_source');
        $web2022UserId = (int) $request->session()->get('web2022_user_id');

        if ($web2022UserId === 0 && $request->user()) {
            $web2022UserId = (int) $request->user()->web2022_user_id;
        }

        $web2022Url = rtrim((string) config('services.web2022.url'), '/');
        $secret = (string) config('services.web2022.sso_secret');

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($authSource === 'web2022' && $web2022UserId > 0 && $web2022Url !== '' && $secret !== '') {
            $timestamp = now()->timestamp;
            $nonce = Str::random(32);
            $payload = $web2022UserId . '|' . $timestamp . '|' . $nonce;
            $signature = hash_hmac('sha256', $payload, $secret);
            $logoutToken = rtrim(strtr(base64_encode($payload . '|' . $signature), '+/', '-_'), '=');

            return redirect()->away(
                $web2022Url . '/accounting/sso/logout?token=' . rawurlencode($logoutToken)
            );
        }

        return redirect()->route('logout.success');
    }
}
