<?php

namespace App\Services;

use App\Notifications\WorkflowNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use RuntimeException;

class InventoryService
{
    public function available(int $companyId,int $goodsId): float
    {
        return (float) DB::table('inventory as i')
            ->join('locations as l', 'l.id', '=', 'i.location_id')
            ->where('i.company_id', $companyId)
            ->where('i.goods_id', $goodsId)
            ->where('l.company_id', $companyId)
            ->where('l.is_active', true)
            // Finished goods may be received into a dedicated Persian "محصول" location.
            // Count that stock for order allocation, but keep raw-material and scrap locations excluded.
            ->where(function ($query) {
                $query->whereRaw('LOWER(TRIM(l.type)) = ?', ['warehouse'])
                    ->orWhereRaw('TRIM(l.type) = ?', ['محصول']);
            })
            ->sum('i.quantity');
    }

    public function receive(int $companyId,int $locationId,int $goodsId,float $quantity,string $referenceType,int $referenceId,array $metadata=[]): void
    {
        $this->receiveIntoInventory($companyId,$locationId,$goodsId,$quantity,'purchase_receipt',$referenceType,$referenceId,$metadata);
    }

    public function issue(int $companyId,int $locationId,int $goodsId,float $quantity,string $referenceType,int $referenceId,array $metadata=[]): void
    {
        if($quantity<=0) throw new RuntimeException('مقدار خروج کالا باید بیشتر از صفر باشد.');
        abort_unless(DB::table('locations')->where('id',$locationId)->where('company_id',$companyId)->where('type','warehouse')->where('is_active',true)->exists(),422,'انبار معتبر نیست.');
        $row=DB::table('inventory')->where('company_id',$companyId)->where('location_id',$locationId)->where('goods_id',$goodsId)->lockForUpdate()->first();
        abort_unless($row && (float)$row->quantity + 0.0000001 >= $quantity,422,'موجودی انبار کافی نیست.');
        DB::table('inventory')->where('id',$row->id)->update(['quantity'=>DB::raw('quantity - '.(float)$quantity),'updated_at'=>now()]);
        $newQuantity=(float)$row->quantity-(float)$quantity;
        $this->notifyIfReorderPointReached($companyId,$locationId,$goodsId,(float)$row->quantity,$newQuantity,(float)($row->reorder_point??0));
        DB::table('inventory_movements')->insert([
            'company_id'=>$companyId,'goods_id'=>$goodsId,'location_id'=>$locationId,'quantity'=>-$quantity,'movement_type'=>'material_handover',
            'reference_type'=>$referenceType,'reference_id'=>$referenceId,'occurred_at'=>now(),'metadata'=>json_encode($metadata),'created_at'=>now(),'updated_at'=>now()
        ]);
    }

