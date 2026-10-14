<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ProductionOutputService;
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
            $permission=DB::table('permissions')->where('slug',$slug)->value('id');
            if (!$permission) {
                $permission=DB::table('permissions')->insertGetId(['name'=>$slug,'slug'=>$slug,'module'=>'sales','created_at'=>now(),'updated_at'=>now()]);
            }
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

    public function test_production_output_confirmation_feeds_finished_inventory_then_sales_and_delivery(): void
    {
        Notification::fake(); $c=$this->context(0);
        $response=$this->postJson('/api/orders',['fiscal_year_id'=>$c['fy'],'customer_id'=>$c['customer'],'number'=>'SO-FG-1','ordered_at'=>'2026-10-07','requested_delivery_at'=>'2026-10-20','items'=>[['goods_id'=>$c['goods'],'quantity'=>4]]]);
        $response->assertCreated()->assertJsonPath('data.status','production_required');
        $order=$response->json('data.id');

        $route=DB::table('production_routes')->insertGetId([
            'company_id'=>$c['company'],'goods_id'=>$c['goods'],'code'=>'ROUTE-FG-1','name'=>'Finished Goods Route',
            'status'=>'active','created_at'=>now(),'updated_at'=>now()
        ]);
        $production=DB::table('productions')->insertGetId([
            'company_id'=>$c['company'],'fiscal_year_id'=>$c['fy'],'goods_id'=>$c['goods'],'production_route_id'=>$route,
            'number'=>'PR-FG-1','planned_quantity'=>4,'produced_quantity'=>0,'rejected_quantity'=>0,'status'=>'completed',
            'completed_at'=>now(),'created_at'=>now(),'updated_at'=>now()
        ]);
        DB::table('production_order')->insert(['production_id'=>$production,'order_id'=>$order,'created_at'=>now(),'updated_at'=>now()]);

        $service=app(ProductionOutputService::class);
        $output=$service->create($c['company'],$production,$order,$c['warehouse'],4,$c['user']);
        $this->assertSame('pending',$output->status);
        $this->assertDatabaseHas('inventory',['location_id'=>$c['warehouse'],'goods_id'=>$c['goods'],'quantity'=>0]);

        $service->confirm($c['company'],$output->id,$c['user']);

        $this->assertDatabaseHas('production_outputs',['id'=>$output->id,'status'=>'received','order_id'=>$order]);
        $this->assertDatabaseHas('finished_goods_receipts',['production_output_id'=>$output->id,'status'=>'approved']);
        $this->assertDatabaseHas('inventory',['location_id'=>$c['warehouse'],'goods_id'=>$c['goods'],'quantity'=>4]);
        $this->assertDatabaseHas('inventory_movements',['goods_id'=>$c['goods'],'movement_type'=>'finished_goods_receipt','reference_type'=>'production_outputs','reference_id'=>$output->id,'quantity'=>4]);

        $this->postJson('/api/orders/'.$order.'/refresh')->assertOk()->assertJsonPath('data.status','ready_for_delivery');
        $this->assertDatabaseHas('order_items',['order_id'=>$order,'shortage_quantity'=>0,'fulfillment_status'=>'ready_for_delivery']);

        $this->postJson('/api/orders/'.$order.'/delivery-request',['scheduled_at'=>'2026-10-08 10:00:00','recipient_name'=>'Receiver','vehicle'=>'Truck FG'])->assertCreated();
        $delivery=DB::table('delivery_requests')->where('order_id',$order)->first();
        $this->postJson('/api/delivery-requests/'.$delivery->id.'/issue')->assertOk();
        $this->assertDatabaseHas('inventory',['location_id'=>$c['warehouse'],'goods_id'=>$c['goods'],'quantity'=>0]);
        $this->assertDatabaseHas('inventory_movements',['goods_id'=>$c['goods'],'movement_type'=>'delivery_issue','reference_type'=>'delivery_request','reference_id'=>$delivery->id,'quantity'=>-4]);

        $this->postJson('/api/delivery-requests/'.$delivery->id.'/handover',['received_by'=>'Customer Receiver'])->assertOk();
        $this->assertDatabaseHas('delivery_requests',['id'=>$delivery->id,'status'=>'completed']);
        $this->assertDatabaseHas('orders',['id'=>$order,'status'=>'completed']);
    }

    public function test_shortage_enters_production_path_and_due_date_can_return_to_sales(): void
    {
        Notification::fake(); $c=$this->context(2);
        // The notification service excludes the actor, so provide a separate active production supervisor.
        $supervisorId=DB::table('users')->insertGetId(['account_id'=>DB::table('companies')->where('id',$c['company'])->value('account_id'),'name'=>'Supervisor','username'=>'supervisor'.uniqid(),'email'=>uniqid().'@test.local','password'=>bcrypt('secret'),'created_at'=>now(),'updated_at'=>now()]);
        $roleId=DB::table('roles')->where('company_id',$c['company'])->where('slug','sales-ops')->value('id');
        DB::table('company_user')->insert(['company_id'=>$c['company'],'user_id'=>$supervisorId,'role_id'=>$roleId,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $response=$this->postJson('/api/orders',['fiscal_year_id'=>$c['fy'],'customer_id'=>$c['customer'],'number'=>'SO-2','ordered_at'=>'2026-10-07','requested_delivery_at'=>'2026-10-20','items'=>[['goods_id'=>$c['goods'],'quantity'=>5]]]);
        $response->assertCreated()->assertJsonPath('data.status','production_required');
        Notification::assertSentTo(User::find($supervisorId), \App\Notifications\WorkflowNotification::class);
        $order=$response->json('data.id');
        $this->postJson('/api/orders/'.$order.'/production-due',['production_due_at'=>'2026-10-18'])->assertOk()->assertJsonPath('data.status','awaiting_production');
        DB::table('inventory')->where('location_id',$c['warehouse'])->where('goods_id',$c['goods'])->update(['quantity'=>5]);
        $this->postJson('/api/orders/'.$order.'/refresh')->assertOk()->assertJsonPath('data.status','ready_for_delivery');
        $this->assertDatabaseHas('order_items',['order_id'=>$order,'shortage_quantity'=>0,'fulfillment_status'=>'ready_for_delivery']);
    }

    public function test_finished_goods_receipt_clears_existing_order_shortage_and_notifies_sales_only_when_fully_covered(): void
    {
        Notification::fake();
        $c = $this->context(0);

        $salesUserId = DB::table('users')->insertGetId([
            'account_id' => DB::table('companies')->where('id', $c['company'])->value('account_id'),
            'name' => 'Sales User',
            'username' => 'sales'.uniqid(),
            'email' => uniqid().'@test.local',
            'password' => bcrypt('secret'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $roleId = DB::table('company_user')->where('company_id', $c['company'])->where('user_id', $c['user'])->value('role_id');
        DB::table('company_user')->insert([
            'company_id' => $c['company'], 'user_id' => $salesUserId, 'role_id' => $roleId,
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $salesUser = User::findOrFail($salesUserId);

        $orderId = $this->postJson('/api/orders', [
            'fiscal_year_id' => $c['fy'],
            'customer_id' => $c['customer'],
            'requested_delivery_at' => '2026-10-20',
            'items' => [['goods_id' => $c['goods'], 'quantity' => 4]],
        ])->assertCreated()->assertJsonPath('data.status', 'production_required')->json('data.id');

        $routeId = DB::table('production_routes')->insertGetId([
            'company_id' => $c['company'], 'goods_id' => $c['goods'], 'code' => 'ROUTE-SHORTAGE',
            'name' => 'Finished Goods Route', 'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $service = app(ProductionOutputService::class);

        foreach ([2, 2] as $index => $quantity) {
            $productionId = DB::table('productions')->insertGetId([
                'company_id' => $c['company'], 'fiscal_year_id' => $c['fy'], 'goods_id' => $c['goods'],
                'production_route_id' => $routeId, 'number' => 'PR-SHORTAGE-'.$index,
                'planned_quantity' => $quantity, 'produced_quantity' => 0, 'rejected_quantity' => 0,
                'status' => 'completed', 'completed_at' => now(), 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('production_order')->insert([
                'production_id' => $productionId, 'order_id' => $orderId, 'created_at' => now(), 'updated_at' => now(),
            ]);
            $output = $service->create($c['company'], $productionId, $orderId, $c['warehouse'], $quantity, $c['user']);
            $service->confirm($c['company'], $output->id, $c['user']);

            if ($index === 0) {
                $this->assertDatabaseHas('orders', ['id' => $orderId, 'status' => 'production_required']);
                $this->assertDatabaseHas('order_items', ['order_id' => $orderId, 'shortage_quantity' => 2]);
                Notification::assertNotSentTo($salesUser, \App\Notifications\WorkflowNotification::class, function ($notification) use ($salesUser) {
                    return $notification->toArray($salesUser)['event'] === 'order.ready_for_delivery';
                });
            }
        }

        $this->assertDatabaseHas('orders', ['id' => $orderId, 'status' => 'ready_for_delivery']);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $orderId, 'available_quantity' => 4, 'shortage_quantity' => 0,
            'fulfillment_status' => 'ready_for_delivery',
        ]);
        Notification::assertSentTo($salesUser, \App\Notifications\WorkflowNotification::class, function ($notification) use ($salesUser, $orderId) {
            $data = $notification->toArray($salesUser);
            return $data['event'] === 'order.ready_for_delivery' && (int) $data['order_id'] === (int) $orderId;
        });

        $this->postJson('/api/orders/'.$orderId.'/refresh')->assertOk()->assertJsonPath('data.status', 'ready_for_delivery');
        $readyNotifications = Notification::sent($salesUser, \App\Notifications\WorkflowNotification::class)
            ->filter(fn ($notification) => $notification->toArray($salesUser)['event'] === 'order.ready_for_delivery');
        $this->assertCount(1, $readyNotifications, 'Refreshing an already-ready order must not send a duplicate ready notification.');
    }

    public function test_shift_report_warehouse_receipt_clears_shortage_and_notifies_sales(): void
    {
        Notification::fake();
        $c = $this->context(0);

        $salesUserId = DB::table('users')->insertGetId([
            'account_id' => DB::table('companies')->where('id', $c['company'])->value('account_id'),
            'name' => 'Sales User',
            'username' => 'sales-shift'.uniqid(),
            'email' => uniqid().'@test.local',
            'password' => bcrypt('secret'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $roleId = DB::table('company_user')->where('company_id', $c['company'])->where('user_id', $c['user'])->value('role_id');
        DB::table('company_user')->insert([
            'company_id' => $c['company'], 'user_id' => $salesUserId, 'role_id' => $roleId,
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $salesUser = User::findOrFail($salesUserId);

        $orderId = $this->postJson('/api/orders', [
            'fiscal_year_id' => $c['fy'],
            'customer_id' => $c['customer'],
            'requested_delivery_at' => '2026-10-20',
            'items' => [['goods_id' => $c['goods'], 'quantity' => 4]],
        ])->assertCreated()->assertJsonPath('data.status', 'production_required')->json('data.id');

        $sectionLocation = DB::table('locations')->insertGetId([
            'company_id' => $c['company'], 'code' => 'SHIFT-SEC-'.uniqid(), 'name' => 'Production Section',
            'type' => 'production', 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $sectionId = DB::table('production_sections')->insertGetId([
            'location_id' => $sectionLocation, 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $reportId = DB::table('production_shift_reports')->insertGetId([
            'company_id' => $c['company'],
            'production_section_id' => $sectionId,
            'goods_id' => $c['goods'],
            'reported_by_user_id' => $c['user'],
            'reviewed_by_user_id' => $c['user'],
            'shift_name' => 'صبح',
            'worked_hours' => 8,
            'overtime_hours' => 0,
            'holiday_hours' => 0,
            'work_date' => '2026-10-13',
            'produced_quantity' => 4,
            'scrap_quantity' => 0,
            'status' => 'approved',
            'reviewed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $receiverId = DB::table('users')->insertGetId([
            'account_id' => DB::table('companies')->where('id', $c['company'])->value('account_id'),
            'name' => 'Warehouse Receiver',
            'username' => 'receiver'.uniqid(),
            'email' => uniqid().'@test.local',
            'password' => bcrypt('secret'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('company_user')->insert([
            'company_id' => $c['company'], 'user_id' => $receiverId, 'role_id' => $roleId,
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->actingAs(User::findOrFail($receiverId))->withSession(['company_id' => $c['company']])
            ->post(route('production.outputs.shift-reports.receive', $reportId), [
                'warehouse_location_id' => $c['warehouse'],
            ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('orders', ['id' => $orderId, 'status' => 'ready_for_delivery']);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $orderId, 'available_quantity' => 4, 'shortage_quantity' => 0,
            'fulfillment_status' => 'ready_for_delivery',
        ]);
        Notification::assertSentTo($salesUser, \\App\\Notifications\\WorkflowNotification::class, function ($notification) use ($salesUser, $orderId) {
            $data = $notification->toArray($salesUser);
            return $data['event'] === 'order.ready_for_delivery' && (int) $data['order_id'] === (int) $orderId;
        });
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
