<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class DefinitionRouteSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_definition_operational_routes_are_registered(): void
    {
        $names=collect(Route::getRoutes()->getRoutes())->map(fn($route)=>$route->getName())->filter()->all();
        foreach([
            'master.index','master.create','workflows.supply','workflows.purchasing','workflows.handovers',
            'workflows.receipts','inventory.index','production.execution.index','production.execution.create',
            'production.outputs.index','reports.costing','notifications.index','backups.index','imports.excel.index',
            'sales.orders.index','sales.deliveries.index'
        ] as $name){
            $this->assertContains($name,$names,"Route [$name] is missing.");
        }
    }
}
