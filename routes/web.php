<?php

use App\Http\Controllers\AccountingSsoController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExcelImportController;
use App\Http\Controllers\PersonnelController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\WarehouseController;
use App\Http\Controllers\ProductionSectionController;
use App\Http\Controllers\GoodController;
use App\Http\Controllers\InventoryOperationController;
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

    Route::prefix('definitions')->name('definitions.')->group(function () {
        Route::get('suppliers',[SupplierController::class,'index'])->middleware('company.permission:supplier.view')->name('suppliers.index');
        Route::get('suppliers/create',[SupplierController::class,'create'])->middleware('company.permission:supplier.create')->name('suppliers.create');
        Route::post('suppliers',[SupplierController::class,'store'])->middleware('company.permission:supplier.create')->name('suppliers.store');
        Route::get('suppliers/{supplier}/edit',[SupplierController::class,'edit'])->middleware('company.permission:supplier.update')->name('suppliers.edit');
        Route::put('suppliers/{supplier}',[SupplierController::class,'update'])->middleware('company.permission:supplier.update')->name('suppliers.update');
        Route::patch('suppliers/{supplier}/activate',[SupplierController::class,'activate'])->middleware('company.permission:supplier.activate')->name('suppliers.activate');
        Route::patch('suppliers/{supplier}/deactivate',[SupplierController::class,'deactivate'])->middleware('company.permission:supplier.deactivate')->name('suppliers.deactivate');

        Route::get('warehouses',[WarehouseController::class,'index'])->middleware('company.permission:warehouse.view')->name('warehouses.index');
        Route::get('warehouses/create',[WarehouseController::class,'create'])->middleware('company.permission:warehouse.create')->name('warehouses.create');
        Route::post('warehouses',[WarehouseController::class,'store'])->middleware('company.permission:warehouse.create')->name('warehouses.store');
        Route::get('warehouses/{warehouse}/edit',[WarehouseController::class,'edit'])->middleware('company.permission:warehouse.update')->name('warehouses.edit');
        Route::put('warehouses/{warehouse}',[WarehouseController::class,'update'])->middleware('company.permission:warehouse.update')->name('warehouses.update');
        Route::patch('warehouses/{warehouse}/activate',[WarehouseController::class,'activate'])->middleware('company.permission:warehouse.activate')->name('warehouses.activate');
        Route::patch('warehouses/{warehouse}/deactivate',[WarehouseController::class,'deactivate'])->middleware('company.permission:warehouse.deactivate')->name('warehouses.deactivate');

        Route::get('production-sections',[ProductionSectionController::class,'index'])->middleware('company.permission:production_section.view')->name('production-sections.index');
        Route::get('production-sections/create',[ProductionSectionController::class,'create'])->middleware('company.permission:production_section.create')->name('production-sections.create');
        Route::post('production-sections',[ProductionSectionController::class,'store'])->middleware('company.permission:production_section.create')->name('production-sections.store');
        Route::get('production-sections/{section}/edit',[ProductionSectionController::class,'edit'])->middleware('company.permission:production_section.update')->name('production-sections.edit');
        Route::put('production-sections/{section}',[ProductionSectionController::class,'update'])->middleware('company.permission:production_section.update')->name('production-sections.update');
        Route::patch('production-sections/{section}/activate',[ProductionSectionController::class,'activate'])->middleware('company.permission:production_section.activate')->name('production-sections.activate');
        Route::patch('production-sections/{section}/deactivate',[ProductionSectionController::class,'deactivate'])->middleware('company.permission:production_section.deactivate')->name('production-sections.deactivate');

        Route::get('goods',[GoodController::class,'index'])->middleware('company.permission:goods.view')->name('goods.index');
        Route::get('goods/create',[GoodController::class,'create'])->middleware('company.permission:goods.create')->name('goods.create');
        Route::post('goods',[GoodController::class,'store'])->middleware('company.permission:goods.create')->name('goods.store');
        Route::get('goods/{good}/edit',[GoodController::class,'edit'])->middleware('company.permission:goods.update')->name('goods.edit');
        Route::put('goods/{good}',[GoodController::class,'update'])->middleware('company.permission:goods.update')->name('goods.update');
        Route::patch('goods/{good}/activate',[GoodController::class,'activate'])->middleware('company.permission:goods.activate')->name('goods.activate');
        Route::patch('goods/{good}/deactivate',[GoodController::class,'deactivate'])->middleware('company.permission:goods.deactivate')->name('goods.deactivate');

        Route::get('goods-operations',[InventoryOperationController::class,'index'])->middleware('company.permission:goods.operation.view')->name('goods.operations.index');
        Route::get('goods-operations/create',[InventoryOperationController::class,'create'])->middleware('company.permission:goods.operation.create')->name('goods.operations.create');
        Route::post('goods-operations',[InventoryOperationController::class,'store'])->middleware('company.permission:goods.operation.create')->name('goods.operations.store');
    });
});