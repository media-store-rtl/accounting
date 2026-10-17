<?php

namespace App\Http\Middleware;

use App\Services\ProductionShiftReportMaterialConsumptionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class ConsumeApprovedShiftReportMaterials
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->routeIs('production.shift-reports.review') && $request->boolean('approve')) {
            $reportId = (int) $request->route('report');
            $companyId = (int) $request->user()?->currentCompanyId();

            if ($reportId > 0 && $companyId > 0) {
                return DB::transaction(function () use ($request, $next, $companyId, $reportId) {
                    $response = $next($request);
                    if ($response->isSuccessful() || $response->isRedirection()) {
                        app(ProductionShiftReportMaterialConsumptionService::class)->consumeApprovedReport(
                            $companyId,
                            $reportId,
                            (int) $request->user()->id
                        );
                    }
                    return $response;
                });
            }
        }

        return $next($request);
    }
}
