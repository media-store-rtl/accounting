<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuditProductionMaterialCostingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['inventory_consumption_costs', 'inventory_movements', 'production_shift_report_materials', 'production_shift_reports'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('production_shift_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('production_section_id')->nullable();
            $table->unsignedBigInteger('goods_id')->nullable();
            $table->date('work_date');
            $table->string('status');
        });
        Schema::create('production_shift_report_materials', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('production_shift_report_id');
            $table->unsignedBigInteger('goods_id');
            $table->decimal('quantity', 12, 4);
        });
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('goods_id');
            $table->unsignedBigInteger('location_id')->nullable();
            $table->decimal('quantity', 12, 4);
            $table->string('movement_type');
            $table->string('reference_type');
            $table->unsignedBigInteger('reference_id');
            $table->dateTime('occurred_at')->nullable();
        });
        Schema::create('inventory_consumption_costs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('inventory_movement_id');
            $table->decimal('quantity', 12, 4);
            $table->decimal('unit_cost', 14, 4);
            $table->decimal('total_cost', 14, 4);
            $table->dateTime('consumed_at')->nullable();
        });
    }

    protected function tearDown(): void
    {
        foreach (['inventory_consumption_costs', 'inventory_movements', 'production_shift_report_materials', 'production_shift_reports'] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_it_reports_missing_movement_without_writing_to_inventory_tables(): void
    {
        DB::table('production_shift_reports')->insert([
            'id' => 10, 'company_id' => 1, 'production_section_id' => 1, 'goods_id' => 99,
            'work_date' => '2026-10-14', 'status' => 'received',
        ]);
        DB::table('production_shift_report_materials')->insert([
            'company_id' => 1, 'production_shift_report_id' => 10, 'goods_id' => 1, 'quantity' => 2,
        ]);

        $this->artisan('costing:audit-production-materials', ['--company' => 1, '--from' => '2026-10-12', '--to' => '2026-10-16'])
            ->expectsOutputToContain('missing_movement')
            ->assertExitCode(0);

        $this->assertSame(0, DB::table('inventory_movements')->count());
        $this->assertSame(0, DB::table('inventory_consumption_costs')->count());
    }

    public function test_it_reports_linked_movement_and_valuation_as_matched(): void
    {
        DB::table('production_shift_reports')->insert([
            'id' => 10, 'company_id' => 1, 'production_section_id' => 1, 'goods_id' => 99,
            'work_date' => '2026-10-14', 'status' => 'received',
        ]);
        DB::table('production_shift_report_materials')->insert([
            'company_id' => 1, 'production_shift_report_id' => 10, 'goods_id' => 1, 'quantity' => 2,
        ]);
        DB::table('inventory_movements')->insert([
            'id' => 20, 'company_id' => 1, 'goods_id' => 1, 'location_id' => 2, 'quantity' => -2,
            'movement_type' => 'production_consumption', 'reference_type' => 'production_shift_reports',
            'reference_id' => 10, 'occurred_at' => '2026-10-14 23:59:59',
        ]);
        DB::table('inventory_consumption_costs')->insert([
            'company_id' => 1, 'inventory_movement_id' => 20, 'quantity' => 2,
            'unit_cost' => 100, 'total_cost' => 200, 'consumed_at' => '2026-10-14 23:59:59',
        ]);

        $this->artisan('costing:audit-production-materials', ['--company' => 1, '--report' => 10])
            ->expectsOutputToContain('matched')
            ->expectsOutputToContain('200')
            ->assertExitCode(0);
    }
}
