<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'ایمیل یا رمز عبور صحیح نیست.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();
        $request->session()->put('auth_source', 'local');
        $request->session()->forget('web2022_user_id');

        return redirect()->intended('/dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        $authSource = (string) $request->session()->get('auth_source', 'local');
        $web2022UserId = (int) $request->session()->get('web2022_user_id', 0);
        $web2022Url = rtrim((string) config('services.web2022.url'), '/');
        $secret = (string) config('services.web2022.sso_secret');

        $web2022LogoutUrl = null;

        if (
            $authSource === 'web2022'
            && $web2022UserId > 0
            && $web2022Url !== ''
            && $secret !== ''
        ) {
            $payload = $web2022UserId . '|' . now()->timestamp . '|' . Str::random(32);
            $signature = hash_hmac('sha256', $payload, $secret);
            $token = rtrim(strtr(base64_encode($payload . '|' . $signature), '+/', '-_'), '=');

            $web2022LogoutUrl = $web2022Url . '/accounting/sso/logout?token=' . rawurlencode($token);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($web2022LogoutUrl !== null) {
            return redirect()->away($web2022LogoutUrl);
        }

        return redirect()->route('logout.success');
    }
}
