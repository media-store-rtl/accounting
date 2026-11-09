<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductionShiftReportMaterialCostMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::dropIfExists('production_shift_report_material_costs');
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('production_shift_report_material_costs');
        parent::tearDown();
    }

    public function test_report_material_lookup_index_uses_a_mysql_safe_explicit_name(): void
    {
        $migration = require database_path('migrations/2026_11_09_000001_create_production_shift_report_material_costs_table.php');
        $migration->up();

        $indexNames = array_column(
            Schema::getIndexes('production_shift_report_material_costs'),
            'name'
        );

        $this->assertContains('shift_report_material_cost_lookup_idx', $indexNames);
        $this->assertLessThanOrEqual(64, strlen('shift_report_material_cost_lookup_idx'));
    }
}
