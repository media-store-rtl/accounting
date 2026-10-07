<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function edit(Request $request): View
    {
        $company = $request->user()->currentCompany();
        abort_unless($company, 404);

        return view('company.edit', compact('company'));
    }

    public function update(Request $request): RedirectResponse
    {
        $company = $request->user()->currentCompany();
        abort_unless($company, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'trade_name' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:50'],
            'national_id' => ['nullable', 'string', 'max:50'],
            'registration_number' => ['nullable', 'string', 'max:50'],
            'economic_code' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:50'],
            'mobile' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'province' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string'],
            'postal_code' => ['nullable', 'string', 'max:30'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('companies', 'code')
                    ->where(fn ($query) => $query->where('account_id', $company->account_id))
                    ->ignore($company->id),
            ],
        ]);

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('company-logos', 'public');
        }
        unset($data['logo']);

        $company->update($data);

        return redirect()->route('dashboard')->with('success', 'اطلاعات مجموعه با موفقیت به‌روزرسانی شد.');
    }
}