    public function transferToProductionSection(int $companyId, int $warehouseLocationId, int $sectionId, int $goodsId, float $quantity, string $referenceType, int $referenceId, array $metadata = []): void
    {
        if ($quantity <= 0) throw new RuntimeException('مقدار انتقال باید بیشتر از صفر باشد.');
        $section = DB::table('production_sections as s')
            ->join('locations as target', 'target.id', '=', 's.location_id')
            ->where('s.id', $sectionId)->where('target.company_id', $companyId)->where('target.is_active', true)
            ->select('target.id as location_id')->first();
        abort_unless($section, 422, 'قسمت تولید مقصد معتبر یا فعال نیست.');
        abort_unless(DB::table('locations')->where('id', $warehouseLocationId)->where('company_id', $companyId)
            ->where('type', 'warehouse')->where('is_active', true)->exists(), 422, 'انبار مبدأ معتبر نیست.');

        $this->issue($companyId, $warehouseLocationId, $goodsId, $quantity, $referenceType, $referenceId,
            $metadata + ['production_section_id' => $sectionId, 'movement_leg' => 'out']);
        DB::table('inventory')->insertOrIgnore([
            'company_id' => $companyId, 'location_id' => (int) $section->location_id, 'goods_id' => $goodsId,
            'quantity' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $targetRow = DB::table('inventory')->where('company_id', $companyId)->where('location_id', (int) $section->location_id)
            ->where('goods_id', $goodsId)->lockForUpdate()->first();
        abort_unless($targetRow, 422, 'ثبت موجودی قسمت تولید ممکن نشد.');
        DB::table('inventory')->where('id', $targetRow->id)->update([
            'quantity' => DB::raw('quantity + '.(float) $quantity), 'updated_at' => now(),
        ]);
        DB::table('inventory_movements')->insert([
            'company_id' => $companyId, 'goods_id' => $goodsId, 'location_id' => (int) $section->location_id,
            'quantity' => $quantity, 'movement_type' => 'production_material_receipt',
            'reference_type' => $referenceType, 'reference_id' => $referenceId, 'occurred_at' => now(),
            'metadata' => json_encode($metadata + ['production_section_id' => $sectionId, 'movement_leg' => 'in']),
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function consumeFromProductionSection(int $companyId, int $sectionId, int $goodsId, float $quantity, string $referenceType, int $referenceId, array $metadata = []): void
    {
        if ($quantity <= 0) throw new RuntimeException('مقدار مصرف باید بیشتر از صفر باشد.');

        $section = DB::table('production_sections as s')
            ->join('locations as l', 'l.id', '=', 's.location_id')
            ->where('s.id', $sectionId)
            ->where('l.company_id', $companyId)
            ->where('l.is_active', true)
            ->select('l.id as location_id')
            ->first();
        abort_unless($section, 422, 'قسمت تولید برای کسر مواد اولیه معتبر یا فعال نیست.');

        $row = DB::table('inventory')
            ->where('company_id', $companyId)
            ->where('location_id', (int) $section->location_id)
            ->where('goods_id', $goodsId)
            ->lockForUpdate()
            ->first();
        abort_unless($row && (float) $row->quantity + 0.0000001 >= $quantity, 422, 'موجودی مواد اولیه در قسمت تولید کافی نیست.');

        $previous = (float) $row->quantity;
        $current = $previous - $quantity;
        DB::table('inventory')->where('id', $row->id)->update([
            'quantity' => $current,
            'updated_at' => now(),
        ]);
        $this->notifyIfReorderPointReached(
            $companyId, (int) $section->location_id, $goodsId, $previous, $current,
            (float) ($row->reorder_point ?? 0)
        );
        DB::table('inventory_movements')->insert([
            'company_id' => $companyId,
            'goods_id' => $goodsId,
            'location_id' => (int) $section->location_id,
            'quantity' => -$quantity,
            'movement_type' => 'production_consumption',
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'occurred_at' => now(),
            'metadata' => json_encode($metadata + ['production_section_id' => $sectionId, 'movement_leg' => 'consumption']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function receiveFinishedGoods(int $companyId,int $locationId,int $goodsId,float $quantity,string $referenceType,int $referenceId,array $metadata=[]): void
    {
        $this->receiveIntoInventory($companyId,$locationId,$goodsId,$quantity,'finished_goods_receipt',$referenceType,$referenceId,$metadata,true);
    }

    public function adjust(int $companyId,int $locationId,int $goodsId,float $newQuantity,string $reason,int $userId): void
    {
        if ($newQuantity < 0) throw new RuntimeException('موجودی نهایی نمی‌تواند منفی باشد.');
        DB::transaction(function () use ($companyId,$locationId,$goodsId,$newQuantity,$reason,$userId) {
            abort_unless(DB::table('locations')->where('id',$locationId)->where('company_id',$companyId)->where('type','warehouse')->where('is_active',true)->exists(),422,'انبار معتبر نیست.');
            $row=DB::table('inventory')->where('company_id',$companyId)->where('location_id',$locationId)->where('goods_id',$goodsId)->lockForUpdate()->first();
            $previous=(float)($row->quantity??0);
            $reorderPoint=(float)($row->reorder_point??0);
            $delta=$newQuantity-$previous;
            if($row) {
                DB::table('inventory')->where('id',$row->id)->update(['quantity'=>$newQuantity,'updated_at'=>now()]);
            } else {
                DB::table('inventory')->insert(['company_id'=>$companyId,'location_id'=>$locationId,'goods_id'=>$goodsId,'quantity'=>$newQuantity,'reorder_point'=>0,'created_at'=>now(),'updated_at'=>now()]);
            }
            DB::table('inventory_movements')->insert([
                'company_id'=>$companyId,'goods_id'=>$goodsId,'location_id'=>$locationId,'quantity'=>$delta,
                'movement_type'=>'inventory_adjustment','reference_type'=>'inventory','reference_id'=>$row->id??0,
                'occurred_at'=>now(),'metadata'=>json_encode(['reason'=>$reason,'adjusted_by_user_id'=>$userId]),
                'created_at'=>now(),'updated_at'=>now(),
            ]);
            app(AuditTrailService::class)->recordDirect($companyId,$userId,'warehouse','inventory.adjustment',(int)($row->id??0),
                ['quantity'=>$previous],['quantity'=>$newQuantity,'reason'=>$reason]);
            $this->notifyIfReorderPointReached($companyId,$locationId,$goodsId,$previous,$newQuantity,$reorderPoint);
        });
    }

    public function setReorderPoint(int $companyId,int $inventoryId,float $reorderPoint,int $userId): void
    {
        if ($reorderPoint < 0) throw new RuntimeException('نقطه سفارش نمی‌تواند منفی باشد.');
        DB::transaction(function () use ($companyId,$inventoryId,$reorderPoint,$userId) {
            $row=DB::table('inventory as i')->join('locations as l','l.id','=','i.location_id')->where('i.id',$inventoryId)->where('i.company_id',$companyId)->where('l.company_id',$companyId)->lockForUpdate()->select('i.*')->first();
            abort_unless($row,404);
            $old=(float)($row->reorder_point??0);
            DB::table('inventory')->where('id',$inventoryId)->update(['reorder_point'=>$reorderPoint,'updated_at'=>now()]);
            app(AuditTrailService::class)->recordDirect($companyId,$userId,'warehouse','inventory.reorder_point.update',$inventoryId,
                ['reorder_point'=>$old],['reorder_point'=>$reorderPoint]);
            $this->notifyIfReorderPointReached($companyId,(int)$row->location_id,(int)$row->goods_id,(float)$row->quantity,(float)$row->quantity,$reorderPoint);
        });
    }

    private function notifyIfReorderPointReached(int $companyId,int $locationId,int $goodsId,float $previous,float $current,float $reorderPoint): void
    {
        if ($reorderPoint <= 0) return;

        $inventory = DB::table('inventory')
            ->where('company_id', $companyId)->where('location_id', $locationId)->where('goods_id', $goodsId)
            ->lockForUpdate()->first();
        if (!$inventory) return;

        if ($current > $reorderPoint) {
            if ((bool) ($inventory->reorder_alert_active ?? false)) {
                DB::table('inventory')->where('id', $inventory->id)->update(['reorder_alert_active' => false, 'updated_at' => now()]);
            }
            return;
        }

        if ((bool) ($inventory->reorder_alert_active ?? false)) return;

        $goods = DB::table('goods')->where('company_id', $companyId)->where('id', $goodsId)
            ->select('name', 'purchasable', 'producible', 'sellable')->first();
        if (!$goods) return;

        if ((bool) $goods->producible && (bool) $goods->sellable) {
            $event = 'inventory.finished_goods_low';
            $permission = 'production.supervise';
        } elseif ((bool) $goods->purchasable && !(bool) $goods->sellable) {
            $event = 'inventory.raw_material_low';
            $permission = 'supply.manage';
        } else {
            $event = 'inventory_reorder_point_reached';
            $permission = 'supply.manage';
        }

        DB::table('inventory')->where('id', $inventory->id)->update(['reorder_alert_active' => true, 'updated_at' => now()]);
        app(NotificationRecipientService::class)->send(
            $companyId,
            $event,
            [
                'location_id' => $locationId,
                'goods_id' => $goodsId,
                'goods_name' => $goods->name,
                'current_quantity' => $current,
                'reorder_point' => $reorderPoint,
            ],
            null,
            $permission
        );
    }

    private function receiveIntoInventory(int $companyId,int $locationId,int $goodsId,float $quantity,string $movementType,string $referenceType,int $referenceId,array $metadata=[],bool $allowProductionReceiptLocationTypes=false): void
    {
        if($quantity<=0) throw new RuntimeException('مقدار ورود کالا باید بیشتر از صفر باشد.');
                $locationQuery = DB::table('locations')->where('id',$locationId)->where('company_id',$companyId)->where('is_active',true);
        if ($allowProductionReceiptLocationTypes) {
            $locationQuery->where(function ($query) {
                $query->whereRaw('LOWER(TRIM(type)) = ?', ['warehouse'])
                    ->orWhereIn(DB::raw('TRIM(type)'), ['مواد', 'محصول', 'ضایعات']);
            });
        } else {
            $locationQuery->where('type','warehouse');
        }
        abort_unless($locationQuery->exists(),422,'انبار معتبر نیست.');
        $row=DB::table('inventory')->where('location_id',$locationId)->where('goods_id',$goodsId)->lockForUpdate()->first();
        if($row) {
            $previousQuantity=(float)$row->quantity;
            DB::table('inventory')->where('id',$row->id)->update(['quantity'=>DB::raw('quantity + '.(float)$quantity),'updated_at'=>now()]);
            $this->notifyIfReorderPointReached($companyId,$locationId,$goodsId,$previousQuantity,$previousQuantity+(float)$quantity,(float)($row->reorder_point??0));
        } else {
            DB::table('inventory')->insert(['company_id'=>$companyId,'location_id'=>$locationId,'goods_id'=>$goodsId,'quantity'=>$quantity,'reorder_point'=>0,'created_at'=>now(),'updated_at'=>now()]);
        }
        DB::table('inventory_movements')->insert([
            'company_id'=>$companyId,'goods_id'=>$goodsId,'location_id'=>$locationId,'quantity'=>$quantity,'movement_type'=>$movementType,
            'reference_type'=>$referenceType,'reference_id'=>$referenceId,'occurred_at'=>now(),'metadata'=>json_encode($metadata),'created_at'=>now(),'updated_at'=>now()
        ]);
    }
}