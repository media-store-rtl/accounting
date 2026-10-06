<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\AccountingSsoController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FiscalYearController;
use App\Http\Middleware\EnsureActiveSubscription;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : view('accounting');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', fn () => view('auth.login'))->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
    Route::get('/logout-success', fn () => view('auth.logout-success'))->name('logout.success');
});

Route::get('/sso/start', [AccountingSsoController::class, 'start'])->name('sso.start');
Route::get('/sso/callback', [AccountingSsoController::class, 'callback'])->name('sso.callback');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/company', [CompanyController::class, 'edit'])->name('company.edit');
    Route::put('/company', [CompanyController::class, 'update'])->name('company.update');

    Route::get('/fiscal-years', [FiscalYearController::class, 'index'])->name('fiscal-years.index');

    Route::middleware(EnsureActiveSubscription::class)->group(function () {
        Route::get('/fiscal-years/create', [FiscalYearController::class, 'create'])->name('fiscal-years.create');
        Route::post('/fiscal-years', [FiscalYearController::class, 'store'])->name('fiscal-years.store');
    });

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});
