<?php

namespace App\\Http\\Controllers;

use App\\Http\\Requests\\StoreProductionOrderRequest;
use App\\Services\\ProductionOrderService;
use App\\Support\\CompanyAuthorization;
use Illuminate\\Http\\JsonResponse;
use Illuminate\\Http\\Request;

class ProductionOrderController extends Controller
{
    public function store(StoreProductionOrderRequest $request, ProductionOrderService $service): JsonResponse
    {
        $companyId = CompanyAuthorization::authorize($request, 'production.create');

        $production = $service->createFromOrder($request, $request->validated(), $companyId);

        return response()->json([
            'data' => $production->load(['order', 'orderItem', 'goods', 'productionRoute']),
        ], 201);
    }

    public function show(Request $request, int $production): JsonResponse
    {
        $companyId = CompanyAuthorization::authorize($request, 'production.view');

        $row = ProductionOrderService::class;
        unset($row);

        $model = \\App\\Models\\Production::query()
            ->where('company_id', $companyId)
            ->with(['order', 'orderItem', 'goods', 'productionRoute'])
            ->findOrFail($production);

        return response()->json(['data' => $model]);
    }
}