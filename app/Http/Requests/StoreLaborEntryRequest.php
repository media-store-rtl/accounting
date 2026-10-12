<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLaborEntryRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'fiscal_year_id' => ['required', 'integer'],
            'production_section_id' => ['required', 'integer'],
            'goods_id' => ['required', 'integer'],
            'personnel_id' => ['required', 'integer'],
            'hours_worked' => ['required', 'numeric', 'gt:0'],
            'quantity_completed' => ['required', 'numeric', 'min:0'],
            'pay_basis' => ['required', 'in:hourly,per_unit'],
            'worked_at' => ['required', 'date'],
            'shift_name' => ['nullable', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}