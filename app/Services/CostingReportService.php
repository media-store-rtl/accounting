<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

final class CostingReportService
{
    public function generate(int $companyId, ?int $fiscalYearId = null, ?int $orderId = null, ?int $goodsId = null, ?string $startDate = null, ?string $endDate = null, ?int $personnelId = null, ?int $productionSectionId = null, ?int $productionStageId = null): array
    {
        if ($fiscalYearId === null && $startDate === null && $endDate === null) {
            $fiscalYearId = DB::table('fiscal_years')->where('company_id',$companyId)->orderByDesc('id')->value('id');
        }
        if ($fiscalYearId !== null) abort_unless(DB::table('fiscal_years')->where('id',$fiscalYearId)->where('company_id',$companyId)->exists(),422,'سال مالی متعلق به شرکت نیست.');
        if ($orderId !== null) abort_unless(DB::table('orders')->where('id',$orderId)->where('company_id',$companyId)->exists(),422,'سفارش متعلق به شرکت نیست.');
        if ($goodsId !== null) abort_unless(DB::table('goods')->where('id',$goodsId)->where('company_id',$companyId)->exists(),422,'کالا متعلق به شرکت نیست.');

        $rows=[];
        $this->materials($rows,$companyId,$fiscalYearId,$orderId,$goodsId,$startDate,$endDate);
        $this->labor($rows,$companyId,$fiscalYearId,$orderId,$goodsId,$startDate,$endDate,$personnelId);
        $this->approvedShiftReportLabor($rows,$companyId,$fiscalYearId,$orderId,$goodsId,$startDate,$endDate,$personnelId,$productionSectionId,$productionStageId);
        $this->directPurchaseCosts($rows,$companyId,$fiscalYearId,$orderId,$goodsId,$startDate,$endDate);
        $this->scraps($rows,$companyId,$fiscalYearId,$orderId,$goodsId,$startDate,$endDate);

        $ids=array_values(array_unique(array_filter(array_column($rows,'goods_id'))));
        $goods=$ids ? DB::table('goods')->where('company_id',$companyId)->whereIn('id',$ids)->get(['id','code','name'])->keyBy('id') : collect();

        foreach($rows as &$row){
            $g=$goods->get($row['goods_id']);
            $row['goods_code']=$g?->code;
            $row['goods_name']=$g?->name;
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
        $productionQuantityReports=$this->productionShiftReports($companyId,$fiscalYearId,$orderId,$goodsId,$startDate,$endDate,$personnelId,$productionSectionId,$productionStageId);
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

        $valuationMethods=[];
        foreach($rows as $row){
            foreach(($row['valuation_methods'] ?? []) as $method){
                $valuationMethods[$method]=true;
            }
        }

        return [
            'fiscal_year_id'=>$fiscalYearId,
            'filters'=>['start_date'=>$startDate,'end_date'=>$endDate,'personnel_id'=>$personnelId],
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
            ],
            'valuation_methods'=>array_keys($valuationMethods),
        ];
    }

