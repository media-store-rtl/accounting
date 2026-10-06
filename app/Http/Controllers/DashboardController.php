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

        return view('dashboard', compact(
            'user',
            'account',
            'company',
            'subscription',
            'fiscalYears'
        ));
    }
}
