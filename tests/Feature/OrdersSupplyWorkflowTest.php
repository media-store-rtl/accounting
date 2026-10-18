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

        $this->get('/supply')
            ->assertOk()
            ->assertSee('ثبت درخواست جدید')
            ->assertDontSee('name="production_section_id"', false);

        $this->get('/supply/create')
            ->assertOk()
            ->assertSee('name="production_section_id"', false)
            ->assertSee('قسمت برش');

        $this->from('/supply')->post('/supply', [
            'fiscal_year_id' => $c['fy'],
            'production_section_id' => $section,
            'production_id' => '',
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


    public function test_supply_request_without_production_reference_returns_validation_error_instead_of_http_exception(): void
    {
        $c = $this->context();

        $this->from('/supply')->post('/supply', [
            'fiscal_year_id' => $c['fy'],
            'production_section_id' => '',
            'production_id' => '',
            'order_id' => '',
            'needed_at' => '2026-10-20',
            'items' => [['goods_id' => $c['goods'], 'quantity' => 2]],
        ])->assertRedirect('/supply')
          ->assertSessionHasErrors([
              'production_section_id' => 'قسمت تولید، تولید قدیمی یا سفارش باید مشخص شود.',
          ]);

        $this->assertDatabaseCount('supply_requests', 0);
    }
}
