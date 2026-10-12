<?php

namespace Tests\\Feature;

use App\\Services\\CostingReportService;
use Illuminate\\Foundation\\Testing\\RefreshDatabase;
use Illuminate\\Support\\Facades\\DB;
use Tests\\TestCase;

class ShiftLaborCostingTest extends TestCase
{
    use RefreshDatabase;

    public function test_approved_shift_labor_is_included_in_product_cost_report_by_fiscal_year(): void
    {
        $account = DB::table('accounts')->insertGetId(['name'=>'Shift Cost Account','code'=>'SCA'.uniqid(),'created_at'=>now(),'updated_at'=>now()]);
        $company = DB::table('companies')->insertGetId(['account_id'=>$account,'name'=>'Shift Cost Company','code'=>'SCC'.uniqid(),'is_active'=>true,'settings'=>json_encode([]),'inventory_valuation_method'=>'fifo','created_at'=>now(),'updated_at'=>now()]);
        $user = DB::table('users')->insertGetId(['account_id'=>$account,'name'=>'Shift User','username'=>'su'.uniqid(),'email'=>uniqid().'@test.local','password'=>bcrypt('secret'),'created_at'=>now(),'updated_at'=>now()]);
        $fy = DB::table('fiscal_years')->insertGetId(['company_id'=>$company,'name'=>'1405','code'=>'SFY'.uniqid(),'starts_at'=>'2026-03-21','ends_at'=>'2027-03-20','is_closed'=>false,'created_at'=>now(),'updated_at'=>now()]);
        $category = DB::table('goods_categories')->insertGetId(['company_id'=>$company,'name'=>'Finished','code'=>'SFG'.uniqid(),'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $goods = DB::table('goods')->insertGetId(['company_id'=>$company,'category_id'=>$category,'code'=>'SF'.uniqid(),'name'=>'Finished Part','purchasable'=>false,'producible'=>true,'sellable'=>true,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $location = DB::table('locations')->insertGetId(['company_id'=>$company,'code'=>'SEC'.uniqid(),'name'=>'Assembly','type'=>'production','is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $section = DB::table('production_sections')->insertGetId(['location_id'=>$location,'created_at'=>now(),'updated_at'=>now()]);
        $personnel = DB::table('personnel')->insertGetId(['account_id'=>$account,'user_id'=>$user,'code'=>'SP'.uniqid(),'name'=>'Shift Worker','is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);

        DB::table('production_shift_reports')->insert([
            'company_id'=>$company,
            'production_section_id'=>$section,
            'goods_id'=>$goods,
            'personnel_id'=>$personnel,
            'reported_by_user_id'=>$user,
            'reviewed_by_user_id'=>$user,
            'shift_name'=>'Morning',
            'work_date'=>'2026-10-12',
            'worked_hours'=>4,
            'labor_rate_type'=>'hourly',
            'labor_unit_rate'=>250,
            'labor_total_cost'=>1000,
            'labor_cost_calculated_at'=>now(),
            'produced_quantity'=>20,
            'scrap_quantity'=>1,
            'warehouse_quantity'=>19,
            'status'=>'received',
            'reviewed_at'=>now(),
            'warehouse_received_at'=>now(),
            'created_at'=>now(),
            'updated_at'=>now(),
        ]);

        $report = app(CostingReportService::class)->generate($company, $fy);

        $this->assertCount(1, $report['rows']);
        $this->assertSame($goods, $report['rows'][0]['goods_id']);
        $this->assertNull($report['rows'][0]['order_id']);
        $this->assertSame(1000.0, $report['rows'][0]['labor_cost']);
        $this->assertSame(1000.0, $report['rows'][0]['total_cost']);
        $this->assertSame(1000.0, $report['totals']['labor_cost']);
    }
}
