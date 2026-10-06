<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFiscalYearRequest;
use App\Models\FiscalYear;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class FiscalYearController extends Controller
{
    public function index(): View
    {
        $company = request()->user()->account->company;

        return view('fiscal-years.index', [
            'company' => $company,
            'fiscalYears' => $company->fiscalYears()->orderByDesc('starts_at')->get(),
            'subscription' => $company->subscriptionEntitlement,
        ]);
    }

    public function create(): View
    {
        return view('fiscal-years.create', [
            'startsAt' => now()->toDateString(),
        ]);
    }

    public function store(StoreFiscalYearRequest $request): RedirectResponse
    {
        $company = $request->user()->account->company;
        $subscription = $company->subscriptionEntitlement;

        if (! $subscription?->isActive()) {
            return redirect()->route('dashboard')
                ->with('subscription_error', 'برای ایجاد سال مالی باید اشتراک فعال داشته باشید.');
        }

        $data = $request->validated();

        // A fiscal year is attached to the entitlement that authorized its creation.
        // This prevents renewal of the same subscription from creating another fiscal year.
        if ($company->fiscalYears()->where('subscription_entitlement_id', $subscription->id)->exists()) {
            return back()->withErrors([
                'name' => 'برای این اشتراک قبلاً یک سال مالی ایجاد شده است.',
            ])->withInput();
        }

        // The database unique constraint on (company_id, subscription_entitlement_id)
        // is the final concurrency guard; this check keeps the normal UX clear.
        $fiscalYear = $company->fiscalYears()->create([
            'name' => $data['name'],
            'code' => 'FY-'.Str::upper(Str::random(10)),
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'],
            'subscription_entitlement_id' => $subscription->id,
            'is_closed' => false,
        ]);

        return redirect()->route('fiscal-years.index')
            ->with('success', "سال مالی «{$fiscalYear->name}» ثبت شد.");
    }
}
