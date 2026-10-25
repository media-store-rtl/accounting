<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuditHistoricalProductionMaterialReconciliationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['inventory_cost_layers', 'inventory_consumption_costs', 'inventory_movements', 'material_handover_items', 'material_handovers', 'production_shift_report_materials', 'production_shift_reports'] as $table) {
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
            $table->text('metadata')->nullable();
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
        Schema::create('inventory_cost_layers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('goods_id');
            $table->unsignedBigInteger('location_id')->nullable();
            $table->decimal('quantity_received', 12, 4);
            $table->decimal('fifo_quantity_remaining', 12, 4);
            $table->decimal('weighted_average_quantity_remaining', 12, 4);
            $table->decimal('unit_cost', 14, 4);
            $table->decimal('total_cost', 14, 4);
            $table->dateTime('received_at');
        });
        Schema::create('material_handovers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('production_section_id')->nullable();
            $table->dateTime('handed_over_at')->nullable();
        });
        Schema::create('material_handover_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('material_handover_id');
            $table->unsignedBigInteger('goods_id');
            $table->decimal('quantity', 12, 4);
        });
    }

    protected function tearDown(): void
    {
        foreach (['inventory_cost_layers', 'inventory_consumption_costs', 'inventory_movements', 'material_handover_items', 'material_handovers', 'production_shift_report_materials', 'production_shift_reports'] as $table) {
            Schema::dropIfExists($table);
        }
        parent::tearDown();
    }

    public function test_missing_historical_consumption_is_reported_without_writing(): void
    {
        DB::table('production_shift_reports')->insert([
            'id' => 14, 'company_id' => 1, 'production_section_id' => 1, 'goods_id' => 2,
            'work_date' => '2026-10-14', 'status' => 'received',
        ]);
        DB::table('production_shift_report_materials')->insert([
            'company_id' => 1, 'production_shift_report_id' => 14, 'goods_id' => 1, 'quantity' => 2,
        ]);
        DB::table('inventory_cost_layers')->insert([
            'company_id' => 1, 'goods_id' => 1, 'location_id' => 2, 'quantity_received' => 5,
            'fifo_quantity_remaining' => 5, 'weighted_average_quantity_remaining' => 5,
            'unit_cost' => 100, 'total_cost' => 500, 'received_at' => '2026-10-13 10:00:00',
        ]);
        DB::table('inventory_cost_layers')->insert([
            'company_id' => 1, 'goods_id' => 1, 'location_id' => 2, 'quantity_received' => 5,
            'fifo_quantity_remaining' => 5, 'weighted_average_quantity_remaining' => 5,
            'unit_cost' => 999, 'total_cost' => 4995, 'received_at' => '2026-10-17 10:00:00',
        ]);

        $this->artisan('costing:reconcile-production-materials', [
            '--company' => 1, '--from' => '2026-10-14', '--to' => '2026-10-14',
        ])->expectsOutputToContain('missing_movement_and_handover')->assertExitCode(0);

        $this->assertSame(0, DB::table('inventory_movements')->count());
        $this->assertSame(0, DB::table('inventory_consumption_costs')->count());
    }

    public function test_consumption_linked_to_report_but_dated_later_is_flagged(): void
    {
        DB::table('production_shift_reports')->insert([
            'id' => 9, 'company_id' => 1, 'production_section_id' => 1, 'goods_id' => 2,
            'work_date' => '2026-10-14', 'status' => 'received',
        ]);
        DB::table('production_shift_report_materials')->insert([
            'company_id' => 1, 'production_shift_report_id' => 9, 'goods_id' => 1, 'quantity' => 2,
        ]);
        DB::table('inventory_movements')->insert([
            'id' => 31, 'company_id' => 1, 'goods_id' => 1, 'location_id' => 2,
            'quantity' => -2, 'movement_type' => 'production_consumption',
            'reference_type' => 'production_shift_reports', 'reference_id' => 9,
            'occurred_at' => '2026-10-17 10:00:00', 'metadata' => json_encode(['shift_report_id' => 9]),
        ]);

        $this->artisan('costing:reconcile-production-materials', ['--company' => 1, '--report' => 9])
            ->expectsOutputToContain('movement_after_report_date')->assertExitCode(0);

        $this->assertSame(1, DB::table('inventory_movements')->count());
        $this->assertSame(0, DB::table('inventory_consumption_costs')->count());
    }

    public function test_rejected_report_is_never_classified_as_consumption_candidate(): void
    {
        DB::table('production_shift_reports')->insert([
            'id' => 7, 'company_id' => 1, 'production_section_id' => 1, 'goods_id' => 2,
            'work_date' => '2026-10-14', 'status' => 'rejected',
        ]);
        DB::table('production_shift_report_materials')->insert([
            'company_id' => 1, 'production_shift_report_id' => 7, 'goods_id' => 1, 'quantity' => 1,
        ]);

        $this->artisan('costing:reconcile-production-materials', ['--company' => 1, '--report' => 7])
            ->expectsOutputToContain('rejected_report')->assertExitCode(0);
        $this->assertSame(0, DB::table('inventory_movements')->count());
    }
}
