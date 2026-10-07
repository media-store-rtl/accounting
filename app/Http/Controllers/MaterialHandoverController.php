<?php

namespace App\Http\Controllers;

use App\Services\InventoryService;
use App\Support\CompanyAuthorization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MaterialHandoverController extends Controller
{
    public function store(Request $request, InventoryService $inventory): JsonResponse
    {
        $companyId = CompanyAuthorization::authorize($request, 'supply_request.handover.create');

        $data = $request->validate([
            'supply_request_id' => ['required', 'integer'],
            'warehouse_location_id' => ['required', 'integer'],
            'delivered_by_user_id' => ['required', 'integer'],
            'received_by_user_id' => ['required', 'integer'],
            'handed_over_at' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.goods_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
        ]);

        $handoverId = DB::transaction(function () use ($data, $companyId, $inventory) {
            $requestRow = DB::table('supply_requests')
                ->where('id', $data['supply_request_id'])
                ->where('company_id', $companyId)
                ->lockForUpdate()
                ->first();

            abort_unless($requestRow && !in_array($requestRow->status, ['fulfilled', 'cancelled'], true), 422, 'درخواست تأمین قابل تحویل نیست.');

            abort_unless(
                DB::table('locations')
                    ->where('id', $data['warehouse_location_id'])
                    ->where('company_id', $companyId)
                    ->where('type', 'warehouse')
                    ->where('is_active', true)
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

                $available = (float) DB::table('inventory')
                    ->where('company_id', $companyId)
                    ->where('location_id', $data['warehouse_location_id'])
                    ->where('goods_id', $item['goods_id'])
                    ->lockForUpdate()
                    ->value('quantity');

                abort_unless($available + 0.0000001 >= $quantity, 422, 'موجودی انبار برای تحویل کافی نیست.');

                $inventory->issue(
                    $companyId,
                    (int) $data['warehouse_location_id'],
                    (int) $item['goods_id'],
                    $quantity,
                    'material_handovers',
                    $handoverId,
                    ['supply_request_id' => $requestRow->id]
                );

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