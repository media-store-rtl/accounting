<?php

namespace App\Http\Controllers;

use App\Services\InventoryService;
use App\Services\InventoryValuationService;
use App\Support\CompanyAuthorization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MaterialHandoverController extends Controller
{
    public function store(Request $request, InventoryService $inventory, InventoryValuationService $valuation): JsonResponse
    {
        $companyId = CompanyAuthorization::authorize($request, 'supply_request.handover.create');

        $data = $request->validate([
            'supply_request_id' => ['required', 'integer'],
            'warehouse_location_id' => ['required', 'integer'],
            'production_section_id' => ['nullable', 'integer'],
            'production_stage_id' => ['nullable', 'integer'],
            'delivered_by_user_id' => ['required', 'integer'],
            'received_by_user_id' => ['required', 'integer'],
            'handed_over_at' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.goods_id' => ['required', 'integer', 'distinct'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
        ]);

        $handoverId = DB::transaction(function () use ($data, $companyId, $inventory, $valuation) {
            $requestRow = DB::table('supply_requests')
                ->where('id', $data['supply_request_id'])
                ->where('company_id', $companyId)
                ->lockForUpdate()
                ->first();

            abort_unless($requestRow && !in_array($requestRow->status, ['fulfilled', 'cancelled'], true), 422, 'درخواست تأمین قابل تحویل نیست.');

            $productionSectionId = isset($data['production_section_id']) && $data['production_section_id'] !== ''
                ? (int) $data['production_section_id']
                : ($requestRow->production_section_id ? (int) $requestRow->production_section_id : null);
            $productionStageId = isset($data['production_stage_id']) && $data['production_stage_id'] !== ''
                ? (int) $data['production_stage_id']
                : null;

            if ($productionSectionId) {
                abort_unless(
                    DB::table('production_sections as ps')
                        ->join('locations as l', 'l.id', '=', 'ps.location_id')
                        ->where('ps.id', $productionSectionId)
                        ->where('l.company_id', $companyId)
                        ->where('l.is_active', true)
                        ->where('ps.is_active', true)
                        ->exists(),
                    422,
                    'قسمت تولید معتبر نیست.'
                );
            }

            if ($productionStageId) {
                abort_unless($productionSectionId && DB::table('production_stages as s')
                    ->join('production_routes as pr', 'pr.id', '=', 's.production_route_id')
                    ->where('s.id', $productionStageId)
                    ->where('s.production_section_id', $productionSectionId)
                    ->where('s.status', 'active')
                    ->where('pr.company_id', $companyId)
                    ->exists(), 422, 'مرحله تولید با قسمت انتخاب‌شده سازگار نیست.');
            }

            abort_unless(
                DB::table('locations')
                    ->where('id', $data['warehouse_location_id'])
                    ->where('company_id', $companyId)
                    ->whereRaw("LOWER(TRIM(type)) IN (?, ?, ?, ?)", ['warehouse', 'انبار', 'مواد', 'مواد اولیه'])
                    ->where('is_active', true)
                    ->whereNotExists(function ($query) {
                        $query->select(DB::raw(1))
                            ->from('production_sections as ps')
                            ->whereColumn('ps.location_id', 'locations.id')
                            ->where('ps.is_active', true);
                    })
                    ->exists(),
                422,
                'انبار معتبر نیست.'
            );

            foreach (['delivered_by_user_id', 'received_by_user_id'] as $key) {
                abort_unless(
                    DB::table('company_user')->where('company_id', $companyId)->where('user_id', $data[$key])->where('is_active', true)->exists(),
                    422,
                    'تحویل‌دهنده یا تحویل‌گیرنده عضو فعال شرکت نیست.'
                );
            }

            $requestItems = DB::table('supply_request_items')
                ->where('supply_request_id', $requestRow->id)
                ->lockForUpdate()
                ->get()
                ->keyBy('goods_id');

            $handoverId = DB::table('material_handovers')->insertGetId([
                'company_id' => $companyId,
                'fiscal_year_id' => $requestRow->fiscal_year_id,
                'supply_request_id' => $requestRow->id,
                'warehouse_location_id' => $data['warehouse_location_id'],
                'production_section_id' => $productionSectionId,
                'production_stage_id' => $productionStageId,
                'delivered_by_user_id' => $data['delivered_by_user_id'],
                'received_by_user_id' => $data['received_by_user_id'],
                'handed_over_at' => $data['handed_over_at'],
                'status' => 'completed',
                'notes' => $data['notes'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($data['items'] as $item) {
                $requestItem = $requestItems->get($item['goods_id']);
                abort_unless($requestItem, 422, 'کالای تحویل خارج از درخواست است.');

                $alreadySupplied = (float) $requestItem->supplied_quantity;
                $quantity = (float) $item['quantity'];
                abort_unless($alreadySupplied + $quantity <= (float) $requestItem->requested_quantity + 0.0000001, 422, 'مقدار تحویل بیش از مقدار درخواستی است.');

                if ($productionSectionId) {
                    $inventory->transferToProductionSection($companyId, (int) $data['warehouse_location_id'],
                        $productionSectionId, (int) $item['goods_id'], $quantity,
                        'material_handovers', $handoverId, ['supply_request_id' => (int) $requestRow->id, 'production_stage_id' => $productionStageId]);
                } else {
                    // Preserve legacy behavior for old requests without a production section.
                    $inventory->issue($companyId, (int) $data['warehouse_location_id'], (int) $item['goods_id'], $quantity,
                        'material_handovers', $handoverId, ['supply_request_id' => $requestRow->id]);
                }

                // For legacy requests without a production section, a handover is still
                // the costing event. Production-section handovers are location transfers;
                // their cost is recognized only when production actually consumes the stock.
                if (empty($productionSectionId)) {
                    $movement = DB::table('inventory_movements')
                        ->where('company_id', $companyId)
                        ->where('movement_type', 'material_handover')
                        ->where('reference_type', 'material_handovers')
                        ->where('reference_id', $handoverId)
                        ->where('goods_id', (int) $item['goods_id'])
                        ->where('location_id', (int) $data['warehouse_location_id'])
                        ->where('quantity', '<', 0)
                        ->orderByDesc('id')
                        ->first();

                    if ($movement) {
                        $valuation->valueInventoryMovement((int) $movement->id, (int) $requestRow->fiscal_year_id);
                    }
                }

                DB::table('material_handover_items')->insert([
                    'material_handover_id' => $handoverId,
                    'goods_id' => $item['goods_id'],
                    'quantity' => $quantity,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('supply_request_items')
                    ->where('id', $requestItem->id)
                    ->update([
                        'supplied_quantity' => $alreadySupplied + $quantity,
                        'updated_at' => now(),
                    ]);
            }

            $remaining = DB::table('supply_request_items')
                ->where('supply_request_id', $requestRow->id)
                ->whereColumn('supplied_quantity', '<', 'requested_quantity')
                ->exists();

            DB::table('supply_requests')
                ->where('id', $requestRow->id)
                ->update([
                    'status' => $remaining ? 'partially_supplied' : 'fulfilled',
                    'updated_at' => now(),
                ]);

            return $handoverId;
        });

        return response()->json([
            'data' => DB::table('material_handovers')->where('id', $handoverId)->first(),
            'items' => DB::table('material_handover_items')->where('material_handover_id', $handoverId)->get(),
        ], 201);
    }

    public function show(Request $request, int $materialHandover): JsonResponse
    {
        $companyId = CompanyAuthorization::authorize($request, 'supply_request.handover.view');

        $row = DB::table('material_handovers')
            ->where('id', $materialHandover)
            ->where('company_id', $companyId)
            ->first();

        abort_unless($row, 404);

        return response()->json([
            'data' => $row,
            'items' => DB::table('material_handover_items')->where('material_handover_id', $materialHandover)->get(),
        ]);
    }
}