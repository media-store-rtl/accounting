<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SalesOperationalWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function context(float $stock=10): array
    {
        $account=DB::table('accounts')->insertGetId(['name'=>'Test Account','code'=>'ACC'.uniqid(),'created_at'=>now(),'updated_at'=>now()]);
        $company=DB::table('companies')->insertGetId(['account_id'=>$account,'name'=>'Test Company','code'=>'COM'.uniqid(),'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $user=DB::table('users')->insertGetId(['account_id'=>$account,'name'=>'Tester','username'=>'u'.uniqid(),'email'=>uniqid().'@test.local','password'=>bcrypt('secret'),'created_at'=>now(),'updated_at'=>now()]);
        $role=DB::table('roles')->insertGetId(['company_id'=>$company,'name'=>'Sales Operations','slug'=>'sales-ops','is_system'=>false,'created_at'=>now(),'updated_at'=>now()]);
        $slugs=['customer.view','customer.create','order.view','order.create','order.refresh','production.supervise','delivery_request.view','delivery_request.create','delivery_request.issue','delivery_request.handover','warehouse.delivery.manage'];
        foreach($slugs as $slug){
            $permission=DB::table('permissions')->insertGetId(['name'=>$slug,'slug'=>$slug,'module'=>'sales','created_at'=>now(),'updated_at'=>now()]);
            DB::table('role_permissions')->insert(['role_id'=>$role,'permission_id'=>$permission]);
        }
        DB::table('company_user')->insert(['company_id'=>$company,'user_id'=>$user,'role_id'=>$role,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $fy=DB::table('fiscal_years')->insertGetId(['company_id'=>$company,'name'=>'1405','code'=>'FY1405','starts_at'=>'2026-03-21','ends_at'=>'2027-03-20','is_closed'=>false,'created_at'=>now(),'updated_at'=>now()]);
        $category=DB::table('goods_categories')->insertGetId(['company_id'=>$company,'name'=>'Finished','code'=>'FG','is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $goods=DB::table('goods')->insertGetId(['company_id'=>$company,'category_id'=>$category,'code'=>'FG1','name'=>'Finished Good','purchasable'=>false,'producible'=>true,'sellable'=>true,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $customer=DB::table('customers')->insertGetId(['company_id'=>$company,'name'=>'Customer','code'=>'CU1','is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $warehouse=DB::table('locations')->insertGetId(['company_id'=>$company,'code'=>'W1','name'=>'Warehouse','type'=>'warehouse','is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        DB::table('inventory')->insert(['company_id'=>$company,'location_id'=>$warehouse,'goods_id'=>$goods,'quantity'=>$stock,'created_at'=>now(),'updated_at'=>now()]);
        $this->actingAs(User::findOrFail($user))->withSession(['company_id'=>$company]);
        return compact('company','user','fy','goods','customer','warehouse');
    }

    public function test_in_stock_order_can_create_delivery_issue_and_customer_handover(): void
    {
        Notification::fake(); $c=$this->context(10);
        $response=$this->postJson('/api/orders',['fiscal_year_id'=>$c['fy'],'customer_id'=>$c['customer'],'number'=>'SO-1','ordered_at'=>'2026-10-07','requested_delivery_at'=>'2026-10-20','items'=>[['goods_id'=>$c['goods'],'quantity'=>4]]]);
        $response->assertCreated()->assertJsonPath('data.status','ready_for_delivery');
        $order=$response->json('data.id');
        $this->postJson('/api/orders/'.$order.'/delivery-request',['scheduled_at'=>'2026-10-08 10:00:00','recipient_name'=>'Receiver','vehicle'=>'Truck 1'])->assertCreated();
        $delivery=DB::table('delivery_requests')->where('order_id',$order)->first();
        $this->postJson('/api/delivery-requests/'.$delivery->id.'/issue')->assertOk();
        $this->assertDatabaseHas('inventory',['location_id'=>$c['warehouse'],'goods_id'=>$c['goods'],'quantity'=>6]);
        $this->assertDatabaseHas('inventory_movements',['goods_id'=>$c['goods'],'movement_type'=>'delivery_issue','quantity'=>-4]);
        $this->postJson('/api/delivery-requests/'.$delivery->id.'/handover',['received_by'=>'Customer Receiver'])->assertOk();
        $this->assertDatabaseHas('delivery_requests',['id'=>$delivery->id,'status'=>'completed']);
        $this->assertDatabaseHas('orders',['id'=>$order,'status'=>'completed']);
    }

    public function test_shortage_enters_production_path_and_due_date_can_return_to_sales(): void
    {
        Notification::fake(); $c=$this->context(2);
        $response=$this->postJson('/api/orders',['fiscal_year_id'=>$c['fy'],'customer_id'=>$c['customer'],'number'=>'SO-2','ordered_at'=>'2026-10-07','requested_delivery_at'=>'2026-10-20','items'=>[['goods_id'=>$c['goods'],'quantity'=>5]]]);
        $response->assertCreated()->assertJsonPath('data.status','production_required');
        Notification::assertSentTo(User::find($c['user']), \App\Notifications\WorkflowNotification::class);
        $order=$response->json('data.id');
        $this->postJson('/api/orders/'.$order.'/production-due',['production_due_at'=>'2026-10-18'])->assertOk()->assertJsonPath('data.status','awaiting_production');
        DB::table('inventory')->where('location_id',$c['warehouse'])->where('goods_id',$c['goods'])->update(['quantity'=>5]);
        $this->postJson('/api/orders/'.$order.'/refresh')->assertOk()->assertJsonPath('data.status','ready_for_delivery');
        $this->assertDatabaseHas('order_items',['order_id'=>$order,'shortage_quantity'=>0,'fulfillment_status'=>'ready_for_delivery']);
    }

    public function test_sales_pages_are_user_facing_and_server_authorized(): void
    {
        $c=$this->context(5);
        $this->get('/sales/customers')->assertOk();
        $this->get('/sales/orders')->assertOk();
        $this->get('/sales/orders/create')->assertOk();
        $this->get('/sales/deliveries')->assertOk();
        DB::table('company_user')->where('company_id',$c['company'])->where('user_id',$c['user'])->update(['is_active'=>false]);
        $this->get('/sales/orders')->assertForbidden();
    }
}
