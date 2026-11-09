<?php

namespace Tests\Feature;

use App\Services\ProductionReportCurrentCostRefreshService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProductionReportCurrentCostRefreshTest extends TestCase
{
    private string $originalConnection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalConnection = config('database.default');
        config(['database.connections.current_cost_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
            'foreign_key_constraints' => false,
        ]]);
        DB::purge('current_cost_test');
        DB::setDefaultConnection('current_cost_test');

        Schema::create('production_shift_reports', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('company_id'); $table->string('status'); $table->date('work_date')->nullable();
        });
        Schema::create('production_shift_report_materials', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('production_shift_report_id');
            $table->unsignedBigInteger('goods_id'); $table->decimal('quantity', 16, 4);
        });
        Schema::create('inventory_movements', function (Blueprint $table) { $table->id(); $table->unsignedBigInteger('company_id'); $table->unsignedBigInteger('goods_id'); $table->unsignedBigInteger('location_id'); $table->decimal('quantity', 16, 4); $table->dateTime('occurred_at'); });
        Schema::create('inventory_cost_layers', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('company_id'); $table->unsignedBigInteger('goods_id');
            $table->dateTime('received_at'); $table->decimal('unit_cost', 20, 4);
            $table->decimal('fifo_quantity_remaining', 16, 4)->default(0);
        });
        Schema::create('production_shift_report_material_costs', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('production_shift_report_id'); $table->unsignedBigInteger('goods_id');
            $table->decimal('quantity', 16, 4); $table->decimal('valued_quantity', 16, 4);
            $table->decimal('unit_cost', 20, 4)->nullable(); $table->decimal('total_cost', 20, 4)->nullable();
            $table->unsignedBigInteger('source_cost_layer_id')->nullable(); $table->unsignedBigInteger('refreshed_by')->nullable();
            $table->dateTime('refreshed_at')->nullable(); $table->dateTime('created_at')->nullable(); $table->dateTime('updated_at')->nullable();
            $table->unique(['company_id', 'production_shift_report_id', 'goods_id']);
        });
        Schema::create('audit_trails', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('company_id'); $table->unsignedBigInteger('user_id');
            $table->string('module'); $table->string('action'); $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id'); $table->text('before')->nullable(); $table->text('after')->nullable();
            $table->string('method'); $table->unsignedInteger('status_code'); $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
        });

        DB::table('production_shift_reports')->insert(['id' => 8, 'company_id' => 1, 'status' => 'received', 'work_date' => '2026-10-18']);
        DB::table('inventory_movements')->insert(['id' => 90, 'company_id' => 1, 'goods_id' => 1, 'location_id' => 7, 'quantity' => -5, 'occurred_at' => '2026-10-22 09:00:00']);
        DB::table('production_shift_report_materials')->insert([
            ['company_id' => 1, 'production_shift_report_id' => 8, 'goods_id' => 1, 'quantity' => 5],
            ['company_id' => 1, 'production_shift_report_id' => 8, 'goods_id' => 2, 'quantity' => 3],
        ]);
        DB::table('inventory_cost_layers')->insert([
            ['id' => 10, 'company_id' => 1, 'goods_id' => 1, 'received_at' => '2026-10-20 10:00:00', 'unit_cost' => 120, 'fifo_quantity_remaining' => 0],
            ['id' => 11, 'company_id' => 1, 'goods_id' => 1, 'received_at' => '2026-11-01 10:00:00', 'unit_cost' => 150, 'fifo_quantity_remaining' => 0],
        ]);
    }

    protected function tearDown(): void
    {
        DB::setDefaultConnection($this->originalConnection);
        DB::purge('current_cost_test');
        parent::tearDown();
    }

    public function test_repeated_refresh_replaces_the_report_snapshot_instead_of_adding_duplicate_cost_rows(): void
    {
        $service = app(ProductionReportCurrentCostRefreshService::class);
        $service->refresh(1, 8, 55);
        DB::table('inventory_cost_layers')->insert([
            'id' => 12, 'company_id' => 1, 'goods_id' => 1,
            'received_at' => '2026-11-08 10:00:00', 'unit_cost' => 175, 'fifo_quantity_remaining' => 0,
        ]);

        $service->refresh(1, 8, 55);

        $this->assertSame(2, DB::table('production_shift_report_material_costs')->count());
        $this->assertDatabaseHas('production_shift_report_material_costs', [
            'company_id' => 1, 'production_shift_report_id' => 8, 'goods_id' => 1,
            'unit_cost' => 175, 'total_cost' => 875, 'source_cost_layer_id' => 12,
        ]);
    }

    public function test_refresh_uses_latest_price_even_when_layer_is_fully_consumed_and_does_not_touch_inventory_movements(): void
    {
        $result = app(ProductionReportCurrentCostRefreshService::class)->refresh(1, 8, 55);

        $this->assertSame(1, $result['valued_count']);
        $this->assertSame(1, $result['unvalued_count']);
        $this->assertDatabaseHas('production_shift_report_material_costs', [
            'company_id' => 1, 'production_shift_report_id' => 8, 'goods_id' => 1,
            'quantity' => 5, 'valued_quantity' => 5, 'unit_cost' => 150, 'total_cost' => 750,
            'source_cost_layer_id' => 11,
        ]);
        $this->assertDatabaseHas('production_shift_report_material_costs', [
            'company_id' => 1, 'production_shift_report_id' => 8, 'goods_id' => 2,
            'valued_quantity' => 0, 'unit_cost' => null,
        ]);
        $this->assertSame(1, DB::table('inventory_movements')->count());
        $this->assertSame(-5.0, (float) DB::table('inventory_movements')->where('id', 90)->value('quantity'));
        $this->assertSame(0, DB::table('inventory_cost_layers')->where('goods_id', 1)->where('fifo_quantity_remaining', '<>', 0)->count());
        $this->assertDatabaseHas('audit_trails', [
            'company_id' => 1, 'user_id' => 55,
            'action' => 'production_shift_report.current_cost_refreshed',
            'auditable_type' => 'production_shift_reports', 'auditable_id' => 8,
        ]);
    }
}
