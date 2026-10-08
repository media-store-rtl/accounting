<?php

namespace Tests\Feature;

use App\Services\ProductionExecutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductionExecutionTest extends TestCase
{
    use RefreshDatabase;

    public function test_operation_execution_is_submitted_reviewed_and_completes_production(): void
    {
        $account=DB::table('accounts')->insertGetId(['name'=>'Execution Account','code'=>'EA'.uniqid(),'created_at'=>now(),'updated_at'=>now()]);
        $company=DB::table('companies')->insertGetId(['account_id'=>$account,'name'=>'Execution Company','code'=>'EC'.uniqid(),'is_active'=>true,'settings'=>json_encode([]),'inventory_valuation_method'=>'fifo','created_at'=>now(),'updated_at'=>now()]);
        $user=DB::table('users')->insertGetId(['account_id'=>$account,'name'=>'Supervisor','username'=>'sup'.uniqid(),'email'=>uniqid().'@test.local','password'=>bcrypt('secret'),'created_at'=>now(),'updated_at'=>now()]);
        $personnel=DB::table('personnel')->insertGetId(['account_id'=>$account,'user_id'=>$user,'code'=>'PE'.uniqid(),'name'=>'Supervisor','is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $location=DB::table('locations')->insertGetId(['company_id'=>$company,'code'=>'PS'.uniqid(),'name'=>'Production Section','type'=>'production','is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $section=DB::table('production_sections')->insertGetId(['location_id'=>$location,'supervisor_personnel_id'=>$personnel,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $category=DB::table('goods_categories')->insertGetId(['company_id'=>$company,'name'=>'Goods','code'=>'GC'.uniqid(),'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $goods=DB::table('goods')->insertGetId(['company_id'=>$company,'category_id'=>$category,'code'=>'G'.uniqid(),'name'=>'Product','producible'=>true,'sellable'=>true,'purchasable'=>false,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $route=DB::table('production_routes')->insertGetId(['company_id'=>$company,'goods_id'=>$goods,'code'=>'R'.uniqid(),'name'=>'Route','status'=>'active','created_at'=>now(),'updated_at'=>now()]);
        $stage=DB::table('production_stages')->insertGetId(['production_route_id'=>$route,'production_section_id'=>$section,'code'=>'S'.uniqid(),'name'=>'Stage','sequence'=>1,'status'=>'active','created_at'=>now(),'updated_at'=>now()]);
        $operation=DB::table('production_operations')->insertGetId(['production_stage_id'=>$stage,'code'=>'O'.uniqid(),'name'=>'Operation','sequence'=>1,'status'=>'active','created_at'=>now(),'updated_at'=>now()]);
        $fy=DB::table('fiscal_years')->insertGetId(['company_id'=>$company,'name'=>'1405','code'=>'FY'.uniqid(),'starts_at'=>'2026-03-21','ends_at'=>'2027-03-20','is_closed'=>false,'created_at'=>now(),'updated_at'=>now()]);
        $production=DB::table('productions')->insertGetId(['company_id'=>$company,'fiscal_year_id'=>$fy,'goods_id'=>$goods,'production_route_id'=>$route,'number'=>'P'.uniqid(),'planned_quantity'=>10,'produced_quantity'=>0,'rejected_quantity'=>0,'status'=>'in_progress','created_at'=>now(),'updated_at'=>now()]);
        $stageRun=DB::table('production_stage_runs')->insertGetId(['production_id'=>$production,'production_stage_id'=>$stage,'sequence'=>1,'status'=>'pending','planned_quantity'=>10,'created_at'=>now(),'updated_at'=>now()]);
        $run=DB::table('production_operation_runs')->insertGetId(['production_stage_run_id'=>$stageRun,'production_operation_id'=>$operation,'sequence'=>1,'status'=>'pending','planned_quantity'=>10,'created_at'=>now(),'updated_at'=>now()]);

        app(ProductionExecutionService::class)->submit($company,$run,
            [['goods_id'=>$goods,'quantity'=>10]],
            [['goods_id'=>$goods,'quantity'=>9,'output_type'=>'product']],
            [['goods_id'=>$goods,'quantity'=>1,'reason'=>'کنترل کیفیت']],
            $user
        );

        $this->assertSame('pending_review',DB::table('production_operation_runs')->where('id',$run)->value('status'));
        $this->assertDatabaseHas('production_operation_inputs',['production_operation_run_id'=>$run,'goods_id'=>$goods,'quantity'=>10]);
        $this->assertDatabaseHas('production_operation_outputs',['production_operation_run_id'=>$run,'goods_id'=>$goods,'quantity'=>9]);
        $this->assertDatabaseHas('scraps',['production_operation_run_id'=>$run,'goods_id'=>$goods,'quantity'=>1]);

        app(ProductionExecutionService::class)->review($company,$run,$user,true);

        $this->assertSame('completed',DB::table('production_operation_runs')->where('id',$run)->value('status'));
        $this->assertSame('completed',DB::table('production_stage_runs')->where('id',$stageRun)->value('status'));
        $this->assertSame('completed',DB::table('productions')->where('id',$production)->value('status'));
        $this->assertDatabaseHas('audit_trails',['module'=>'production','action'=>'operation.approve','auditable_id'=>$run]);
    }
}
