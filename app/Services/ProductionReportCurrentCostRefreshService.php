<?php

namespace App\\Services;

use Illuminate\\Support\\Facades\\DB;
use RuntimeException;

final class ProductionReportCurrentCostRefreshService
{
    public function refresh(int $companyId, int $reportId, int $actorId, string $method = 'WEB'): array
    {
        return DB::transaction(function () use ($companyId, $reportId, $actorId, $method) {
            $report = DB::table('production_shift_reports')
                ->where('company_id', $companyId)
                ->where('id', $reportId)
                ->lockForUpdate()
                ->first();

            if (!$report || !in_array($report->status, ['approved', 'received'], true)) {
                throw new RuntimeException('فقط گزارش تولید تأییدشده یا تحویل‌شده قابل بروزرسانی است.');
            }

            $materials = DB::table('production_shift_report_materials')
                ->where('company_id', $companyId)
                ->where('production_shift_report_id', $reportId)
                ->select('goods_id', DB::raw('SUM(quantity) as quantity'))
                ->groupBy('goods_id')
                ->orderBy('goods_id')
                ->get();

            if ($materials->isEmpty()) {
                throw new RuntimeException('برای این گزارش ریزمواد ثبت نشده است.');
            }

            $valued = 0;
            $unvalued = 0;
            $now = now();

            foreach ($materials as $material) {
                $quantity = (float) $material->quantity;
                if ($quantity <= 0) {
                    throw new RuntimeException('مقدار یکی از مواد گزارش معتبر نیست.');
                }

                // Latest known company-wide layer price, regardless of when stock
                // was consumed or whether the layer currently has remaining quantity.
                $layer = DB::table('inventory_cost_layers')
                    ->where('company_id', $companyId)
                    ->where('goods_id', (int) $material->goods_id)
                    ->orderByDesc('received_at')
                    ->orderByDesc('id')
                    ->first(['id', 'unit_cost']);

                $hasPrice = $layer !== null && $layer->unit_cost !== null && (float) $layer->unit_cost >= 0;
                $unitCost = $hasPrice ? (float) $layer->unit_cost : null;
                $valuedQuantity = $hasPrice ? $quantity : 0.0;
                $totalCost = $hasPrice ? $quantity * $unitCost : null;

                DB::table('production_shift_report_material_costs')->updateOrInsert(
                    [
                        'company_id' => $companyId,
                        'production_shift_report_id' => $reportId,
                        'goods_id' => (int) $material->goods_id,
                    ],
                    [
                        'quantity' => $quantity,
                        'valued_quantity' => $valuedQuantity,
                        'unit_cost' => $unitCost,
                        'total_cost' => $totalCost,
                        'source_cost_layer_id' => $hasPrice ? (int) $layer->id : null,
                        'refreshed_by' => $actorId,
                        'refreshed_at' => $now,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );

                if ($hasPrice) $valued++;
                else $unvalued++;
            }

            DB::table('audit_trails')->insert([
                'company_id' => $companyId,
                'user_id' => $actorId,
                'module' => 'costing',
                'action' => 'production_shift_report.current_cost_refreshed',
                'auditable_type' => 'production_shift_reports',
                'auditable_id' => $reportId,
                'before' => null,
                'after' => json_encode([
                    'valuation_basis' => 'latest_company_cost_layer',
                    'valued_material_count' => $valued,
                    'unvalued_material_count' => $unvalued,
                ]),
                'method' => $method,
                'status_code' => 200,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return ['valued_count' => $valued, 'unvalued_count' => $unvalued];
        });
    }
}
