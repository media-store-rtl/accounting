<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFiscalYearRequest;
use App\Http\Requests\UpdateFiscalYearRequest;
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

    public function edit(FiscalYear $fiscalYear): View
    {
        $this->ensureBelongsToCurrentCompany($fiscalYear);

        return view('fiscal-years.edit', compact('fiscalYear'));
    }

    public function update(UpdateFiscalYearRequest $request, FiscalYear $fiscalYear): RedirectResponse
    {
        $this->ensureBelongsToCurrentCompany($fiscalYear);

        if ($fiscalYear->is_closed) {
            abort(422, 'سال مالی بسته قابل ویرایش نیست.');
        }

        $data = $request->validated();
        $company = $request->user()->account->company;

        DB::transaction(function () use ($company, $fiscalYear, $data) {
            $company = $company->newQuery()->lockForUpdate()->findOrFail($company->id);
            $fiscalYear = $company->fiscalYears()->lockForUpdate()->findOrFail($fiscalYear->id);

            if ($fiscalYear->is_closed) {
                abort(422, 'سال مالی بسته قابل ویرایش نیست.');
            }

            if ($company->fiscalYears()
                ->where($fiscalYear->getKeyName(), '<>', $fiscalYear->getKey())
                ->whereDate('starts_at', '<=', $data['ends_at'])
                ->whereDate('ends_at', '>=', $data['starts_at'])
                ->exists()) {
                abort(422, 'بازه سال مالی جدید با یک سال مالی موجود هم‌پوشانی دارد.');
            }

            $fiscalYear->update([
                'name' => $data['name'],
                'starts_at' => $data['starts_at'],
                'ends_at' => $data['ends_at'],
            ]);
        });

        return redirect()->route('fiscal-years.index')
            ->with('success', "سال مالی «{$fiscalYear->name}» به‌روزرسانی شد.");
    }

    public function destroy(FiscalYear $fiscalYear): RedirectResponse
    {
        $this->ensureBelongsToCurrentCompany($fiscalYear);

        if ($fiscalYear->is_closed) {
            abort(422, 'سال مالی بسته قابل حذف نیست.');
        }

        $fiscalYear->delete();

        if ((int) session('fiscal_year_id') === (int) $fiscalYear->id) {
            session()->forget('fiscal_year_id');
        }

        return redirect()->route('fiscal-years.index')
            ->with('success', "سال مالی «{$fiscalYear->name}» حذف شد.");
    }

    public function activate(FiscalYear $fiscalYear): RedirectResponse
    {
        $this->ensureBelongsToCurrentCompany($fiscalYear);

        if ($fiscalYear->is_closed) {
            abort(422, 'سال مالی بسته را نمی‌توان فعال کرد.');
        }

        session(['fiscal_year_id' => $fiscalYear->id]);

        return redirect()->route('dashboard');
    }

    private function ensureBelongsToCurrentCompany(FiscalYear $fiscalYear): void
    {
        $company = request()->user()->account->company;

        abort_unless($company && $fiscalYear->company_id === $company->id, 404);
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
