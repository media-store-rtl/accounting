<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use App\Services\LaborCostService;

class LaborCostTest extends TestCase
{
    use RefreshDatabase;

    public function test_labor_rate_is_snapshotted_and_approved_cost_is_traceable(): void
    {
        $account=DB::table('accounts')->insertGetId(['name'=>'Labor Account','code'=>'LA'.uniqid(),'created_at'=>now(),'updated_at'=>now()]);
        $company=DB::table('companies')->insertGetId(['account_id'=>$account,'name'=>'Labor Company','code'=>'LC'.uniqid(),'is_active'=>true,'settings'=>json_encode([]),'inventory_valuation_method'=>'fifo','created_at'=>now(),'updated_at'=>now()]);
        $user=DB::table('users')->insertGetId(['account_id'=>$account,'name'=>'Labor User','username'=>'lu'.uniqid(),'email'=>uniqid().'@test.local','password'=>bcrypt('secret'),'created_at'=>now(),'updated_at'=>now()]);
        $fy=DB::table('fiscal_years')->insertGetId(['company_id'=>$company,'name'=>'1405','code'=>'LFY'.uniqid(),'starts_at'=>'2026-03-21','ends_at'=>'2027-03-20','is_closed'=>false,'created_at'=>now(),'updated_at'=>now()]);
        $personnel=DB::table('personnel')->insertGetId(['account_id'=>$account,'user_id'=>$user,'code'=>'P1'.uniqid(),'name'=>'Worker','is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $category=DB::table('goods_categories')->insertGetId(['company_id'=>$company,'name'=>'Raw','code'=>'GC'.uniqid(),'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $goods=DB::table('goods')->insertGetId(['company_id'=>$company,'category_id'=>$category,'code'=>'G'.uniqid(),'name'=>'Product','purchasable'=>false,'producible'=>true,'sellable'=>true,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $route=DB::table('production_routes')->insertGetId(['company_id'=>$company,'goods_id'=>$goods,'code'=>'R'.uniqid(),'name'=>'Route','status'=>'active','created_at'=>now(),'updated_at'=>now()]);
        $stage=DB::table('production_stages')->insertGetId(['production_route_id'=>$route,'code'=>'S'.uniqid(),'name'=>'Stage','sequence'=>1,'status'=>'active','created_at'=>now(),'updated_at'=>now()]);
        $production=DB::table('productions')->insertGetId(['company_id'=>$company,'fiscal_year_id'=>$fy,'goods_id'=>$goods,'production_route_id'=>$route,'number'=>'P'.uniqid(),'planned_quantity'=>10,'produced_quantity'=>0,'rejected_quantity'=>0,'status'=>'completed','created_at'=>now(),'updated_at'=>now()]);
        $stageRun=DB::table('production_stage_runs')->insertGetId(['production_id'=>$production,'production_stage_id'=>$stage,'sequence'=>1,'status'=>'completed','planned_quantity'=>10,'input_quantity'=>10,'output_quantity'=>10,'rejected_quantity'=>0,'created_at'=>now(),'updated_at'=>now()]);
        $operation=DB::table('production_operations')->insertGetId(['production_stage_id'=>$stage,'code'=>'OP'.uniqid(),'name'=>'Operation','sequence'=>1,'status'=>'active','created_at'=>now(),'updated_at'=>now()]);
        $run=DB::table('production_operation_runs')->insertGetId(['production_stage_run_id'=>$stageRun,'production_operation_id'=>$operation,'sequence'=>1,'status'=>'completed','planned_quantity'=>10,'input_quantity'=>10,'output_quantity'=>10,'rejected_quantity'=>0,'created_at'=>now(),'updated_at'=>now()]);
        app(LaborCostService::class)->setRate($company,$personnel,'hourly',100,'2026-10-01');
        $entry=app(LaborCostService::class)->record($company,$run,$personnel,'hours',3,'2026-10-07 10:00:00',$user);
        $this->assertSame(300.0,(float)DB::table('production_labor_entries')->where('id',$entry)->value('total_cost'));
        app(LaborCostService::class)->review($company,$entry,$user,true);
        $cost=DB::table('production_labor_costs')->where('labor_entry_id',$entry)->first();
        $this->assertNotNull($cost);
        $this->assertSame(300.0,(float)$cost->total_cost);
        $this->assertSame('approved',DB::table('production_labor_entries')->where('id',$entry)->value('status'));
    }
}