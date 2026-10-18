<?php

use App\Http\Controllers\AccountingSsoController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FiscalYearController;
use App\Http\Controllers\ExcelImportController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\LaborCostController;
use App\Http\Controllers\MaterialHandoverController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CostingReportController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\PersonnelController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\PurchaseReceiptController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SupplyRequestController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\MasterDataController;
use App\Http\Controllers\ProductionOutputController;
use App\Http\Controllers\WorkflowPagesController;
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
    Route::prefix('master')->name('master.')->group(function () {
        Route::get('/{module}', [MasterDataController::class,'index'])->name('index');
        Route::get('/{module}/create', [MasterDataController::class,'create'])->name('create');
        Route::post('/{module}', [MasterDataController::class,'store'])->name('store');
        Route::get('/{module}/{id}/edit', [MasterDataController::class,'edit'])->name('edit');
        Route::put('/{module}/{id}', [MasterDataController::class,'update'])->name('update');
        Route::post('/{module}/{id}/deactivate', [MasterDataController::class,'deactivate'])->name('deactivate');
    });

    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::resource('notifications', \App\Http\Controllers\NotificationCenterController::class)
        ->only(['index'])
        ->middleware('company.permission:notification.view');
    Route::post('/notifications/{id}/read', [\App\Http\Controllers\NotificationCenterController::class,'read'])->middleware('company.permission:notification.view')->name('notifications.read');

    Route::get('/company', [CompanyController::class, 'edit'])
        ->middleware(['company.permission:company.view', 'account.owner'])
        ->name('company.edit');
    Route::put('/company', [CompanyController::class, 'update'])
        ->middleware(['company.permission:company.update', 'account.owner'])
        ->name('company.update');

    Route::middleware('company.permission:fiscal_year.view')->group(function () {
        Route::resource('fiscal-years', FiscalYearController::class)
            ->only(['index'])
            ->parameters(['fiscal-years' => 'fiscalYear']);
        Route::post('/fiscal-years/{fiscalYear}/select', [FiscalYearController::class, 'select'])
            ->name('fiscal-years.select');
    });
    Route::middleware('company.permission:fiscal_year.create')->group(function () {
        Route::resource('fiscal-years', FiscalYearController::class)
            ->only(['create', 'store'])
            ->parameters(['fiscal-years' => 'fiscalYear']);
    });
    Route::middleware('company.permission:fiscal_year.update')->group(function () {
        Route::resource('fiscal-years', FiscalYearController::class)
            ->only(['edit'])
            ->parameters(['fiscal-years' => 'fiscalYear']);
        Route::put('/fiscal-years/{fiscalYear}', [FiscalYearController::class, 'update'])
            ->name('fiscal-years.update');
    });
    Route::post('/fiscal-years/{fiscalYear}/close', [FiscalYearController::class, 'close'])
        ->middleware('company.permission:fiscal_year.close')
        ->name('fiscal-years.close');

    Route::resource('reports/costing', CostingReportController::class)
        ->only(['index'])
        ->middleware('company.permission:costing.report.view')
        ->names(['index' => 'reports.costing']);
    Route::post('/reports/costing/calculate', [CostingReportController::class, 'calculate'])->middleware('company.permission:costing.report.view')->name('reports.costing.calculate');
    Route::post('/reports/costing/production-reports/{reportId}/value', [CostingReportController::class, 'valueProductionReport'])->whereNumber('reportId')->middleware('company.permission:costing.report.view')->name('reports.costing.production-reports.value');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/sso/logout', [AccountingSsoController::class, 'logout'])->name('sso.logout');

    Route::middleware('company.permission:customer.view')->group(function () {
        Route::resource('sales/customers', CustomerController::class)->only(['index']);
    });
    Route::middleware('company.permission:customer.create')->group(function () {
        Route::resource('sales/customers', CustomerController::class)->only(['create', 'store']);
    });
    Route::middleware('company.permission:customer.update')->group(function () {
        Route::get('/sales/customers/{id}/edit', [CustomerController::class, 'edit'])
            ->whereNumber('id')
            ->name('sales.customers.edit');
        Route::put('/sales/customers/{id}', [CustomerController::class, 'update'])
            ->whereNumber('id')
            ->name('sales.customers.update');
    });
    Route::middleware('company.permission:order.view')->group(function () {
        Route::resource('sales/orders', OrderController::class)
            ->only(['index', 'show'])
            ->parameters(['orders' => 'order']);
    });
    Route::middleware('company.permission:order.create')->group(function () {
        Route::resource('sales/orders', OrderController::class)
            ->only(['create', 'store'])
            ->parameters(['orders' => 'order']);
    });
    Route::post('/sales/orders/{order}/cancel', [OrderController::class, 'cancel'])->middleware('company.permission:order.create')->whereNumber('order')->name('sales.orders.cancel');
    Route::resource('sales/deliveries', DeliveryController::class)
        ->only(['index'])
        ->middleware('company.permission:delivery_request.view');
    Route::post('/api/orders/{order}/refresh', [OrderController::class, 'refreshFulfillment'])->middleware('company.permission:order.refresh')->name('sales.orders.refresh');
    Route::post('/api/orders/{order}/production-due', [OrderController::class, 'setProductionDue'])->middleware('company.permission:production.supervise')->name('sales.orders.production-due');
    Route::post('/api/orders/{order}/delivery-request', [OrderController::class, 'deliveryRequest'])->middleware('company.permission:delivery_request.sales_approve')->name('sales.orders.delivery-request');
    Route::post('/sales/delivery-requests/{delivery}/sales-approve', [OrderController::class, 'approveDeliveryRelease'])->middleware('company.permission:delivery_request.sales_approve')->whereNumber('delivery')->name('sales.delivery.sales-approve');
    Route::post('/api/delivery-requests/{delivery}/prepare', [DeliveryController::class, 'prepare'])->middleware('company.permission:delivery_request.issue')->name('sales.delivery.prepare');
    Route::post('/api/delivery-requests/{delivery}/issue', [DeliveryController::class, 'issue'])->middleware('company.permission:delivery_request.issue')->name('sales.delivery.issue');
    Route::post('/api/delivery-requests/{delivery}/handover', [DeliveryController::class, 'handover'])->middleware('company.permission:delivery_request.issue')->name('sales.delivery.handover');
    Route::get('/api/delivery-requests/{delivery}/receipt', [DeliveryController::class, 'receipt'])->middleware('company.permission:delivery_request.view')->name('sales.delivery.receipt');

    Route::prefix('api')->name('api.')->group(function () {
        Route::middleware('company.permission:order.create')->group(function () {
            Route::apiResource('orders', OrderController::class)->only(['store']);
        });
        Route::middleware('company.permission:order.view')->group(function () {
            Route::apiResource('orders', OrderController::class)->only(['show'])->parameters(['orders' => 'order']);
        });
        Route::middleware('company.permission:supply_request.create')->group(function () {
            Route::apiResource('supply-requests', SupplyRequestController::class)
                ->only(['store'])
                ->parameters(['supply-requests' => 'supplyRequest']);
        });
        Route::middleware('company.permission:supply_request.view')->group(function () {
            Route::apiResource('supply-requests', SupplyRequestController::class)
                ->only(['show'])
                ->parameters(['supply-requests' => 'supplyRequest']);
        });
        Route::middleware('company.permission:supply_request.handover.create')->group(function () {
            Route::apiResource('material-handovers', MaterialHandoverController::class)
                ->only(['store'])
                ->parameters(['material-handovers' => 'materialHandover']);
        });
        Route::middleware('company.permission:supply_request.handover.view')->group(function () {
            Route::apiResource('material-handovers', MaterialHandoverController::class)
                ->only(['show'])
                ->parameters(['material-handovers' => 'materialHandover']);
        });
        Route::middleware('company.permission:purchase.create')->group(function () {
            Route::apiResource('purchases', PurchaseController::class)->only(['store']);
        });
        Route::middleware('company.permission:purchase.view')->group(function () {
            Route::apiResource('purchases', PurchaseController::class)->only(['show'])->parameters(['purchases' => 'purchase']);
        });
        Route::post('/purchase-receipts', [PurchaseReceiptController::class, 'store'])->middleware('company.permission:purchase.receipt.create')->name('purchase-receipts.store');
        Route::post('/purchase-receipts/{purchaseReceipt}/approve', [PurchaseReceiptController::class, 'approve'])->middleware('company.permission:purchase.receipt.approve')->name('purchase-receipts.approve');
        Route::apiResource('production/labor', LaborCostController::class)
            ->only(['store'])
            ->middleware('company.permission:production.labor.create');
        Route::post('/production/labor/{laborEntry}/review', [LaborCostController::class, 'review'])->middleware('company.permission:production.labor.review')->name('production.labor.review');
        Route::post('/production/labor/rates', [LaborCostController::class, 'setRate'])->middleware('company.permission:production.labor.rate.manage')->name('production.labor.rates.store');
    });

    // Supply requests
    Route::prefix('supply')->group(function () {
        Route::get('/', [WorkflowPagesController::class, 'supply'])
            ->middleware('company.permission:supply_request.view')
            ->name('workflows.supply');
        Route::post('/', [WorkflowPagesController::class, 'storeSupply'])
            ->middleware('company.permission:supply_request.create')
            ->name('workflows.supply.store');
        Route::post('/{supplyRequest}/stock-review', [SupplyRequestController::class, 'reviewStock'])
            ->middleware('company.permission:supply_request.handover.create')
            ->whereNumber('supplyRequest')
            ->name('workflows.supply.stock-review');
    });

    // Production workflow pages and actions
    Route::prefix('production')->group(function () {
        Route::get('/material-requests', [WorkflowPagesController::class, 'productionSalesShortages'])
            ->middleware('company.permission:production.supervise')
            ->name('production.material-requests');
        Route::get('/due-dates', [WorkflowPagesController::class, 'productionDue'])
            ->middleware('company.permission:production.supervise')
            ->name('production.due-dates.index');
        Route::post('/orders/{order}/due-date', [OrderController::class, 'setProductionDue'])
            ->middleware('company.permission:production.supervise')
            ->whereNumber('order')
            ->name('production.orders.due-date');
        Route::get('/labor', [WorkflowPagesController::class, 'labor'])
            ->middleware('company.permission:production.labor.create')
            ->name('workflows.labor');
        Route::post('/labor', [WorkflowPagesController::class, 'storeLabor'])
            ->middleware('company.permission:production.labor.create')
            ->name('workflows.labor.store');
    });

    // Warehouse handoff workflow
    Route::prefix('warehouse')->group(function () {
        Route::get('/supply-requests', [WorkflowPagesController::class, 'warehouseSupplyRequests'])
            ->middleware('company.permission:supply_request.handover.create')
            ->name('warehouse.supply-requests');
        Route::post('/supply-requests/{supplyRequest}/reject', [WorkflowPagesController::class, 'rejectSupplyRequest'])
            ->middleware('company.permission:supply_request.handover.create')
            ->whereNumber('supplyRequest')
            ->name('warehouse.supply-requests.reject');
    });

    // Purchasing workflow
    Route::prefix('purchasing')->group(function () {
        Route::get('/', [WorkflowPagesController::class, 'purchasing'])
            ->middleware('company.permission:purchase.view')
            ->name('workflows.purchasing');
        Route::post('/', [WorkflowPagesController::class, 'storePurchase'])
            ->middleware('company.permission:purchase.create')
            ->name('workflows.purchasing.store');
        Route::post('/supply-requests/{supplyRequest}/status', [WorkflowPagesController::class, 'updateSupplyPurchaseStatus'])
            ->middleware('company.permission:supply.manage')
            ->whereNumber('supplyRequest')
            ->name('workflows.purchasing.supply-status');
    });

    // Inventory
    Route::prefix('inventory')->group(function () {
        Route::get('/', [WorkflowPagesController::class, 'inventory'])
            ->middleware('company.permission:inventory.view')
            ->name('inventory.index');
        Route::post('/{location}/{goods}/reorder-point', [WorkflowPagesController::class, 'setReorderPoint'])
            ->middleware('company.permission:inventory.reorder_point.manage')
            ->whereNumber('location')
            ->whereNumber('goods')
            ->name('inventory.reorder-point');
    });

    // Material handovers
    Route::prefix('handovers')->group(function () {
        Route::get('/', [WorkflowPagesController::class, 'handovers'])
            ->middleware('company.permission:supply_request.handover.view')
            ->name('workflows.handovers');
        Route::post('/', [WorkflowPagesController::class, 'storeHandover'])
            ->middleware('company.permission:supply_request.handover.create')
            ->name('workflows.handovers.store');
    });

    // Purchase receipts
    Route::prefix('receipts')->group(function () {
        Route::get('/', [WorkflowPagesController::class, 'receipts'])
            ->middleware('company.permission:purchase.receipt.create')
            ->name('workflows.receipts');
        Route::post('/', [WorkflowPagesController::class, 'storeReceipt'])
            ->middleware('company.permission:purchase.receipt.create')
            ->name('workflows.receipts.store');
        Route::post('/{id}/approve', [WorkflowPagesController::class, 'approveReceipt'])
            ->middleware('company.permission:purchase.receipt.approve')
            ->name('workflows.receipts.approve');
    });

    Route::prefix('production')->name('production.')->group(function () {
        Route::middleware('company.permission:production.operation.view')->group(function () {
            Route::resource('shift-reports', \App\Http\Controllers\ProductionShiftReportController::class)->only(['index']);
            Route::get('/shift-reports/{report}', [\App\Http\Controllers\ProductionShiftReportController::class, 'show'])
                ->whereNumber('report')
                ->name('shift-reports.show');
        });
        Route::middleware('company.permission:production.operation.create')->group(function () {
            Route::resource('shift-reports', \App\Http\Controllers\ProductionShiftReportController::class)->only(['create', 'store']);
        });
        Route::put('/shift-reports/{report}', [\App\Http\Controllers\ProductionShiftReportController::class, 'update'])
            ->middleware('company.permission:production.operation.review')
            ->whereNumber('report')
            ->name('shift-reports.update');
    });
    Route::post('/production/shift-reports/{report}/review', [\App\Http\Controllers\ProductionShiftReportController::class,'review'])->middleware('company.permission:production.operation.review')->whereNumber('report')->name('production.shift-reports.review');
    Route::post('/production/shift-reports/{report}/receive', [\App\Http\Controllers\ProductionShiftReportController::class,'receive'])->middleware('company.permission:production.output.receive')->whereNumber('report')->name('production.shift-reports.receive');
    Route::post('/production/outputs/shift-reports/{report}/receive', [ProductionOutputController::class, 'receiveShiftReport'])->middleware('company.permission:production.output.receive')->whereNumber('report')->name('production.outputs.shift-reports.receive');

    Route::prefix('production')->name('production.')->group(function () {
        Route::resource('execution', \App\Http\Controllers\ProductionExecutionController::class)
            ->only(['index'])
            ->middleware('company.permission:production.operation.view');
        Route::resource('execution', \App\Http\Controllers\ProductionExecutionController::class)
            ->only(['create', 'store'])
            ->middleware('company.permission:production.operation.create');
    });
    Route::post('/production/execution/{run}/review', [\App\Http\Controllers\ProductionExecutionController::class,'review'])->middleware('company.permission:production.operation.review')->name('production.execution.review');
    Route::post('/production/execution/handovers/{handover}/receive', [\App\Http\Controllers\ProductionExecutionController::class,'receiveHandover'])->middleware('company.permission:production.operation.create')->name('production.execution.handover.receive');

    Route::middleware('company.permission:production.output.view')->group(function () {
        Route::resource('production/outputs', ProductionOutputController::class)->only(['index']);
    });
    Route::middleware('company.permission:production.output.create')->group(function () {
        Route::resource('production/outputs', ProductionOutputController::class)->only(['create', 'store']);
    });
    Route::prefix('production/outputs')->name('production.outputs.')->group(function () {
        Route::post('/{output}/confirm',[ProductionOutputController::class,'confirm'])->name('confirm')->middleware('company.permission:production.output.confirm');
        Route::post('/{output}/reject',[ProductionOutputController::class,'reject'])->name('reject')->middleware('company.permission:production.output.confirm');
        Route::post('/{output}/receive',[ProductionOutputController::class,'receive'])->name('receive')->middleware('company.permission:production.output.receive');
    });

    Route::resource('backups', BackupController::class)
        ->only(['index'])
        ->middleware('company.permission:backup.view');

    Route::prefix('backups')->name('backups.')->group(function () {
        Route::post('/create', [BackupController::class, 'create'])->middleware('company.permission:backup.create')->name('create');
        Route::get('/{backup}/download', [BackupController::class, 'download'])->middleware('company.permission:backup.view')->name('download');
        Route::post('/upload', [BackupController::class, 'upload'])->middleware('company.permission:backup.upload')->name('upload');
        Route::post('/{backup}/restore', [BackupController::class, 'restore'])->middleware('company.permission:backup.restore')->name('restore');
    });

    Route::resource('imports/excel', ExcelImportController::class)
        ->only(['index'])
        ->middleware('company.permission:import.excel');

    Route::prefix('imports/excel')->name('imports.excel.')->middleware('company.permission:import.excel')->group(function () {
        Route::post('/inspect', [ExcelImportController::class, 'inspect'])->name('inspect');
        Route::post('/validate', [ExcelImportController::class, 'validateMapping'])->name('validate');
        Route::post('/import', [ExcelImportController::class, 'import'])->name('import');
    });

    Route::prefix('settings')->group(function () {
        Route::resource('departments', \App\Http\Controllers\OrganizationalDepartmentController::class)
            ->only(['index', 'store'])
            ->middleware('company.permission:user.update')
            ->names([
                'index' => 'settings.departments.index',
                'store' => 'settings.departments.store',
            ]);
        Route::put('/departments/{department}', [\App\Http\Controllers\OrganizationalDepartmentController::class, 'update'])->whereNumber('department')->middleware('company.permission:user.update')->name('settings.departments.update');
        Route::post('/departments/{department}/members', [\App\Http\Controllers\OrganizationalDepartmentController::class, 'syncMembers'])->whereNumber('department')->middleware('company.permission:user.update')->name('settings.departments.members');
        Route::post('/departments/{department}/deactivate', [\App\Http\Controllers\OrganizationalDepartmentController::class, 'deactivate'])->whereNumber('department')->middleware('company.permission:user.update')->name('settings.departments.deactivate');

        Route::get('/notification-recipients', [\App\Http\Controllers\NotificationRecipientController::class, 'index'])->middleware('company.permission:user.update')->name('settings.notification-recipients.index');
        Route::put('/notification-recipients/{rule}', [\App\Http\Controllers\NotificationRecipientController::class, 'update'])->whereNumber('rule')->middleware('company.permission:user.update')->name('settings.notification-recipients.update');

        Route::middleware('company.permission:personnel.view')->group(function () {
            Route::resource('personnel', PersonnelController::class)->only(['index']);
        });
        Route::middleware(['company.permission:personnel.create', 'account.owner'])->group(function () {
            Route::resource('personnel', PersonnelController::class)->only(['create', 'store']);
        });
        Route::get('/personnel/{personnel}/labor-rates', [PersonnelController::class, 'laborRates'])->middleware('company.permission:production.labor.rate.manage')->name('personnel.labor-rates');
        Route::post('/personnel/{personnel}/labor-rates', [PersonnelController::class, 'storeLaborRate'])->middleware('company.permission:production.labor.rate.manage')->name('personnel.labor-rates.store');
        Route::put('/personnel/{personnel}/labor-rates/{rate}', [PersonnelController::class, 'updateLaborRate'])->whereNumber('rate')->middleware('company.permission:production.labor.rate.manage')->name('personnel.labor-rates.update');
        Route::middleware('company.permission:personnel.update')->group(function () {
            Route::resource('personnel', PersonnelController::class)->only(['edit']);
            Route::put('/personnel/{personnel}', [PersonnelController::class, 'update'])->name('personnel.update');
        });
        Route::post('/personnel/{personnel}/deactivate', [PersonnelController::class, 'deactivate'])->middleware('company.permission:personnel.deactivate')->name('personnel.deactivate');

        Route::middleware('company.permission:user.view')->group(function () {
            Route::resource('users', UserManagementController::class)->only(['index']);
        });
        Route::middleware('company.permission:user.create')->group(function () {
            Route::resource('users', UserManagementController::class)->only(['create', 'store']);
        });
        Route::middleware('company.permission:user.update')->group(function () {
            Route::resource('users', UserManagementController::class)->only(['edit']);
            Route::put('/users/{user}', [UserManagementController::class, 'update'])->name('users.update');
        });
            Route::get('/users/{user}/access', [UserManagementController::class, 'access'])->middleware('company.permission:user.access.manage')->name('users.access');
            Route::put('/users/{user}/access', [UserManagementController::class, 'updateAccess'])->middleware('company.permission:user.access.manage')->name('users.access.update');
            Route::post('/users/{user}/activate', [UserManagementController::class, 'activate'])->middleware('company.permission:user.activate')->name('users.activate');
            Route::post('/users/{user}/deactivate', [UserManagementController::class, 'deactivate'])->middleware('company.permission:user.deactivate')->name('users.deactivate');
        Route::middleware('company.permission:role.view')->group(function () {
            Route::resource('roles', RoleController::class)->only(['index', 'show']);
        });
        Route::middleware('company.permission:role.create')->group(function () {
            Route::resource('roles', RoleController::class)->only(['create', 'store']);
        });
        Route::middleware('company.permission:role.update')->group(function () {
            Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
        });
        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])
            ->middleware('company.permission:role.delete')
            ->name('roles.destroy');
    });
});
