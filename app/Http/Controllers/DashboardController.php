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

        $companyId = (int) session('company_id');

        if (! $companyId || ! $companies->contains('id', $companyId)) {
            $companyId = $companies->first()?->id;
            if ($companyId) {
                session(['company_id' => $companyId]);
            }
        }

        $company = $companies->firstWhere('id', $companyId);
        $subscription = $company?->subscriptionEntitlement;
        $fiscalYears = $company?->fiscalYears()->orderByDesc('starts_at')->get() ?? collect();
        $activeFiscalYear = null;

        if ($company) {
            $activeFiscalYear = $company->fiscalYears()
                ->whereKey(session('fiscal_year_id'))
                ->where('is_closed', false)
                ->first();

            if (! $activeFiscalYear) {
                $activeFiscalYear = $company->fiscalYears()
                    ->where('is_closed', false)
                    ->orderByDesc('starts_at')
                    ->first();

                if ($activeFiscalYear) {
                    session(['fiscal_year_id' => $activeFiscalYear->id]);
                } else {
                    session()->forget('fiscal_year_id');
                }
            }
        }

        return view('dashboard', compact('user', 'companies', 'company', 'subscription', 'fiscalYears', 'activeFiscalYear'));
    }
}
