<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFiscalYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        $company = $this->user()?->account?->company;

        return $company?->fiscalYears()->whereKey($this->route('fiscalYear'))->exists() ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after_or_equal:starts_at'],
        ];
    }
}