<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

final class CostingReportService
{
    public function generate(int $companyId, ?int $fiscalYearId = null, ?int $orderId = null, ?int $goodsId = null, ?string $startDate = null, ?string $endDate = null, ?int $personnelId = null, ?int $productionSectionId = null, ?int $productionStageId = null, ?string $valuationMethod = null): array
    {
        if ($fiscalYearId === null && $startDate === null && $endDate === null) {
            $fiscalYearId = DB::table('fiscal_years')->where('company_id',$companyId)->orderByDesc('id')->value('id');
        }
        if ($fiscalYearId !== null) abort_unless(DB::table('fiscal_years')->where('id',$fiscalYearId)->where('company_id',$companyId)->exists(),422,'سال مالی متعلق به شرکت نیست.');
        if ($orderId !== null) abort_unless(DB::table('orders')->where('id',$orderId)->where('company_id',$companyId)->exists(),422,'سفارش متعلق به شرکت نیست.');
        if ($goodsId !== null) abort_unless(DB::table('goods')->where('id',$goodsId)->where('company_id',$companyId)->exists(),422,'کالا متعلق به شرکت نیست.');

        $valuationOverrides = $valuationMethod !== null ? app(HistoricalInventoryValuationReportService::class)->calculate($companyId, $valuationMethod) : [];
        $rows=[];
        $this->materials($rows,$companyId,$fiscalYearId,$orderId,$goodsId,$startDate,$endDate,$valuationOverrides);
        $this->labor($rows,$companyId,$fiscalYearId,$orderId,$goodsId,$startDate,$endDate,$personnelId);
        $this->approvedShiftReportLabor($rows,$companyId,$fiscalYearId,$orderId,$goodsId,$startDate,$endDate,$personnelId,$productionSectionId,$productionStageId);
        $this->directPurchaseCosts($rows,$companyId,$fiscalYearId,$orderId,$goodsId,$startDate,$endDate);
        $this->scraps($rows,$companyId,$fiscalYearId,$orderId,$goodsId,$startDate,$endDate);

        $ids=array_values(array_unique(array_filter(array_column($rows,'goods_id'))));
        $goods=$ids ? DB::table('goods as g')->leftJoin('goods_units as gu', function ($join) { $join->on('gu.goods_id','=','g.id')->where('gu.is_base',true); })->leftJoin('units as u','u.id','=','gu.unit_id')->where('g.company_id',$companyId)->whereIn('g.id',$ids)->get(['g.id','g.code','g.name','u.name as unit_name','u.symbol as unit_symbol'])->keyBy('id') : collect();

        foreach($rows as &$row){
            $g=$goods->get($row['goods_id']);
            $row['goods_code']=$g?->code;
            $row['goods_name']=$g?->name;
            $row['unit_name']=$g?->unit_name;
            $row['unit_symbol']=$g?->unit_symbol;
            $row['valuation_methods']=array_keys($row['valuation_methods'] ?? []);
            $row['material_cost']=round($row['material_cost'],4);
            $row['labor_cost']=round($row['labor_cost'],4);
            $row['scrap_cost']=round($row['scrap_cost'],4);
            $row['direct_cost']=round($row['direct_cost'],4);
            $row['total_cost']=round($row['material_cost']+$row['labor_cost']+$row['scrap_cost']+$row['direct_cost'],4);
            $row['unvalued_material_quantity']=round($row['unvalued_material_quantity'],4);
        } unset($row);

        usort($rows,fn($a,$b)=>[$a['order_id']??PHP_INT_MAX,$a['goods_name']??'']<=>[$b['order_id']??PHP_INT_MAX,$b['goods_name']??'']);
        $productRows=[];
        foreach($rows as $row){
            $gid=(int)$row['goods_id'];
            if(!isset($productRows[$gid])){
                $productRows[$gid]=array_merge($row,['order_id'=>null,'material_cost'=>0.0,'labor_cost'=>0.0,'scrap_cost'=>0.0,'direct_cost'=>0.0,'total_cost'=>0.0,'unvalued_material_quantity'=>0.0,'valuation_methods'=>[]]);
            }
            foreach(['material_cost','labor_cost','scrap_cost','direct_cost','unvalued_material_quantity'] as $field){$productRows[$gid][$field]+=(float)$row[$field];}
            $productRows[$gid]['total_cost']+=(float)$row['total_cost'];
            foreach($row['valuation_methods'] as $method){$productRows[$gid]['valuation_methods'][$method]=true;}
        }
        foreach($productRows as &$productRow){$productRow['valuation_methods']=array_keys($productRow['valuation_methods']);} unset($productRow);
        $productionQuantityReports=$this->productionShiftReports($companyId,$fiscalYearId,$orderId,$goodsId,$startDate,$endDate,$personnelId,$productionSectionId,$productionStageId,true);
        foreach($productionQuantityReports as $productionReport){
            $gid=(int)$productionReport['goods_id'];
            if(!isset($productRows[$gid])){
                $productRows[$gid]=[
                    'order_id'=>null,'goods_id'=>$gid,'goods_code'=>$productionReport['goods_code'],'goods_name'=>$productionReport['goods_name'],
                    'material_cost'=>0.0,'labor_cost'=>0.0,'scrap_cost'=>0.0,'direct_cost'=>0.0,'total_cost'=>0.0,
                    'unvalued_material_quantity'=>0.0,'valuation_methods'=>[],'produced_quantity'=>0.0,'unit_cost'=>null,
                ];
            }
            $productRows[$gid]['produced_quantity']=(float)($productRows[$gid]['produced_quantity']??0)+(float)$productionReport['produced_quantity'];
        }
        foreach($productRows as &$productRow){
            $productRow['produced_quantity']=round((float)($productRow['produced_quantity']??0),4);
            $productRow['unit_cost']=$productRow['produced_quantity']>0?round($productRow['total_cost']/$productRow['produced_quantity'],4):null;
        } unset($productRow);

        // The detail table is specifically for reports already received by the warehouse.
        // Approved-but-not-received reports still contribute to product produced quantity above.
        $productionReports=$this->productionShiftReports($companyId,$fiscalYearId,$orderId,$goodsId,$startDate,$endDate,$personnelId,$productionSectionId,$productionStageId,true);
        $productionReports = $this->attachReportMaterialDetails($companyId, $productionReports, $valuationOverrides);

        $valuationMethods=[];
        foreach($rows as $row){
            foreach(($row['valuation_methods'] ?? []) as $method){
                $valuationMethods[$method]=true;
            }
        }

        return [
            'fiscal_year_id'=>$fiscalYearId,
            'filters'=>['start_date'=>$startDate,'end_date'=>$endDate,'personnel_id'=>$personnelId,'valuation_method'=>$valuationMethod],
            'selected_valuation_method'=>$valuationMethod,
            'rows'=>array_values($rows),
            'product_rows'=>array_values($productRows),
            'production_reports'=>$productionReports,
            'totals'=>[
                'material_cost'=>round(array_sum(array_column($rows,'material_cost')),4),
                'labor_cost'=>round(array_sum(array_column($rows,'labor_cost')),4),
                'scrap_cost'=>round(array_sum(array_column($rows,'scrap_cost')),4),
                'direct_cost'=>round(array_sum(array_column($rows,'direct_cost')),4),
                'total_cost'=>round(array_sum(array_column($rows,'total_cost')),4),
                'unvalued_material_quantity'=>round(array_sum(array_column($rows,'unvalued_material_quantity')),4),
                // Report-level usage is a separate traceability measure, not another product cost.
                'production_report_unvalued_material_quantity'=>round(array_sum(array_column($productionReports,'material_unvalued_quantity')),4),
            ],
            'valuation_methods'=>array_keys($valuationMethods),
        ];
    }

