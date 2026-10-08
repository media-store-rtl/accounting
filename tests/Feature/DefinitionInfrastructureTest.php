<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class DefinitionInfrastructureTest extends TestCase
{
    public function test_final_definition_routes_are_registered(): void
    {
        foreach ([
            'reports.costing.calculate','inventory.adjust','reports.costing',
            'reports.costing.calculate',
            'inventory.index',
            'inventory.adjust',
            'inventory.reorder-point',
            'backups.index',
        ] as $name) {
            $this->assertTrue(Route::has($name), "Missing route: {$name}");
        }
    }
}
