<?php

namespace App\Services;

use App\Models\Production;
use App\Support\CompanyAuthorization;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProductionOrderService
{
    public function __construct(private DatabaseManager $db)
    {
    }

    public function createFromOrder(Request $request, array $data, int $companyId): Production
    {
        return $this->db->transaction(function () use ($request, $data, $companyId) {
            $order = $this->db->table('orders')
                ->where('id', $data['order_id'])
                ->where('company_id', $companyId)
                ->lockForUpdate()
                ->first();

            if (! $order) {
                throw ValidationException::withMessages(['order_id' => 'سفارش متعلق به شرکت جاری نیست.']);
            }

            if (! in_array($order->status, ['production_required', 'in_production'], true)) {
                throw ValidationException::withMessages(['order_id' => 'این سفارش در وضعیت قابل ایجاد دستور تولید نیست.']);
            }

            $item = $this->db->table('order_items')
                ->where('id', $data['order_item_id'])
                ->where('order_id', $order->id)
                ->lockForUpdate()
                ->first();

            if (! $item) {
                throw ValidationException::withMessages(['order_item_id' => 'قلم انتخاب‌شده متعلق به سفارش نیست.']);
            }

            if ((float) $item->shortage_quantity <= 0) {
                throw ValidationException::withMessages(['order_item_id' => 'برای این قلم کسری تولید ثبت نشده است.']);
            }

            $route = $this->db->table('production_routes')
                ->where('id', $data['production_route_id'])
                ->where('company_id', $companyId)
                ->where('goods_id', $item->goods_id)
                ->where('status', 'active')
                ->first();

            if (! $route) {
                throw ValidationException::withMessages(['production_route_id' => 'مسیر تولید فعال و متناظر با کالای سفارش یافت نشد.']);
            }

            if ((float) $data['planned_quantity'] > (float) $item->shortage_quantity) {
                throw ValidationException::withMessages(['planned_quantity' => 'مقدار تولید نمی‌تواند بیشتر از کسری قلم سفارش باشد.']);
            }

            $fiscalYearId = (int) $order->fiscal_year_id;
            $fiscalYearValid = $this->db->table('fiscal_years')
                ->where('id', $fiscalYearId)
                ->where('company_id', $companyId)
                ->where('is_closed', false)
                ->exists();

            if (! $fiscalYearValid) {
                throw ValidationException::withMessages(['order_id' => 'سال مالی سفارش معتبر یا باز نیست.']);
            }

            $productionId = $this->db->table('productions')->insertGetId([
                'company_id' => $companyId,
                'fiscal_year_id' => $fiscalYearId,
                'order_id' => $order->id,
                'order_item_id' => $item->id,
                'goods_id' => $item->goods_id,
                'production_route_id' => $route->id,
                'number' => $data['number'],
                'planned_quantity' => $data['planned_quantity'],
                'produced_quantity' => 0,
                'rejected_quantity' => 0,
                'status' => 'draft',
                'planned_start_at' => $data['planned_start_at'] ?? null,
                'planned_end_at' => $data['planned_end_at'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($order->status === 'production_required') {
                $this->db->table('orders')->where('id', $order->id)->update([
                    'status' => 'in_production',
                    'updated_at' => now(),
                ]);
            }

            return Production::query()->findOrFail($productionId);
        });
    }
}