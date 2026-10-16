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
        DB::table('inventory_movements')->insert(['company_id'=>$company,'location_id'=>$location,'goods_id'=>$goods,'quantity'=>10,'movement_type'=>'opening_balance','reference_type'=>'inventory','reference_id'=>$inventory,'occurred_at'=>now(),'metadata'=>json_encode(['source'=>'test opening balance']),'created_at'=>now(),'updated_at'=>now()]);

        app(InventoryService::class)->adjust($company,$location,$goods,4,'count adjustment',$user);
        app(InventoryService::class)->setReorderPoint($company,$inventory,6,$user);

        $this->assertDatabaseHas('inventory',['id'=>$inventory,'quantity'=>10,'reorder_point'=>6]);
        $this->assertSame(4.0,(float)DB::table('inventory_movements')->where('company_id',$company)->where('location_id',$location)->where('goods_id',$goods)->sum('quantity'));
        $this->assertDatabaseHas('inventory_movements',['company_id'=>$company,'goods_id'=>$goods,'location_id'=>$location,'movement_type'=>'inventory_adjustment','quantity'=>-6]);
        $this->assertDatabaseHas('audit_trails',['company_id'=>$company,'user_id'=>$user,'module'=>'warehouse','action'=>'inventory.adjustment','auditable_id'=>$inventory]);
        $this->assertDatabaseHas('audit_trails',['company_id'=>$company,'user_id'=>$user,'module'=>'warehouse','action'=>'inventory.reorder_point.update','auditable_id'=>$inventory]);
    }

    public function test_raw_material_below_reorder_point_creates_one_automatic_procurement_request(): void
    {
        $account=DB::table('accounts')->insertGetId(['name'=>'Supply Account','code'=>'SA'.uniqid(),'created_at'=>now(),'updated_at'=>now()]);
        $company=DB::table('companies')->insertGetId(['account_id'=>$account,'name'=>'Supply Company','code'=>'SC'.uniqid(),'is_active'=>true,'settings'=>json_encode([]),'inventory_valuation_method'=>'fifo','created_at'=>now(),'updated_at'=>now()]);
        $user=DB::table('users')->insertGetId(['account_id'=>$account,'name'=>'Supply User','username'=>'su'.uniqid(),'email'=>uniqid().'@test.local','password'=>bcrypt('secret'),'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $role=DB::table('roles')->insertGetId(['company_id'=>$company,'name'=>'Supply Role','slug'=>'sr'.uniqid(),'is_system'=>false,'created_at'=>now(),'updated_at'=>now()]);
        DB::table('company_user')->insert(['company_id'=>$company,'user_id'=>$user,'role_id'=>$role,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $fiscalYear=DB::table('fiscal_years')->insertGetId([
            'company_id'=>$company,'name'=>'Current','code'=>'FY'.uniqid(),
            'starts_at'=>today()->subYear()->toDateString(),'ends_at'=>today()->addYear()->toDateString(),
            'is_closed'=>false,'created_at'=>now(),'updated_at'=>now(),
        ]);
        $category=DB::table('goods_categories')->insertGetId(['company_id'=>$company,'name'=>'Raw Materials','code'=>'RM'.uniqid(),'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $goods=DB::table('goods')->insertGetId(['company_id'=>$company,'category_id'=>$category,'code'=>'RM'.uniqid(),'name'=>'Raw Material','purchasable'=>true,'producible'=>false,'sellable'=>false,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $location=DB::table('locations')->insertGetId(['company_id'=>$company,'code'=>'W'.uniqid(),'name'=>'Warehouse','type'=>'warehouse','is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $inventory=DB::table('inventory')->insertGetId(['company_id'=>$company,'location_id'=>$location,'goods_id'=>$goods,'quantity'=>10,'reorder_point'=>5,'created_at'=>now(),'updated_at'=>now()]);

        app(InventoryService::class)->adjust($company,$location,$goods,4,'stock fell below reorder point',$user);

        $request=DB::table('supply_requests')->where('company_id',$company)->first();
        $this->assertNotNull($request);
        $this->assertSame('shortage_pending',$request->status);
        $this->assertSame((string)$fiscalYear,(string)$request->fiscal_year_id);
        $this->assertDatabaseHas('supply_request_items',[
            'supply_request_id'=>$request->id,'goods_id'=>$goods,'requested_quantity'=>1,
            'available_quantity'=>0,'shortage_quantity'=>1,
        ]);

        app(InventoryService::class)->adjust($company,$location,$goods,3,'stock remains below reorder point',$user);
        $this->assertSame(1,DB::table('supply_requests')->where('company_id',$company)->count());
        $this->assertDatabaseHas('inventory',['id'=>$inventory,'reorder_alert_active'=>true]);
    }


    public function test_raw_material_shortage_notifications_only_go_to_configured_warehouse_and_procurement_users(): void
    {
        \\Illuminate\\Support\\Facades\\Notification::fake();

        $account = DB::table('accounts')->insertGetId([
            'name' => 'Notification Account', 'code' => 'NA'.uniqid(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $company = DB::table('companies')->insertGetId([
            'account_id' => $account, 'name' => 'Notification Company', 'code' => 'NC'.uniqid(),
            'is_active' => true, 'settings' => json_encode([]), 'inventory_valuation_method' => 'fifo',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $role = DB::table('roles')->insertGetId([
            'company_id' => $company, 'name' => 'Notification Test Role', 'slug' => 'nr'.uniqid(),
            'is_system' => false, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $users = [];
        foreach (['Owner', 'Production', 'Warehouse', 'Procurement'] as $label) {
            $id = DB::table('users')->insertGetId([
                'account_id' => $account, 'name' => $label, 'username' => strtolower($label).uniqid(),
                'email' => uniqid().'@test.local', 'password' => bcrypt('secret'),
                'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('company_user')->insert([
                'company_id' => $company, 'user_id' => $id, 'role_id' => $role,
                'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
            $users[$label] = \\App\\Models\\User::findOrFail($id);
        }

        $fiscalYear = DB::table('fiscal_years')->insertGetId([
            'company_id' => $company, 'name' => 'Current', 'code' => 'FY'.uniqid(),
            'starts_at' => today()->subYear()->toDateString(), 'ends_at' => today()->addYear()->toDateString(),
            'is_closed' => false, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $category = DB::table('goods_categories')->insertGetId([
            'company_id' => $company, 'name' => 'Raw Materials', 'code' => 'RM'.uniqid(),
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $goods = DB::table('goods')->insertGetId([
            'company_id' => $company, 'category_id' => $category, 'code' => 'RM'.uniqid(),
            'name' => 'Test Raw Material', 'purchasable' => true, 'producible' => false, 'sellable' => false,
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $location = DB::table('locations')->insertGetId([
            'company_id' => $company, 'code' => 'W'.uniqid(), 'name' => 'Warehouse',
            'type' => 'warehouse', 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $inventory = DB::table('inventory')->insertGetId([
            'company_id' => $company, 'location_id' => $location, 'goods_id' => $goods,
            'quantity' => 10, 'reorder_point' => 5, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('inventory_movements')->insert([
            'company_id' => $company, 'location_id' => $location, 'goods_id' => $goods, 'quantity' => 10,
            'movement_type' => 'opening_balance', 'reference_type' => 'inventory', 'reference_id' => $inventory,
            'occurred_at' => now(), 'metadata' => json_encode(['source' => 'notification routing test']),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        foreach (['inventory.raw_material_low', 'supply.shortage'] as $event) {
            $rule = DB::table('notification_recipient_rules')->insertGetId([
                'company_id' => $company, 'event_key' => $event, 'name' => $event,
                'is_active' => true, 'is_configured' => true, 'exclude_actor' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            foreach (['Warehouse', 'Procurement'] as $label) {
                DB::table('notification_recipient_rule_users')->insert([
                    'rule_id' => $rule, 'user_id' => $users[$label]->id,
                ]);
            }
        }

        app(InventoryService::class)->adjust($company, $location, $goods, 4, 'trigger reorder alert', $users['Owner']->id);

        foreach (['inventory.raw_material_low', 'supply.shortage'] as $event) {
            foreach (['Warehouse', 'Procurement'] as $label) {
                \\Illuminate\\Support\\Facades\\Notification::assertSentTo(
                    $users[$label],
                    \\App\\Notifications\\WorkflowNotification::class,
                    fn ($notification) => $notification->toArray($users[$label])['event'] === $event
                );
            }
            foreach (['Owner', 'Production'] as $label) {
                \\Illuminate\\Support\\Facades\\Notification::assertNotSentTo(
                    $users[$label],
                    \\App\\Notifications\\WorkflowNotification::class,
                    fn ($notification) => $notification->toArray($users[$label])['event'] === $event
                );
            }
        }

        $this->assertSame(1, DB::table('supply_requests')->where('company_id', $company)->count());
    }

}
