<?php

namespace Tests\Feature;

use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InventoryAdjustmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_adjustment_and_reorder_point_are_traceable(): void
    {
        $account=DB::table('accounts')->insertGetId(['name'=>'Inventory Account','code'=>'IA'.uniqid(),'created_at'=>now(),'updated_at'=>now()]);
        $company=DB::table('companies')->insertGetId(['account_id'=>$account,'name'=>'Inventory Company','code'=>'IC'.uniqid(),'is_active'=>true,'settings'=>json_encode([]),'inventory_valuation_method'=>'fifo','created_at'=>now(),'updated_at'=>now()]);
        $user=DB::table('users')->insertGetId(['account_id'=>$account,'name'=>'Inventory User','username'=>'iu'.uniqid(),'email'=>uniqid().'@test.local','password'=>bcrypt('secret'),'created_at'=>now(),'updated_at'=>now()]);
        $category=DB::table('goods_categories')->insertGetId(['company_id'=>$company,'name'=>'Goods','code'=>'IG'.uniqid(),'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $goods=DB::table('goods')->insertGetId(['company_id'=>$company,'category_id'=>$category,'code'=>'G'.uniqid(),'name'=>'Material','purchasable'=>true,'producible'=>false,'sellable'=>true,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $location=DB::table('locations')->insertGetId(['company_id'=>$company,'code'=>'W'.uniqid(),'name'=>'Warehouse','type'=>'warehouse','is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $inventory=DB::table('inventory')->insertGetId(['company_id'=>$company,'location_id'=>$location,'goods_id'=>$goods,'quantity'=>10,'reorder_point'=>5,'created_at'=>now(),'updated_at'=>now()]);

        app(InventoryService::class)->adjust($company,$location,$goods,4,'count adjustment',$user);
        app(InventoryService::class)->setReorderPoint($company,$inventory,6,$user);

        $this->assertDatabaseHas('inventory',['id'=>$inventory,'quantity'=>4,'reorder_point'=>6]);
        $this->assertDatabaseHas('inventory_movements',['company_id'=>$company,'goods_id'=>$goods,'location_id'=>$location,'movement_type'=>'inventory_adjustment','quantity'=>-6]);
        $this->assertDatabaseHas('audit_trails',['company_id'=>$company,'user_id'=>$user,'module'=>'warehouse','action'=>'inventory.adjustment','auditable_id'=>$inventory]);
        $this->assertDatabaseHas('audit_trails',['company_id'=>$company,'user_id'=>$user,'module'=>'warehouse','action'=>'inventory.reorder_point.update','auditable_id'=>$inventory]);
    }
}
