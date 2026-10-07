<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = request()->user();
        $account = $user->account;
        $company = $account?->company;

        if ($company) {
            session(['company_id' => $company->id]);
        }

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

        return view('dashboard', compact(
            'user',
            'account',
            'company',
            'subscription',
            'fiscalYears',
            'activeFiscalYear'
        ));
    }
}
