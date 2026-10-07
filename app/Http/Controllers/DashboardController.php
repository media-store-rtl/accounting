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
        }

        $company = $companies->firstWhere('id', $companyId);
        $fiscalYears = $company?->fiscalYears()->orderByDesc('starts_at')->get() ?? collect();

        return view('dashboard', compact('user', 'companies', 'company', 'fiscalYears'));
    }
}
