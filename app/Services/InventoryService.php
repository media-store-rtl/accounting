<?php

namespace App\Services;

use App\Notifications\WorkflowNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function available(int $companyId,int $goodsId): float
    {
        return app(InventoryBalanceService::class)->available($companyId, $goodsId);
    }

    public function receive(int $companyId,int $locationId,int $goodsId,float $quantity,string $referenceType,int $referenceId,array $metadata=[]): void
    {
        $this->receiveIntoInventory($companyId,$locationId,$goodsId,$quantity,'purchase_receipt',$referenceType,$referenceId,$metadata);
    }

    public function issue(int $companyId,int $locationId,int $goodsId,float $quantity,string $referenceType,int $referenceId,array $metadata=[]): void
    {
        if ($quantity <= 0) throw new RuntimeException('مقدار خروج کالا باید بیشتر از صفر باشد.');
        abort_unless(DB::table('locations')->where('id',$locationId)->where('company_id',$companyId)->where('type','warehouse')->where('is_active',true)->exists(),422,'انبار معتبر نیست.');
        DB::table('inventory')->insertOrIgnore(['company_id'=>$companyId,'location_id'=>$locationId,'goods_id'=>$goodsId,'quantity'=>0,'reorder_point'=>0,'created_at'=>now(),'updated_at'=>now()]);
        $row=DB::table('inventory')->where('company_id',$companyId)->where('location_id',$locationId)->where('goods_id',$goodsId)->lockForUpdate()->first();
        abort_unless($row,422,'ثبت ردیف کنترل انبار ممکن نشد.');
        $previous=app(InventoryBalanceService::class)->quantityAtLocation($companyId,$locationId,$goodsId);
        abort_unless($previous+0.0000001 >= $quantity,422,'موجودی انبار کافی نیست.');
        DB::table('inventory_movements')->insert([
            'company_id'=>$companyId,'goods_id'=>$goodsId,'location_id'=>$locationId,'quantity'=>-$quantity,'movement_type'=>'material_handover',
            'reference_type'=>$referenceType,'reference_id'=>$referenceId,'occurred_at'=>now(),'metadata'=>json_encode($metadata),'created_at'=>now(),'updated_at'=>now()
        ]);
        $this->notifyIfReorderPointReached($companyId,$locationId,$goodsId,$previous,$previous-$quantity,(float)($row->reorder_point??0));
    }

    public function issueAcrossWarehouseAndProductLocations(int $companyId,int $goodsId,float $quantity,string $referenceType,int $referenceId,array $metadata=[]): void
    {
        if ($quantity <= 0) throw new RuntimeException('مقدار خروج کالا باید بیشتر از صفر باشد.');
        $locations=DB::table('locations')->where('company_id',$companyId)->where('is_active',true)
            ->where(function($q){$q->whereRaw('LOWER(TRIM(type)) = ?', ['warehouse'])->orWhereRaw('TRIM(type) = ?', ['محصول']);})
            ->orderBy('id')->get(['id']);
        $remaining=$quantity;
        foreach($locations as $location){
            if($remaining<=0.0000001) break;
            $locationId=(int)$location->id;
            DB::table('inventory')->insertOrIgnore(['company_id'=>$companyId,'location_id'=>$locationId,'goods_id'=>$goodsId,'quantity'=>0,'reorder_point'=>0,'created_at'=>now(),'updated_at'=>now()]);
            $row=DB::table('inventory')->where('company_id',$companyId)->where('location_id',$locationId)->where('goods_id',$goodsId)->lockForUpdate()->first();
            if(!$row) continue;
            $previous=app(InventoryBalanceService::class)->quantityAtLocation($companyId,$locationId,$goodsId);
            if($previous<=0.0000001) continue;
            $take=min($remaining,$previous);
            DB::table('inventory_movements')->insert([
                'company_id'=>$companyId,'goods_id'=>$goodsId,'location_id'=>$locationId,'quantity'=>-$take,'movement_type'=>'delivery_issue',
                'reference_type'=>$referenceType,'reference_id'=>$referenceId,'occurred_at'=>now(),
                'metadata'=>json_encode($metadata+['location_id'=>$locationId]),'created_at'=>now(),'updated_at'=>now(),
            ]);
            $this->notifyIfReorderPointReached($companyId,$locationId,$goodsId,$previous,$previous-$take,(float)($row->reorder_point??0));
            $remaining-=$take;
        }
        abort_unless($remaining<=0.0000001,422,'موجودی کافی برای خروج کالا وجود ندارد.');
    }

    public function transferToProductionSection(int $companyId,int $warehouseLocationId,int $sectionId,int $goodsId,float $quantity,string $referenceType,int $referenceId,array $metadata=[]): void
    {
        if($quantity<=0) throw new RuntimeException('مقدار انتقال باید بیشتر از صفر باشد.');
        $section=DB::table('production_sections as s')->join('locations as target','target.id','=','s.location_id')
            ->where('s.id',$sectionId)->where('target.company_id',$companyId)->where('target.is_active',true)->select('target.id as location_id')->first();
        abort_unless($section,422,'قسمت تولید مقصد معتبر یا فعال نیست.');
        abort_unless(DB::table('locations')->where('id',$warehouseLocationId)->where('company_id',$companyId)->where('type','warehouse')->where('is_active',true)->exists(),422,'انبار مبدأ معتبر نیست.');
        $this->issue($companyId,$warehouseLocationId,$goodsId,$quantity,$referenceType,$referenceId,$metadata+['production_section_id'=>$sectionId,'movement_leg'=>'out']);
        // A handover to production is a location transfer, not material consumption.
        // Keep the warehouse issue out of costing totals and move its cost layers.
        $sourceMovement = DB::table('inventory_movements')->where('company_id',$companyId)
            ->where('reference_type',$referenceType)->where('reference_id',$referenceId)
            ->where('goods_id',$goodsId)->where('location_id',$warehouseLocationId)
            ->where('quantity','<',0)->orderByDesc('id')->first();
        if ($sourceMovement) {
            DB::table('inventory_movements')->where('id',$sourceMovement->id)->update([
                'movement_type'=>'production_material_transfer_out',
                'updated_at'=>now(),
            ]);
        }
        app(InventoryValuationService::class)->transferLayers($companyId,$goodsId,$warehouseLocationId,(int)$section->location_id,$quantity);
        $targetId=(int)$section->location_id;
        DB::table('inventory')->insertOrIgnore(['company_id'=>$companyId,'location_id'=>$targetId,'goods_id'=>$goodsId,'quantity'=>0,'reorder_point'=>0,'created_at'=>now(),'updated_at'=>now()]);
        $targetRow=DB::table('inventory')->where('company_id',$companyId)->where('location_id',$targetId)->where('goods_id',$goodsId)->lockForUpdate()->first();
        abort_unless($targetRow,422,'ثبت موجودی قسمت تولید ممکن نشد.');
        $previous=app(InventoryBalanceService::class)->quantityAtLocation($companyId,$targetId,$goodsId);
        DB::table('inventory_movements')->insert([
            'company_id'=>$companyId,'goods_id'=>$goodsId,'location_id'=>$targetId,'quantity'=>$quantity,'movement_type'=>'production_material_receipt',
            'reference_type'=>$referenceType,'reference_id'=>$referenceId,'occurred_at'=>now(),
            'metadata'=>json_encode($metadata+['production_section_id'=>$sectionId,'movement_leg'=>'in']),'created_at'=>now(),'updated_at'=>now(),
        ]);
        $this->notifyIfReorderPointReached($companyId,$targetId,$goodsId,$previous,$previous+$quantity,(float)($targetRow->reorder_point??0));
    }

    public function consumeFromProductionSection(int $companyId,int $sectionId,int $goodsId,float $quantity,string $referenceType,int $referenceId,array $metadata=[]): void
    {
        if($quantity<=0) throw new RuntimeException('مقدار مصرف باید بیشتر از صفر باشد.');
        $section=DB::table('production_sections as s')->join('locations as l','l.id','=','s.location_id')
            ->where('s.id',$sectionId)->where('l.company_id',$companyId)->where('l.is_active',true)->select('l.id as location_id')->first();
        if(!$section) throw ValidationException::withMessages(['operation'=>'قسمت تولید برای کسر مواد اولیه معتبر یا فعال نیست.']);
        $locationId=(int)$section->location_id;
        DB::table('inventory')->insertOrIgnore(['company_id'=>$companyId,'location_id'=>$locationId,'goods_id'=>$goodsId,'quantity'=>0,'reorder_point'=>0,'created_at'=>now(),'updated_at'=>now()]);
        $row=DB::table('inventory')->where('company_id',$companyId)->where('location_id',$locationId)->where('goods_id',$goodsId)->lockForUpdate()->first();
        if(!$row) throw ValidationException::withMessages(['operation'=>'ردیف کنترل موجودی قسمت تولید پیدا نشد.']);
        $previous=app(InventoryBalanceService::class)->quantityAtLocation($companyId,$locationId,$goodsId);
        if($previous+0.0000001<$quantity) throw ValidationException::withMessages(['operation'=>'موجودی مواد اولیه در قسمت تولید کافی نیست.']);
        // Carry the relevant supply request into the consumption ledger so the costing
        // report can attribute the valued raw material to its production/order.
        if (empty($metadata['supply_request_id'])) {
            $supplyRequestId = DB::table('supply_requests as sr')
                ->join('supply_request_items as sri','sri.supply_request_id','=','sr.id')
                ->where('sr.company_id',$companyId)->where('sr.production_section_id',$sectionId)
                ->where('sri.goods_id',$goodsId)->whereNotIn('sr.status',['cancelled'])
                ->orderByDesc('sr.id')->value('sr.id');
            if ($supplyRequestId) $metadata['supply_request_id'] = (int) $supplyRequestId;
        }
        $movementId = DB::table('inventory_movements')->insertGetId([
            'company_id'=>$companyId,'goods_id'=>$goodsId,'location_id'=>$locationId,'quantity'=>-$quantity,'movement_type'=>'production_consumption',
            'reference_type'=>$referenceType,'reference_id'=>$referenceId,'occurred_at'=>now(),
            'metadata'=>json_encode($metadata+['production_section_id'=>$sectionId,'movement_leg'=>'consumption']),'created_at'=>now(),'updated_at'=>now(),
        ]);
        $fiscalYearId = (int) ($metadata['fiscal_year_id'] ?? 0);
        if (!$fiscalYearId && !empty($metadata['supply_request_id'])) {
            $fiscalYearId = (int) DB::table('supply_requests')->where('company_id',$companyId)
                ->where('id',(int)$metadata['supply_request_id'])->value('fiscal_year_id');
        }
        if (!$fiscalYearId) {
            $fiscalYearId = (int) DB::table('fiscal_years')->where('company_id',$companyId)
                ->where('is_closed',false)->orderByDesc('starts_at')->value('id');
        }
        if ($fiscalYearId) {
            app(InventoryValuationService::class)->valueInventoryMovement($movementId,$fiscalYearId);
        }
        $this->notifyIfReorderPointReached($companyId,$locationId,$goodsId,$previous,$previous-$quantity,(float)($row->reorder_point??0));
    }

    public function receiveFinishedGoods(int $companyId,int $locationId,int $goodsId,float $quantity,string $referenceType,int $referenceId,array $metadata=[]): void
    {
        $this->receiveIntoInventory($companyId,$locationId,$goodsId,$quantity,'finished_goods_receipt',$referenceType,$referenceId,$metadata,true);
    }

    public function adjust(int $companyId,int $locationId,int $goodsId,float $newQuantity,string $reason,int $userId): void
    {
        if($newQuantity<0) throw new RuntimeException('موجودی نهایی نمی‌تواند منفی باشد.');
        DB::transaction(function()use($companyId,$locationId,$goodsId,$newQuantity,$reason,$userId){
            abort_unless(DB::table('locations')->where('id',$locationId)->where('company_id',$companyId)->where('type','warehouse')->where('is_active',true)->exists(),422,'انبار معتبر نیست.');
            DB::table('inventory')->insertOrIgnore(['company_id'=>$companyId,'location_id'=>$locationId,'goods_id'=>$goodsId,'quantity'=>0,'reorder_point'=>0,'created_at'=>now(),'updated_at'=>now()]);
            $row=DB::table('inventory')->where('company_id',$companyId)->where('location_id',$locationId)->where('goods_id',$goodsId)->lockForUpdate()->first();
            abort_unless($row,422,'ردیف کنترل موجودی پیدا نشد.');
            $previous=app(InventoryBalanceService::class)->quantityAtLocation($companyId,$locationId,$goodsId);
            $reorderPoint=(float)($row->reorder_point??0); $delta=$newQuantity-$previous;
            DB::table('inventory_movements')->insert([
                'company_id'=>$companyId,'goods_id'=>$goodsId,'location_id'=>$locationId,'quantity'=>$delta,'movement_type'=>'inventory_adjustment',
                'reference_type'=>'inventory','reference_id'=>$row->id,'occurred_at'=>now(),
                'metadata'=>json_encode(['reason'=>$reason,'adjusted_by_user_id'=>$userId]),'created_at'=>now(),'updated_at'=>now(),
            ]);
            app(AuditTrailService::class)->recordDirect($companyId,$userId,'warehouse','inventory.adjustment',(int)$row->id,['quantity'=>$previous],['quantity'=>$newQuantity,'reason'=>$reason]);
            $this->notifyIfReorderPointReached($companyId,$locationId,$goodsId,$previous,$newQuantity,$reorderPoint);
        });
    }

    public function setReorderPoint(int $companyId,int $inventoryId,float $reorderPoint,int $userId): void
    {
        if($reorderPoint<0) throw new RuntimeException('نقطه سفارش نمی‌تواند منفی باشد.');
        DB::transaction(function()use($companyId,$inventoryId,$reorderPoint,$userId){
            $row=DB::table('inventory as i')->join('locations as l','l.id','=','i.location_id')->where('i.id',$inventoryId)->where('i.company_id',$companyId)->where('l.company_id',$companyId)->lockForUpdate()->select('i.*')->first();
            abort_unless($row,404);
            $old=(float)($row->reorder_point??0);
            $current=app(InventoryBalanceService::class)->quantityAtLocation($companyId,(int)$row->location_id,(int)$row->goods_id);
            DB::table('inventory')->where('id',$inventoryId)->update(['reorder_point'=>$reorderPoint,'updated_at'=>now()]);
            app(AuditTrailService::class)->recordDirect($companyId,$userId,'warehouse','inventory.reorder_point.update',$inventoryId,['reorder_point'=>$old],['reorder_point'=>$reorderPoint]);
            $this->notifyIfReorderPointReached($companyId,(int)$row->location_id,(int)$row->goods_id,$current,$current,$reorderPoint);
        });
    }

    private function notifyIfReorderPointReached(int $companyId,int $locationId,int $goodsId,float $previous,float $current,float $reorderPoint): void
    {
        if ($reorderPoint <= 0) return;

        $result = DB::transaction(function () use ($companyId, $locationId, $goodsId, $current, $reorderPoint) {
            $inventory = DB::table('inventory')
                ->where('company_id', $companyId)->where('location_id', $locationId)->where('goods_id', $goodsId)
                ->lockForUpdate()->first();
            if (!$inventory) return null;

            // A new alert cycle starts only after stock recovers to the reorder point.
            if ($current >= $reorderPoint) {
                if ((bool) ($inventory->reorder_alert_active ?? false)) {
                    DB::table('inventory')->where('id', $inventory->id)->update(['reorder_alert_active' => false, 'updated_at' => now()]);
                }
                return null;
            }

            if ((bool) ($inventory->reorder_alert_active ?? false)) return null;

            $goods = DB::table('goods')->where('company_id', $companyId)->where('id', $goodsId)
                ->select('name', 'purchasable', 'producible', 'sellable')->first();
            if (!$goods) return null;

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

            $payload = [
                'location_id' => $locationId,
                'goods_id' => $goodsId,
                'goods_name' => $goods->name,
                'current_quantity' => $current,
                'reorder_point' => $reorderPoint,
            ];

            // Only purchasable raw materials create an automatic request for procurement.
            // The request tops stock back up to the reorder point and is directly visible in Purchasing.
            if ($event === 'inventory.raw_material_low') {
                $requestQuantity = max(0, $reorderPoint - $current);
                if ($requestQuantity <= 0) return null;

                $fiscalYear = DB::table('fiscal_years')
                    ->where('company_id', $companyId)->where('is_closed', false)
                    ->whereDate('starts_at', '<=', today()->toDateString())
                    ->whereDate('ends_at', '>=', today()->toDateString())
                    ->orderByDesc('starts_at')->first();

                // If today's date is outside all open fiscal periods, use the latest open period.
                if (!$fiscalYear) {
                    $fiscalYear = DB::table('fiscal_years')->where('company_id', $companyId)
                        ->where('is_closed', false)->orderByDesc('starts_at')->first();
                }

                $requesterId = DB::table('company_user as cu')
                    ->join('users as u', 'u.id', '=', 'cu.user_id')
                    ->where('cu.company_id', $companyId)->where('cu.is_active', true)->where('u.is_active', true)
                    ->orderBy('cu.user_id')->value('cu.user_id');

                if (!$fiscalYear || !$requesterId) {
                    Log::warning('Automatic supply request was not created because an open fiscal year or active company user is missing.', [
                        'company_id' => $companyId, 'location_id' => $locationId, 'goods_id' => $goodsId,
                    ]);
                    return null;
                }

                $requestId = DB::table('supply_requests')->insertGetId([
                    'company_id' => $companyId,
                    'fiscal_year_id' => $fiscalYear->id,
                    'requested_by_user_id' => $requesterId,
                    'requested_at' => now(),
                    'needed_at' => today()->toDateString(),
                    'status' => 'shortage_pending',
                    'notes' => 'درخواست خودکار تدارکات به دلیل رسیدن موجودی ماده اولیه به کمتر از نقطه سفارش؛ انبار: '.$locationId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::table('supply_request_items')->insert([
                    'supply_request_id' => $requestId,
                    'goods_id' => $goodsId,
                    'requested_quantity' => $requestQuantity,
                    'available_quantity' => 0,
                    'shortage_quantity' => $requestQuantity,
                    'supplied_quantity' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $payload['supply_request_id'] = $requestId;
                $payload['requested_quantity'] = $requestQuantity;
            }

            DB::table('inventory')->where('id', $inventory->id)->update(['reorder_alert_active' => true, 'updated_at' => now()]);

            return ['event' => $event, 'permission' => $permission, 'payload' => $payload];
        });

        if (!$result) return;

        app(NotificationRecipientService::class)->send(
            $companyId,
            $result['event'],
            $result['payload'],
            null,
            $result['permission']
        );

        if ($result['event'] === 'inventory.raw_material_low' && !empty($result['payload']['supply_request_id'])) {
            app(NotificationRecipientService::class)->send(
                $companyId,
                'supply.shortage',
                [
                    'supply_request_id' => $result['payload']['supply_request_id'],
                    'needed_at' => today()->toDateString(),
                    'goods_id' => $goodsId,
                    'goods_name' => $result['payload']['goods_name'],
                    'requested_quantity' => $result['payload']['requested_quantity'],
                    'available_quantity' => 0,
                    'shortage_quantity' => $result['payload']['requested_quantity'],
                ],
                null,
                'supply.manage'
            );
        }
    }

    private function receiveIntoInventory(int $companyId,int $locationId,int $goodsId,float $quantity,string $movementType,string $referenceType,int $referenceId,array $metadata=[],bool $allowProductionReceiptLocationTypes=false): void
    {
        if($quantity<=0) throw new RuntimeException('مقدار ورود کالا باید بیشتر از صفر باشد.');
        $locationQuery=DB::table('locations')->where('id',$locationId)->where('company_id',$companyId)->where('is_active',true);
        if($allowProductionReceiptLocationTypes){$locationQuery->where(function($q){$q->whereRaw('LOWER(TRIM(type)) = ?', ['warehouse'])->orWhereIn(DB::raw('TRIM(type)'), ['مواد','محصول','ضایعات']);});}
        else {$locationQuery->where('type','warehouse');}
        abort_unless($locationQuery->exists(),422,'انبار معتبر نیست.');
        DB::table('inventory')->insertOrIgnore(['company_id'=>$companyId,'location_id'=>$locationId,'goods_id'=>$goodsId,'quantity'=>0,'reorder_point'=>0,'created_at'=>now(),'updated_at'=>now()]);
        $row=DB::table('inventory')->where('company_id',$companyId)->where('location_id',$locationId)->where('goods_id',$goodsId)->lockForUpdate()->first();
        abort_unless($row,422,'ثبت ردیف کنترل موجودی ممکن نشد.');
        $previous=app(InventoryBalanceService::class)->quantityAtLocation($companyId,$locationId,$goodsId);
        DB::table('inventory_movements')->insert([
            'company_id'=>$companyId,'goods_id'=>$goodsId,'location_id'=>$locationId,'quantity'=>$quantity,'movement_type'=>$movementType,
            'reference_type'=>$referenceType,'reference_id'=>$referenceId,'occurred_at'=>now(),'metadata'=>json_encode($metadata),'created_at'=>now(),'updated_at'=>now(),
        ]);
        $this->notifyIfReorderPointReached($companyId,$locationId,$goodsId,$previous,$previous+$quantity,(float)($row->reorder_point??0));
    }
}