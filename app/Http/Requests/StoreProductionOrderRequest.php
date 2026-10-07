<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductionOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('production.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'order_item_id' => ['required', 'integer', 'exists:order_items,id'],
            'production_route_id' => ['required', 'integer', 'exists:production_routes,id'],
            'number' => ['required', 'string', 'max:100'],
            'planned_quantity' => ['required', 'numeric', 'gt:0'],
            'planned_start_at' => ['nullable', 'date'],
            'planned_end_at' => ['nullable', 'date', 'after_or_equal:planned_start_at'],
            'notes' => ['nullable', 'string'],
        ];
    }
}