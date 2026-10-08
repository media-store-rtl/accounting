<?php

namespace App\Services;

use App\Models\ProductionOutput;
use App\Models\User;
use App\Notifications\WorkflowNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\DB;
use App\Services\InventoryService;
use Illuminate\Validation\ValidationException;

class ProductionOutputService
{
    public function create(int $companyId, int $productionId, ?int $orderId, int $warehouseLocationId, float $quantity, int $userId, ?string $producedAt = null, ?string $notes = null): ProductionOutput
    {
        if ($quantity <= 0) throw ValidationException::withMessages(['quantity' => 'مقدار خروجی باید بیشتر از صفر باشد.']);

        return DB::transaction(function () use ($companyId, $productionId, $orderId, $warehouseLocationId, $quantity, $userId, $producedAt, $notes) {
            $production = DB::table('productions')->where('id', $productionId)->where('company_id', $companyId)->lockForUpdate()->first();
            if (!$production || $production->status !== 'completed') throw ValidationException::withMessages(['production_id' => 'فقط تولید تکمیل‌شده می‌تواند خروجی داشته باشد.']);
            if ($orderId !== null && !DB::table('production_order')->where('production_id', $productionId)->where('order_id', $orderId)->exists()) throw ValidationException::withMessages(['order_id' => 'سفارش به این تولید متصل نیست.']);
            if (!DB::table('locations')->where('id', $warehouseLocationId)->where('company_id', $companyId)->where('type', 'warehouse')->where('is_active', true)->exists()) throw ValidationException::withMessages(['warehouse_location_id' => 'انبار معتبر نیست.']);

            $pendingQuantity=(float)DB::table('production_outputs')->where('production_id',$productionId)->whereIn('status',['pending','confirmed'])->sum('quantity');
            $remaining=(float)$production->planned_quantity-(float)$production->produced_quantity-$pendingQuantity;
            if($quantity>$remaining+0.0000001) throw ValidationException::withMessages(['quantity'=>'مقدار خروجی بیش از مقدار مجاز تولید است.']);

            $id=DB::table('production_outputs')->insertGetId([
                'company_id'=>$companyId,'production_id'=>$productionId,'order_id'=>$orderId,'goods_id'=>$production->goods_id,
                'warehouse_location_id'=>$warehouseLocationId,'quantity'=>$quantity,'status'=>'pending','created_by_user_id'=>$userId,
                'produced_at'=>$producedAt??now(),'notes'=>$notes,'created_at'=>now(),'updated_at'=>now()
            ]);
            DB::table('finished_goods_receipts')->insert([
                'company_id'=>$companyId,'production_output_id'=>$id,'warehouse_location_id'=>$warehouseLocationId,
                'received_by_user_id'=>null,'status'=>'pending','received_at'=>null,'notes'=>null,'created_at'=>now(),'updated_at'=>now()
            ]);
            return ProductionOutput::query()->findOrFail($id);
        });
    }

    public function confirm(int $companyId,int $outputId,int $userId,?string $receivedAt=null): ProductionOutput
    {
        $this->approve($companyId,$outputId,$userId);
        return $this->receive($companyId,$outputId,$userId,$receivedAt);
    }

    public function approve(int $companyId, int $outputId, int $userId): ProductionOutput
    {
        return DB::transaction(function () use ($companyId,$outputId,$userId) {
            $output=DB::table('production_outputs')->where('id',$outputId)->where('company_id',$companyId)->lockForUpdate()->first();
            if(!$output || $output->status!=='pending') throw ValidationException::withMessages(['output'=>'این خروجی در وضعیت قابل تأیید نیست.']);
            $production=DB::table('productions')->where('id',$output->production_id)->where('company_id',$companyId)->first();
            if(!$production || $production->status!=='completed') throw ValidationException::withMessages(['production'=>'تولید باید قبل از تأیید خروجی تکمیل شده باشد.']);
            DB::table('production_outputs')->where('id',$outputId)->update([
                'status'=>'confirmed','confirmed_at'=>now(),'confirmed_by_user_id'=>$userId,'updated_at'=>now()
            ]);
            $this->audit($companyId,$userId,'finished_goods_output.approve',(int)$outputId,$output);
            return ProductionOutput::query()->findOrFail($outputId);
        });
    }

