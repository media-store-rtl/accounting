<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFiscalYearRequest;
use App\Models\FiscalYear;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
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
        $data = $request->validated();

        $fiscalYear = DB::transaction(function () use ($company, $data) {
            // Lock the owning company so two concurrent create requests cannot
            // both pass the entitlement/overlap checks.
            $company = $company->newQuery()->lockForUpdate()->findOrFail($company->id);
            $subscription = $company->subscriptionEntitlement;

            if (! $subscription?->isActive()) {
                abort(403, 'برای ایجاد سال مالی باید اشتراک فعال داشته باشید.');
            }

            if ($company->fiscalYears()
                ->where('subscription_entitlement_id', $subscription->id)
                ->exists()) {
                abort(422, 'برای این اشتراک قبلاً یک سال مالی ایجاد شده است.');
            }

            $overlaps = $company->fiscalYears()
                ->whereDate('starts_at', '<=', $data['ends_at'])
                ->whereDate('ends_at', '>=', $data['starts_at'])
                ->exists();

            if ($overlaps) {
                abort(422, 'بازه سال مالی جدید با یک سال مالی موجود هم‌پوشانی دارد.');
            }

            return $company->fiscalYears()->create([
                'name' => $data['name'],
                'code' => 'FY-'.Str::upper(Str::random(10)),
                'starts_at' => $data['starts_at'],
                'ends_at' => $data['ends_at'],
                'subscription_entitlement_id' => $subscription->id,
                'is_closed' => false,
            ]);
        });

        return redirect()->route('fiscal-years.index')
            ->with('success', "سال مالی «{$fiscalYear->name}» ثبت شد.");
    }
}
