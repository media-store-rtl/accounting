<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
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

        if (! $user) {
            $user = new User();
            $user->password = Str::random(64);
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

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended('/dashboard');
    }
}
