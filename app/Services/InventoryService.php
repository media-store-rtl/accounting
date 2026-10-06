<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class InventoryService
{
    public function available(int $companyId,int $goodsId): float
    {
        return (float)DB::table('inventory as i')->join('locations as l','l.id','=','i.location_id')
            ->where('i.company_id',$companyId)->where('i.goods_id',$goodsId)->where('l.company_id',$companyId)
            ->where('l.type','warehouse')->where('l.is_active',true)->sum('i.quantity');
    }

    public function receive(int $companyId,int $locationId,int $goodsId,float $quantity,string $referenceType,int $referenceId,array $metadata=[]): void
    {
        if($quantity<=0) throw new RuntimeException('مقدار ورود کالا باید بیشتر از صفر باشد.');
        abort_unless(DB::table('locations')->where('id',$locationId)->where('company_id',$companyId)->where('type','warehouse')->where('is_active',true)->exists(),422,'انبار معتبر نیست.');
        $row=DB::table('inventory')->where('location_id',$locationId)->where('goods_id',$goodsId)->lockForUpdate()->first();
        if($row) DB::table('inventory')->where('id',$row->id)->update(['quantity'=>DB::raw('quantity + '.(float)$quantity),'updated_at'=>now()]);
        else DB::table('inventory')->insert(['company_id'=>$companyId,'location_id'=>$locationId,'goods_id'=>$goodsId,'quantity'=>$quantity,'created_at'=>now(),'updated_at'=>now()]);
        DB::table('inventory_movements')->insert([
            'company_id'=>$companyId,'goods_id'=>$goodsId,'location_id'=>$locationId,'quantity'=>$quantity,'movement_type'=>'purchase_receipt',
            'reference_type'=>$referenceType,'reference_id'=>$referenceId,'occurred_at'=>now(),'metadata'=>json_encode($metadata),'created_at'=>now(),'updated_at'=>now()
        ]);
    }
}