<?php

namespace Tests\Feature;

use App\Services\InventoryValuationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InventoryValuationTest extends TestCase
{
    use RefreshDatabase;

    private function context(string $method): array
    {
        $account=DB::table('accounts')->insertGetId(['name'=>'Valuation Account','code'=>'VA'.uniqid(),'created_at'=>now(),'updated_at'=>now()]);
        $company=DB::table('companies')->insertGetId(['account_id'=>$account,'name'=>'Valuation Company','code'=>'VC'.uniqid(),'is_active'=>true,'settings'=>json_encode([]),'inventory_valuation_method'=>$method,'created_at'=>now(),'updated_at'=>now()]);
        $user=DB::table('users')->insertGetId(['account_id'=>$account,'name'=>'Valuation User','username'=>'vu'.uniqid(),'email'=>uniqid().'@test.local','password'=>bcrypt('secret'),'created_at'=>now(),'updated_at'=>now()]);
        $fy=DB::table('fiscal_years')->insertGetId(['company_id'=>$company,'name'=>'1405','code'=>'FY'.uniqid(),'starts_at'=>'2026-03-21','ends_at'=>'2027-03-20','is_closed'=>false,'created_at'=>now(),'updated_at'=>now()]);
        $category=DB::table('goods_categories')->insertGetId(['company_id'=>$company,'name'=>'Raw','code'=>'RAW'.uniqid(),'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $goods=DB::table('goods')->insertGetId(['company_id'=>$company,'category_id'=>$category,'code'=>'RM1','name'=>'Raw Material','purchasable'=>true,'producible'=>false,'sellable'=>false,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $warehouse=DB::table('locations')->insertGetId(['company_id'=>$company,'code'=>'W1','name'=>'Warehouse','type'=>'warehouse','is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        return compact('company','fy','goods','warehouse','user');
    }

    private function layer(array $c,float $qty,float $cost,string $receivedAt): void
    {
        $supplier=DB::table('suppliers')->insertGetId(['company_id'=>$c['company'],'name'=>'Supplier','code'=>'SUP'.uniqid(),'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        $purchase=DB::table('purchases')->insertGetId(['company_id'=>$c['company'],'fiscal_year_id'=>$c['fy'],'supplier_id'=>$supplier,'purchased_at'=>'2026-10-07','subtotal'=>$qty*$cost,'direct_cost_total'=>0,'total_amount'=>$qty*$cost,'status'=>'received','created_at'=>now(),'updated_at'=>now()]);
        $item=DB::table('purchase_items')->insertGetId(['purchase_id'=>$purchase,'goods_id'=>$c['goods'],'quantity'=>$qty,'unit_price'=>$cost,'line_total'=>$qty*$cost,'created_at'=>now(),'updated_at'=>now()]);
        $receipt=DB::table('purchase_receipts')->insertGetId(['company_id'=>$c['company'],'purchase_id'=>$purchase,'warehouse_location_id'=>$c['warehouse'],'received_by_user_id'=>$c['user'],'received_at'=>$receivedAt,'status'=>'approved','approved_at'=>$receivedAt,'created_at'=>now(),'updated_at'=>now()]);
        $receiptItem=DB::table('purchase_receipt_items')->insertGetId(['purchase_receipt_id'=>$receipt,'goods_id'=>$c['goods'],'quantity'=>$qty,'created_at'=>now(),'updated_at'=>now()]);
        app(InventoryValuationService::class)->registerPurchaseReceipt($c['company'],$purchase,$item,$receipt,$receiptItem,$c['warehouse'],$c['goods'],$c['fy'],$qty,$receivedAt);
    }

    private function movement(array $c,float $qty,int $number=1): int
    {
        return DB::table('inventory_movements')->insertGetId([
            'company_id'=>$c['company'],'goods_id'=>$c['goods'],'location_id'=>$c['warehouse'],'quantity'=>-$qty,
            'movement_type'=>'material_handover','reference_type'=>'material_handovers','reference_id'=>$number,'occurred_at'=>now(),
            'metadata'=>json_encode(['supply_request_id'=>1]),'created_at'=>now(),'updated_at'=>now()
        ]);
    }

    public function test_fifo_consumes_oldest_layers_first(): void
    {
        $c=$this->context(InventoryValuationService::FIFO);
        $this->layer($c,100,10,'2026-10-01 10:00:00');
        $this->layer($c,100,15,'2026-10-02 10:00:00');
        $result=app(InventoryValuationService::class)->valueInventoryMovement($this->movement($c,120),$c['fy']);
        $this->assertSame(1300.0,round($result['total_cost'],4));
        $this->assertSame(10.833333,round($result['unit_cost'],6));
        $this->assertSame(0.0,(float)DB::table('inventory_cost_layers')->orderBy('id')->first()->fifo_quantity_remaining);
        $this->assertSame(80.0,(float)DB::table('inventory_cost_layers')->orderByDesc('id')->first()->fifo_quantity_remaining);
    }

    public function test_weighted_average_uses_one_average_rate_and_preserves_layer_balance(): void
    {
        $c=$this->context(InventoryValuationService::WEIGHTED_AVERAGE);
        $this->layer($c,100,10,'2026-10-01 10:00:00');
        $this->layer($c,100,15,'2026-10-02 10:00:00');
        $result=app(InventoryValuationService::class)->valueInventoryMovement($this->movement($c,120),$c['fy']);
        $this->assertSame(1500.0,round($result['total_cost'],4));
        $this->assertSame(12.5,round($result['unit_cost'],6));
        $this->assertSame(80.0,(float)DB::table('inventory_cost_layers')->sum('weighted_average_quantity_remaining'));
    }

    public function test_method_can_be_selected_before_valued_transactions(): void
    {
        $c=$this->context(InventoryValuationService::FIFO);
        $service=app(InventoryValuationService::class);
        $this->assertSame(InventoryValuationService::FIFO,$service->methodForCompany($c['company']));
        $service->setMethod($c['company'],InventoryValuationService::WEIGHTED_AVERAGE);
        $this->assertSame(InventoryValuationService::WEIGHTED_AVERAGE,$service->methodForCompany($c['company']));
    }
}
