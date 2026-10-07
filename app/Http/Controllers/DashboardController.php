<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = request()->user();

        $companies = $user->companies()
            ->where('companies.is_active', true)
            ->orderBy('companies.name')
            ->get();

        $requestedCompanyId = (int) request()->query('company');
        $sessionCompanyId = (int) session('company_id');
        $companyId = $requestedCompanyId ?: $sessionCompanyId;

        if (! $companyId || ! $companies->contains('id', $companyId)) {
            $companyId = $companies->first()?->id;
        }

        if ($companyId) {
            session(['company_id' => $companyId]);
        } else {
            session()->forget('fiscal_year_id');
        }

        $company = $companies->firstWhere('id', $companyId);
        $fiscalYears = $company?->fiscalYears()->orderByDesc('starts_at')->get() ?? collect();

        $fiscalYearId = (int) session('fiscal_year_id');
        $fiscalYear = $fiscalYears->firstWhere('id', $fiscalYearId);

        // If the selected year is missing/invalid/closed, automatically use the newest open year.
        if (! $fiscalYear || $fiscalYear->is_closed) {
            $fiscalYear = $fiscalYears->first(fn ($year) => ! $year->is_closed);

            if ($fiscalYear) {
                session(['fiscal_year_id' => $fiscalYear->id]);
            } else {
                session()->forget('fiscal_year_id');
            }
        }

        return view('dashboard', compact('user', 'companies', 'company', 'fiscalYears', 'fiscalYear'));
    }
}
