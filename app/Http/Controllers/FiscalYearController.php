<?php

namespace App\Http\Controllers;

use App\Models\FiscalYear;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class FiscalYearController extends Controller
{
    public function index(Request $request): View
    {
        $company = $request->user()->currentCompany();
        abort_unless($company, 409);

        $fiscalYears = $company->fiscalYears()->orderByDesc('starts_at')->get();
        $currentFiscalYearId = (int) $request->session()->get('fiscal_year_id');

        if (! $fiscalYears->contains('id', $currentFiscalYearId)) {
            $currentFiscalYearId = 0;
        }

        return view('fiscal-years.index', compact('company', 'fiscalYears', 'currentFiscalYearId'));
    }

    public function create(Request $request): View
    {
        $company = $request->user()->currentCompany();
        abort_unless($company, 409);

        return view('fiscal-years.form', compact('company'));
    }

    public function store(Request $request): RedirectResponse
    {
        $company = $request->user()->currentCompany();
        abort_unless($company, 409);
        abort_unless($company->subscriptionEntitlement?->isActive(), 403, 'برای ایجاد سال مالی، اشتراک فعال لازم است.');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:50', Rule::unique('fiscal_years', 'code')->where(fn ($q) => $q->where('company_id', $company->id))],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after_or_equal:starts_at'],
        ]);

        $fiscalYear = DB::transaction(function () use ($company, $data) {
            $company->newQuery()->whereKey($company->id)->lockForUpdate()->firstOrFail();

            abort_if(
                $company->fiscalYears()->where('is_closed', false)->exists(),
                422,
                'این مجموعه در حال حاضر یک سال مالی باز دارد.'
            );

            $this->assertNoOverlap($company->id, $data['starts_at'], $data['ends_at']);

            return $company->fiscalYears()->create([...$data, 'is_closed' => false]);
        });

        // A newly created fiscal year becomes the active period immediately.
        $request->session()->put('fiscal_year_id', $fiscalYear->id);

        return redirect()->route('dashboard')->with('success', 'سال مالی با موفقیت تعریف و فعال شد.');
    }

    public function select(Request $request, FiscalYear $fiscalYear): RedirectResponse
    {
        $this->assertBelongsToCurrentCompany($request, $fiscalYear);
        abort_if($fiscalYear->is_closed, 422, 'سال مالی بسته شده قابل انتخاب نیست.');

        $request->session()->put('fiscal_year_id', $fiscalYear->id);

        return redirect()->route('dashboard')->with('success', 'سال مالی جاری تغییر کرد.');
    }

    public function edit(Request $request, FiscalYear $fiscalYear): View
    {
        $company = $this->assertBelongsToCurrentCompany($request, $fiscalYear);

        return view('fiscal-years.form', compact('company', 'fiscalYear'));
    }

    public function update(Request $request, FiscalYear $fiscalYear): RedirectResponse
    {
        $company = $this->assertBelongsToCurrentCompany($request, $fiscalYear);
        abort_if($fiscalYear->is_closed, 422, 'سال مالی بسته شده قابل ویرایش نیست.');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:50', Rule::unique('fiscal_years', 'code')->ignore($fiscalYear->id)->where(fn ($q) => $q->where('company_id', $company->id))],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after_or_equal:starts_at'],
        ]);

        $this->assertNoOverlap($company->id, $data['starts_at'], $data['ends_at'], $fiscalYear->id);
        $fiscalYear->update($data);

        return redirect()->route('fiscal-years.index')->with('success', 'سال مالی به‌روزرسانی شد.');
    }

    public function close(Request $request, FiscalYear $fiscalYear): RedirectResponse
    {
        $this->assertBelongsToCurrentCompany($request, $fiscalYear);
        abort_if($fiscalYear->is_closed, 422, 'سال مالی قبلاً بسته شده است.');

        $fiscalYear->update(['is_closed' => true]);

        if ((int) $request->session()->get('fiscal_year_id') === (int) $fiscalYear->id) {
            $request->session()->forget('fiscal_year_id');
        }

        return back()->with('success', 'سال مالی بسته شد.');
    }

    private function assertBelongsToCurrentCompany(Request $request, FiscalYear $fiscalYear): \App\Models\Company
    {
        $company = $request->user()->currentCompany();
        abort_unless($company && (int) $fiscalYear->company_id === (int) $company->id, 404);

        return $company;
    }

    private function assertNoOverlap(int $companyId, string $startsAt, string $endsAt, ?int $ignoreId = null): void
    {
        $q = FiscalYear::where('company_id', $companyId)
            ->where('starts_at', '<=', $endsAt)
            ->where('ends_at', '>=', $startsAt);

        if ($ignoreId) {
            $q->where('id', '!=', $ignoreId);
        }

        abort_if($q->exists(), 422, 'بازه زمانی این سال مالی با یک سال مالی دیگر هم‌پوشانی دارد.');
    }
}
