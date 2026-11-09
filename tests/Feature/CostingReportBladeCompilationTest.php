<?php

namespace Tests\Feature;

use Tests\TestCase;

class CostingReportBladeCompilationTest extends TestCase
{
    public function test_costing_report_blade_template_compiles_to_valid_php(): void
    {
        $source = file_get_contents(resource_path('views/reports/costing.blade.php'));

        $this->assertNotFalse($source);

        $compiled = app('blade.compiler')->compileString($source);

        // TOKEN_PARSE asks PHP to validate syntax without executing the view.
        token_get_all($compiled, TOKEN_PARSE);

        $this->assertNotSame('', $compiled);
    }
}