    public function receive(int $companyId, int $outputId, int $userId, ?string $receivedAt=null): ProductionOutput
    {
        return DB::transaction(function() use ($companyId,$outputId,$userId,$receivedAt) {
            $output=DB::table('production_outputs')->where('id',$outputId)->where('company_id',$companyId)->lockForUpdate()->first();
            if(!$output || $output->status!=='confirmed') throw ValidationException::withMessages(['output'=>'خروجی باید ابتدا توسط سرپرست تولید تأیید شود.']);
            $receipt=DB::table('finished_goods_receipts')->where('production_output_id',$output->id)->where('company_id',$companyId)->lockForUpdate()->first();
            if(!$receipt || $receipt->status!=='pending') throw ValidationException::withMessages(['receipt'=>'رسید کالای ساخته‌شده قابل دریافت نیست.']);
            $production=DB::table('productions')->where('id',$output->production_id)->where('company_id',$companyId)->lockForUpdate()->first();
            if(!$production || $production->status!=='completed') throw ValidationException::withMessages(['production'=>'تولید باید تکمیل شده باشد.']);
            $remaining=(float)$production->planned_quantity-(float)$production->produced_quantity;
            if((float)$output->quantity>$remaining+0.0000001) throw ValidationException::withMessages(['quantity'=>'مقدار دریافت‌شده بیش از مقدار باقی‌مانده تولید است.']);

            app(InventoryService::class)->receiveFinishedGoods($companyId,(int)$output->warehouse_location_id,(int)$output->goods_id,(float)$output->quantity,'production_outputs',(int)$output->id,['production_id'=>(int)$output->production_id,'order_id'=>$output->order_id]);
            DB::table('finished_goods_receipts')->where('id',$receipt->id)->update([
                'status'=>'approved','received_by_user_id'=>$userId,'received_at'=>$receivedAt??now(),'approved_at'=>now(),'approved_by_user_id'=>$userId,'updated_at'=>now()
            ]);
            DB::table('productions')->where('id',$production->id)->update([
                'produced_quantity'=>DB::raw('produced_quantity + '.(float)$output->quantity),'updated_at'=>now()
            ]);
            $this->audit($companyId,$userId,'finished_goods.receive',(int)$outputId,$output);
            $salesUsers=DB::table('company_user as cu')->join('role_permissions as rp','rp.role_id','=','cu.role_id')->join('permissions as p','p.id','=','rp.permission_id')->where('cu.company_id',$companyId)->where('cu.is_active',true)->where('p.slug','order.view')->pluck('cu.user_id');
            if($salesUsers->isNotEmpty()) Notification::send(User::whereIn('id',$salesUsers)->get(),new WorkflowNotification('finished_goods_received',['production_output_id'=>(int)$outputId,'production_id'=>(int)$output->production_id,'goods_id'=>(int)$output->goods_id,'quantity'=>(float)$output->quantity]));
            return ProductionOutput::query()->findOrFail($outputId);
        });
    }

    private function audit(int $companyId,int $userId,string $action,int $outputId,object $before): void
    {
        DB::table('audit_trails')->insert([
            'company_id'=>$companyId,'user_id'=>$userId,'module'=>'production','action'=>$action,
            'auditable_type'=>'production_outputs','auditable_id'=>$outputId,
            'before'=>json_encode((array)$before),'after'=>json_encode((array)DB::table('production_outputs')->where('id',$outputId)->first()),
            'method'=>'SERVICE','status_code'=>200,'created_at'=>now(),'updated_at'=>now()
        ]);
    }

    public function reject(int $companyId,int $outputId,int $userId,?string $reason=null): ProductionOutput
    {
        return DB::transaction(function()use($companyId,$outputId,$userId,$reason){
            $output=DB::table('production_outputs')->where('id',$outputId)->where('company_id',$companyId)->lockForUpdate()->first();
            if(!$output||$output->status!=='pending') throw ValidationException::withMessages(['output'=>'این خروجی در وضعیت قابل رد نیست.']);
            DB::table('production_outputs')->where('id',$output->id)->update(['status'=>'rejected','rejected_at'=>now(),'rejected_by_user_id'=>$userId,'rejection_reason'=>$reason,'updated_at'=>now()]);
            DB::table('finished_goods_receipts')->where('production_output_id',$output->id)->update(['status'=>'rejected','updated_at'=>now()]);
            $this->audit($companyId,$userId,'finished_goods_output.reject',(int)$outputId,$output);
            return ProductionOutput::query()->findOrFail($output->id);
        });
    }
}