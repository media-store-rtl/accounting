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

        foreach (['inventory_consumption_cost_items', 'inventory_cost_layers', 'inventory_consumption_costs', 'inventory_movements', 'material_handover_items', 'material_handovers', 'production_shift_report_materials', 'production_shift_reports'] as $table) {
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
            $table->string('valuation_method')->nullable();
            $table->dateTime('consumed_at')->nullable();
        });
        Schema::create('inventory_consumption_cost_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inventory_consumption_cost_id');
            $table->unsignedBigInteger('inventory_cost_layer_id');
            $table->decimal('quantity', 12, 4);
            $table->decimal('unit_cost', 14, 4);
            $table->decimal('total_cost', 14, 4);
            $table->timestamps();
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
        foreach (['inventory_consumption_cost_items', 'inventory_cost_layers', 'inventory_consumption_costs', 'inventory_movements', 'material_handover_items', 'material_handovers', 'production_shift_report_materials', 'production_shift_reports'] as $table) {
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

    public function test_identical_consumption_movements_are_flagged_as_possible_duplicates(): void
    {
        DB::table('production_shift_reports')->insert([
            'id' => 10, 'company_id' => 1, 'production_section_id' => 1, 'goods_id' => 2,
            'work_date' => '2026-10-14', 'status' => 'received',
        ]);
        DB::table('production_shift_report_materials')->insert([
            'company_id' => 1, 'production_shift_report_id' => 10, 'goods_id' => 1, 'quantity' => 2,
        ]);
        foreach ([41, 42] as $id) {
            DB::table('inventory_movements')->insert([
                'id' => $id, 'company_id' => 1, 'goods_id' => 1, 'location_id' => 2,
                'quantity' => -2, 'movement_type' => 'production_consumption',
                'reference_type' => 'production_shift_reports', 'reference_id' => 10,
                'occurred_at' => '2026-10-14 12:00:00', 'metadata' => json_encode(['shift_report_id' => 10]),
            ]);
        }

        $this->artisan('costing:reconcile-production-materials', ['--company' => 1, '--report' => 10])
            ->expectsOutputToContain('duplicate_consumption_movement_signature')->assertExitCode(0);

        $this->assertSame(2, DB::table('inventory_movements')->count());
        $this->assertSame(0, DB::table('inventory_consumption_costs')->count());
    }

    public function test_fifo_valuation_with_mismatched_layer_allocations_is_flagged(): void
    {
        DB::table('production_shift_reports')->insert([
            'id' => 11, 'company_id' => 1, 'production_section_id' => 1, 'goods_id' => 2,
            'work_date' => '2026-10-14', 'status' => 'received',
        ]);
        DB::table('production_shift_report_materials')->insert([
            'company_id' => 1, 'production_shift_report_id' => 11, 'goods_id' => 1, 'quantity' => 2,
        ]);
        DB::table('inventory_movements')->insert([
            'id' => 51, 'company_id' => 1, 'goods_id' => 1, 'location_id' => 2,
            'quantity' => -2, 'movement_type' => 'production_consumption',
            'reference_type' => 'production_shift_reports', 'reference_id' => 11,
            'occurred_at' => '2026-10-14 12:00:00', 'metadata' => json_encode(['shift_report_id' => 11]),
        ]);
        DB::table('inventory_consumption_costs')->insert([
            'id' => 61, 'company_id' => 1, 'inventory_movement_id' => 51, 'quantity' => 2,
            'unit_cost' => 100, 'total_cost' => 200, 'valuation_method' => 'fifo',
            'consumed_at' => '2026-10-14 12:00:00',
        ]);
        DB::table('inventory_cost_layers')->insert([
            'id' => 71, 'company_id' => 1, 'goods_id' => 1, 'location_id' => 2,
            'quantity_received' => 2, 'fifo_quantity_remaining' => 0,
            'weighted_average_quantity_remaining' => 0, 'unit_cost' => 100,
            'total_cost' => 200, 'received_at' => '2026-10-13 10:00:00',
        ]);
        DB::table('inventory_consumption_cost_items')->insert([
            'inventory_consumption_cost_id' => 61, 'inventory_cost_layer_id' => 71,
            'quantity' => 1, 'unit_cost' => 100, 'total_cost' => 100,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->artisan('costing:reconcile-production-materials', ['--company' => 1, '--report' => 11])
            ->expectsOutputToContain('fifo_allocation_mismatch')->assertExitCode(0);

        $this->assertSame(1, DB::table('inventory_movements')->count());
        $this->assertSame(1, DB::table('inventory_consumption_costs')->count());
    }

    public function test_fifo_allocation_to_later_receipt_layer_is_flagged(): void
    {
        DB::table('production_shift_reports')->insert([
            'id' => 12, 'company_id' => 1, 'production_section_id' => 1, 'goods_id' => 2,
            'work_date' => '2026-10-14', 'status' => 'received',
        ]);
        DB::table('production_shift_report_materials')->insert([
            'company_id' => 1, 'production_shift_report_id' => 12, 'goods_id' => 1, 'quantity' => 1,
        ]);
        DB::table('inventory_movements')->insert([
            'id' => 52, 'company_id' => 1, 'goods_id' => 1, 'location_id' => 2,
            'quantity' => -1, 'movement_type' => 'production_consumption',
            'reference_type' => 'production_shift_reports', 'reference_id' => 12,
            'occurred_at' => '2026-10-14 12:00:00', 'metadata' => json_encode(['shift_report_id' => 12]),
        ]);
        DB::table('inventory_consumption_costs')->insert([
            'id' => 62, 'company_id' => 1, 'inventory_movement_id' => 52, 'quantity' => 1,
            'unit_cost' => 100, 'total_cost' => 100, 'valuation_method' => 'fifo',
            'consumed_at' => '2026-10-14 12:00:00',
        ]);
        DB::table('inventory_cost_layers')->insert([
            'id' => 72, 'company_id' => 1, 'goods_id' => 1, 'location_id' => 2,
            'quantity_received' => 1, 'fifo_quantity_remaining' => 0,
            'weighted_average_quantity_remaining' => 0, 'unit_cost' => 100,
            'total_cost' => 100, 'received_at' => '2026-10-17 10:00:00',
        ]);
        DB::table('inventory_consumption_cost_items')->insert([
            'inventory_consumption_cost_id' => 62, 'inventory_cost_layer_id' => 72,
            'quantity' => 1, 'unit_cost' => 100, 'total_cost' => 100,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->artisan('costing:reconcile-production-materials', ['--company' => 1, '--report' => 12])
            ->expectsOutputToContain('fifo_allocation_invalid_layer')->assertExitCode(0);
    }

    public function test_fifo_allocation_unit_cost_must_match_source_layer(): void
    {
        DB::table('production_shift_reports')->insert([
            'id' => 13, 'company_id' => 1, 'production_section_id' => 1, 'goods_id' => 2,
            'work_date' => '2026-10-14', 'status' => 'received',
        ]);
        DB::table('production_shift_report_materials')->insert([
            'company_id' => 1, 'production_shift_report_id' => 13, 'goods_id' => 1, 'quantity' => 1,
        ]);
        DB::table('inventory_movements')->insert([
            'id' => 53, 'company_id' => 1, 'goods_id' => 1, 'location_id' => 2,
            'quantity' => -1, 'movement_type' => 'production_consumption',
            'reference_type' => 'production_shift_reports', 'reference_id' => 13,
            'occurred_at' => '2026-10-14 12:00:00', 'metadata' => json_encode(['shift_report_id' => 13]),
        ]);
        DB::table('inventory_consumption_costs')->insert([
            'id' => 63, 'company_id' => 1, 'inventory_movement_id' => 53, 'quantity' => 1,
            'unit_cost' => 120, 'total_cost' => 120, 'valuation_method' => 'fifo',
            'consumed_at' => '2026-10-14 12:00:00',
        ]);
        DB::table('inventory_cost_layers')->insert([
            'id' => 73, 'company_id' => 1, 'goods_id' => 1, 'location_id' => 2,
            'quantity_received' => 1, 'fifo_quantity_remaining' => 0,
            'weighted_average_quantity_remaining' => 0, 'unit_cost' => 100,
            'total_cost' => 100, 'received_at' => '2026-10-13 10:00:00',
        ]);
        DB::table('inventory_consumption_cost_items')->insert([
            'inventory_consumption_cost_id' => 63, 'inventory_cost_layer_id' => 73,
            'quantity' => 1, 'unit_cost' => 120, 'total_cost' => 120,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->artisan('costing:reconcile-production-materials', ['--company' => 1, '--report' => 13])
            ->expectsOutputToContain('fifo_allocation_invalid_layer')->assertExitCode(0);
    }

    public function test_consumption_exceeding_ledger_stock_is_flagged(): void
    {
        DB::table('production_shift_reports')->insert([
            'id' => 15, 'company_id' => 1, 'production_section_id' => 1, 'goods_id' => 2,
            'work_date' => '2026-10-14', 'status' => 'received',
        ]);
        DB::table('production_shift_report_materials')->insert([
            'company_id' => 1, 'production_shift_report_id' => 15, 'goods_id' => 1, 'quantity' => 2,
        ]);
        DB::table('inventory_movements')->insert([
            'id' => 55, 'company_id' => 1, 'goods_id' => 1, 'location_id' => 2,
            'quantity' => -2, 'movement_type' => 'production_consumption',
            'reference_type' => 'production_shift_reports', 'reference_id' => 15,
            'occurred_at' => '2026-10-14 12:00:00', 'metadata' => json_encode(['shift_report_id' => 15]),
        ]);

        $this->artisan('costing:reconcile-production-materials', ['--company' => 1, '--report' => 15])
            ->expectsOutputToContain('insufficient_stock_at_consumption')->assertExitCode(0);

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
