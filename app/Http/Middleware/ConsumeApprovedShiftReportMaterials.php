<?php

namespace App\Http\Middleware;

use App\Services\ProductionShiftReportMaterialConsumptionService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ConsumeApprovedShiftReportMaterials
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->hasSession()) {
            return $next($request);
        }

        $reportId = (int) $request->route('report');
        $companyId = (int) $request->session()->get('company_id');

        if ($reportId > 0 && $companyId > 0 && $request->routeIs('production.shift-reports.update')) {
            $report = DB::table('production_shift_reports')
                ->where('company_id', $companyId)
                ->where('id', $reportId)
                ->first(['status']);
            $hasConsumption = DB::table('inventory_movements')
                ->where('company_id', $companyId)
                ->where('reference_type', 'production_shift_reports')
                ->where('reference_id', $reportId)
                ->where('movement_type', 'production_consumption')
                ->exists();

            if ($report && $report->status === 'approved' && $hasConsumption) {
                throw ValidationException::withMessages([
                    'report' => 'این گزارش پس از ثبت مصرف مواد در انبار قابل ویرایش نیست؛ برای اصلاح باید مصرف انبار نیز به‌صورت همزمان اصلاح شود.',
                ]);
            }
        }

        if ($reportId > 0 && $companyId > 0 && $request->routeIs('production.shift-reports.review') && $request->boolean('approve')) {
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

        return $next($request);
    }
}
