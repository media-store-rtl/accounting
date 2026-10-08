<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
final class CostCalculationService {
 public function calculate(int $companyId,int $userId,?int $fiscalYearId=null,?int $orderId=null,?int $goodsId=null): array {
  $report=app(CostingReportService::class)->generate($companyId,$fiscalYearId,$orderId,$goodsId);$created=[];
  DB::transaction(function()use($companyId,$userId,$report,&$created){foreach($report['rows'] as $row){if($row['order_id']===null&&$row['goods_id']===null)continue;$id=DB::table('cost_calculations')->insertGetId(['company_id'=>$companyId,'fiscal_year_id'=>$report['fiscal_year_id'],'order_id'=>$row['order_id'],'goods_id'=>$row['goods_id'],'status'=>'calculated','material_cost'=>$row['material_cost'],'labor_cost'=>$row['labor_cost'],'scrap_cost'=>$row['scrap_cost'],'direct_cost'=>$row['direct_cost'],'total_cost'=>$row['total_cost'],'calculated_at'=>now(),'calculated_by_user_id'=>$userId,'created_at'=>now(),'updated_at'=>now()]);foreach([['material',$row['material_cost'],['valuation_methods'=>$row['valuation_methods']]],['labor',$row['labor_cost'],[]],['scrap',$row['scrap_cost'],[]],['direct',$row['direct_cost'],[]]] as [$type,$amount,$metadata]){if((float)$amount<=0)continue;DB::table('cost_components')->insert(['cost_calculation_id'=>$id,'component_type'=>$type,'source_type'=>'costing_report','source_id'=>null,'amount'=>$amount,'metadata'=>$metadata===[]?null:json_encode($metadata),'created_at'=>now(),'updated_at'=>now()]);}$created[]=$id;}});
  return ['ids'=>$created,'count'=>count($created),'report'=>$report];
 }
}