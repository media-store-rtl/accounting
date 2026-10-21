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
        foreach(['order.create','order.view','production.supervise','supply_request.create','supply_request.view','purchase.create','purchase.view','purchase.receipt.create','purchase.receipt.approve'] as $slug){
            $permission=DB::table('permissions')->where('slug',$slug)->first();
            $permissionId=$permission?->id ?? DB::table('permissions')->insertGetId(['name'=>$slug,'slug'=>$slug,'module'=>'orders-supply','created_at'=>now(),'updated_at'=>now()]);
            DB::table('role_permissions')->insertOrIgnore(['role_id'=>$role,'permission_id'=>$permissionId]);
        }
        DB::table('company_user')->insert(['company_id'=>$company,'user_id'=>$user,'role_id'=>$role,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $fy=DB::table('fiscal_years')->insertGetId(['company_id'=>$company,'name'=>'1405','code'=>'FY1405','starts_at'=>'2026-03-21','ends_at'=>'2027-03-20','is_closed'=>false,'created_at'=>now(),'updated_at'=>now()]);
        $category=DB::table('goods_categories')->insertGetId(['company_id'=>$company,'name'=>'Raw','code'=>'RAW','is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $goods=DB::table('goods')->insertGetId(['company_id'=>$company,'category_id'=>$category,'code'=>'G1','name'=>'Material','purchasable'=>true,'producible'=>false,'sellable'=>true,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $customer=DB::table('customers')->insertGetId(['company_id'=>$company,'name'=>'Customer','code'=>'CU1','is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $warehouse=DB::table('locations')->insertGetId(['company_id'=>$company,'code'=>'W1','name'=>'Warehouse','type'=>'مواد اولیه','is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $inventoryId=DB::table('inventory')->insertGetId(['company_id'=>$company,'location_id'=>$warehouse,'goods_id'=>$goods,'quantity'=>5,'created_at'=>now(),'updated_at'=>now()]);
        DB::table('inventory_movements')->insert(['company_id'=>$company,'location_id'=>$warehouse,'goods_id'=>$goods,'quantity'=>5,'movement_type'=>'opening_balance','reference_type'=>'inventory','reference_id'=>$inventoryId,'occurred_at'=>now(),'metadata'=>json_encode(['source'=>'test opening balance']),'created_at'=>now(),'updated_at'=>now()]);
        $supplier=DB::table('suppliers')->insertGetId(['company_id'=>$company,'name'=>'Supplier','code'=>'SUP1','is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $this->actingAs(\App\Models\User::findOrFail($user))->withSession(['company_id'=>$company,'fiscal_year_id'=>$fy]);
        return compact('company','user','fy','goods','customer','warehouse','supplier');
    }

    public function test_supply_request_stage_options_distinguish_same_named_stages_by_production(): void
    {
        $c = $this->context();
        $route = DB::table('production_routes')->insertGetId([
            'company_id' => $c['company'], 'goods_id' => $c['goods'], 'code' => 'R' . uniqid(),
            'name' => 'Route', 'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $stage = DB::table('production_stages')->insertGetId([
            'production_route_id' => $route, 'code' => 'CUT', 'name' => 'برش',
            'sequence' => 1, 'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
        ]);
        foreach (['PR-100', 'PR-101'] as $number) {
            $production = DB::table('productions')->insertGetId([
                'company_id' => $c['company'], 'fiscal_year_id' => $c['fy'], 'goods_id' => $c['goods'],
                'production_route_id' => $route, 'number' => $number, 'planned_quantity' => 10,
                'status' => 'in_progress', 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('production_stage_runs')->insert([
                'production_id' => $production, 'production_stage_id' => $stage,
                'sequence' => 1, 'status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $response = $this->get(route('workflows.supply.create'))
            ->assertOk()
            ->assertSee('فقط مراحل تعریف‌شده در مسیر تولید نمایش داده می‌شوند؛ هر تعریف مرحله یک‌بار در فهرست می‌آید.')
            ->assertSee('name="production_stage_id"', false)
            ->assertSee('برش</option>', false)
            ->assertDontSee('PR-100')
            ->assertDontSee('PR-101')
            ->assertDontSee('production_stage_run_id', false);

        preg_match('/<select id="production_stage_id".*?<\/select>/s', $response->getContent(), $stageSelect);
        $this->assertSame(1, substr_count($stageSelect[0] ?? '', 'value="'.$stage.'"'));
    }

    public function test_supply_request_saves_the_selected_production_stage_definition(): void
    {
        $c = $this->context();
        $route = DB::table('production_routes')->insertGetId([
            'company_id' => $c['company'],
            'goods_id' => $c['goods'],
            'code' => 'STAGE-DEF-' . uniqid(),
            'name' => 'مسیر تولید',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $stage = DB::table('production_stages')->insertGetId([
            'production_route_id' => $route,
            'code' => 'CUT-' . uniqid(),
            'name' => 'برش',
            'sequence' => 1,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->from('/supply')->post('/supply', [
            'fiscal_year_id' => $c['fy'],
            'production_stage_id' => $stage,
            'production_section_id' => '',
            'production_id' => '',
            'order_id' => '',
            'needed_at' => '2026-10-20',
            'items' => [['goods_id' => $c['goods'], 'quantity' => 2]],
        ])->assertRedirect('/supply')->assertSessionHasNoErrors();

        $this->assertDatabaseHas('supply_requests', [
            'company_id' => $c['company'],
            'production_stage_id' => $stage,
            'production_stage_run_id' => null,
            'production_id' => null,
        ]);
    }

    public function test_order_number_is_generated_by_server_and_cannot_be_overridden(): void
    {
        $c = $this->context();

        $first = $this->postJson('/api/orders', [
            'fiscal_year_id' => $c['fy'],
            'customer_id' => $c['customer'],
            'number' => '9999',
            'requested_delivery_at' => '2026-10-20',
            'items' => [['goods_id' => $c['goods'], 'quantity' => 1]],
        ])->assertCreated();

        $this->assertSame('1', (string) $first->json('data.number'));

        $second = $this->postJson('/api/orders', [
            'fiscal_year_id' => $c['fy'],
            'customer_id' => $c['customer'],
            'number' => '1',
            'requested_delivery_at' => '2026-10-20',
            'items' => [['goods_id' => $c['goods'], 'quantity' => 1]],
        ])->assertCreated();

        $this->assertSame('2', (string) $second->json('data.number'));
    }

    public function test_order_detects_shortage_before_production_notification(): void
    {
        $c=$this->context();
        $response=$this->postJson('/api/orders',['fiscal_year_id'=>$c['fy'],'customer_id'=>$c['customer'],'number'=>'O1','ordered_at'=>'2026-10-06','requested_delivery_at'=>'2026-10-20','items'=>[['goods_id'=>$c['goods'],'quantity'=>8]]]);
        $response->assertCreated()->assertJsonPath('data.status','production_required');
        $this->assertDatabaseHas('order_items',['order_id'=>$response->json('data.id'),'shortage_quantity'=>3]);
    }

    public function test_production_due_date_can_only_be_set_once(): void
    {
        $c = $this->context();
        $order = $this->postJson('/api/orders', [
            'fiscal_year_id' => $c['fy'],
            'customer_id' => $c['customer'],
            'number' => 'DUE-1',
            'ordered_at' => '2026-10-06',
            'requested_delivery_at' => '2026-10-30',
            'items' => [['goods_id' => $c['goods'], 'quantity' => 8]],
        ])->assertCreated()->json('data.id');

        $this->postJson('/production/orders/'.$order.'/due-date', [
            'production_due_at' => '2026-10-22',
        ])->assertOk();

        $this->assertDatabaseHas('orders', [
            'id' => $order,
            'production_due_at' => '2026-10-22',
            'status' => 'awaiting_production',
        ]);

        $this->postJson('/production/orders/'.$order.'/due-date', [
            'production_due_at' => '2026-10-25',
        ])->assertUnprocessable()
            ->assertJsonPath('message', 'موعد تولید این سفارش قبلاً ثبت شده و قابل تغییر نیست.');

        $this->assertDatabaseHas('orders', [
            'id' => $order,
            'production_due_at' => '2026-10-22',
            'status' => 'awaiting_production',
        ]);
    }

    public function test_order_does_not_double_allocate_stock_to_multiple_open_orders(): void
    {
        $c = $this->context();
        $payload = [
            'fiscal_year_id' => $c['fy'], 'customer_id' => $c['customer'],
            'ordered_at' => '2026-10-06', 'requested_delivery_at' => '2026-10-20',
            'items' => [['goods_id' => $c['goods'], 'quantity' => 4]],
        ];

        $first = $this->postJson('/api/orders', $payload + ['number' => 'O1'])
            ->assertCreated()->assertJsonPath('data.status', 'ready_for_delivery');

        $secondPayload = $payload;
        $secondPayload['number'] = 'O2';
        $secondPayload['items'] = [['goods_id' => $c['goods'], 'quantity' => 3]];
        $second = $this->postJson('/api/orders', $secondPayload)
            ->assertCreated()->assertJsonPath('data.status', 'production_required');

        $this->assertDatabaseHas('order_items', [
            'order_id' => $first->json('data.id'), 'available_quantity' => 4, 'shortage_quantity' => 0,
        ]);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $second->json('data.id'), 'available_quantity' => 1, 'shortage_quantity' => 2,
        ]);
    }

    public function test_order_rejects_duplicate_goods_rows_with_validation_error(): void
    {
        $c = $this->context();
        $response = $this->postJson('/api/orders', [
            'fiscal_year_id' => $c['fy'], 'customer_id' => $c['customer'], 'number' => 'O-DUP',
            'ordered_at' => '2026-10-06', 'requested_delivery_at' => '2026-10-20',
            'items' => [
                ['goods_id' => $c['goods'], 'quantity' => 2],
                ['goods_id' => $c['goods'], 'quantity' => 1],
            ],
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['items.1.goods_id']);
        $this->assertDatabaseMissing('orders', ['number' => 'O-DUP']);
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
        $this->assertDatabaseHas('inventory',['location_id'=>$c['warehouse'],'goods_id'=>$c['goods'],'quantity'=>5]);
        $this->assertSame(8.0,(float)DB::table('inventory_movements')->where('company_id',$c['company'])->where('location_id',$c['warehouse'])->where('goods_id',$c['goods'])->sum('quantity'));
        $this->assertDatabaseHas('inventory_movements',['goods_id'=>$c['goods'],'movement_type'=>'purchase_receipt','quantity'=>3]);
        $this->assertDatabaseHas('purchases',['id'=>$purchase,'status'=>'received']);
        $this->assertDatabaseHas('supply_requests',['id'=>$sr,'status'=>'fulfilled']);
    }


    public function test_receipt_form_starts_with_actual_warehouse_quantities_without_purchase_selection(): void
    {
        $this->context();

        $this->get('/receipts')->assertOk()
            ->assertSee('ثبت رسید اولیه توسط انبار')
            ->assertSee('مقدار دریافتی')
            ->assertSee('name="items[0][goods_id]"', false)
            ->assertDontSee('name="purchase_id"', false)
            ->assertSee('تأیید و نهایی‌سازی از این صفحه انجام نمی‌شود', false);
    }

    public function test_purchase_edit_shows_a_friendly_notice_when_a_receipt_already_exists(): void
    {
        $c=$this->context();
        $purchase=DB::table('purchases')->insertGetId([
            'company_id'=>$c['company'],'fiscal_year_id'=>$c['fy'],'supplier_id'=>$c['supplier'],
            'purchased_at'=>'2026-10-16','subtotal'=>500,'direct_cost_total'=>0,'total_amount'=>500,
            'status'=>'draft','created_at'=>now(),'updated_at'=>now(),
        ]);
        DB::table('purchase_items')->insert([
            'purchase_id'=>$purchase,'goods_id'=>$c['goods'],'quantity'=>5,'unit_price'=>100,'line_total'=>500,
            'created_at'=>now(),'updated_at'=>now(),
        ]);
        $this->postJson('/api/purchase-receipts',[
            'purchase_id'=>$purchase,'warehouse_location_id'=>$c['warehouse'],'received_at'=>'2026-10-16 10:00:00',
            'items'=>[['goods_id'=>$c['goods'],'quantity'=>1]],
        ])->assertCreated();

        $this->get('/purchasing/'.$purchase.'/edit')
            ->assertRedirect('/purchasing')
            ->assertSessionHas('error','این خرید رسید انبار دارد؛ برای حفظ سوابق موجودی و بهای تمام‌شده، ویرایش آن غیرفعال است.');
    }

    public function test_purchase_can_be_registered_without_a_supply_request_and_requires_positive_unit_price(): void
    {
        $c=$this->context();
        $payload=[
            'fiscal_year_id'=>$c['fy'],
            'supplier_id'=>$c['supplier'],
            'supply_request_id'=>'',
            'purchased_at'=>'2026-10-16',
            'items'=>[['goods_id'=>$c['goods'],'quantity'=>2,'unit_price'=>125]],
        ];
        $this->from('/purchasing')->post('/purchasing',$payload)->assertRedirect();
        $purchaseId=(int)DB::table('purchases')->where('company_id',$c['company'])->whereNull('supply_request_id')->orderByDesc('id')->value('id');
        $this->assertDatabaseHas('purchases',['id'=>$purchaseId,'supply_request_id'=>null,'total_amount'=>250]);

        $zeroPrice=$payload;
        $zeroPrice['items'][0]['unit_price']=0;
        $this->from('/purchasing')->post('/purchasing',$zeroPrice)->assertRedirect()->assertSessionHasErrors('items.0.unit_price');
    }



    public function test_purchase_can_be_registered_without_supplier_or_explicit_fiscal_year(): void
    {
        $c=$this->context();
        $this->from('/purchasing')->post('/purchasing',[
            'purchased_at'=>'2026-10-16',
            'items'=>[['goods_id'=>$c['goods'],'quantity'=>1,'unit_price'=>50]],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('purchases',[
            'company_id'=>$c['company'],'fiscal_year_id'=>$c['fy'],'supplier_id'=>null,
            'supply_request_id'=>null,'total_amount'=>50,
        ]);
    }

    public function test_purchase_registration_accepts_multiple_items_without_fiscal_year_input(): void
    {
        $c = $this->context();
        $categoryId = (int) DB::table('goods')->where('id', $c['goods'])->value('category_id');
        $secondGoods = DB::table('goods')->insertGetId([
            'company_id' => $c['company'],
            'category_id' => $categoryId,
            'code' => 'G2',
            'name' => 'Second Material',
            'purchasable' => true,
            'producible' => false,
            'sellable' => true,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->from('/purchasing/create')->post('/purchasing', [
            'supplier_id' => $c['supplier'],
            'purchased_at' => '2026-10-16',
            'items' => [
                ['goods_id' => $c['goods'], 'quantity' => 2, 'unit_price' => 50],
                ['goods_id' => $secondGoods, 'quantity' => 3, 'unit_price' => 100],
            ],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $purchase = DB::table('purchases')->where('company_id', $c['company'])->latest('id')->first();
        $this->assertNotNull($purchase);
        $this->assertSame((int) $c['fy'], (int) $purchase->fiscal_year_id);
        $this->assertSame(400.0, (float) $purchase->total_amount);
        $this->assertSame(2, DB::table('purchase_items')->where('purchase_id', $purchase->id)->count());
        $this->assertDatabaseHas('purchase_items', [
            'purchase_id' => $purchase->id,
            'goods_id' => $c['goods'],
            'quantity' => 2,
            'unit_price' => 50,
            'line_total' => 100,
        ]);
        $this->assertDatabaseHas('purchase_items', [
            'purchase_id' => $purchase->id,
            'goods_id' => $secondGoods,
            'quantity' => 3,
            'unit_price' => 100,
            'line_total' => 300,
        ]);
    }

    public function test_purchase_from_supply_request_saves_unit_price_and_marks_request_purchased_in_one_submission(): void
    {
        $c = $this->context();
        $requestId = DB::table('supply_requests')->insertGetId([
            'company_id' => $c['company'],
            'fiscal_year_id' => $c['fy'],
            'requested_by_user_id' => $c['user'],
            'requested_at' => now(),
            'needed_at' => '2026-10-20',
            'status' => 'purchasing',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('supply_request_items')->insert([
            'supply_request_id' => $requestId,
            'goods_id' => $c['goods'],
            'requested_quantity' => 8,
            'available_quantity' => 5,
            'shortage_quantity' => 3,
            'supplied_quantity' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->from('/purchasing')->post('/purchasing', [
            'fiscal_year_id' => $c['fy'],
            'supplier_id' => '',
            'supply_request_id' => $requestId,
            'purchased_at' => '2026-10-16',
            'items' => [['goods_id' => $c['goods'], 'quantity' => 3, 'unit_price' => 125]],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $this->assertDatabaseHas('purchases', [
            'company_id' => $c['company'],
            'supply_request_id' => $requestId,
            'supplier_id' => null,
            'total_amount' => 375,
        ]);
        $this->assertDatabaseHas('purchase_items', [
            'goods_id' => $c['goods'],
            'quantity' => 3,
            'unit_price' => 125,
            'line_total' => 375,
        ]);
        $this->assertDatabaseHas('supply_requests', [
            'id' => $requestId,
            'status' => 'purchased',
        ]);
    }

    public function test_unpriced_purchase_receipt_cannot_increase_inventory(): void
    {
        $c=$this->context();
        $purchase=DB::table('purchases')->insertGetId([
            'company_id'=>$c['company'],'fiscal_year_id'=>$c['fy'],'supplier_id'=>$c['supplier'],
            'supply_request_id'=>null,'purchased_at'=>'2026-10-16','subtotal'=>0,'direct_cost_total'=>0,
            'total_amount'=>0,'status'=>'draft','created_at'=>now(),'updated_at'=>now(),
        ]);
        DB::table('purchase_items')->insert([
            'purchase_id'=>$purchase,'goods_id'=>$c['goods'],'quantity'=>2,'unit_price'=>0,'line_total'=>0,
            'created_at'=>now(),'updated_at'=>now(),
        ]);
        $this->from('/receipts')->post('/receipts',[
            'purchase_id'=>$purchase,'warehouse_location_id'=>$c['warehouse'],'received_at'=>'2026-10-16 10:00:00',
            'items'=>[['goods_id'=>$c['goods'],'quantity'=>2]],
        ])->assertRedirect();
        $receipt=(int)DB::table('purchase_receipts')->where('purchase_id',$purchase)->value('id');

        $this->postJson('/receipts/'.$receipt.'/approve')->assertUnprocessable();
        $this->assertDatabaseHas('inventory',['location_id'=>$c['warehouse'],'goods_id'=>$c['goods'],'quantity'=>5]);
        $this->assertDatabaseHas('purchase_receipts',['id'=>$receipt,'status'=>'pending']);
    }

    public function test_supply_request_can_be_created_for_a_production_section_without_order_or_legacy_production(): void
    {
        $c = $this->context();
        $location = DB::table('locations')->insertGetId([
            'company_id' => $c['company'],
            'code' => 'PS'.uniqid(),
            'name' => 'قسمت برش',
            'type' => 'production',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $section = DB::table('production_sections')->insertGetId([
            'company_id' => $c['company'],
            'location_id' => $location,
            'name' => 'قسمت برش',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $route = DB::table('production_routes')->insertGetId([
            'company_id' => $c['company'],
            'goods_id' => $c['goods'],
            'code' => 'PR'.uniqid(),
            'name' => 'مسیر تولید آزمایشی',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $production = DB::table('productions')->insertGetId([
            'company_id' => $c['company'],
            'fiscal_year_id' => $c['fy'],
            'goods_id' => $c['goods'],
            'production_route_id' => $route,
            'number' => 'PROD-1',
            'planned_quantity' => 12,
            'status' => 'in_progress',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $stage = DB::table('production_stages')->insertGetId([
            'production_route_id' => $route,
            'production_section_id' => $section,
            'code' => 'STG-'.uniqid(),
            'name' => 'برش',
            'sequence' => 1,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $stageRun = DB::table('production_stage_runs')->insertGetId([
            'production_id' => $production,
            'production_stage_id' => $stage,
            'sequence' => 1,
            'status' => 'pending',
            'planned_quantity' => 12,
            'rejected_quantity' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('orders')->insert([
            'company_id' => $c['company'],
            'fiscal_year_id' => $c['fy'],
            'customer_id' => $c['customer'],
            'number' => 'ORD-5',
            'ordered_at' => '2026-10-06',
            'requested_delivery_at' => '2026-10-20',
            'status' => 'production_required',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get('/supply')
            ->assertOk()
            ->assertSee('ثبت درخواست جدید')
            ->assertDontSee('name="production_section_id"', false);

        $this->get('/supply/create')
            ->assertOk()
            ->assertSee('name="production_section_id"', false)
            ->assertSee('قسمت برش')
            ->assertSee('مرحله تولید و مقصد مصرف')
            ->assertSee('فقط مراحل تعریف‌شده در مسیر تولید نمایش داده می‌شوند؛ هر تعریف مرحله یک‌بار در فهرست می‌آید.')
            ->assertSee('name="production_stage_id"', false)
            ->assertSee('برش</option>', false)
            ->assertDontSee('PROD-1')
            ->assertDontSee('مقدار برنامه‌ریزی‌شده:')
            ->assertSee('مشتری: Customer')
            ->assertSee('سفارش ORD-5')
            ->assertDontSee('سال مالی')
            ->assertSee('name="fiscal_year_id"', false)
            ->assertSee('type="hidden" name="fiscal_year_id"', false);

        $this->from('/supply')->post('/supply', [
            'fiscal_year_id' => $c['fy'],
            'production_section_id' => $section,
            'production_id' => '',
            'production_stage_run_id' => '',
            'order_id' => '',
            'needed_at' => '2026-10-20',
            'items' => [['goods_id' => $c['goods'], 'quantity' => 2]],
        ])->assertRedirect('/supply')->assertSessionHasNoErrors();

        $this->assertDatabaseHas('supply_requests', [
            'company_id' => $c['company'],
            'fiscal_year_id' => $c['fy'],
            'production_section_id' => $section,
            'production_id' => null,
            'order_id' => null,
        ]);
    }


    public function test_supply_request_saves_the_selected_real_production_stage_run(): void
    {
        $c = $this->context();
        $route = DB::table('production_routes')->insertGetId([
            'company_id' => $c['company'],
            'goods_id' => $c['goods'],
            'code' => 'PR'.uniqid(),
            'name' => 'مسیر تولید',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $production = DB::table('productions')->insertGetId([
            'company_id' => $c['company'],
            'fiscal_year_id' => $c['fy'],
            'goods_id' => $c['goods'],
            'production_route_id' => $route,
            'number' => 'PROD-STAGE-2',
            'planned_quantity' => 12,
            'status' => 'in_progress',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $stage = DB::table('production_stages')->insertGetId([
            'production_route_id' => $route,
            'code' => 'CUT-'.uniqid(),
            'name' => 'برش',
            'sequence' => 1,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $stageRun = DB::table('production_stage_runs')->insertGetId([
            'production_id' => $production,
            'production_stage_id' => $stage,
            'sequence' => 1,
            'status' => 'pending',
            'planned_quantity' => 12,
            'rejected_quantity' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->from('/supply')->post('/supply', [
            'fiscal_year_id' => $c['fy'],
            'production_stage_run_id' => $stageRun,
            'production_section_id' => '',
            'production_id' => '',
            'order_id' => '',
            'needed_at' => '2026-10-20',
            'items' => [['goods_id' => $c['goods'], 'quantity' => 2]],
        ])->assertRedirect('/supply')->assertSessionHasNoErrors();

        $this->assertDatabaseHas('supply_requests', [
            'company_id' => $c['company'],
            'production_stage_run_id' => $stageRun,
            'production_id' => $production,
            'production_section_id' => null,
            'order_id' => null,
        ]);
    }

    public function test_supply_request_without_production_reference_returns_validation_error_instead_of_http_exception(): void
    {
        $c = $this->context();

        $this->from('/supply')->post('/supply', [
            'fiscal_year_id' => $c['fy'],
            'production_section_id' => '',
            'production_id' => '',
            'production_stage_run_id' => '',
            'order_id' => '',
            'needed_at' => '2026-10-20',
            'items' => [['goods_id' => $c['goods'], 'quantity' => 2]],
        ])->assertRedirect('/supply')
          ->assertSessionHasErrors([
              'production_stage_id' => 'مرحله تولید، قسمت تولید یا سفارش باید مشخص شود.',
          ]);

        $this->assertDatabaseCount('supply_requests', 0);
    }
    public function test_purchasing_list_explains_purchase_details_and_receipt_status_in_persian(): void
    {
        $c = $this->context();
        $purchase = DB::table('purchases')->insertGetId([
            'company_id' => $c['company'],
            'fiscal_year_id' => $c['fy'],
            'supplier_id' => $c['supplier'],
            'purchased_at' => '2026-10-16',
            'subtotal' => 100,
            'direct_cost_total' => 0,
            'total_amount' => 100,
            'status' => 'received',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('purchase_items')->insert([
            'purchase_id' => $purchase,
            'goods_id' => $c['goods'],
            'quantity' => 2,
            'unit_price' => 50,
            'line_total' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get('/purchasing')
            ->assertOk()
            ->assertSee('تاریخ خرید')
            ->assertSee('تأمین‌کننده')
            ->assertSee('اقلام خرید')
            ->assertSee('مبلغ ثبت‌شده')
            ->assertSee('وضعیت تحویل به انبار')
            ->assertSee('Supplier')
            ->assertSee('Material')
            ->assertSee('تحویل کامل به انبار')
            ->assertDontSee('>received<', false);
    }

    public function test_purchase_registration_form_is_separate_from_purchasing_list(): void
    {
        $this->context();

        $this->get('/purchasing')
            ->assertOk()
            ->assertSee('ثبت خرید جدید')
            ->assertSee(route('workflows.purchasing.create'))
            ->assertDontSee('name="items[0][goods_id]"', false);

        $this->get('/purchasing/create')
            ->assertOk()
            ->assertSee('name="items[0][goods_id]"', false)
            ->assertSee('name="items[0][quantity]"', false)
            ->assertSee('name="items[0][unit_price]"', false)
            ->assertSee('items[__INDEX__][goods_id]', false)
            ->assertSee('add-purchase-item', false)
            ->assertDontSee('name="fiscal_year_id"', false)
            ->assertDontSee('سال مالی فعال')
            ->assertSee('purchased_date_picker')
            ->assertSee('ثبت خرید');
    }

    public function test_pending_purchase_receipt_can_be_edited_without_changing_inventory(): void
    {
        $c = $this->context();
        $purchase = DB::table('purchases')->insertGetId([
            'company_id' => $c['company'], 'fiscal_year_id' => $c['fy'], 'supplier_id' => $c['supplier'],
            'purchased_at' => '2026-10-16', 'subtotal' => 500, 'direct_cost_total' => 0, 'total_amount' => 500,
            'status' => 'draft', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('purchase_items')->insert([
            'purchase_id' => $purchase, 'goods_id' => $c['goods'], 'quantity' => 5,
            'unit_price' => 100, 'line_total' => 500, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $receipt = $this->postJson('/api/purchase-receipts', [
            'purchase_id' => $purchase, 'warehouse_location_id' => $c['warehouse'],
            'received_at' => '2026-10-16 10:00:00', 'items' => [['goods_id' => $c['goods'], 'quantity' => 2]],
        ])->assertCreated()->json('data.id');
        $itemId = (int) DB::table('purchase_receipt_items')->where('purchase_receipt_id', $receipt)->value('id');

        $this->get('/receipts/'.$receipt.'/edit')->assertOk()->assertSee('ویرایش رسید انبار');
        $this->put('/receipts/'.$receipt, [
            'warehouse_location_id' => $c['warehouse'],
            'received_at' => '2026-10-17T11:30',
            'notes' => 'اصلاح مقدار دریافتی',
            'items' => [['id' => $itemId, 'goods_id' => $c['goods'], 'quantity' => 4]],
        ])->assertRedirect('/receipts')->assertSessionHasNoErrors();

        $this->assertDatabaseHas('purchase_receipts', [
            'id' => $receipt, 'status' => 'pending', 'received_at' => '2026-10-17T11:30',
            'notes' => 'اصلاح مقدار دریافتی',
        ]);
        $this->assertDatabaseHas('purchase_receipt_items', [
            'id' => $itemId, 'purchase_receipt_id' => $receipt, 'quantity' => 4,
        ]);
        $this->assertDatabaseHas('inventory', [
            'location_id' => $c['warehouse'], 'goods_id' => $c['goods'], 'quantity' => 5,
        ]);
    }

    public function test_approved_purchase_receipt_cannot_be_edited(): void
    {
        $c = $this->context();
        $purchase = DB::table('purchases')->insertGetId([
            'company_id' => $c['company'], 'fiscal_year_id' => $c['fy'], 'supplier_id' => $c['supplier'],
            'purchased_at' => '2026-10-16', 'subtotal' => 100, 'direct_cost_total' => 0, 'total_amount' => 100,
            'status' => 'partially_received', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('purchase_items')->insert([
            'purchase_id' => $purchase, 'goods_id' => $c['goods'], 'quantity' => 1,
            'unit_price' => 100, 'line_total' => 100, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $receipt = DB::table('purchase_receipts')->insertGetId([
            'company_id' => $c['company'], 'purchase_id' => $purchase, 'warehouse_location_id' => $c['warehouse'],
            'received_by_user_id' => $c['user'], 'received_at' => '2026-10-16 10:00:00', 'status' => 'approved',
            'approved_at' => now(), 'approved_by_user_id' => $c['user'], 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('purchase_receipt_items')->insert([
            'purchase_receipt_id' => $receipt, 'goods_id' => $c['goods'], 'quantity' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->get('/receipts/'.$receipt.'/edit')
            ->assertRedirect('/receipts')
            ->assertSessionHas('error', 'فقط رسیدهای در انتظار تأیید قابل ویرایش هستند. رسید تأییدشده برای حفظ سوابق موجودی و بهای تمام‌شده قفل است.');
    }

    public function test_purchase_can_be_edited_before_any_receipt_and_totals_are_recalculated(): void
    {
        $c = $this->context();
        $purchase = DB::table('purchases')->insertGetId([
            'company_id' => $c['company'],
            'fiscal_year_id' => $c['fy'],
            'supplier_id' => $c['supplier'],
            'supply_request_id' => null,
            'invoice_number' => 'INV-OLD',
            'invoice_date' => null,
            'purchased_at' => '2026-10-01',
            'subtotal' => 20,
            'direct_cost_total' => 0,
            'total_amount' => 20,
            'status' => 'draft',
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $item = DB::table('purchase_items')->insertGetId([
            'purchase_id' => $purchase,
            'goods_id' => $c['goods'],
            'quantity' => 2,
            'unit_price' => 10,
            'line_total' => 20,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get(route('workflows.purchasing.edit', $purchase))->assertOk()->assertSee('ویرایش خرید');
        $this->put(route('workflows.purchasing.update', $purchase), [
            'supplier_id' => $c['supplier'],
            'invoice_number' => 'INV-NEW',
            'purchased_at' => '2026-10-02',
            'items' => [[
                'id' => $item,
                'goods_id' => $c['goods'],
                'quantity' => 3,
                'unit_price' => 15,
            ]],
        ])->assertRedirect(route('workflows.purchasing'));

        $this->assertDatabaseHas('purchases', [
            'id' => $purchase,
            'company_id' => $c['company'],
            'invoice_number' => 'INV-NEW',
            'subtotal' => 45,
            'total_amount' => 45,
        ]);
        $this->assertDatabaseHas('purchase_items', [
            'id' => $item,
            'purchase_id' => $purchase,
            'quantity' => 3,
            'unit_price' => 15,
            'line_total' => 45,
        ]);
    }


    public function test_warehouse_can_register_receipt_before_purchase_and_procurement_finalizes_it_once(): void
    {
        $c = $this->context();

        $this->get('/receipts')
            ->assertOk()
            ->assertSee('ثبت رسید اولیه توسط انبار')
            ->assertDontSee('name="purchase_id"', false);

        $receipt = $this->postJson('/api/purchase-receipts', [
            'warehouse_location_id' => $c['warehouse'],
            'received_at' => '2026-10-21 10:30:00',
            'notes' => 'تحویل واقعی انبار',
            'items' => [['goods_id' => $c['goods'], 'quantity' => 2]],
        ])->assertCreated()->json('data.id');

        $this->assertDatabaseHas('purchase_receipts', [
            'id' => $receipt,
            'company_id' => $c['company'],
            'purchase_id' => null,
            'status' => 'pending',
        ]);
        $this->assertDatabaseMissing('inventory_movements', [
            'reference_type' => 'purchase_receipts',
            'reference_id' => $receipt,
        ]);
        $this->get('/receipts')->assertOk()->assertSee('تأیید و نهایی‌سازی از این صفحه انجام نمی‌شود')->assertDontSee('تکمیل خرید و نهایی‌سازی');

        $this->postJson('/api/purchase-receipts/'.$receipt.'/approve', [
            'supplier_id' => $c['supplier'],
            'invoice_number' => 'INV-WH-1',
            'unit_prices' => [$c['goods'] => 125],
        ])->assertOk()->assertJsonPath('data.status', 'approved');

        $purchaseId = (int) DB::table('purchase_receipts')->where('id', $receipt)->value('purchase_id');
        $this->assertGreaterThan(0, $purchaseId);
        $this->assertDatabaseHas('purchases', [
            'id' => $purchaseId,
            'company_id' => $c['company'],
            'supplier_id' => $c['supplier'],
            'invoice_number' => 'INV-WH-1',
            'subtotal' => 250,
            'total_amount' => 250,
            'status' => 'received',
        ]);
        $this->assertDatabaseHas('purchase_items', [
            'purchase_id' => $purchaseId,
            'goods_id' => $c['goods'],
            'quantity' => 2,
            'unit_price' => 125,
            'line_total' => 250,
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'reference_type' => 'purchase_receipts',
            'reference_id' => $receipt,
            'goods_id' => $c['goods'],
            'location_id' => $c['warehouse'],
            'quantity' => 2,
        ]);
        $this->assertDatabaseHas('inventory_cost_layers', [
            'purchase_id' => $purchaseId,
            'purchase_receipt_id' => $receipt,
            'goods_id' => $c['goods'],
            'quantity_received' => 2,
            'unit_cost' => 125,
        ]);

        $this->postJson('/api/purchase-receipts/'.$receipt.'/approve', [
            'supplier_id' => $c['supplier'],
            'unit_prices' => [$c['goods'] => 125],
        ])->assertStatus(422);
        $this->assertSame(1, DB::table('inventory_movements')
            ->where('reference_type', 'purchase_receipts')->where('reference_id', $receipt)->count());
    }


}
