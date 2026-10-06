<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCompanyRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function edit(): View
    {
        return view('company.edit', [
            'company' => request()->user()->account->company,
        ]);
    }

    public function update(UpdateCompanyRequest $request): RedirectResponse
    {
        $company = $request->user()->account->company;
        $data = $request->validated();

        unset($data['logo']);

        if ($request->hasFile('logo')) {
            if ($company->logo_path) {
                Storage::disk('public')->delete($company->logo_path);
            }

            $data['logo_path'] = $request->file('logo')->store('companies', 'public');
        }

        $company->update($data);

        return redirect()->route('company.edit')->with('success', 'اطلاعات شرکت با موفقیت ذخیره شد.');
    }
}
