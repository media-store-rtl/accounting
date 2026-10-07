<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

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
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('companies', 'code')
                    ->where(fn ($query) => $query->where('account_id', $company->account_id))
                    ->ignore($company->id),
            ],
        ], [
            'name.required' => 'نام مجموعه الزامی است.',
            'name.max' => 'نام مجموعه نباید بیشتر از ۲۵۵ کاراکتر باشد.',
            'code.required' => 'کد مجموعه الزامی است.',
            'code.unique' => 'این کد قبلاً برای مجموعه دیگری در همین حساب ثبت شده است.',
        ]);

        $company->update($data);

        return redirect()->route('dashboard')->with('success', 'اطلاعات مجموعه با موفقیت به‌روزرسانی شد.');
    }
}
