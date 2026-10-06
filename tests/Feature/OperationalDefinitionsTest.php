<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OperationalDefinitionsTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsCompanyUser(): array
    {
        $account=DB::table('accounts')->insertGetId(['name'=>'A','code'=>'A-1','is_active'=>1,'created_at'=>now(),'updated_at'=>now()]);
        $company=DB::table('companies')->insertGetId(['account_id'=>$account,'name'=>'C','code'=>'C-1','is_active'=>1,'created_at'=>now(),'updated_at'=>now()]);
        $userId=DB::table('users')->insertGetId([
            'account_id'=>$account,'name'=>'Owner','username'=>'owner','email'=>'owner@example.test',
            'password'=>bcrypt('password'),'created_at'=>now(),'updated_at'=>now(),
        ]);
        $permission=DB::table('permissions')->insertGetId(['name'=>'Supplier View','slug'=>'supplier.view','module'=>'supplier','created_at'=>now(),'updated_at'=>now()]);
        $role=DB::table('roles')->insertGetId(['company_id'=>$company,'name'=>'Owner','slug'=>'owner','is_system'=>1,'created_at'=>now(),'updated_at'=>now()]);
        DB::table('role_permissions')->insert(['role_id'=>$role,'permission_id'=>$permission]);
        DB::table('company_user')->insert(['company_id'=>$company,'user_id'=>$userId,'role_id'=>$role,'is_active'=>1,'created_at'=>now(),'updated_at'=>now()]);
        $user=\App\Models\User::findOrFail($userId);
        $this->actingAs($user)->withSession(['company_id'=>$company]);
        return [$account,$company,$user];
    }

    public function test_supplier_index_is_company_scoped(): void
    {
        [$account,$company]= $this->actingAsCompanyUser();
        $other=DB::table('accounts')->insertGetId(['name'=>'B','code'=>'B-1','is_active'=>1,'created_at'=>now(),'updated_at'=>now()]);
        $otherCompany=DB::table('companies')->insertGetId(['account_id'=>$other,'name'=>'Other','code'=>'O-1','is_active'=>1,'created_at'=>now(),'updated_at'=>now()]);
        \App\Models\Supplier::create(['company_id'=>$company,'name'=>'Mine','code'=>'S-1']);
        \App\Models\Supplier::create(['company_id'=>$otherCompany,'name'=>'Other','code'=>'S-1']);
        $this->get(route('definitions.suppliers.index'))->assertOk()->assertSee('Mine')->assertDontSee('Other');
    }

    public function test_good_requires_company_owned_category_and_unit(): void
    {
        [$account,$company]= $this->actingAsCompanyUser();
        $perm=DB::table('permissions')->insertGetId(['name'=>'Goods Create','slug'=>'goods.create','module'=>'goods','created_at'=>now(),'updated_at'=>now()]);
        $role=DB::table('company_user')->where('company_id',$company)->value('role_id');
        DB::table('role_permissions')->insert(['role_id'=>$role,'permission_id'=>$perm]);
        $category=DB::table('goods_categories')->insertGetId(['company_id'=>$company,'name'=>'Raw','code'=>'RAW','is_active'=>1,'created_at'=>now(),'updated_at'=>now()]);
        $unit=DB::table('units')->insertGetId(['company_id'=>$company,'name'=>'عدد','code'=>'EA','symbol'=>'ea','unit_type'=>'count','is_active'=>1,'created_at'=>now(),'updated_at'=>now()]);
        $response=$this->post(route('definitions.goods.store'),['category_id'=>$category,'code'=>'G-1','name'=>'Test','item_type'=>'product','product_type'=>'raw_material','base_unit_id'=>$unit,'supplier_ids'=>[]]);
        $response->assertRedirect(route('definitions.goods.index'));
        $this->assertDatabaseHas('goods',['company_id'=>$company,'code'=>'G-1']);
        $this->assertDatabaseHas('goods_units',['unit_id'=>$unit,'is_base'=>1]);
    }

    public function test_inventory_issue_cannot_make_stock_negative(): void
    {
        [$account,$company]= $this->actingAsCompanyUser();
        $permission=DB::table('permissions')->insertGetId(['name'=>'Operation Create','slug'=>'goods.operation.create','module'=>'goods','created_at'=>now(),'updated_at'=>now()]);
        $role=DB::table('company_user')->where('company_id',$company)->value('role_id');
        DB::table('role_permissions')->insert(['role_id'=>$role,'permission_id'=>$permission]);
        $category=DB::table('goods_categories')->insertGetId(['company_id'=>$company,'name'=>'Raw','code'=>'RAW','is_active'=>1,'created_at'=>now(),'updated_at'=>now()]);
        $good=\App\Models\Good::create(['company_id'=>$company,'category_id'=>$category,'code'=>'G-1','name'=>'Test','item_type'=>'product','product_type'=>'raw_material']);
        $warehouse=DB::table('locations')->insertGetId(['company_id'=>$company,'code'=>'W-1','name'=>'Main','type'=>'warehouse','is_active'=>1,'created_at'=>now(),'updated_at'=>now()]);
        $this->post(route('definitions.goods.operations.store'),['good_id'=>$good->id,'movement_type'=>'issue','location_id'=>$warehouse,'quantity'=>1,'occurred_at'=>now()->format('Y-m-d H:i')])->assertStatus(422);
        $this->assertDatabaseMissing('inventory_movements',['goods_id'=>$good->id]);
    }
}
