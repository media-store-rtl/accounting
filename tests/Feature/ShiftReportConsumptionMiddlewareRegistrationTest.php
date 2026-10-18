<?php

namespace Tests\Feature;

use App\Http\Middleware\ConsumeApprovedShiftReportMaterials;
use Tests\TestCase;

class ShiftReportConsumptionMiddlewareRegistrationTest extends TestCase
{
    public function test_consumption_middleware_is_registered_in_the_web_group_not_globally(): void
    {
        $bootstrap = file_get_contents(base_path('bootstrap/app.php'));

        $this->assertIsString($bootstrap);
        $this->assertStringContainsString(
            '$middleware->web(append: [ConsumeApprovedShiftReportMaterials::class]);',
            $bootstrap
        );
        $this->assertStringNotContainsString(
            '->append(ConsumeApprovedShiftReportMaterials::class)',
            $bootstrap
        );
    }
}
