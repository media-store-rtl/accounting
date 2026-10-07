<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductionOutputRequest;
use App\Services\ProductionOutputService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProductionOutputController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = (int) $request->session()->get('company_id');
        $outputs = DB::table('production_outputs as po')
            ->join('productions as p', 'p.id', '=', 'po.production_id')
            ->join('goods as g', 'g.id', '=', 'po.goods_id')
            ->join('locations as l', 'l.id', '=', 'po.warehouse_location_id')
            ->leftJoin('orders as o', 'o.id', '=', 'po.order_id')
            ->where('po.company_id', $companyId)
            ->orderByDesc('po.id')
            ->select('po.*', 'p.number as production_number', 'g.name as goods_name', 'l.name as warehouse_name', 'o.number as order_number')
            ->get();

        $productions = DB::table('productions')->where('company_id', $companyId)->where('status', 'completed')->orderByDesc('id')->get();
        $orders = DB::table('orders')->where('company_id', $companyId)->orderByDesc('id')->get();
        $warehouses = DB::table('locations')->where('company_id', $companyId)->where('type', 'warehouse')->where('is_active', true)->orderBy('name')->get();

        return view('production.outputs', compact('outputs', 'productions', 'orders', 'warehouses'));
    }

    public function store(StoreProductionOutputRequest $request, ProductionOutputService $service): JsonResponse
    {
        $output = $service->create(
            (int) $request->session()->get('company_id'), (int) $request->integer('production_id'),
            $request->filled('order_id') ? (int) $request->integer('order_id') : null,
            (int) $request->integer('warehouse_location_id'), (float) $request->input('quantity'),
            (int) $request->user()->id, $request->input('produced_at'), $request->input('notes')
        );
        return response()->json(['data' => $output], 201);
    }

    public function confirm(Request $request, int $productionOutput, ProductionOutputService $service): JsonResponse
    {
        $data = $request->validate(['received_at' => ['nullable', 'date']]);
        return response()->json(['data' => $service->confirm((int) $request->session()->get('company_id'), $productionOutput, (int) $request->user()->id, $data['received_at'] ?? null)]);
    }

    public function reject(Request $request, int $productionOutput, ProductionOutputService $service): JsonResponse
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:2000']]);
        return response()->json(['data' => $service->reject((int) $request->session()->get('company_id'), $productionOutput, (int) $request->user()->id, $data['reason'] ?? null)]);
    }
}