<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/** The inventory movement ledger is the source of truth for stock quantities. */
final class InventoryBalanceService
{
    public function quantityAtLocation(int $companyId, int $locationId, int $goodsId): float
    {
        return (float) DB::table('inventory_movements')->where('company_id',$companyId)
            ->where('location_id',$locationId)->where('goods_id',$goodsId)->sum('quantity');
    }

    public function available(int $companyId, int $goodsId): float
    {
        return (float) DB::table('inventory_movements as m')->join('locations as l','l.id','=','m.location_id')
            ->where('m.company_id',$companyId)->where('m.goods_id',$goodsId)->where('l.company_id',$companyId)
            ->where('l.is_active',true)->where(function($query){
                $query->whereRaw('LOWER(TRIM(l.type)) IN (?, ?, ?, ?)', ['warehouse', 'انبار', 'مواد', 'مواد اولیه'])->orWhereRaw('TRIM(l.type) = ?', ['محصول']);
            })->sum('m.quantity');
    }

    public function availableInWarehouses(int $companyId, int $goodsId): float
    {
        return (float) DB::table('inventory_movements as m')->join('locations as l','l.id','=','m.location_id')
            ->where('m.company_id',$companyId)->where('m.goods_id',$goodsId)->where('l.company_id',$companyId)
            ->where('l.is_active',true)->whereRaw('LOWER(TRIM(l.type)) IN (?, ?, ?)', ['warehouse', 'انبار', 'مواد'])->sum('m.quantity');
    }
}
