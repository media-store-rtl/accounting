<?php

namespace App\Providers;

use App\Contracts\ProductionShiftReportMaterialConsumption;
use App\Services\ProductionShiftReportMaterialConsumptionService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            ProductionShiftReportMaterialConsumption::class,
            ProductionShiftReportMaterialConsumptionService::class
        );
    }

    public function boot(): void
    {
        Gate::define('permission', function ($user, string $permission) {
            return $user->hasPermission($permission);
        });
    }
}
