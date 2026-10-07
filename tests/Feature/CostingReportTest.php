<?php

namespace Tests\Feature;

use App\Services\CostingReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CostingReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_costing_report_aggregates_material_and_approved_labor_by_order_and_product(): void
    {
        $account=DB::table('accounts')->insertGetId(['name'=>'Report Account','code'=>'RA'.uniqid(),'created_at'=>now(),'updated_at'=>now()]);
        $company=DB::table('companies')->insertGetId(['account_id'=>$account,'name'=>'Report Company','code'=>'RC'.uniqid(),'is_active'=>true,'settings'=>json_encode([]),'inventory_valuation_method'=>'fifo','created_at'=>now(),'updated_at'=>now()]);
        $user=DB::table('users')->insertGetId(['account_id'=>$account,'name'=>'Report User','username'=>'ru'.uniqid(),'email'=>uniqid().'@test.local','password'=>bcrypt('secret'),'created_at'=>now(),'updated_at'=>now()]);
        $fy=DB::table('fiscal_years')->insertGetId(['company_id'=>$company,'name'=>'1405','code'=>'FY'.uniqid(),'starts_at'=>'2026-03-21','ends_at'=>'2027-03-20','is_closed'=>false,'created_at'=>now(),'updated_at'=>now()]);
        $customer=DB::table('customers')->insertGetId(['company_id'=>$company,'name'=>'Customer','code'=>'CUS'.uniqid(),'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $category=DB::table('goods_categories')->insertGetId(['company_id'=>$company,'name'=>'Goods','code'=>'GC'.uniqid(),'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $goods=DB::table('goods')->insertGetId(['company_id'=>$company,'category_id'=>$category,'code'=>'FG1','name'=>'Finished Good','purchasable'=>false,'producible'=>true,'sellable'=>true,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $route=DB::table('production_routes')->insertGetId(['company_id'=>$company,'goods_id'=>$goods,'code'=>'R'.uniqid(),'name'=>'Route','status'=>'active','created_at'=>now(),'updated_at'=>now()]);
        $stage=DB::table('production_stages')->insertGetId(['production_route_id'=>$route,'code'=>'S'.uniqid(),'name'=>'Stage','sequence'=>1,'status'=>'active','created_at'=>now(),'updated_at'=>now()]);
        $production=DB::table('productions')->insertGetId(['company_id'=>$company,'fiscal_year_id'=>$fy,'goods_id'=>$goods,'production_route_id'=>$route,'number'=>'P'.uniqid(),'planned_quantity'=>10,'produced_quantity'=>0,'rejected_quantity'=>0,'status'=>'completed','created_at'=>now(),'updated_at'=>now()]);
        $order=DB::table('orders')->insertGetId(['company_id'=>$company,'fiscal_year_id'=>$fy,'customer_id'=>$customer,'number'=>'ORD-1','ordered_at'=>'2026-10-07','requested_delivery_at'=>'2026-10-10','status'=>'production_required','created_at'=>now(),'updated_at'=>now()]);
        DB::table('production_order')->insert(['production_id'=>$production,'order_id'=>$order,'created_at'=>now(),'updated_at'=>now()]);

        $supply=DB::table('supply_requests')->insertGetId(['company_id'=>$company,'fiscal_year_id'=>$fy,'production_id'=>$production,'order_id'=>$order,'requested_by_user_id'=>$user,'requested_at'=>now(),'needed_at'=>now(),'status'=>'fulfilled','notes'=>null,'created_at'=>now(),'updated_at'=>now()]);
        $location=DB::table('locations')->insertGetId(['company_id'=>$company,'code'=>'W'.uniqid(),'name'=>'Warehouse','type'=>'warehouse','is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $movement=DB::table('inventory_movements')->insertGetId(['company_id'=>$company,'goods_id'=>$goods,'location_id'=>$location,'quantity'=>-2,'movement_type'=>'material_handover','reference_type'=>'material_handovers','reference_id'=>1,'occurred_at'=>now(),'metadata'=>json_encode(['supply_request_id'=>$supply]),'created_at'=>now(),'updated_at'=>now()]);
        DB::table('inventory_consumption_costs')->insert(['company_id'=>$company,'fiscal_year_id'=>$fy,'goods_id'=>$goods,'location_id'=>$location,'inventory_movement_id'=>$movement,'valuation_method'=>'fifo','quantity'=>2,'unit_cost'=>10,'total_cost'=>20,'consumed_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);

        $stageRun=DB::table('production_stage_runs')->insertGetId(['production_id'=>$production,'production_stage_id'=>$stage,'sequence'=>1,'status'=>'completed','planned_quantity'=>10,'input_quantity'=>10,'output_quantity'=>10,'rejected_quantity'=>0,'created_at'=>now(),'updated_at'=>now()]);
        $operation=DB::table('production_operations')->insertGetId(['production_stage_id'=>$stage,'code'=>'OP'.uniqid(),'name'=>'Operation','sequence'=>1,'status'=>'active','created_at'=>now(),'updated_at'=>now()]);
        $run=DB::table('production_operation_runs')->insertGetId(['production_stage_run_id'=>$stageRun,'production_operation_id'=>$operation,'sequence'=>1,'status'=>'completed','planned_quantity'=>10,'input_quantity'=>10,'output_quantity'=>10,'rejected_quantity'=>0,'created_at'=>now(),'updated_at'=>now()]);
        $personnel=DB::table('personnel')->insertGetId(['account_id'=>$account,'user_id'=>$user,'code'=>'P'.uniqid(),'name'=>'Worker','is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $entry=DB::table('production_labor_entries')->insertGetId(['company_id'=>$company,'fiscal_year_id'=>$fy,'production_id'=>$production,'production_stage_id'=>$stage,'production_operation_run_id'=>$run,'personnel_id'=>$personnel,'measure_type'=>'hours','measure_quantity'=>2,'unit_rate'=>15,'total_cost'=>30,'worked_at'=>now(),'status'=>'approved','created_by_user_id'=>$user,'reviewed_by_user_id'=>$user,'reviewed_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);
        DB::table('production_labor_costs')->insert(['labor_entry_id'=>$entry,'company_id'=>$company,'fiscal_year_id'=>$fy,'production_id'=>$production,'production_stage_id'=>$stage,'production_operation_run_id'=>$run,'personnel_id'=>$personnel,'measure_type'=>'hours','measure_quantity'=>2,'unit_rate'=>15,'total_cost'=>30,'worked_at'=>now(),'approved_at'=>now(),'approved_by_user_id'=>$user,'created_at'=>now(),'updated_at'=>now()]);

        $report=app(CostingReportService::class)->generate($company,$fy,$order,$goods);
        $this->assertCount(1,$report['rows']);
        $this->assertSame(20.0,$report['rows'][0]['material_cost']);
        $this->assertSame(30.0,$report['rows'][0]['labor_cost']);
        $this->assertSame(50.0,$report['rows'][0]['total_cost']);
        $this->assertSame(['fifo'],$report['rows'][0]['valuation_methods']);
        $this->assertSame(0.0,$report['totals']['unvalued_material_quantity']);
    }
}
