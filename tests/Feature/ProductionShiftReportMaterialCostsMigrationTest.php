<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductionShiftReportMaterialCostsMigrationTest extends TestCase
{
    public function test_existing_material_costs_table_is_accepted_and_missing_lookup_index_is_added(): void
    {
        $table = 'production_shift_report_material_costs';
        $migration = require database_path('migrations/2026_11_09_000001_create_production_shift_report_material_costs_table.php');
        $migration->up();
        $this->assertTrue(Schema::hasTable($table));
        $this->assertTrue(Schema::hasIndex($table, 'shift_report_material_cost_unique'));
        $this->assertTrue(Schema::hasIndex($table, 'shift_report_material_cost_lookup_idx'));
    }

    public function test_running_material_costs_migration_again_is_safe(): void
    {
        $migration = require database_path('migrations/2026_11_09_000001_create_production_shift_report_material_costs_table.php');
        $migration->up();
        $migration->up();
        $this->assertTrue(Schema::hasTable('production_shift_report_material_costs'));
        $this->assertTrue(Schema::hasIndex('production_shift_report_material_costs', 'shift_report_material_cost_lookup_idx'));
    }
}
