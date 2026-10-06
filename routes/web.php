<?php

use App\Http\Controllers\AccountingSsoController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PersonnelController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserManagementController;
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
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::prefix('settings')->group(function () {
        Route::get('/personnel', [PersonnelController::class, 'index'])->middleware('permission:personnel.view')->name('personnel.index');
        Route::get('/personnel/create', [PersonnelController::class, 'create'])->middleware(['permission:personnel.create', 'account.owner'])->name('personnel.create');
        Route::post('/personnel', [PersonnelController::class, 'store'])->middleware(['permission:personnel.create', 'account.owner'])->name('personnel.store');
        Route::get('/personnel/{personnel}/edit', [PersonnelController::class, 'edit'])->middleware('permission:personnel.update')->name('personnel.edit');
        Route::put('/personnel/{personnel}', [PersonnelController::class, 'update'])->middleware('permission:personnel.update')->name('personnel.update');
        Route::post('/personnel/{personnel}/deactivate', [PersonnelController::class, 'deactivate'])->middleware('permission:personnel.deactivate')->name('personnel.deactivate');

        Route::middleware('account.owner')->group(function () {
            Route::get('/users', [UserManagementController::class, 'index'])->middleware('permission:user.view')->name('users.index');
            Route::get('/users/create', [UserManagementController::class, 'create'])->middleware('permission:user.create')->name('users.create');
            Route::post('/users', [UserManagementController::class, 'store'])->middleware('permission:user.create')->name('users.store');
            Route::get('/users/{user}/edit', [UserManagementController::class, 'edit'])->middleware('permission:user.update')->name('users.edit');
            Route::put('/users/{user}', [UserManagementController::class, 'update'])->middleware('permission:user.update')->name('users.update');
            Route::get('/users/{user}/access', [UserManagementController::class, 'access'])->middleware('permission:user.access.manage')->name('users.access');
            Route::put('/users/{user}/access', [UserManagementController::class, 'updateAccess'])->middleware('permission:user.access.manage')->name('users.access.update');
            Route::post('/users/{user}/activate', [UserManagementController::class, 'activate'])->middleware('permission:user.activate')->name('users.activate');
            Route::post('/users/{user}/deactivate', [UserManagementController::class, 'deactivate'])->middleware('permission:user.deactivate')->name('users.deactivate');

            Route::get('/roles', [RoleController::class, 'index'])->middleware('permission:role.view')->name('roles.index');
            Route::post('/roles', [RoleController::class, 'store'])->middleware('permission:role.create')->name('roles.store');
            Route::put('/roles/{role}', [RoleController::class, 'update'])->middleware('permission:role.update')->name('roles.update');
            Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->middleware('permission:role.delete')->name('roles.destroy');
        });
    });
});