<?php

namespace Tests\\Feature;

use App\\Http\\Middleware\\ConsumeApprovedShiftReportMaterials;
use App\\Services\\ProductionShiftReportMaterialConsumptionService;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Http\\Request;
use Illuminate\\Routing\\Route;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Schema;
use Mockery;
use Symfony\\Component\\HttpFoundation\\RedirectResponse;
use Tests\\TestCase;

class ConsumeApprovedShiftReportMaterialsMiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('production_shift_reports');

        Schema::create('production_shift_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('status');
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
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('production_shift_reports');

        parent::tearDown();
    }

    public function test_redirect_does_not_consume_materials_when_report_was_not_approved(): void
    {
        DB::table('production_shift_reports')->insert([
            'id' => 7,
            'company_id' => 1,
            'status' => 'pending',
        ]);

        $service = Mockery::mock(ProductionShiftReportMaterialConsumptionService::class);
        $service->shouldNotReceive('consumeApprovedReport');
        $this->app->instance(ProductionShiftReportMaterialConsumptionService::class, $service);

        $response = $this->runMiddleware(7);

        $this->assertSame(302, $response->getStatusCode());
    }

    public function test_redirect_consumes_materials_when_report_was_persisted_as_approved(): void
    {
        DB::table('production_shift_reports')->insert([
            'id' => 8,
            'company_id' => 1,
            'status' => 'approved',
        ]);

        $service = Mockery::mock(ProductionShiftReportMaterialConsumptionService::class);
        $service->shouldReceive('consumeApprovedReport')
            ->once()
            ->with(1, 8, 42);
        $this->app->instance(ProductionShiftReportMaterialConsumptionService::class, $service);

        $response = $this->runMiddleware(8);

        $this->assertSame(302, $response->getStatusCode());
    }

    private function runMiddleware(int $reportId): RedirectResponse
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
            fn () => new RedirectResponse('/production/shift-reports')
        );
    }
}
