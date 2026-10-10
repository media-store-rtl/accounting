<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt(array_merge($credentials, ['is_active' => true]), $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'ایمیل یا رمز عبور صحیح نیست یا کاربر غیرفعال است.'])->onlyInput('email');
        }

        $request->session()->regenerate();
        $request->session()->put('auth_source', 'local');
        $request->session()->forget('web2022_user_id');

        $user = Auth::user();
        $user->ensureAccountOwnerAccess();

        if ($company = $user->currentCompany()) {
            $request->session()->put('company_id', $company->id);
        }

        return redirect()->intended('/dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        if ($request->session()->get('auth_source') === 'web2022' && $request->user()?->web2022_user_id) {
            return redirect()->route('sso.logout');
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('logout.success');
    }
}