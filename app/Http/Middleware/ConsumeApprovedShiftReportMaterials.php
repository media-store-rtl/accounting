<?php

namespace App\Http\Middleware;

use App\Contracts\ProductionShiftReportMaterialConsumption;
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
                $reportBeforeReview = DB::table('production_shift_reports')
                    ->where('company_id', $companyId)
                    ->where('id', $reportId)
                    ->first(['status', 'work_date']);

                // Automatic consumption uses today's available stock. Do not post it with a
                // historical or future effective date; those cases need a separately controlled flow.
                $today = now()->toDateString();
                if ($reportBeforeReview
                    && $reportBeforeReview->status === 'pending'
                    && $reportBeforeReview->work_date !== null) {
                    $workDate = (string) $reportBeforeReview->work_date;

                    if ($workDate < $today) {
                        throw ValidationException::withMessages([
                            'work_date' => 'این گزارش تاریخ گذشته دارد؛ برای جلوگیری از مصرف موجودی امروز با تاریخ تاریخی، تأیید خودکار مصرف انبار مجاز نیست. ابتدا باید از مسیر تطبیق تاریخیِ تأییدشده استفاده شود.',
                        ]);
                    }

                    if ($workDate > $today) {
                        throw ValidationException::withMessages([
                            'work_date' => 'تاریخ این گزارش در آینده است؛ تأیید و مصرف خودکار مواد پیش از تاریخ گزارش مجاز نیست.',
                        ]);
                    }
                }

                $statusBeforeReview = $reportBeforeReview->status ?? null;

                $response = $next($request);
                // A redirect can also represent validation failure or a rejected review.
                // Only create inventory consumption after the controller actually persisted approval.
                if ($statusBeforeReview === 'pending' && ($response->isSuccessful() || $response->isRedirection())) {
                    $status = DB::table('production_shift_reports')
                        ->where('company_id', $companyId)
                        ->where('id', $reportId)
                        ->value('status');

                    if ($status === 'approved') {
                        app(ProductionShiftReportMaterialConsumption::class)->consumeApprovedReport(
                            $companyId,
                            $reportId,
                            (int) $request->user()->id
                        );
                    }
                }
                return $response;
            });
        }

        return $next($request);
    }
}
