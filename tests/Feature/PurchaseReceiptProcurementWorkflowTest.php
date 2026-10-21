<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PurchaseReceiptProcurementWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function context(): array
    {
        $account=DB::table('accounts')->insertGetId(['name'=>'Test Account','code'=>'ACC'.uniqid(),'created_at'=>now(),'updated_at'=>now()]);
        $company=DB::table('companies')->insertGetId(['account_id'=>$account,'name'=>'Test Company','code'=>'COM'.uniqid(),'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $role=DB::table('roles')->insertGetId(['company_id'=>$company,'name'=>'Operations','slug'=>'ops'.uniqid(),'is_system'=>false,'created_at'=>now(),'updated_at'=>now()]);
        $permissionSlugs=['purchase.create','purchase.view','purchase.receipt.create','purchase.receipt.approve','notification.view'];
        foreach($permissionSlugs as $slug){
            $permission=DB::table('permissions')->where('slug',$slug)->first();
            $permissionId=$permission?->id ?? DB::table('permissions')->insertGetId(['name'=>$slug,'slug'=>$slug,'module'=>'purchases','created_at'=>now(),'updated_at'=>now()]);
            DB::table('role_permissions')->insertOrIgnore(['role_id'=>$role,'permission_id'=>$permissionId]);
        }
        $warehouseUser=DB::table('users')->insertGetId(['account_id'=>$account,'name'=>'Warehouse User','username'=>'warehouse'.uniqid(),'email'=>uniqid().'@test.local','password'=>Hash::make('secret'),'created_at'=>now(),'updated_at'=>now()]);
        $procurementUser=DB::table('users')->insertGetId(['account_id'=>$account,'name'=>'Procurement User','username'=>'procurement'.uniqid(),'email'=>uniqid().'@test.local','password'=>Hash::make('secret'),'created_at'=>now(),'updated_at'=>now()]);
        foreach([$warehouseUser,$procurementUser] as $userId){
            DB::table('company_user')->insert(['company_id'=>$company,'user_id'=>$userId,'role_id'=>$role,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        }
        $fy=DB::table('fiscal_years')->insertGetId(['company_id'=>$company,'name'=>'1405','code'=>'FY'.uniqid(),'starts_at'=>'2026-03-21','ends_at'=>'2027-03-20','is_closed'=>false,'created_at'=>now(),'updated_at'=>now()]);
        $category=DB::table('goods_categories')->insertGetId(['company_id'=>$company,'name'=>'Raw','code'=>'RAW'.uniqid(),'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $goods=DB::table('goods')->insertGetId(['company_id'=>$company,'category_id'=>$category,'code'=>'G'.uniqid(),'name'=>'Material','purchasable'=>true,'producible'=>false,'sellable'=>true,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $warehouse=DB::table('locations')->insertGetId(['company_id'=>$company,'code'=>'W'.uniqid(),'name'=>'Warehouse','type'=>'مواد اولیه','is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $supplier=DB::table('suppliers')->insertGetId(['company_id'=>$company,'name'=>'Supplier','code'=>'SUP'.uniqid(),'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);

        return compact('company','warehouseUser','procurementUser','fy','goods','warehouse','supplier');
    }

    public function test_warehouse_receipt_notifies_procurement_and_keeps_receipt_pending(): void
    {
        $c=$this->context();
        $this->actingAs(User::findOrFail($c['warehouseUser']))->withSession(['company_id'=>$c['company'],'fiscal_year_id'=>$c['fy']]);

        $response=$this->postJson('/api/purchase-receipts',[
            'warehouse_location_id'=>$c['warehouse'],
            'received_at'=>'2026-10-21 10:00:00',
            'notes'=>'Warehouse receipt',
            'items'=>[['goods_id'=>$c['goods'],'quantity'=>4]],
        ])->assertCreated();

        $receiptId=(int)$response->json('data.id');
        $this->assertDatabaseHas('purchase_receipts',['id'=>$receiptId,'company_id'=>$c['company'],'purchase_id'=>null,'status'=>'pending']);
        $notification=DB::table('notifications')->where('notifiable_id',$c['procurementUser'])->latest('created_at')->first();
        $this->assertNotNull($notification);
        $data=json_decode($notification->data,true);
        $this->assertSame('purchase.receipt_pending_financialization',$data['event']);
        $this->assertSame($receiptId,(int)$data['receipt_id']);
    }

    public function test_pending_receipt_is_visible_in_procurement_section(): void
    {
        $c=$this->context();
        $receiptId=DB::table('purchase_receipts')->insertGetId([
            'company_id'=>$c['company'],'purchase_id'=>null,'warehouse_location_id'=>$c['warehouse'],'received_by_user_id'=>$c['warehouseUser'],
            'received_at'=>'2026-10-21 10:00:00','status'=>'pending','notes'=>null,'created_at'=>now(),'updated_at'=>now(),
        ]);
        DB::table('purchase_receipt_items')->insert(['purchase_receipt_id'=>$receiptId,'goods_id'=>$c['goods'],'quantity'=>4,'created_at'=>now(),'updated_at'=>now()]);

        $this->actingAs(User::findOrFail($c['procurementUser']))->withSession(['company_id'=>$c['company'],'fiscal_year_id'=>$c['fy']]);
        $this->get('/purchasing')->assertOk()->assertSee('رسیدهای در انتظار ریالی‌شدن')->assertSee('#'.$receiptId)->assertSee('تأیید و نهایی‌سازی رسید');
    }
}
