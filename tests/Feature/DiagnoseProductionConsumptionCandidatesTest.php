<?php

namespace Tests\\Feature;

use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Schema;
use Tests\\TestCase;

class DiagnoseProductionConsumptionCandidatesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['production_sections', 'locations', 'inventory_movements', 'production_shift_report_materials', 'production_shift_reports'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('production_shift_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('production_section_id')->nullable();
            $table->unsignedBigInteger('order_id')->nullable();
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
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->dateTime('occurred_at')->nullable();
            $table->text('metadata')->nullable();
        });
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
        });
        Schema::create('production_sections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('location_id');
        });
    }

    protected function tearDown(): void
    {
        foreach (['production_sections', 'locations', 'inventory_movements', 'production_shift_report_materials', 'production_shift_reports'] as $table) {
            Schema::dropIfExists($table);
        }
        parent::tearDown();
    }

    public function test_direct_and_legacy_candidates_are_reported_without_writing(): void
    {
        DB::table('production_shift_reports')->insert([
            'id' => 10, 'company_id' => 1, 'production_section_id' => 5, 'order_id' => 7,
            'work_date' => '2026-10-14', 'status' => 'received',
        ]);
        DB::table('production_shift_report_materials')->insert([
            'company_id' => 1, 'production_shift_report_id' => 10, 'goods_id' => 99, 'quantity' => 2,
        ]);
        DB::table('locations')->insert(['id' => 20, 'company_id' => 1]);
        DB::table('production_sections')->insert(['id' => 5, 'location_id' => 20]);
        DB::table('inventory_movements')->insert([
            [
                'id' => 30, 'company_id' => 1, 'goods_id' => 99, 'location_id' => 20,
                'quantity' => -2, 'movement_type' => 'production_consumption',
                'reference_type' => 'production_shift_reports', 'reference_id' => 10,
                'occurred_at' => '2026-10-14 12:00:00',
                'metadata' => json_encode(['shift_report_id' => 10, 'production_section_id' => 5, 'order_id' => 7]),
            ],
            [
                'id' => 31, 'company_id' => 1, 'goods_id' => 99, 'location_id' => 20,
                'quantity' => -1, 'movement_type' => 'production_consumption',
                'reference_type' => 'supply_requests', 'reference_id' => 77,
                'occurred_at' => '2026-10-13 12:00:00',
                'metadata' => json_encode(['production_section_id' => 5, 'order_id' => 7]),
            ],
        ]);

        $this->artisan('costing:diagnose-production-consumption', ['--company' => 1, '--report' => 10])
            ->expectsOutputToContain('direct_link_found')
            ->expectsOutputToContain('legacy_pool_candidates_need_allocation_review')
            ->assertExitCode(0);

        $this->assertSame(2, DB::table('inventory_movements')->count());
        $this->assertSame(1, DB::table('production_shift_reports')->count());
        $this->assertSame(1, DB::table('production_shift_report_materials')->count());
    }
}