    private function materials(array &$rows,int $companyId,?int $fy,?int $orderId,?int $goodsId,?string $startDate,?string $endDate): void
    {
        // Consumption rows reference the raw material. Attribute their cost to
        // the finished good produced by the linked supply request when possible.
        $costs=DB::table('inventory_consumption_costs as c')
            ->join('inventory_movements as m','m.id','=','c.inventory_movement_id')
            ->where('c.company_id',$companyId)
            ->when($fy,fn($q)=>$q->where('c.fiscal_year_id',$fy))
            ->when($startDate,fn($q)=>$q->whereDate('c.consumed_at','>=',$startDate))
            ->when($endDate,fn($q)=>$q->whereDate('c.consumed_at','<=',$endDate))
            ->get(['c.goods_id','c.total_cost','c.valuation_method','m.metadata']);

        $movements=DB::table('inventory_movements as m')
            ->where('m.company_id',$companyId)
            ->where('m.quantity','<',0)
            ->where('m.movement_type','material_handover')
            ->when($startDate,fn($q)=>$q->whereDate('m.occurred_at','>=',$startDate))
            ->when($endDate,fn($q)=>$q->whereDate('m.occurred_at','<=',$endDate))
            ->whereNotExists(function($q){
                $q->selectRaw('1')->from('inventory_consumption_costs as vc')
                    ->whereColumn('vc.inventory_movement_id','m.id');
            })
            ->get(['m.goods_id','m.quantity','m.metadata']);

        $supplyIdFromMetadata=fn($item)=>(int)(json_decode((string)$item->metadata,true)['supply_request_id']??0);
        $ids=$costs->map($supplyIdFromMetadata)->merge($movements->map($supplyIdFromMetadata))->filter()->unique()->values();
        $supplies=$ids->isEmpty()?collect():DB::table('supply_requests')
            ->where('company_id',$companyId)->whereIn('id',$ids)
            ->get(['id','order_id','production_id','fiscal_year_id'])->keyBy('id');
        $prodIds=$supplies->pluck('production_id')->filter()->unique()->values();
        $productions=$prodIds->isEmpty()?collect():DB::table('productions')
            ->where('company_id',$companyId)->whereIn('id',$prodIds)
            ->get(['id','goods_id'])->keyBy('id');
        $links=$prodIds->isEmpty()?collect():DB::table('production_order')
            ->whereIn('production_id',$prodIds)->get(['production_id','order_id'])->groupBy('production_id');

        $resolveTarget=function($item) use ($supplyIdFromMetadata,$supplies,$productions,$links) {
            $meta=json_decode((string)$item->metadata,true)?:[];
            $supply=$supplies->get($supplyIdFromMetadata($item));
            $productionId=(int)($supply?->production_id??0);
            $production=$productions->get($productionId);
            $targetGoodsId=$production?->goods_id!==null?(int)$production->goods_id:(int)$item->goods_id;
            $resolvedOrderId=$supply?->order_id;
            if($resolvedOrderId===null&&$productionId){
                $resolvedOrderId=$links->get($productionId)?->first()?->order_id;
            }
            return [$resolvedOrderId!==null?(int)$resolvedOrderId:null,$targetGoodsId,$supply];
        };

        foreach($costs as $c){
            [$oid,$targetGoodsId]=$resolveTarget($c);
            if($orderId!==null&&$oid!==$orderId)continue;
            if($goodsId!==null&&$targetGoodsId!==$goodsId)continue;
            $key=$this->key($oid,$targetGoodsId);
            $this->row($rows,$key,$oid,$targetGoodsId);
            $rows[$key]['material_cost']+=(float)$c->total_cost;
            $rows[$key]['valuation_methods'][(string)$c->valuation_method]=true;
        }

        foreach($movements as $m){
            $sid=$supplyIdFromMetadata($m);
            if(!$sid)continue;
            [$oid,$targetGoodsId,$supply]=$resolveTarget($m);
            if(!$supply)continue;
            if($fy!==null&&(int)$supply->fiscal_year_id!==$fy)continue;
            if($orderId!==null&&$oid!==$orderId)continue;
            if($goodsId!==null&&$targetGoodsId!==$goodsId)continue;
            $key=$this->key($oid,$targetGoodsId);
            $this->row($rows,$key,$oid,$targetGoodsId);
            $rows[$key]['unvalued_material_quantity']+=abs((float)$m->quantity);
        }
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
        $purchases=DB::table('purchases as p')
            ->join('supply_requests as sr','sr.id','=','p.supply_request_id')
            ->join('purchase_items as pi','pi.purchase_id','=','p.id')
            ->join('purchase_direct_costs as dc','dc.purchase_id','=','p.id')
            ->where('p.company_id',$companyId)
            ->when($fy,fn($q)=>$q->where('p.fiscal_year_id',$fy))
            ->when($startDate,fn($q)=>$q->whereDate('p.purchased_at','>=',$startDate))
            ->when($endDate,fn($q)=>$q->whereDate('p.purchased_at','<=',$endDate))
            ->when($goodsId,fn($q)=>$q->where('pi.goods_id',$goodsId))
            ->select('p.id as purchase_id','sr.order_id','sr.production_id','pi.goods_id','pi.line_total','p.subtotal','dc.amount')
            ->get();
        $prodIds=$purchases->pluck('production_id')->filter()->unique()->values();
        $links=$prodIds->isEmpty()?collect():DB::table('production_order')->whereIn('production_id',$prodIds)->get(['production_id','order_id'])->groupBy('production_id');
        foreach($purchases->groupBy('purchase_id') as $purchaseRows){
            $subtotal=(float)$purchaseRows->first()->subtotal;
            if($subtotal<=0) continue;
            $directTotal=(float)$purchaseRows->sum('amount');
            foreach($purchaseRows->unique('goods_id') as $line){
                $oid=$line->order_id;
                if($oid===null && $line->production_id) $oid=$links->get((int)$line->production_id)?->first()?->order_id;
                if($orderId!==null && (int)$oid!==$orderId) continue;
                $lineShare=max(0.0,(float)$line->line_total)/$subtotal;
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
                'r.id as report_id', 'r.work_date', 'r.warehouse_received_at',
                'r.produced_quantity', 'r.scrap_quantity', 'r.warehouse_quantity',
                'r.worked_hours', 'r.overtime_hours', 'r.holiday_hours',
                'r.personnel_id', 'r.goods_id', 'r.order_id',
                'pe.name as worker_name', 'section_location.name as section_name',
                'warehouse.name as warehouse_name', 'g.code as goods_code', 'g.name as goods_name',
            ])
            ->orderByDesc('r.work_date')->orderByDesc('r.id')
            ->get();

        return $query->map(function ($report) use ($companyId) {
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
                'goods_id' => (int) $report->goods_id,
                'personnel_id' => $report->personnel_id !== null ? (int) $report->personnel_id : null,
                'work_date' => $date,
                'warehouse_received_at' => $report->warehouse_received_at,
                'worker_name' => $report->worker_name,
                'section_name' => $report->section_name,
                'warehouse_name' => $report->warehouse_name,
                'goods_code' => $report->goods_code,
                'goods_name' => $report->goods_name,
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
