<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\WorkflowNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
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

    public function receiveFinishedGoods(int $companyId,int $locationId,int $goodsId,float $quantity,string $referenceType,int $referenceId,array $metadata=[]): void
    {
        $this->receiveIntoInventory($companyId,$locationId,$goodsId,$quantity,'finished_goods_receipt',$referenceType,$referenceId,$metadata);
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
        if ($reorderPoint <= 0 || !($previous > $reorderPoint && $current <= $reorderPoint)) return;
        $recipients=User::query()->whereIn('id',DB::table('company_user as cu')
            ->join('role_permissions as rp','rp.role_id','=','cu.role_id')
            ->join('permissions as p','p.id','=','rp.permission_id')
            ->where('cu.company_id',$companyId)->where('cu.is_active',true)->where('p.slug','supply.manage')->pluck('cu.user_id'))->get();
        if ($recipients->isEmpty()) return;
        Notification::send($recipients,new WorkflowNotification('inventory_reorder_point_reached',[
            'company_id'=>$companyId,'location_id'=>$locationId,'goods_id'=>$goodsId,
            'current_quantity'=>$current,'reorder_point'=>$reorderPoint,
        ]));
    }

    private function receiveIntoInventory(int $companyId,int $locationId,int $goodsId,float $quantity,string $movementType,string $referenceType,int $referenceId,array $metadata=[]): void
    {
        if($quantity<=0) throw new RuntimeException('مقدار ورود کالا باید بیشتر از صفر باشد.');
        abort_unless(DB::table('locations')->where('id',$locationId)->where('company_id',$companyId)->where('type','warehouse')->where('is_active',true)->exists(),422,'انبار معتبر نیست.');
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