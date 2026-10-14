<?php

namespace App\Http\Controllers;

use App\Models\Personnel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PersonnelController extends Controller
{
    public function index(Request $request): View
    {
        $personnel = Personnel::where('account_id', $request->user()->account_id)
            ->with('user')
            ->latest()
            ->paginate(20);

        return view('personnel.index', compact('personnel'));
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('users.create');
    }

    public function store(): RedirectResponse
    {
        abort(422, 'پرسنل جدید باید هم‌زمان با کاربر ایجاد شود.');
    }

    public function edit(Request $request, Personnel $personnel): View
    {
        $this->assertSameAccount($request, $personnel);
        return view('personnel.form', compact('personnel'));
    }

    public function update(Request $request, Personnel $personnel): RedirectResponse
    {
        $this->assertSameAccount($request, $personnel);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:50'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'national_id' => ['nullable', 'string', 'max:50'],
            'job_title' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:50'],
            'mobile' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'employment_type' => ['nullable', 'string', 'max:50'],
        ]);

        DB::transaction(function () use ($data, $personnel) {
            $personnel->update(array_merge($data, [
                'name' => trim($data['first_name'].' '.$data['last_name']),
            ]));

            if ($personnel->user) {
                $personnel->user->update([
                    'name' => $personnel->name,
                    'email' => $data['email'] ?? $personnel->user->email,
                ]);
            }
        });

        return redirect()->route('personnel.index')->with('success', 'پرسنل ویرایش شد.');
    }

    public function deactivate(Request $request, Personnel $personnel): RedirectResponse
    {
        $this->assertSameAccount($request, $personnel);
        abort_if($personnel->user_id && (int) $personnel->user_id === (int) $request->user()->id, 422, 'کاربر جاری را نمی‌توان غیرفعال کرد.');

        DB::transaction(function () use ($personnel) {
            $personnel->update(['is_active' => false]);

            if ($personnel->user) {
                $personnel->user->update(['is_active' => false]);
                $personnel->user->companies()->updateExistingPivot(
                    $personnel->user->companies()->pluck('companies.id')->all(),
                    ['is_active' => false]
                );
            }
        });

        return back()->with('success', 'پرسنل غیرفعال شد.');
    }

    public function laborRates(Request $request, Personnel $personnel): View
    {
        $this->assertSameAccount($request, $personnel);
        $companyId = \\App\\Support\\CompanyAuthorization::authorize($request, 'production.labor.rate.manage');
        abort_unless((int) DB::table('companies')->where('id', $companyId)->value('account_id') === (int) $personnel->account_id, 404);

        $rates = DB::table('personnel_labor_rates')
            ->where('company_id', $companyId)
            ->where('personnel_id', $personnel->id)
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->get();

        return view('personnel.labor-rates', compact('personnel', 'rates'));
    }

    public function storeLaborRate(Request $request, Personnel $personnel, \\App\\Services\\LaborCostService $service): RedirectResponse
    {
        $this->assertSameAccount($request, $personnel);
        $companyId = \\App\\Support\\CompanyAuthorization::authorize($request, 'production.labor.rate.manage');
        abort_unless((int) DB::table('companies')->where('id', $companyId)->value('account_id') === (int) $personnel->account_id, 404);

        $data = $request->validate([
            'rate_type' => ['required', 'in:hourly,per_unit'],
            'rate' => ['required', 'numeric', 'min:0'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
        ]);

        $service->setRate(
            $companyId,
            (int) $personnel->id,
            $data['rate_type'],
            (float) $data['rate'],
            $data['effective_from'],
            $data['effective_to'] ?? null
        );

        return redirect()->route('personnel.labor-rates', $personnel)->with('success', 'نرخ دستمزد ثبت شد.');
    }

    private function assertSameAccount(Request $request, Personnel $personnel): void
    {
        abort_unless((int) $personnel->account_id === (int) $request->user()->account_id, 404);
    }
}