    private function materials(array &$rows,int $companyId,?int $fy,?int $orderId,?int $goodsId,?string $startDate,?string $endDate,array $valuationOverrides = []): void
    {
        // Consumption rows reference the raw material. Attribute their cost to
        // the finished good produced by the linked supply request when possible.
        $costs=DB::table('inventory_consumption_costs as c')
            ->join('inventory_movements as m','m.id','=','c.inventory_movement_id')
            ->where('c.company_id',$companyId)
            ->when($fy,fn($q)=>$q->where('c.fiscal_year_id',$fy))
            ->when($startDate,fn($q)=>$q->whereDate(empty($valuationOverrides) ? 'c.consumed_at' : 'm.occurred_at','>=',$startDate))
            ->when($endDate,fn($q)=>$q->whereDate(empty($valuationOverrides) ? 'c.consumed_at' : 'm.occurred_at','<=',$endDate))
            ->get(['c.goods_id','c.total_cost','c.valuation_method','m.metadata','m.id as movement_id','m.movement_type','c.fiscal_year_id']);


        if (!empty($valuationOverrides)) {
            $costs = $costs->map(function ($cost) use ($valuationOverrides) {
                $id = (int) $cost->movement_id;
                if (!in_array($cost->movement_type, ['production_consumption', 'material_handover'], true)) return $cost;
                $override = $valuationOverrides[$id] ?? null;
                if (!$override || (float) $override['valued_quantity'] <= 0.0000001) return null;
                $cost->total_cost = (float) $override['total_cost'];
                $cost->valuation_method = $override['method'];
                $cost->virtual_valuation = true;
                return $cost;
            })->filter()->values();

            $knownIds = $costs->pluck('movement_id')->map(fn ($id) => (int) $id)->all();
            $missing = DB::table('inventory_movements as m')
                ->where('m.company_id', $companyId)
                ->where('m.quantity', '<', 0)
                ->whereIn('m.movement_type', ['production_consumption', 'material_handover'])
                ->when($startDate, fn ($q) => $q->whereDate('m.occurred_at', '>=', $startDate))
                ->when($endDate, fn ($q) => $q->whereDate('m.occurred_at', '<=', $endDate))
                ->when(!empty($knownIds), fn ($q) => $q->whereNotIn('m.id', $knownIds))
                ->get(['m.id as movement_id', 'm.goods_id', 'm.metadata', 'm.movement_type']);
            foreach ($missing as $movement) {
                $override = $valuationOverrides[(int) $movement->movement_id] ?? null;
                if (!$override || (float) $override['valued_quantity'] <= 0.0000001) continue;
                $costs->push((object) [
                    'goods_id' => (int) $movement->goods_id,
                    'total_cost' => (float) $override['total_cost'],
                    'valuation_method' => $override['method'],
                    'metadata' => $movement->metadata,
                    'movement_id' => (int) $movement->movement_id,
                    'movement_type' => $movement->movement_type,
                    'fiscal_year_id' => null,
                    'virtual_valuation' => true,
                ]);
            }
        }

        $movements=DB::table('inventory_movements as m')
            ->leftJoin('inventory_consumption_costs as vc','vc.inventory_movement_id','=','m.id')
            ->where('m.company_id',$companyId)
            ->where('m.quantity','<',0)
            ->where(function($q) use ($valuationOverrides) {
                $q->where('m.movement_type','production_consumption')
                    ->orWhere(function($handover) use ($valuationOverrides) {
                        $handover->where('m.movement_type','material_handover');
                        if (empty($valuationOverrides)) $handover->whereNull('vc.id');
                    });
            })
            ->when($startDate,fn($q)=>$q->whereDate('m.occurred_at','>=',$startDate))
            ->when($endDate,fn($q)=>$q->whereDate('m.occurred_at','<=',$endDate))
            ->get(['m.id as movement_id','m.goods_id','m.quantity','m.metadata','m.movement_type','vc.quantity as valued_quantity','vc.fiscal_year_id as cost_fiscal_year_id']);

        $supplyIdFromMetadata=fn($item)=>(int)(json_decode((string)$item->metadata,true)['supply_request_id']??0);
        $ids=$costs->map($supplyIdFromMetadata)->merge($movements->map($supplyIdFromMetadata))->filter()->unique()->values();
        $supplies=$ids->isEmpty()?collect():DB::table('supply_requests')
            ->where('company_id',$companyId)->whereIn('id',$ids)
            ->get(['id','order_id','production_id','fiscal_year_id'])->keyBy('id');
        $metadataProductionIds=$costs->map(fn($item)=>(int)((json_decode((string)$item->metadata,true)['production_id']??0)))
            ->merge($movements->map(fn($item)=>(int)((json_decode((string)$item->metadata,true)['production_id']??0))));
        $prodIds=$supplies->pluck('production_id')->merge($metadataProductionIds)->filter()->unique()->values();
        $productions=$prodIds->isEmpty()?collect():DB::table('productions')
            ->where('company_id',$companyId)->whereIn('id',$prodIds)
            ->get(['id','goods_id','fiscal_year_id'])->keyBy('id');
        $links=$prodIds->isEmpty()?collect():DB::table('production_order')
            ->whereIn('production_id',$prodIds)->get(['production_id','order_id'])->groupBy('production_id');

        $resolveTarget=function($item) use ($supplyIdFromMetadata,$supplies,$productions,$links) {
            $meta=json_decode((string)$item->metadata,true)?:[];
            $supply=$supplies->get($supplyIdFromMetadata($item));
            $productionId=(int)($supply?->production_id??($meta['production_id']??0));
            $production=$productions->get($productionId);
            // Shift-report consumption can be valid without a linked production row.
            // In that case use the produced good and order captured on the movement itself.
            $targetGoodsId=$production?->goods_id!==null
                ?(int)$production->goods_id
                :(int)($meta['produced_goods_id']??$item->goods_id);
            $resolvedOrderId=$supply?->order_id??($meta['order_id']??null);
            if($resolvedOrderId===null&&$productionId){
                $resolvedOrderId=$links->get($productionId)?->first()?->order_id;
            }
            return [$resolvedOrderId!==null?(int)$resolvedOrderId:null,$targetGoodsId,$supply];
        };

        foreach($costs as $c){
            [$oid,$targetGoodsId,$resolvedSupply]=$resolveTarget($c);
            if (!empty($valuationOverrides) && !empty($c->virtual_valuation)) {
                $meta = json_decode((string) $c->metadata, true) ?: [];
                $productionId = (int) ($resolvedSupply?->production_id ?? ($meta['production_id'] ?? 0));
                $production = $productions->get($productionId);
                $movementFiscalYearId = $resolvedSupply?->fiscal_year_id ?? $production?->fiscal_year_id ?? ($meta['fiscal_year_id'] ?? ($c->fiscal_year_id ?? null));
                if ($fy !== null && (int) ($movementFiscalYearId ?? 0) !== $fy) continue;
            }
            if($orderId!==null&&$oid!==$orderId)continue;
            if($goodsId!==null&&$targetGoodsId!==$goodsId)continue;
            $key=$this->key($oid,$targetGoodsId);
            $this->row($rows,$key,$oid,$targetGoodsId);
            $rows[$key]['material_cost']+=(float)$c->total_cost;
            $rows[$key]['valuation_methods'][(string)$c->valuation_method]=true;
        }

        foreach($movements as $m){
            [$oid,$targetGoodsId,$supply]=$resolveTarget($m);
            $meta=json_decode((string)$m->metadata,true)?:[];
            $productionId=(int)($supply?->production_id??($meta['production_id']??0));
            $production=$productions->get($productionId);
            $movementFiscalYearId=$supply?->fiscal_year_id
                ??$production?->fiscal_year_id
                ??($meta['fiscal_year_id']??($m->cost_fiscal_year_id??null));
            // Never attribute movements with an unknown fiscal year to a specific year.
            if($fy!==null&&(int)($movementFiscalYearId??0)!==$fy)continue;
            if($orderId!==null&&$oid!==$orderId)continue;
            if($goodsId!==null&&$targetGoodsId!==$goodsId)continue;
            $valuedQuantity = !empty($valuationOverrides) ? (float) ($valuationOverrides[(int) $m->movement_id]['valued_quantity'] ?? 0) : (float) ($m->valued_quantity ?? 0);
            $unvaluedQuantity=max(0.0,abs((float)$m->quantity)-$valuedQuantity);
            if($unvaluedQuantity<=0.0000001)continue;
            $key=$this->key($oid,$targetGoodsId);
            $this->row($rows,$key,$oid,$targetGoodsId);
            $rows[$key]['unvalued_material_quantity']+=$unvaluedQuantity;
        }
    }

