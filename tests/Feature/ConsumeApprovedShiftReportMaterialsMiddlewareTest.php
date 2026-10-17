<?php

namespace Tests\Feature;

use App\Http\Middleware\ConsumeApprovedShiftReportMaterials;
use App\Contracts\ProductionShiftReportMaterialConsumption;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Tests\TestCase;

class ConsumeApprovedShiftReportMaterialsMiddlewareTest extends TestCase
{
    private string $originalConnection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalConnection = config('database.default');
        config(['database.connections.shift_report_middleware_test' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => false,
        ]]);
        DB::purge('shift_report_middleware_test');
        DB::setDefaultConnection('shift_report_middleware_test');

        Schema::create('production_shift_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('status');
            $table->date('work_date')->nullable();
        });

        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('reference_type');
            $table->unsignedBigInteger('reference_id');
            $table->string('movement_type');
            $table->decimal('quantity', 12, 4)->default(0);
        });
    }

    protected function tearDown(): void
    {
        DB::setDefaultConnection($this->originalConnection);
        DB::purge('shift_report_middleware_test');
        parent::tearDown();
    }

    public function test_redirect_does_not_consume_materials_when_report_was_not_approved(): void
    {
        DB::table('production_shift_reports')->insert([
            'id' => 7,
            'company_id' => 1,
            'status' => 'pending',
        ]);

        $service = Mockery::mock(ProductionShiftReportMaterialConsumption::class);
        $service->shouldNotReceive('consumeApprovedReport');
        $this->app->instance(ProductionShiftReportMaterialConsumption::class, $service);

        $response = $this->runMiddleware(7);

        $this->assertSame(302, $response->getStatusCode());
    }

    public function test_redirect_consumes_materials_when_report_was_persisted_as_approved(): void
    {
        DB::table('production_shift_reports')->insert([
            'id' => 8,
            'company_id' => 1,
            'status' => 'pending',
        ]);

        $service = Mockery::mock(ProductionShiftReportMaterialConsumption::class);
        $service->shouldReceive('consumeApprovedReport')
            ->once()
            ->with(1, 8, 42);
        $this->app->instance(ProductionShiftReportMaterialConsumption::class, $service);

        $response = $this->runMiddleware(8, true);

        $this->assertSame(302, $response->getStatusCode());
    }

    public function test_already_approved_report_does_not_trigger_consumption_again(): void
    {
        DB::table('production_shift_reports')->insert([
            'id' => 9,
            'company_id' => 1,
            'status' => 'approved',
        ]);

        $service = Mockery::mock(ProductionShiftReportMaterialConsumption::class);
        $service->shouldNotReceive('consumeApprovedReport');
        $this->app->instance(ProductionShiftReportMaterialConsumption::class, $service);

        $response = $this->runMiddleware(9);

        $this->assertSame(302, $response->getStatusCode());
    }


    public function test_backdated_pending_report_is_blocked_before_approval_or_inventory_consumption(): void
    {
        DB::table('production_shift_reports')->insert([
            'id' => 10,
            'company_id' => 1,
            'status' => 'pending',
            'work_date' => now()->subDay()->toDateString(),
        ]);

        $service = Mockery::mock(ProductionShiftReportMaterialConsumption::class);
        $service->shouldNotReceive('consumeApprovedReport');
        $this->app->instance(ProductionShiftReportMaterialConsumption::class, $service);

        try {
            $this->runMiddleware(10, true);
            $this->fail('Expected a validation exception for a backdated report.');
        } catch (\\Illuminate\\Validation\\ValidationException $exception) {
            $this->assertArrayHasKey('work_date', $exception->errors());
        }

        $this->assertSame(
            'pending',
            DB::table('production_shift_reports')->where('id', 10)->value('status')
        );
    }

    private function runMiddleware(int $reportId, bool $persistApproval = false): RedirectResponse
    {
        $request = Request::create(
            '/production/shift-reports/'.$reportId.'/review',
            'POST',
            ['approve' => '1']
        );
        $session = app('session')->driver('array');
        $session->start();
        $session->put('company_id', 1);
        $request->setLaravelSession($session);
        $request->setUserResolver(fn () => (object) ['id' => 42]);

        $route = new Route(
            ['POST'],
            '/production/shift-reports/{report}/review',
            fn () => null
        );
        $route->name('production.shift-reports.review');
        $route->bind($request);
        $request->setRouteResolver(fn () => $route);

        $middleware = new ConsumeApprovedShiftReportMaterials();

        return $middleware->handle(
            $request,
            function () use ($reportId, $persistApproval) {
                if ($persistApproval) {
                    DB::table('production_shift_reports')
                        ->where('id', $reportId)
                        ->update(['status' => 'approved']);
                }

                return new RedirectResponse('/production/shift-reports');
            }
        );
    }
}
