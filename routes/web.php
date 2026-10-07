<?php

use App\Http\Controllers\AccountingSsoController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\FiscalYearController;
use App\Http\Middleware\EnsureActiveSubscription;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExcelImportController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PersonnelController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\PurchaseReceiptController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SupplyRequestController;
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
    Route::get('/company', [CompanyController::class, 'edit'])->name('company.edit');
    Route::put('/company', [CompanyController::class, 'update'])->name('company.update');

    Route::get('/fiscal-years', [FiscalYearController::class, 'index'])->name('fiscal-years.index');
    Route::get('/fiscal-years/{fiscalYear}/edit', [FiscalYearController::class, 'edit'])->name('fiscal-years.edit');

    Route::middleware(EnsureActiveSubscription::class)->group(function () {
        Route::get('/fiscal-years/create', [FiscalYearController::class, 'create'])->name('fiscal-years.create');
        Route::post('/fiscal-years', [FiscalYearController::class, 'store'])->name('fiscal-years.store');
        Route::put('/fiscal-years/{fiscalYear}', [FiscalYearController::class, 'update'])->name('fiscal-years.update');
        Route::delete('/fiscal-years/{fiscalYear}', [FiscalYearController::class, 'destroy'])->name('fiscal-years.destroy');
        Route::post('/fiscal-years/{fiscalYear}/activate', [FiscalYearController::class, 'activate'])->name('fiscal-years.activate');
    });

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Session-authenticated JSON endpoints used by the Accounting application.
    // These stay in web.php because CompanyAuthorization and the authenticated
    // Accounting workflow rely on the active company stored in the session.
    Route::prefix('api')->name('api.')->group(function () {
        Route::post('/orders', [OrderController::class, 'store'])
            ->middleware('company.permission:order.create')->name('orders.store');
        Route::get('/orders/{order}', [OrderController::class, 'show'])
            ->middleware('company.permission:order.view')->name('orders.show');

        Route::post('/supply-requests', [SupplyRequestController::class, 'store'])
            ->middleware('company.permission:supply_request.create')->name('supply-requests.store');
        Route::get('/supply-requests/{supplyRequest}', [SupplyRequestController::class, 'show'])
            ->middleware('company.permission:supply_request.view')->name('supply-requests.show');

        Route::post('/purchases', [PurchaseController::class, 'store'])
            ->middleware('company.permission:purchase.create')->name('purchases.store');
        Route::get('/purchases/{purchase}', [PurchaseController::class, 'show'])
            ->middleware('company.permission:purchase.view')->name('purchases.show');

        Route::post('/purchase-receipts', [PurchaseReceiptController::class, 'store'])
            ->middleware('company.permission:purchase.receipt.create')->name('purchase-receipts.store');
        Route::post('/purchase-receipts/{purchaseReceipt}/approve', [PurchaseReceiptController::class, 'approve'])
            ->middleware('company.permission:purchase.receipt.approve')->name('purchase-receipts.approve');
    });

    Route::prefix('backups')->name('backups.')->group(function () {
        Route::get('/', [BackupController::class, 'index'])->middleware('company.permission:backup.view')->name('index');
        Route::post('/create', [BackupController::class, 'create'])->middleware('company.permission:backup.create')->name('create');
        Route::get('/{backup}/download', [BackupController::class, 'download'])->middleware('company.permission:backup.view')->name('download');
        Route::post('/upload', [BackupController::class, 'upload'])->middleware('company.permission:backup.upload')->name('upload');
        Route::post('/{backup}/restore', [BackupController::class, 'restore'])->middleware('company.permission:backup.restore')->name('restore');
    });

    Route::prefix('imports/excel')->name('imports.excel.')->middleware('company.permission:import.excel')->group(function () {
        Route::post('/inspect', [ExcelImportController::class, 'inspect'])->name('inspect');
        Route::post('/validate', [ExcelImportController::class, 'validateMapping'])->name('validate');
        Route::post('/import', [ExcelImportController::class, 'import'])->name('import');
    });

    Route::prefix('settings')->group(function () {
        Route::get('/personnel', [PersonnelController::class, 'index'])->middleware('company.permission:personnel.view')->name('personnel.index');
        Route::get('/personnel/create', [PersonnelController::class, 'create'])->middleware(['company.permission:personnel.create', 'account.owner'])->name('personnel.create');
        Route::post('/personnel', [PersonnelController::class, 'store'])->middleware(['company.permission:personnel.create', 'account.owner'])->name('personnel.store');
        Route::get('/personnel/{personnel}/edit', [PersonnelController::class, 'edit'])->middleware('company.permission:personnel.update')->name('personnel.edit');
        Route::put('/personnel/{personnel}', [PersonnelController::class, 'update'])->middleware('company.permission:personnel.update')->name('personnel.update');
        Route::post('/personnel/{personnel}/deactivate', [PersonnelController::class, 'deactivate'])->middleware('company.permission:personnel.deactivate')->name('personnel.deactivate');

        Route::middleware('account.owner')->group(function () {
            Route::get('/users', [UserManagementController::class, 'index'])->middleware('company.permission:user.view')->name('users.index');
            Route::get('/users/create', [UserManagementController::class, 'create'])->middleware('company.permission:user.create')->name('users.create');
            Route::post('/users', [UserManagementController::class, 'store'])->middleware('company.permission:user.create')->name('users.store');
            Route::get('/users/{user}/edit', [UserManagementController::class, 'edit'])->middleware('company.permission:user.update')->name('users.edit');
            Route::put('/users/{user}', [UserManagementController::class, 'update'])->middleware('company.permission:user.update')->name('users.update');
            Route::get('/users/{user}/access', [UserManagementController::class, 'access'])->middleware('company.permission:user.access.manage')->name('users.access');
            Route::put('/users/{user}/access', [UserManagementController::class, 'updateAccess'])->middleware('company.permission:user.access.manage')->name('users.access.update');
            Route::post('/users/{user}/activate', [UserManagementController::class, 'activate'])->middleware('company.permission:user.activate')->name('users.activate');
            Route::post('/users/{user}/deactivate', [UserManagementController::class, 'deactivate'])->middleware('company.permission:user.deactivate')->name('users.deactivate');

            Route::get('/roles', [RoleController::class, 'index'])->middleware('company.permission:role.view')->name('roles.index');
            Route::post('/roles', [RoleController::class, 'store'])->middleware('company.permission:role.create')->name('roles.store');
            Route::put('/roles/{role}', [RoleController::class, 'update'])->middleware('company.permission:role.update')->name('roles.update');
            Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->middleware('company.permission:role.delete')->name('roles.destroy');
        });
    });
});
