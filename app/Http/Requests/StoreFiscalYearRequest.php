<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFiscalYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->account?->company?->id !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after_or_equal:starts_at'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['starts_at' => $this->input('starts_at') ?: now()->toDateString()]);
    }
}