    /**
     * Attach report-level material usage to valued production-consumption movements
     * without adding these amounts to product totals a second time.
     *
     * Allocation is deterministic and conservative: same company, section,
     * material and order; consumption dated no later than the report; and only
     * the quantity not already allocated to earlier approved/received reports.
     * Missing or unvalued stock remains visible instead of receiving a guessed rate.
     */
    private function attachReportMaterialDetails(int $companyId, array $productionReports, array $valuationOverrides = []): array
    {
        if (empty($productionReports) || !DB::getSchemaBuilder()->hasTable('production_shift_report_materials')) {
            foreach ($productionReports as &$report) {
                $report['materials'] = [];
                $report['material_cost'] = 0.0;
                $report['material_unvalued_quantity'] = 0.0;
                $report['material_cost_complete'] = false;
            }
            unset($report);
            return $productionReports;
        }

        $usageRows = DB::table('production_shift_report_materials as rm')
            ->join('production_shift_reports as r', 'r.id', '=', 'rm.production_shift_report_id')
            ->join('goods as mg', 'mg.id', '=', 'rm.goods_id')
            ->leftJoin('goods_units as gu', function ($join) {
                $join->on('gu.goods_id', '=', 'mg.id')->where('gu.is_base', true);
            })
            ->leftJoin('units as u', 'u.id', '=', 'gu.unit_id')
            ->where('r.company_id', $companyId)
            ->whereIn('r.status', ['approved', 'received'])
            ->orderBy('r.work_date')->orderBy('r.id')->orderBy('rm.id')
            ->get([
                'rm.id as material_row_id', 'rm.production_shift_report_id as report_id',
                'rm.goods_id as material_id', 'rm.quantity', 'rm.scrap_quantity',
                'r.production_section_id', 'r.order_id', 'r.work_date',
                'mg.code as material_code', 'mg.name as material_name',
                'u.name as unit_name', 'u.symbol as unit_symbol',
            ]);

        if ($usageRows->isEmpty()) {
            foreach ($productionReports as &$report) {
                $report['materials'] = [];
                $report['material_cost'] = 0.0;
                $report['material_unvalued_quantity'] = 0.0;
                $report['material_cost_complete'] = false;
            }
            unset($report);
            return $productionReports;
        }

        $details = [];
        $reportIdsByMaterialRow = [];
        $groups = [];
        foreach ($usageRows as $usage) {
            $id = (int) $usage->material_row_id;
            $quantity = max(0.0, (float) $usage->quantity);
            $details[$id] = [
                'material_id' => (int) $usage->material_id,
                'material_code' => $usage->material_code,
                'material_name' => $usage->material_name,
                'unit_name' => $usage->unit_name,
                'unit_symbol' => $usage->unit_symbol,
                'quantity' => $quantity,
                'scrap_quantity' => (float) ($usage->scrap_quantity ?? 0),
                'allocated_quantity' => 0.0,
                'valued_quantity' => 0.0,
                'unmatched_quantity' => $quantity,
                'unvalued_quantity' => $quantity,
                'unit_cost' => null,
                'material_cost' => 0.0,
                'valuation_methods' => [],
                'cost_complete' => false,
            ];
            $reportIdsByMaterialRow[$id] = (int) $usage->report_id;
            $key = (int) $usage->production_section_id . ':' . (int) $usage->material_id . ':' . (int) ($usage->order_id ?? 0);
            $groups[$key][] = $id;
        }

        $canAllocate = DB::getSchemaBuilder()->hasTable('inventory_movements')
            && DB::getSchemaBuilder()->hasTable('inventory_consumption_costs')
            && DB::getSchemaBuilder()->hasTable('supply_requests');
        if ($canAllocate && DB::getSchemaBuilder()->hasColumn('supply_requests', 'production_section_id')) {
            // Production costs are recorded against the approved consumption movement,
            // not against the earlier warehouse handover/transfer movement.
            $consumptionRows = DB::table('inventory_movements as m')
                ->leftJoin('inventory_consumption_costs as c', 'c.inventory_movement_id', '=', 'm.id')
                ->join('locations as l', function ($join) use ($companyId) {
                    $join->on('l.id', '=', 'm.location_id')->where('l.company_id', '=', $companyId);
                })
                ->leftJoin('production_sections as ps', 'ps.location_id', '=', 'l.id')
                ->where('m.company_id', $companyId)
                ->where('m.movement_type', 'production_consumption')
                ->where('m.quantity', '<', 0)
                ->orderBy('m.occurred_at')->orderBy('m.id')
                ->get([
                    'm.id as movement_id', 'm.goods_id as material_id', 'm.quantity',
                    'm.location_id', 'm.occurred_at', 'm.metadata', 'm.reference_type', 'm.reference_id',
                    'ps.id as location_section_id',
                    'c.id as cost_id', 'c.quantity as cost_quantity', 'c.unit_cost',
                    'c.valuation_method',
                ]);

            $supplyIds = $consumptionRows->map(function ($movement) {
                return (int) (json_decode((string) $movement->metadata, true)['supply_request_id'] ?? 0);
            })->filter()->unique()->values();
            $supplies = $supplyIds->isEmpty() ? collect() : DB::table('supply_requests')
                ->where('company_id', $companyId)->whereIn('id', $supplyIds)
                ->get(['id', 'production_section_id', 'order_id'])->keyBy('id');

            // Some valid production-consumption movements link directly to a
            // production operation and only carry production_id in metadata.
            // Resolve their order the same way the main costing aggregation does,
            // otherwise report usage rows for an order can never match them.
            $productionIds = $consumptionRows->map(function ($movement) {
                return (int) (json_decode((string) $movement->metadata, true)['production_id'] ?? 0);
            })->filter()->unique()->values();
            $productionOrders = $productionIds->isEmpty() ? collect() : DB::table('production_order')
                ->whereIn('production_id', $productionIds)
                ->orderBy('order_id')
                ->get(['production_id', 'order_id'])
                ->groupBy('production_id')
                ->map(fn ($links) => $links->first());

            $costIds = $consumptionRows->pluck('cost_id')->filter()->unique()->values();
            $costSlicesById = [];
            if ($costIds->isNotEmpty() && DB::getSchemaBuilder()->hasTable('inventory_consumption_cost_items')) {
                $costSlices = DB::table('inventory_consumption_cost_items')
                    ->whereIn('inventory_consumption_cost_id', $costIds)
                    ->orderBy('id')
                    ->get(['inventory_consumption_cost_id', 'quantity', 'unit_cost']);
                foreach ($costSlices as $slice) {
                    $costSlicesById[(int) $slice->inventory_consumption_cost_id][] = [
                        'remaining_quantity' => (float) $slice->quantity,
                        'unit_cost' => (float) $slice->unit_cost,
                    ];
                }
            }

            $legacyConsumptionGroups = [];
            $directConsumptionGroups = [];
            $usageReportMaterialRows = [];
            foreach ($usageRows as $usage) {
                $usageReportMaterialRows[(int) $usage->report_id][(int) $usage->material_id] = true;
            }

            foreach ($consumptionRows as $movement) {
                $metadata = json_decode((string) $movement->metadata, true) ?: [];
                $supply = $supplies->get((int) ($metadata['supply_request_id'] ?? 0));
                $productionOrder = $productionOrders->get((int) ($metadata['production_id'] ?? 0));
                $sectionId = (int) ($supply->production_section_id ?? $movement->location_section_id ?? 0);
                if ($sectionId <= 0) continue;
                $orderKey = (int) ($supply->order_id ?? $productionOrder->order_id ?? $metadata['order_id'] ?? 0);
                $key = $sectionId . ':' . (int) $movement->material_id . ':' . $orderKey;
                $quantity = abs((float) $movement->quantity);
                if ($quantity <= 0.0000001) continue;

                $directReportId = (int) ($metadata['shift_report_id'] ?? 0);
                if ($directReportId <= 0 && (string) $movement->reference_type === 'production_shift_reports') {
                    $directReportId = (int) $movement->reference_id;
                }

                $costId = $movement->cost_id !== null ? (int) $movement->cost_id : null;
                $override = $valuationOverrides[(int) $movement->movement_id] ?? null;
                if ($override !== null) {
                    $slices = array_map(fn ($slice) => [
                        'remaining_quantity' => (float) $slice['quantity'],
                        'unit_cost' => (float) $slice['unit_cost'],
                    ], $override['slices'] ?? []);
                    $valuationMethodForMovement = $override['method'];
                } else {
                    $slices = $costId !== null ? ($costSlicesById[$costId] ?? []) : [];
                    if ($costId !== null && empty($slices) && $movement->cost_quantity !== null && (float) $movement->cost_quantity > 0) {
                        $slices[] = [
                            'remaining_quantity' => (float) $movement->cost_quantity,
                            'unit_cost' => (float) $movement->unit_cost,
                        ];
                    }
                    $valuationMethodForMovement = $movement->valuation_method;
                }
                $source = [
                    'handed_over_date' => substr((string) $movement->occurred_at, 0, 10),
                    'remaining_quantity' => $quantity,
                    'valuation_method' => $valuationMethodForMovement,
                    'cost_slices' => $slices,
                ];

                // An explicit shift-report reference is authoritative. Never put it
                // into the legacy section/material/order pool, where an earlier report
                // could otherwise consume it before its actual owner.
                if ($directReportId > 0) {
                    if (isset($usageReportMaterialRows[$directReportId][(int) $movement->material_id])) {
                        $directConsumptionGroups[$directReportId][(int) $movement->material_id][] = $source;
                    }
                    continue;
                }

                $legacyConsumptionGroups[$key][] = $source;
            }

            $remainingUsageByRow = [];

            // First consume report-specific movements only for the matching report
            // and material. A later report's explicit movement cannot be stolen by
            // an earlier report in the same legacy allocation group.
            foreach ($usageRows as $usage) {
                $materialRowId = (int) $usage->material_row_id;
                $remainingUsage = max(0.0, (float) $usage->quantity);
                $reportId = (int) $usage->report_id;
                $materialId = (int) $usage->material_id;
                $workDate = substr((string) $usage->work_date, 0, 10);
                if (!empty($directConsumptionGroups[$reportId][$materialId])) {

                foreach ($sourceRows as $sourceIndex => &$source) {
                    if ($remainingUsage <= 0.0000001) break;
                    if ($source['handed_over_date'] > $workDate || $source['remaining_quantity'] <= 0.0000001) continue;

                    $allocated = min($remainingUsage, (float) $source['remaining_quantity']);
                    $source['remaining_quantity'] -= $allocated;
                    $details[$materialRowId]['allocated_quantity'] += $allocated;
                    $remainingUsage -= $allocated;

                    $valuedRemaining = $allocated;
                    foreach ($source['cost_slices'] as &$slice) {
                        if ($valuedRemaining <= 0.0000001) break;
                        if ($slice['remaining_quantity'] <= 0.0000001) continue;
                        $valued = min($valuedRemaining, (float) $slice['remaining_quantity']);
                        $slice['remaining_quantity'] -= $valued;
                        $valuedRemaining -= $valued;
                        $details[$materialRowId]['valued_quantity'] += $valued;
                        $details[$materialRowId]['material_cost'] += $valued * (float) $slice['unit_cost'];
                        if (!empty($source['valuation_method'])) {
                            $details[$materialRowId]['valuation_methods'][$source['valuation_method']] = true;
                        }
                    }
                    unset($slice);
                }
                unset($source);
                $remainingUsageByRow[$materialRowId] = $remainingUsage;
            }

            // Only movements without an explicit report reference use the legacy
            // section/material/order/date pool.
            $nextLegacyByGroup = [];
            $activeLegacyByGroup = [];
            foreach ($usageRows as $usage) {
                $materialRowId = (int) $usage->material_row_id;
                $key = (int) $usage->production_section_id . ':' . (int) $usage->material_id . ':' . (int) ($usage->order_id ?? 0);
                $sourceRows = $legacyConsumptionGroups[$key] ?? [];
                if (empty($sourceRows)) continue;

                $next = $nextLegacyByGroup[$key] ?? 0;
                $active = $activeLegacyByGroup[$key] ?? [];
                $workDate = substr((string) $usage->work_date, 0, 10);
                while ($next < count($sourceRows) && $sourceRows[$next]['handed_over_date'] <= $workDate) {
                    $active[] = $next;
                    $next++;
                }
                $nextLegacyByGroup[$key] = $next;
                $activeLegacyByGroup[$key] = $active;

                $remainingUsage = (float) ($remainingUsageByRow[$materialRowId] ?? max(0.0, (float) $usage->quantity));
                foreach ($active as $sourceIndex) {
                    if ($remainingUsage <= 0.0000001) break;
                    if (($legacyConsumptionGroups[$key][$sourceIndex]['remaining_quantity'] ?? 0) <= 0.0000001) continue;

                    $allocated = min($remainingUsage, (float) $legacyConsumptionGroups[$key][$sourceIndex]['remaining_quantity']);
                    $legacyConsumptionGroups[$key][$sourceIndex]['remaining_quantity'] -= $allocated;
                    $details[$materialRowId]['allocated_quantity'] += $allocated;
                    $remainingUsage -= $allocated;

                    $valuedRemaining = $allocated;
                    foreach ($legacyConsumptionGroups[$key][$sourceIndex]['cost_slices'] as &$slice) {
                        if ($valuedRemaining <= 0.0000001) break;
                        if ($slice['remaining_quantity'] <= 0.0000001) continue;
                        $valued = min($valuedRemaining, (float) $slice['remaining_quantity']);
                        $slice['remaining_quantity'] -= $valued;
                        $valuedRemaining -= $valued;
                        $details[$materialRowId]['valued_quantity'] += $valued;
                        $details[$materialRowId]['material_cost'] += $valued * (float) $slice['unit_cost'];
                        if (!empty($legacyConsumptionGroups[$key][$sourceIndex]['valuation_method'])) {
                            $details[$materialRowId]['valuation_methods'][$legacyConsumptionGroups[$key][$sourceIndex]['valuation_method']] = true;
                        }
                    }
                    unset($slice);
                }
            }
        }

        $byReport = [];
        foreach ($details as $materialRowId => &$detail) {
            $detail['allocated_quantity'] = round($detail['allocated_quantity'], 4);
            $detail['valued_quantity'] = round($detail['valued_quantity'], 4);
            $detail['unmatched_quantity'] = round(max(0.0, $detail['quantity'] - $detail['allocated_quantity']), 4);
            $detail['unvalued_quantity'] = round(max(0.0, $detail['quantity'] - $detail['valued_quantity']), 4);
            $detail['material_cost'] = round($detail['material_cost'], 4);
            $detail['unit_cost'] = $detail['valued_quantity'] > 0
                ? round($detail['material_cost'] / $detail['valued_quantity'], 6)
                : null;
            $detail['valuation_methods'] = array_keys($detail['valuation_methods']);
            $detail['cost_complete'] = $detail['unvalued_quantity'] <= 0.0001;
            $byReport[$reportIdsByMaterialRow[$materialRowId]][] = $detail;
        }
        unset($detail);

        foreach ($productionReports as &$report) {
            $reportMaterials = $byReport[(int) $report['report_id']] ?? [];
            usort($reportMaterials, fn ($a, $b) => [$a['material_name'], $a['material_id']] <=> [$b['material_name'], $b['material_id']]);
            $report['materials'] = $reportMaterials;
            $report['material_cost'] = round(array_sum(array_column($reportMaterials, 'material_cost')), 4);
            $report['material_unvalued_quantity'] = round(array_sum(array_column($reportMaterials, 'unvalued_quantity')), 4);
            $report['material_cost_complete'] = !empty($reportMaterials) && !array_filter($reportMaterials, fn ($material) => !$material['cost_complete']);
        }
        unset($report);

        return $productionReports;
    }

    private function labor(array &$rows,int $companyId,?int $fy,?int $orderId,?int $goodsId,?string $startDate,?string $endDate,?int $personnelId): void
    {
        $costs=DB::table('production_labor_costs')->where('company_id',$companyId)->when($fy,fn($q)=>$q->where('fiscal_year_id',$fy))->when($startDate,fn($q)=>$q->whereDate('worked_at','>=',$startDate))->when($endDate,fn($q)=>$q->whereDate('worked_at','<=',$endDate))->when($personnelId,fn($q)=>$q->where('personnel_id',$personnelId))->get(['production_id','total_cost']);
        $pids=$costs->pluck('production_id')->unique()->values();if($pids->isEmpty())return;
        $goods=DB::table('productions')->whereIn('id',$pids)->get(['id','goods_id'])->keyBy('id');$links=DB::table('production_order')->whereIn('production_id',$pids)->get(['production_id','order_id'])->groupBy('production_id');
        foreach($costs as $c){
            $p=$goods->get((int)$c->production_id);if(!$p||($goodsId!==null&&(int)$p->goods_id!==$goodsId))continue;
            foreach($links->get((int)$c->production_id,collect()) as $link){
                if($orderId!==null&&(int)$link->order_id!==$orderId)continue;$key=$this->key((int)$link->order_id,(int)$p->goods_id);$this->row($rows,$key,(int)$link->order_id,(int)$p->goods_id);$rows[$key]['labor_cost']+=(float)$c->total_cost;
            }
        }
    }


    /**
     * Approved shift reports carry their own order and worker/time information.
     * Resolve the rate valid on the work date so these reports contribute to
     * the same order/product costing report as operation-level labor entries.
     */
    private function approvedShiftReportLabor(array &$rows,int $companyId,?int $fy,?int $orderId,?int $goodsId,?string $startDate,?string $endDate,?int $personnelId,?int $productionSectionId,?int $productionStageId): void
    {
        if (!DB::getSchemaBuilder()->hasColumn('production_shift_reports', 'order_id')) return;

        $reports = DB::table('production_shift_reports as r')
            ->where('r.company_id',$companyId)
            ->whereIn('r.status',['approved','received'])
            ->when($fy,fn($q)=>$q->whereBetween('r.work_date', [
                DB::table('fiscal_years')->where('id',$fy)->value('starts_at'),
                DB::table('fiscal_years')->where('id',$fy)->value('ends_at')
            ]))
            ->when($orderId,fn($q)=>$q->where('r.order_id',$orderId))
            ->when($goodsId,fn($q)=>$q->where('r.goods_id',$goodsId))
            ->when($startDate,fn($q)=>$q->whereDate('r.work_date','>=',$startDate))
            ->when($endDate,fn($q)=>$q->whereDate('r.work_date','<=',$endDate))
            ->when($personnelId,fn($q)=>$q->where('r.personnel_id',$personnelId))
            ->when($productionSectionId !== null,fn($q)=>$q->where('r.production_section_id',$productionSectionId))
            ->when($productionStageId !== null,fn($q)=>$q->whereExists(function($sub) use ($productionStageId, $companyId) {
                $sub->selectRaw('1')->from('production_shift_report_stages as rs')
                    ->whereColumn('rs.production_shift_report_id','r.id')
                    ->where('rs.company_id',$companyId)
                    ->where('rs.production_stage_id',$productionStageId);
            }))
            ->get(['r.order_id','r.goods_id','r.personnel_id','r.work_date','r.worked_hours','r.overtime_hours','r.holiday_hours']);

        foreach ($reports as $report) {
            $date = substr((string)$report->work_date,0,10);
            $total = 0.0;
            foreach ([
                'regular' => (float)$report->worked_hours,
                'overtime' => (float)$report->overtime_hours,
                'holiday' => (float)$report->holiday_hours,
            ] as $rateType => $hours) {
                if ($hours <= 0) continue;
                $rate = DB::table('personnel_labor_rates')
                    ->where('company_id',$companyId)
                    ->where('personnel_id',(int)$report->personnel_id)
                    ->where('rate_type',$rateType)
                    ->where('effective_from','<=',$date)
                    ->where(fn($q)=>$q->whereNull('effective_to')->orWhere('effective_to','>=',$date))
                    ->orderByDesc('effective_from')->orderByDesc('id')
                    ->value('rate');
                if ($rate !== null) $total += $hours * (float)$rate;
            }
            if ($total <= 0) continue;
            $reportOrderId = $report->order_id !== null ? (int)$report->order_id : null;
            $key = $this->key($reportOrderId,(int)$report->goods_id);
            $this->row($rows,$key,$reportOrderId,(int)$report->goods_id);
            $rows[$key]['labor_cost'] += $total;
        }
    }

    private function directPurchaseCosts(array &$rows,int $companyId,?int $fy,?int $orderId,?int $goodsId,?string $startDate,?string $endDate): void
    {
        // Keep purchase lines and purchase-level direct costs separate: joining
        // both one-to-many tables multiplies each direct-cost row by the item count.
        // A purchase may also be standalone (without a supply request), so retain it
        // and report its cost under the unassigned order bucket.
        $purchases=DB::table('purchases as p')
            ->leftJoin('supply_requests as sr','sr.id','=','p.supply_request_id')
            ->join('purchase_items as pi','pi.purchase_id','=','p.id')
            ->where('p.company_id',$companyId)
            ->when($fy,fn($q)=>$q->where('p.fiscal_year_id',$fy))
            ->when($startDate,fn($q)=>$q->whereDate('p.purchased_at','>=',$startDate))
            ->when($endDate,fn($q)=>$q->whereDate('p.purchased_at','<=',$endDate))
            ->when($goodsId,fn($q)=>$q->where('pi.goods_id',$goodsId))
            ->select('p.id as purchase_id','sr.order_id','sr.production_id','pi.goods_id','pi.line_total','p.subtotal')
            ->get();
        if ($purchases->isEmpty()) return;

        $purchaseIds=$purchases->pluck('purchase_id')->unique()->values();
        $directCosts=DB::table('purchase_direct_costs')
            ->whereIn('purchase_id',$purchaseIds)
            ->selectRaw('purchase_id, SUM(amount) as amount')
            ->groupBy('purchase_id')
            ->pluck('amount','purchase_id');
        $prodIds=$purchases->pluck('production_id')->filter()->unique()->values();
        $links=$prodIds->isEmpty()?collect():DB::table('production_order')->whereIn('production_id',$prodIds)->get(['production_id','order_id'])->groupBy('production_id');
        foreach($purchases->groupBy('purchase_id') as $purchaseRows){
            $first=$purchaseRows->first();
            $subtotal=(float)$first->subtotal;
            if($subtotal<=0) continue;
            $directTotal=(float)($directCosts->get($first->purchase_id) ?? 0);
            foreach($purchaseRows->groupBy('goods_id') as $goodsLines){
                $line=$goodsLines->first();
                $oid=$line->order_id;
                if($oid===null && $line->production_id) $oid=$links->get((int)$line->production_id)?->first()?->order_id;
                if($orderId!==null && (int)$oid!==$orderId) continue;
                // Aggregate duplicate lines for the same goods before allocating
                // the purchase's direct cost by each goods' share of subtotal.
                $goodsLineTotal=(float)$goodsLines->sum('line_total');
                $lineShare=max(0.0,$goodsLineTotal)/$subtotal;
                $key=$this->key($oid,(int)$line->goods_id);
                $this->row($rows,$key,$oid,(int)$line->goods_id);
                $rows[$key]['direct_cost'] += $directTotal*$lineShare;
            }
        }
    }

    private function scraps(array &$rows,int $companyId,?int $fy,?int $orderId,?int $goodsId,?string $startDate,?string $endDate): void
    {
        $costs=DB::table('scraps as s')
            ->join('production_operation_runs as r','r.id','=','s.production_operation_run_id')
            ->join('production_stage_runs as sr','sr.id','=','r.production_stage_run_id')
            ->join('productions as p','p.id','=','sr.production_id')
            ->where('p.company_id',$companyId)
            ->when($fy,fn($q)=>$q->where('p.fiscal_year_id',$fy))
            ->when($startDate,fn($q)=>$q->whereDate('s.scrapped_at','>=',$startDate))
            ->when($endDate,fn($q)=>$q->whereDate('s.scrapped_at','<=',$endDate))
            ->when($goodsId,fn($q)=>$q->where('p.goods_id',$goodsId))
            ->get(['s.total_cost','p.id as production_id','p.goods_id']);
        $pids=$costs->pluck('production_id')->unique()->values();
        $links=$pids->isEmpty()?collect():DB::table('production_order')->whereIn('production_id',$pids)->get(['production_id','order_id'])->groupBy('production_id');
        foreach($costs as $s){
            $linksForProduction=$links->get((int)$s->production_id,collect());
            if($linksForProduction->isEmpty()){
                if($orderId!==null) continue;
                $key=$this->key(null,(int)$s->goods_id);$this->row($rows,$key,null,(int)$s->goods_id);$rows[$key]['scrap_cost']+=(float)$s->total_cost;continue;
            }
            foreach($linksForProduction as $link){
                if($orderId!==null&&(int)$link->order_id!==$orderId) continue;
                $key=$this->key((int)$link->order_id,(int)$s->goods_id);$this->row($rows,$key,(int)$link->order_id,(int)$s->goods_id);$rows[$key]['scrap_cost']+=(float)$s->total_cost;
            }
        }
    }


    /**
     * Warehouse-accepted production shift reports are the traceable output records.
     * Labor is calculated per report and worker using the rate effective on work_date.
     */
    private function productionShiftReports(int $companyId, ?int $fy, ?int $orderId, ?int $goodsId, ?string $startDate, ?string $endDate, ?int $personnelId, ?int $productionSectionId, ?int $productionStageId, bool $warehouseReceivedOnly = false): array
    {
        if (!DB::getSchemaBuilder()->hasTable('production_shift_reports')
            || !DB::getSchemaBuilder()->hasColumn('production_shift_reports', 'warehouse_received_at')) return [];

        $query = DB::table('production_shift_reports as r')
            ->join('goods as g', 'g.id', '=', 'r.goods_id')
            ->leftJoin('personnel as pe', 'pe.id', '=', 'r.personnel_id')
            ->leftJoin('production_sections as ps', 'ps.id', '=', 'r.production_section_id')
            ->leftJoin('locations as section_location', 'section_location.id', '=', 'ps.location_id')
            ->leftJoin('locations as warehouse', 'warehouse.id', '=', 'r.warehouse_location_id')
            ->leftJoin('goods_units as gu', function ($join) {
                $join->on('gu.goods_id', '=', 'g.id')->where('gu.is_base', true);
            })
            ->leftJoin('units as u', 'u.id', '=', 'gu.unit_id')
            ->where('r.company_id', $companyId)
            ->whereIn('r.status', ['approved', 'received'])
            ->when($warehouseReceivedOnly, fn($q) => $q->where('r.status', 'received')->whereNotNull('r.warehouse_received_at'))
            ->when($fy, function ($q) use ($fy) {
                $start = DB::table('fiscal_years')->where('id', $fy)->value('starts_at');
                $end = DB::table('fiscal_years')->where('id', $fy)->value('ends_at');
                $q->whereBetween('r.work_date', [$start, $end]);
            })
            ->when($orderId !== null, fn($q) => $q->where('r.order_id', $orderId))
            ->when($goodsId !== null, fn($q) => $q->where('r.goods_id', $goodsId))
            ->when($startDate, fn($q) => $q->whereDate('r.work_date', '>=', $startDate))
            ->when($endDate, fn($q) => $q->whereDate('r.work_date', '<=', $endDate))
            ->when($personnelId !== null, fn($q) => $q->where('r.personnel_id', $personnelId))
            ->when($productionSectionId !== null, fn($q) => $q->where('r.production_section_id', $productionSectionId))
            ->when($productionStageId !== null, fn($q) => $q->whereExists(function($sub) use ($productionStageId, $companyId) {
                $sub->selectRaw('1')->from('production_shift_report_stages as rs')
                    ->whereColumn('rs.production_shift_report_id', 'r.id')
                    ->where('rs.company_id', $companyId)
                    ->where('rs.production_stage_id', $productionStageId);
            }))
            ->select([
                'r.id as report_id', 'r.production_section_id', 'r.order_id', 'r.work_date', 'r.warehouse_received_at',
                'r.produced_quantity', 'r.scrap_quantity', 'r.warehouse_quantity',
                'r.worked_hours', 'r.overtime_hours', 'r.holiday_hours',
                'r.personnel_id', 'r.goods_id', 'r.order_id',
                'pe.name as worker_name', 'section_location.name as section_name',
                'warehouse.name as warehouse_name', 'g.code as goods_code', 'g.name as goods_name',
                'u.name as unit_name', 'u.symbol as unit_symbol',
            ])
            ->orderByDesc('r.work_date')->orderByDesc('r.id')
            ->get();

        $stageNamesByReport = collect();
        if ($query->isNotEmpty() && DB::getSchemaBuilder()->hasTable('production_shift_report_stages')) {
            $stageNamesByReport = DB::table('production_shift_report_stages as rs')
                ->join('production_stages as st', 'st.id', '=', 'rs.production_stage_id')
                ->where('rs.company_id', $companyId)
                ->whereIn('rs.production_shift_report_id', $query->pluck('report_id')->map(fn ($id) => (int) $id)->all())
                ->orderBy('st.sequence')
                ->get(['rs.production_shift_report_id', 'st.name'])
                ->groupBy('production_shift_report_id')
                ->map(fn ($stages) => $stages->pluck('name')->unique()->values()->all());
        }

        return $query->map(function ($report) use ($companyId, $stageNamesByReport) {
            $date = substr((string) $report->work_date, 0, 10);
            $labor = 0.0;
            foreach ([
                'regular' => (float) $report->worked_hours,
                'overtime' => (float) $report->overtime_hours,
                'holiday' => (float) $report->holiday_hours,
            ] as $type => $hours) {
                if ($hours <= 0) continue;
                $rate = DB::table('personnel_labor_rates')
                    ->where('company_id', $companyId)
                    ->where('personnel_id', (int) $report->personnel_id)
                    ->where('rate_type', $type)
                    ->where('effective_from', '<=', $date)
                    ->where(fn($q) => $q->whereNull('effective_to')->orWhere('effective_to', '>=', $date))
                    ->orderByDesc('effective_from')->orderByDesc('id')->value('rate');
                if ($rate !== null) $labor += $hours * (float) $rate;
            }
            return [
                'report_id' => (int) $report->report_id,
                'production_section_id' => (int) $report->production_section_id,
                'order_id' => $report->order_id !== null ? (int) $report->order_id : null,
                'goods_id' => (int) $report->goods_id,
                'personnel_id' => $report->personnel_id !== null ? (int) $report->personnel_id : null,
                'work_date' => $date,
                'warehouse_received_at' => $report->warehouse_received_at,
                'worker_name' => $report->worker_name,
                'section_name' => $report->section_name,
                'stage_names' => $stageNamesByReport->get((int) $report->report_id, []),
                'warehouse_name' => $report->warehouse_name,
                'goods_code' => $report->goods_code,
                'goods_name' => $report->goods_name,
                'unit_name' => $report->unit_name,
                'unit_symbol' => $report->unit_symbol,
                'produced_quantity' => (float) $report->produced_quantity,
                'scrap_quantity' => (float) $report->scrap_quantity,
                'received_quantity' => (float) ($report->warehouse_quantity ?? 0),
                'labor_cost' => round($labor, 4),
            ];
        })->all();
    }

    private function key(?int $orderId,int $goodsId): string{return ($orderId??0).':'.$goodsId;}
    private function row(array &$rows,string $key,?int $orderId,int $goodsId): void{
        if(isset($rows[$key]))return;
        $rows[$key]=['order_id'=>$orderId,'goods_id'=>$goodsId,'material_cost'=>0.0,'labor_cost'=>0.0,'total_cost'=>0.0,'unvalued_material_quantity'=>0.0,'scrap_cost'=>0.0,'direct_cost'=>0.0,'valuation_methods'=>[]];
    }
}
