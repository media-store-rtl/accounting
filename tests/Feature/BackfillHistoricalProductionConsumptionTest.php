<?php

namespace Tests\Feature;

use App\Services\HistoricalProductionConsumptionReconciliationService;
use App\Services\InventoryService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BackfillHistoricalProductionConsumptionTest extends TestCase
{
    private string $originalConnection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalConnection = config('database.default');
        config(['database.connections.production_backfill_test' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]]);
        DB::purge('production_backfill_test');
        DB::setDefaultConnection('production_backfill_test');

        Schema::create('production_shift_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('production_section_id');
            $table->unsignedBigInteger('goods_id');
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
        Schema::create('production_shift_report_stages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('production_shift_report_id');
            $table->unsignedBigInteger('production_stage_id');
        });
        Schema::create('production_sections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('location_id');
        });
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->boolean('is_active')->default(true);
        });
        Schema::create('fiscal_years', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->date('starts_at');
            $table->date('ends_at');
            $table->boolean('is_closed')->default(false);
        });
        Schema::create('users', function (Blueprint $table) {
            $table->id();
        });
        Schema::create('audit_trails', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('user_id');
            $table->string('module');
            $table->string('action');
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id');
            $table->text('before')->nullable();
            $table->text('after')->nullable();
            $table->string('method');
            $table->unsignedInteger('status_code');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
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
        });
        Schema::create('inventory_cost_layers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('goods_id');
            $table->unsignedBigInteger('location_id');
            $table->dateTime('received_at');
            $table->decimal('fifo_quantity_remaining', 12, 4)->default(0);
        });
    }

    protected function tearDown(): void
    {
        DB::setDefaultConnection($this->originalConnection);
        DB::purge('production_backfill_test');
        parent::tearDown();
    }

    public function test_dry_run_refuses_to_infer_consumption_before_stock_arrived(): void
    {
        $this->seedReport('2026-10-13', 'received');
        DB::table('inventory_movements')->insert([
            'company_id' => 1, 'goods_id' => 5, 'location_id' => 10, 'quantity' => 2,
            'movement_type' => 'production_material_receipt', 'reference_type' => 'material_handovers',
            'reference_id' => 1, 'occurred_at' => '2026-10-17 12:00:00',
        ]);

        $exit = Artisan::call('costing:backfill-production-consumption', [
            '--company' => 1, '--report' => 20,
        ]);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('موجودی دفتری مادهٔ 5 در تاریخ گزارش کافی نبوده است', Artisan::output());
        $this->assertSame(1, DB::table('inventory_movements')->count());
    }

    public function test_dry_run_shows_eligible_report_without_writing_inventory_or_audit_rows(): void
    {
        $this->seedReport('2026-10-18', 'received');
        DB::table('inventory_movements')->insert([
            'company_id' => 1, 'goods_id' => 5, 'location_id' => 10, 'quantity' => 2,
            'movement_type' => 'production_material_receipt', 'reference_type' => 'material_handovers',
            'reference_id' => 1, 'occurred_at' => '2026-10-17 12:00:00',
        ]);
        DB::table('inventory_cost_layers')->insert([
            'company_id' => 1, 'goods_id' => 5, 'location_id' => 10,
            'received_at' => '2026-10-17 12:00:00', 'fifo_quantity_remaining' => 2,
        ]);

        $exit = Artisan::call('costing:backfill-production-consumption', [
            '--company' => 1, '--report' => 20,
        ]);

        $this->assertSame(0, $exit, Artisan::output());
        $this->assertSame(1, DB::table('inventory_movements')->count());
        $this->assertSame(0, DB::table('audit_trails')->count());
    }

    public function test_rejected_report_is_never_eligible_for_backfill(): void
    {
        $this->seedReport('2026-10-18', 'rejected');

        $exit = Artisan::call('costing:backfill-production-consumption', [
            '--company' => 1, '--report' => 20,
        ]);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('فقط گزارش تأییدشده یا تحویل‌شده', Artisan::output());
        $this->assertSame(0, DB::table('inventory_movements')->count());
    }

    public function test_preview_refuses_backfill_when_an_unmatched_legacy_consumption_exists(): void
    {
        $this->seedReport('2026-10-18', 'received');
        DB::table('inventory_movements')->insert([
            [
                'company_id' => 1, 'goods_id' => 5, 'location_id' => 10, 'quantity' => 2,
                'movement_type' => 'production_material_receipt', 'reference_type' => 'material_handovers',
                'reference_id' => 1, 'occurred_at' => '2026-10-17 12:00:00',
            ],
            [
                'company_id' => 1, 'goods_id' => 5, 'location_id' => 10, 'quantity' => -1,
                'movement_type' => 'production_consumption', 'reference_type' => 'legacy',
                'reference_id' => 9, 'occurred_at' => '2026-10-17 13:00:00',
            ],
        ]);
        DB::table('inventory_cost_layers')->insert([
            'company_id' => 1, 'goods_id' => 5, 'location_id' => 10,
            'received_at' => '2026-10-17 12:00:00', 'fifo_quantity_remaining' => 2,
        ]);

        $result = app(HistoricalProductionConsumptionReconciliationService::class)->preview(1, 20);

        $this->assertFalse($result['eligible']);
        $this->assertStringContainsString('گردش مصرف قبلی وجود دارد', $result['reason']);
        $this->assertSame(2, DB::table('inventory_movements')->count());
    }

    public function test_web_reconciliation_rechecks_eligibility_and_records_audit_attribution(): void
    {
        $this->seedReport('2026-10-18', 'received');
        DB::table('inventory_movements')->insert([
            'company_id' => 1, 'goods_id' => 5, 'location_id' => 10, 'quantity' => 2,
            'movement_type' => 'production_material_receipt', 'reference_type' => 'material_handovers',
            'reference_id' => 1, 'occurred_at' => '2026-10-17 12:00:00',
        ]);
        DB::table('inventory_cost_layers')->insert([
            'company_id' => 1, 'goods_id' => 5, 'location_id' => 10,
            'received_at' => '2026-10-17 12:00:00', 'fifo_quantity_remaining' => 2,
        ]);
        DB::table('users')->insert(['id' => 55]);

        $inventory = \Mockery::mock(InventoryService::class);
        $inventory->shouldReceive('consumeFromProductionSection')
            ->once()
            ->with(
                1,
                2,
                5,
                2.0,
                'production_shift_reports',
                20,
                \Mockery::on(fn ($metadata) => ($metadata['historical_reconciliation'] ?? false) === true
                    && ($metadata['shift_report_id'] ?? null) === 20
                    && ($metadata['effective_occurred_at'] ?? null) === '2026-10-18 23:59:59')
            );
        $this->app->instance(InventoryService::class, $inventory);

        $result = app(HistoricalProductionConsumptionReconciliationService::class)->apply(1, 20, 55, 'WEB');

        $this->assertTrue($result['eligible']);
        $this->assertDatabaseHas('audit_trails', [
            'company_id' => 1,
            'user_id' => 55,
            'action' => 'production_shift_report.historical_consumption_reconciled',
            'auditable_type' => 'production_shift_reports',
            'auditable_id' => 20,
            'method' => 'WEB',
            'status_code' => 200,
        ]);
    }

    private function seedReport(string $workDate, string $status): void
    {
        DB::table('locations')->insert(['id' => 10, 'company_id' => 1, 'is_active' => true]);
        DB::table('production_sections')->insert(['id' => 2, 'location_id' => 10]);
        DB::table('production_shift_reports')->insert([
            'id' => 20, 'company_id' => 1, 'production_section_id' => 2, 'goods_id' => 99,
            'order_id' => null, 'work_date' => $workDate, 'status' => $status,
        ]);
        DB::table('production_shift_report_materials')->insert([
            'id' => 30, 'company_id' => 1, 'production_shift_report_id' => 20, 'goods_id' => 5, 'quantity' => 2,
        ]);
        DB::table('fiscal_years')->insert([
            'id' => 1, 'company_id' => 1, 'starts_at' => '2026-01-01', 'ends_at' => '2026-12-31', 'is_closed' => false,
        ]);
    }
}
