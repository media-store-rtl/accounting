<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLaborEntryRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'production_operation_run_id' => ['required','integer'],
            'personnel_id' => ['required','integer'],
            'measure_type' => ['required','in:hours,quantity'],
            'measure_quantity' => ['required','numeric','gt:0'],
            'worked_at' => ['required','date'],
            'notes' => ['nullable','string'],
        ];
    }
}