<?php

namespace Tests\Feature;

use App\Http\Controllers\CostingReportController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CostingReportDirectConsumptionAllocationTest extends TestCase
{
    private string $originalConnection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalConnection = config('database.default');
        config(['database.connections.costing_direct_allocation_test' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]]);
        DB::purge('costing_direct_allocation_test');
        DB::setDefaultConnection('costing_direct_allocation_test');

        Schema::create('production_shift_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('production_section_id');
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
            $table->unsignedBigInteger('location_id');
            $table->decimal('quantity', 12, 4);
            $table->string('movement_type');
            $table->string('reference_type');
            $table->unsignedBigInteger('reference_id');
            $table->dateTime('occurred_at');
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
        DB::setDefaultConnection($this->originalConnection);
        DB::purge('costing_direct_allocation_test');
        parent::tearDown();
    }

    public function test_directly_linked_consumption_is_not_stolen_by_an_earlier_report_in_the_same_pool(): void
    {
        DB::table('locations')->insert(['id' => 10, 'company_id' => 1]);
        DB::table('production_sections')->insert(['id' => 5, 'location_id' => 10]);

        DB::table('production_shift_reports')->insert([
            ['id' => 1, 'company_id' => 1, 'production_section_id' => 5, 'order_id' => 7, 'work_date' => '2026-10-22', 'status' => 'received'],
            ['id' => 2, 'company_id' => 1, 'production_section_id' => 5, 'order_id' => 7, 'work_date' => '2026-10-22', 'status' => 'received'],
        ]);
        DB::table('production_shift_report_materials')->insert([
            ['id' => 11, 'company_id' => 1, 'production_shift_report_id' => 1, 'goods_id' => 99, 'quantity' => 2],
            ['id' => 12, 'company_id' => 1, 'production_shift_report_id' => 2, 'goods_id' => 99, 'quantity' => 2],
        ]);
        DB::table('inventory_movements')->insert([
            'id' => 20,
            'company_id' => 1,
            'goods_id' => 99,
            'location_id' => 10,
            'quantity' => -2,
            'movement_type' => 'production_consumption',
            'reference_type' => 'production_shift_reports',
            'reference_id' => 2,
            'occurred_at' => '2026-10-22 23:59:59',
            'metadata' => json_encode(['shift_report_id' => 2, 'production_section_id' => 5, 'order_id' => 7]),
        ]);

        $controller = new CostingReportController();
        $method = new \ReflectionMethod($controller, 'productionReportConsumptionMovements');
        $method->setAccessible(true);
        $allocation = $method->invoke($controller, 1, 2);

        $this->assertSame([20], $allocation['movements']->pluck('id')->map(fn ($id) => (int) $id)->all());
        $this->assertEqualsWithDelta(2.0, (float) $allocation['allocated_by_goods'][99], 0.0001);

        $earlierReportAllocation = $method->invoke($controller, 1, 1);
        $this->assertTrue($earlierReportAllocation['movements']->isEmpty());
        $this->assertSame([], $earlierReportAllocation['allocated_by_goods']);
    }
}
