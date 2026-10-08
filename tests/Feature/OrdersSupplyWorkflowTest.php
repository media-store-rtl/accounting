<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class OrdersSupplyWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function context(): array
    {
        $account=DB::table('accounts')->insertGetId(['name'=>'Test Account','code'=>'ACC'.uniqid(),'created_at'=>now(),'updated_at'=>now()]);
        $company=DB::table('companies')->insertGetId(['account_id'=>$account,'name'=>'Test Company','code'=>'COM'.uniqid(),'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $user=DB::table('users')->insertGetId(['account_id'=>$account,'name'=>'Tester','username'=>'u'.uniqid(),'email'=>uniqid().'@test.local','password'=>Hash::make('secret'),'created_at'=>now(),'updated_at'=>now()]);
        $role=DB::table('roles')->insertGetId(['company_id'=>$company,'name'=>'Operations','slug'=>'ops','is_system'=>false,'created_at'=>now(),'updated_at'=>now()]);
        foreach(['order.create','order.view','supply_request.create','supply_request.view','purchase.create','purchase.view','purchase.receipt.create','purchase.receipt.approve'] as $slug){
            DB::table('permissions')->updateOrInsert(['slug'=>$slug],['name'=>$slug,'module'=>'orders-supply','updated_at'=>now(),'created_at'=>now()]);
            $permission=(int) DB::table('permissions')->where('slug',$slug)->value('id');
            DB::table('role_permissions')->insert(['role_id'=>$role,'permission_id'=>$permission]);
        }
        DB::table('company_user')->insert(['company_id'=>$company,'user_id'=>$user,'role_id'=>$role,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $fy=DB::table('fiscal_years')->insertGetId(['company_id'=>$company,'name'=>'1405','code'=>'FY1405','starts_at'=>'2026-03-21','ends_at'=>'2027-03-20','is_closed'=>false,'created_at'=>now(),'updated_at'=>now()]);
        $category=DB::table('goods_categories')->insertGetId(['company_id'=>$company,'name'=>'Raw','code'=>'RAW','is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $goods=DB::table('goods')->insertGetId(['company_id'=>$company,'category_id'=>$category,'code'=>'G1','name'=>'Material','purchasable'=>true,'producible'=>false,'sellable'=>true,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $customer=DB::table('customers')->insertGetId(['company_id'=>$company,'name'=>'Customer','code'=>'CU1','is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $warehouse=DB::table('locations')->insertGetId(['company_id'=>$company,'code'=>'W1','name'=>'Warehouse','type'=>'warehouse','is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        DB::table('inventory')->insert(['company_id'=>$company,'location_id'=>$warehouse,'goods_id'=>$goods,'quantity'=>5,'created_at'=>now(),'updated_at'=>now()]);
        $supplier=DB::table('suppliers')->insertGetId(['company_id'=>$company,'name'=>'Supplier','code'=>'SUP1','is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $this->actingAs(\App\Models\User::findOrFail($user))->withSession(['company_id'=>$company]);
        return compact('company','user','fy','goods','customer','warehouse','supplier');
    }

    public function test_order_detects_shortage_before_production_notification(): void
    {
        $c=$this->context();
        $response=$this->postJson('/api/orders',['fiscal_year_id'=>$c['fy'],'customer_id'=>$c['customer'],'number'=>'O1','ordered_at'=>'2026-10-06','requested_delivery_at'=>'2026-10-20','items'=>[['goods_id'=>$c['goods'],'quantity'=>8]]]);
        $response->assertCreated()->assertJsonPath('data.status','production_required');
        $this->assertDatabaseHas('order_items',['order_id'=>$response->json('data.id'),'shortage_quantity'=>3]);
    }

    public function test_approved_purchase_receipt_updates_inventory_and_movement(): void
    {
        $c=$this->context();
        $sr=DB::table('supply_requests')->insertGetId(['company_id'=>$c['company'],'fiscal_year_id'=>$c['fy'],'requested_by_user_id'=>$c['user'],'requested_at'=>now(),'needed_at'=>'2026-10-10','status'=>'shortage_pending','created_at'=>now(),'updated_at'=>now()]);
        DB::table('supply_request_items')->insert(['supply_request_id'=>$sr,'goods_id'=>$c['goods'],'requested_quantity'=>8,'available_quantity'=>5,'shortage_quantity'=>3,'supplied_quantity'=>0,'created_at'=>now(),'updated_at'=>now()]);
        $purchase=DB::table('purchases')->insertGetId(['company_id'=>$c['company'],'fiscal_year_id'=>$c['fy'],'supplier_id'=>$c['supplier'],'supply_request_id'=>$sr,'purchased_at'=>'2026-10-06','subtotal'=>300,'direct_cost_total'=>50,'total_amount'=>350,'status'=>'draft','created_at'=>now(),'updated_at'=>now()]);
        DB::table('purchase_items')->insert(['purchase_id'=>$purchase,'goods_id'=>$c['goods'],'quantity'=>3,'unit_price'=>100,'line_total'=>300,'created_at'=>now(),'updated_at'=>now()]);
        $receipt=$this->postJson('/api/purchase-receipts',['purchase_id'=>$purchase,'warehouse_location_id'=>$c['warehouse'],'received_at'=>'2026-10-06 10:00:00','items'=>[['goods_id'=>$c['goods'],'quantity'=>3]]])->assertCreated()->json('data.id');
        $this->postJson('/api/purchase-receipts/'.$receipt.'/approve')->assertOk();
        $this->assertDatabaseHas('inventory',['location_id'=>$c['warehouse'],'goods_id'=>$c['goods'],'quantity'=>8]);
        $this->assertDatabaseHas('inventory_movements',['goods_id'=>$c['goods'],'movement_type'=>'purchase_receipt','quantity'=>3]);
        $this->assertDatabaseHas('purchases',['id'=>$purchase,'status'=>'received']);
        $this->assertDatabaseHas('supply_requests',['id'=>$sr,'status'=>'fulfilled']);
    }
}