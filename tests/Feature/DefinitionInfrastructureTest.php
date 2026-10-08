<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class DefinitionInfrastructureTest extends TestCase
{
    public function test_definition_operational_routes_are_registered(): void
    {
        foreach ([
            'master.index',
            'production.execution.index',
            'production.outputs.index',
            'notifications.index',
            'workflows.supply',
            'workflows.purchasing',
            'inventory.index',
            'backups.index',
            'imports.excel.index',
            'reports.costing',
        ] as $name) {
            $this->assertTrue(Route::has($name), "Missing route: {$name}");
        }
    }
}
