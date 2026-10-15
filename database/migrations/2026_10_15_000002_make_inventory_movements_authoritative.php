<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use RuntimeException;

return new class extends Migration {
    public function up(): void
    {
        DB::table('inventory')->orderBy('id')->chunkById(200, function ($rows): void {
            foreach ($rows as $row) {
                $ledgerQuantity = (float) DB::table('inventory_movements')->where('company_id',$row->company_id)
                    ->where('location_id',$row->location_id)->where('goods_id',$row->goods_id)->sum('quantity');
                $storedQuantity = (float) $row->quantity;
                $movementCount = DB::table('inventory_movements')->where('company_id',$row->company_id)
                    ->where('location_id',$row->location_id)->where('goods_id',$row->goods_id)->count();
                if (abs($storedQuantity - $ledgerQuantity) <= 0.0001) continue;
                if ($movementCount > 0) {
                    throw new RuntimeException('Inventory ledger mismatch for inventory id '.$row->id.
                        ': stored='.$storedQuantity.', ledger='.$ledgerQuantity.
                        '. Reconcile this stock record before running this migration.');
                }
                if (abs($storedQuantity) <= 0.0001) continue;
                DB::table('inventory_movements')->insert([
                    'company_id'=>$row->company_id,'goods_id'=>$row->goods_id,'location_id'=>$row->location_id,
                    'quantity'=>$storedQuantity,'movement_type'=>'opening_balance','reference_type'=>'inventory','reference_id'=>$row->id,
                    'occurred_at'=>$row->created_at ?? now(),
                    'metadata'=>json_encode(['source'=>'inventory.quantity','migration'=>'2026_10_15_000002_make_inventory_movements_authoritative','stored_quantity'=>$storedQuantity]),
                    'created_at'=>now(),'updated_at'=>now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        // Opening movements are accounting history and are intentionally retained on rollback.
    }
};
