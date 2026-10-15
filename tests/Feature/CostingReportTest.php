<?php

namespace Tests\Feature;

use App\Services\CostingReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CostingReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_costing_report_returns_empty_valuation_methods(): void
    {
        $account = DB::table('accounts')->insertGetId([
            'name' => 'Empty Report Account',
            'code' => 'ERA'.uniqid(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $company = DB::table('companies')->insertGetId([
            'account_id' => $account,
            'name' => 'Empty Report Company',
            'code' => 'ERC'.uniqid(),
            'is_active' => true,
            'settings' => json_encode([]),
            'inventory_valuation_method' => 'fifo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $fy = DB::table('fiscal_years')->insertGetId([
            'company_id' => $company,
            'name' => '1405',
            'code' => 'FY'.uniqid(),
            'starts_at' => '2026-03-21',
            'ends_at' => '2027-03-20',
            'is_closed' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $report = app(CostingReportService::class)->generate($company, $fy);

        $this->assertSame([], $report['rows']);
        $this->assertSame([], $report['product_rows']);
        $this->assertSame([], $report['production_reports']);
        $this->assertSame([], $report['valuation_methods']);
        $this->assertSame(0.0, $report['totals']['material_cost']);
        $this->assertSame(0.0, $report['totals']['labor_cost']);
        $this->assertSame(0.0, $report['totals']['total_cost']);
    }

    public function test_costing_report_aggregates_material_and_approved_labor_by_order_and_product(): void
    {
        $account=DB::table('accounts')->insertGetId(['name'=>'Report Account','code'=>'RA'.uniqid(),'created_at'=>now(),'updated_at'=>now()]);
        $company=DB::table('companies')->insertGetId(['account_id'=>$account,'name'=>'Report Company','code'=>'RC'.uniqid(),'is_active'=>true,'settings'=>json_encode([]),'inventory_valuation_method'=>'fifo','created_at'=>now(),'updated_at'=>now()]);
        $user=DB::table('users')->insertGetId(['account_id'=>$account,'name'=>'Report User','username'=>'ru'.uniqid(),'email'=>uniqid().'@test.local','password'=>bcrypt('secret'),'created_at'=>now(),'updated_at'=>now()]);
        $fy=DB::table('fiscal_years')->insertGetId(['company_id'=>$company,'name'=>'1405','code'=>'FY'.uniqid(),'starts_at'=>'2026-03-21','ends_at'=>'2027-03-20','is_closed'=>false,'created_at'=>now(),'updated_at'=>now()]);
        $customer=DB::table('customers')->insertGetId(['company_id'=>$company,'name'=>'Customer','code'=>'CUS'.uniqid(),'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $category=DB::table('goods_categories')->insertGetId(['company_id'=>$company,'name'=>'Goods','code'=>'GC'.uniqid(),'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $goods=DB::table('goods')->insertGetId(['company_id'=>$company,'category_id'=>$category,'code'=>'FG1','name'=>'Finished Good','purchasable'=>false,'producible'=>true,'sellable'=>true,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $rawMaterial=DB::table('goods')->insertGetId(['company_id'=>$company,'category_id'=>$category,'code'=>'RM1','name'=>'Raw Material','purchasable'=>true,'producible'=>false,'sellable'=>false,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $route=DB::table('production_routes')->insertGetId(['company_id'=>$company,'goods_id'=>$goods,'code'=>'R'.uniqid(),'name'=>'Route','status'=>'active','created_at'=>now(),'updated_at'=>now()]);
        $stage=DB::table('production_stages')->insertGetId(['production_route_id'=>$route,'code'=>'S'.uniqid(),'name'=>'Stage','sequence'=>1,'status'=>'active','created_at'=>now(),'updated_at'=>now()]);
        $production=DB::table('productions')->insertGetId(['company_id'=>$company,'fiscal_year_id'=>$fy,'goods_id'=>$goods,'production_route_id'=>$route,'number'=>'P'.uniqid(),'planned_quantity'=>10,'produced_quantity'=>0,'rejected_quantity'=>0,'status'=>'completed','created_at'=>now(),'updated_at'=>now()]);
        $order=DB::table('orders')->insertGetId(['company_id'=>$company,'fiscal_year_id'=>$fy,'customer_id'=>$customer,'number'=>'ORD-1','ordered_at'=>'2026-10-07','requested_delivery_at'=>'2026-10-10','status'=>'production_required','created_at'=>now(),'updated_at'=>now()]);
        DB::table('production_order')->insert(['production_id'=>$production,'order_id'=>$order,'created_at'=>now(),'updated_at'=>now()]);

        $supply=DB::table('supply_requests')->insertGetId(['company_id'=>$company,'fiscal_year_id'=>$fy,'production_id'=>$production,'order_id'=>$order,'requested_by_user_id'=>$user,'requested_at'=>now(),'needed_at'=>now(),'status'=>'fulfilled','notes'=>null,'created_at'=>now(),'updated_at'=>now()]);
        $location=DB::table('locations')->insertGetId(['company_id'=>$company,'code'=>'W'.uniqid(),'name'=>'Warehouse','type'=>'warehouse','is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $movement=DB::table('inventory_movements')->insertGetId(['company_id'=>$company,'goods_id'=>$rawMaterial,'location_id'=>$location,'quantity'=>-2,'movement_type'=>'material_handover','reference_type'=>'material_handovers','reference_id'=>1,'occurred_at'=>now(),'metadata'=>json_encode(['supply_request_id'=>$supply]),'created_at'=>now(),'updated_at'=>now()]);
        DB::table('inventory_consumption_costs')->insert(['company_id'=>$company,'fiscal_year_id'=>$fy,'goods_id'=>$rawMaterial,'location_id'=>$location,'inventory_movement_id'=>$movement,'valuation_method'=>'fifo','quantity'=>2,'unit_cost'=>10,'total_cost'=>20,'consumed_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);
        $purchase=DB::table('purchases')->insertGetId(['company_id'=>$company,'fiscal_year_id'=>$fy,'supplier_id'=>DB::table('suppliers')->insertGetId(['company_id'=>$company,'code'=>'SUP'.uniqid(),'name'=>'Supplier','is_active'=>true,'created_at'=>now(),'updated_at'=>now()]),'supply_request_id'=>$supply,'invoice_number'=>'INV-1','invoice_date'=>'2026-10-07','purchased_at'=>'2026-10-07','subtotal'=>20,'direct_cost_total'=>10,'total_amount'=>30,'status'=>'received','created_at'=>now(),'updated_at'=>now()]);
        $pi=DB::table('purchase_items')->insertGetId(['purchase_id'=>$purchase,'goods_id'=>$goods,'quantity'=>2,'unit_price'=>10,'line_total'=>20,'created_at'=>now(),'updated_at'=>now()]);
        DB::table('purchase_direct_costs')->insert(['purchase_id'=>$purchase,'type'=>'freight','description'=>'حمل','amount'=>10,'created_at'=>now(),'updated_at'=>now()]);

        $stageRun=DB::table('production_stage_runs')->insertGetId(['production_id'=>$production,'production_stage_id'=>$stage,'sequence'=>1,'status'=>'completed','planned_quantity'=>10,'input_quantity'=>10,'output_quantity'=>10,'rejected_quantity'=>0,'created_at'=>now(),'updated_at'=>now()]);
        $operation=DB::table('production_operations')->insertGetId(['production_stage_id'=>$stage,'code'=>'OP'.uniqid(),'name'=>'Operation','sequence'=>1,'status'=>'active','created_at'=>now(),'updated_at'=>now()]);
        $run=DB::table('production_operation_runs')->insertGetId(['production_stage_run_id'=>$stageRun,'production_operation_id'=>$operation,'sequence'=>1,'status'=>'completed','planned_quantity'=>10,'input_quantity'=>10,'output_quantity'=>10,'rejected_quantity'=>0,'created_at'=>now(),'updated_at'=>now()]);
        $personnel=DB::table('personnel')->insertGetId(['account_id'=>$account,'user_id'=>$user,'code'=>'P'.uniqid(),'name'=>'Worker','is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $entry=DB::table('production_labor_entries')->insertGetId(['company_id'=>$company,'fiscal_year_id'=>$fy,'production_id'=>$production,'production_stage_id'=>$stage,'production_operation_run_id'=>$run,'personnel_id'=>$personnel,'measure_type'=>'hours','measure_quantity'=>2,'unit_rate'=>15,'total_cost'=>30,'worked_at'=>now(),'status'=>'approved','created_by_user_id'=>$user,'reviewed_by_user_id'=>$user,'reviewed_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);
        DB::table('production_labor_costs')->insert(['labor_entry_id'=>$entry,'company_id'=>$company,'fiscal_year_id'=>$fy,'production_id'=>$production,'production_stage_id'=>$stage,'production_operation_run_id'=>$run,'personnel_id'=>$personnel,'measure_type'=>'hours','measure_quantity'=>2,'unit_rate'=>15,'total_cost'=>30,'worked_at'=>now(),'approved_at'=>now(),'approved_by_user_id'=>$user,'created_at'=>now(),'updated_at'=>now()]);

        // Shift reports handed over to the warehouse remain part of labor costing.
        $sectionLocation=DB::table('locations')->insertGetId([
            'company_id'=>$company,'code'=>'PS'.uniqid(),'name'=>'Production Section Location',
            'type'=>'production','is_active'=>true,'created_at'=>now(),'updated_at'=>now(),
        ]);
        $section=DB::table('production_sections')->insertGetId([
            'location_id'=>$sectionLocation,'created_at'=>now(),'updated_at'=>now(),
        ]);
        DB::table('supply_requests')->where('id', $supply)->update(['production_section_id' => $section]);
        $handover = DB::table('material_handovers')->insertGetId([
            'company_id'=>$company,'fiscal_year_id'=>$fy,'supply_request_id'=>$supply,
            'warehouse_location_id'=>$location,'delivered_by_user_id'=>$user,'received_by_user_id'=>$user,
            'handed_over_at'=>'2026-10-06 08:00:00','status'=>'completed','notes'=>null,
            'created_at'=>now(),'updated_at'=>now(),
        ]);
        DB::table('material_handover_items')->insert([
            'material_handover_id'=>$handover,'goods_id'=>$rawMaterial,'quantity'=>2,
            'created_at'=>now(),'updated_at'=>now(),
        ]);
        DB::table('inventory_movements')->where('id', $movement)->update(['reference_id' => $handover]);
        DB::table('personnel_labor_rates')->insert([
            'company_id'=>$company,'personnel_id'=>$personnel,'rate_type'=>'regular','rate'=>15,
            'effective_from'=>'2026-10-01','effective_to'=>null,'created_at'=>now(),'updated_at'=>now(),
        ]);
        $receivedShiftReportId = null;
        foreach (['approved','received','pending'] as $shiftStatus) {
            $shiftReportId = DB::table('production_shift_reports')->insertGetId([
                'company_id'=>$company,'production_section_id'=>$section,'goods_id'=>$goods,
                'order_id'=>$order,'personnel_id'=>$personnel,'reported_by_user_id'=>$user,
                'shift_name'=>'Day Shift','worked_hours'=>1,'overtime_hours'=>0,'holiday_hours'=>0,
                'work_date'=>'2026-10-07','produced_quantity'=>1,'scrap_quantity'=>0,
                'status'=>$shiftStatus,
                'warehouse_location_id'=>$shiftStatus==='received'?$location:null,
                'warehouse_received_at'=>$shiftStatus==='received'?now():null,
                'warehouse_quantity'=>$shiftStatus==='received'?1:null,
                'created_at'=>now(),'updated_at'=>now(),
            ]);
            if ($shiftStatus === 'received') $receivedShiftReportId = $shiftReportId;
        }
        if ($receivedShiftReportId !== null && DB::getSchemaBuilder()->hasTable('production_shift_report_stages')) {
            DB::table('production_shift_report_stages')->insert([
                'company_id' => $company,
                'production_shift_report_id' => $receivedShiftReportId,
                'production_stage_id' => $stage,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        if ($receivedShiftReportId !== null) {
            DB::table('production_shift_report_materials')->insert([
                'company_id' => $company,
                'production_shift_report_id' => $receivedShiftReportId,
                'goods_id' => $rawMaterial,
                'quantity' => 2,
                'scrap_quantity' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            // A second usage row must not reuse the same two-unit handover.
            DB::table('production_shift_report_materials')->insert([
                'company_id' => $company,
                'production_shift_report_id' => $receivedShiftReportId,
                'goods_id' => $rawMaterial,
                'quantity' => 2,
                'scrap_quantity' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Approved labor without a sales order must still appear under a null order.
        DB::table('production_shift_reports')->insert([
            'company_id'=>$company,'production_section_id'=>$section,'goods_id'=>$goods,
            'order_id'=>null,'personnel_id'=>$personnel,'reported_by_user_id'=>$user,
            'shift_name'=>'Day Shift','worked_hours'=>1,'overtime_hours'=>0,'holiday_hours'=>0,
            'work_date'=>'2026-10-07','produced_quantity'=>1,'scrap_quantity'=>0,
            'status'=>'approved','created_at'=>now(),'updated_at'=>now(),
        ]);

        $report=app(CostingReportService::class)->generate($company,$fy,$order,$goods);
        $this->assertCount(1,$report['rows']);
        $this->assertSame(20.0,$report['rows'][0]['material_cost']);
        $this->assertSame(60.0,$report['rows'][0]['labor_cost']);
        $this->assertSame(10.0,$report['rows'][0]['direct_cost']);
        $this->assertSame(90.0,$report['rows'][0]['total_cost']);
        $this->assertSame(['fifo'],$report['rows'][0]['valuation_methods']);
        $this->assertSame(0.0,$report['totals']['unvalued_material_quantity']);
        $this->assertCount(1,$report['production_reports']);
        $this->assertSame(['Stage'], $report['production_reports'][0]['stage_names']);
        $this->assertCount(2, $report['production_reports'][0]['materials']);
        $this->assertSame('Raw Material', $report['production_reports'][0]['materials'][0]['material_name']);
        $this->assertSame(2.0, $report['production_reports'][0]['materials'][0]['allocated_quantity']);
        $this->assertSame(2.0, $report['production_reports'][0]['materials'][0]['valued_quantity']);
        $this->assertSame(20.0, $report['production_reports'][0]['materials'][0]['material_cost']);
        $this->assertSame(10.0, $report['production_reports'][0]['materials'][0]['unit_cost']);
        $this->assertTrue($report['production_reports'][0]['materials'][0]['cost_complete']);
        $this->assertSame(0.0, $report['production_reports'][0]['materials'][1]['allocated_quantity']);
        $this->assertSame(2.0, $report['production_reports'][0]['materials'][1]['unmatched_quantity']);
        $this->assertSame(2.0, $report['production_reports'][0]['materials'][1]['unvalued_quantity']);
        $this->assertSame(0.0, $report['production_reports'][0]['materials'][1]['material_cost']);
        $this->assertFalse($report['production_reports'][0]['materials'][1]['cost_complete']);
        $this->assertFalse($report['production_reports'][0]['material_cost_complete']);
        $this->assertSame(20.0, $report['production_reports'][0]['material_cost']);
        $this->assertSame(1.0,$report['product_rows'][0]['produced_quantity']);
        $this->assertSame(90.0,$report['product_rows'][0]['unit_cost']);

        $filtered=app(CostingReportService::class)->generate($company,$fy,$order,$goods,'2026-10-07','2026-10-07',$personnel);
        $this->assertCount(1,$filtered['production_reports']);
        $this->assertSame(1.0,$filtered['product_rows'][0]['produced_quantity']);
        $this->assertSame(30.0,$filtered['totals']['labor_cost']);
        $this->assertSame(40.0,$filtered['product_rows'][0]['unit_cost']);

        $unassignedReport=app(CostingReportService::class)->generate($company,$fy,null,$goods);
        $unassignedRows=array_values(array_filter($unassignedReport['rows'],fn($row)=>$row['order_id']===null&&$row['goods_id']===$goods));
        $this->assertCount(1,$unassignedRows);
        $this->assertSame(15.0,$unassignedRows[0]['labor_cost']);

        $calculation=app(\App\Services\CostCalculationService::class)->calculate($company,$user,$fy,$order,$goods);
        $this->assertSame(1,$calculation['count']);
        $this->assertDatabaseHas('cost_calculations',['id'=>$calculation['ids'][0],'company_id'=>$company,'order_id'=>$order,'goods_id'=>$goods,'total_cost'=>90]);
        $this->assertDatabaseHas('cost_components',['cost_calculation_id'=>$calculation['ids'][0],'component_type'=>'material','amount'=>20]);
        $this->assertDatabaseHas('cost_components',['cost_calculation_id'=>$calculation['ids'][0],'component_type'=>'labor','amount'=>60]);
        $this->assertDatabaseHas('cost_components',['cost_calculation_id'=>$calculation['ids'][0],'component_type'=>'direct','amount'=>10]);
    }
}
