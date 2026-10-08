<?php

namespace App\Services;

use App\Notifications\WorkflowNotification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

final class ProductionExecutionService
{
    public function submit(
        int $companyId,
        int $operationRunId,
        array $inputs,
        array $outputs,
        array $scraps,
        int $userId,
        ?string $completedAt = null,
        ?string $notes = null
    ): void {
        DB::transaction(function () use ($companyId,$operationRunId,$inputs,$outputs,$scraps,$userId,$completedAt,$notes) {
            $context=$this->context($companyId,$operationRunId,true);
            if ($context->status!=='pending' && $context->status!=='in_progress') {
                throw ValidationException::withMessages(['operation'=>'این عملیات در وضعیت قابل ثبت نیست.']);
            }
            if (!$inputs && !$outputs && !$scraps) {
                throw ValidationException::withMessages(['operation'=>'حداقل یک ورودی، خروجی یا ضایعات ثبت کنید.']);
            }

            $before=(array)$context;
            $this->validateLines($companyId,$inputs,'ورودی');
            foreach($inputs as $line){
                if(!empty($line['source_inventory_movement_id'])){
                    $movement=DB::table('inventory_movements')->where('id',(int)$line['source_inventory_movement_id'])->where('company_id',$companyId)->first();
                    if(!$movement || (int)$movement->goods_id!==(int)$line['goods_id'] || (float)$movement->quantity>=0){
                        throw ValidationException::withMessages(['inputs'=>'مرجع خروج موجودی برای ورودی عملیات نامعتبر است.']);
                    }
                }
            }
            $this->validateLines($companyId,$outputs,'خروجی');
            foreach($scraps as $line){
                if((float)($line['quantity']??0)<=0) throw ValidationException::withMessages(['scraps'=>'مقدار ضایعات باید بیشتر از صفر باشد.']);
                if(!DB::table('goods')->where('id',(int)$line['goods_id'])->where('company_id',$companyId)->exists()) throw ValidationException::withMessages(['scraps'=>'کالای ضایعات متعلق به شرکت نیست.']);
            }

            DB::table('production_operation_runs')->where('id',$operationRunId)->update([
                'status'=>'pending_review','input_quantity'=>$this->sum($inputs),
                'output_quantity'=>$this->sum($outputs),'rejected_quantity'=>$this->sum($scraps),
                'completed_at'=>$completedAt?now()->parse($completedAt):now(),'notes'=>$notes,'updated_at'=>now()
            ]);

            foreach($inputs as $line){
                DB::table('production_operation_inputs')->insert([
                    'production_operation_run_id'=>$operationRunId,'goods_id'=>(int)$line['goods_id'],'quantity'=>(float)$line['quantity'],
                    'source_inventory_movement_id'=>$line['source_inventory_movement_id']??null,'recorded_by_user_id'=>$userId,
                    'recorded_at'=>$line['recorded_at']??now(),'notes'=>$line['notes']??null,'created_at'=>now(),'updated_at'=>now()
                ]);
            }
            foreach($outputs as $line){
                DB::table('production_operation_outputs')->insert([
                    'production_operation_run_id'=>$operationRunId,'goods_id'=>(int)$line['goods_id'],'quantity'=>(float)$line['quantity'],
                    'output_type'=>$line['output_type']??'product','recorded_by_user_id'=>$userId,
                    'recorded_at'=>$line['recorded_at']??now(),'notes'=>$line['notes']??null,'created_at'=>now(),'updated_at'=>now()
                ]);
            }
            foreach($scraps as $line){
                $qty=(float)$line['quantity'];
                $unitCost=$this->scrapUnitCost($companyId,$inputs,$line);
                DB::table('scraps')->insert([
                    'production_operation_run_id'=>$operationRunId,'goods_id'=>(int)$line['goods_id'],'quantity'=>$qty,
                    'unit_cost'=>$unitCost,'total_cost'=>$unitCost*$qty,'reason'=>$line['reason']??null,
                    'scrapped_at'=>$line['scrapped_at']??now(),'notes'=>$line['notes']??null,'created_at'=>now(),'updated_at'=>now()
                ]);
            }

            $after=(array)DB::table('production_operation_runs')->where('id',$operationRunId)->first();
            $this->audit($companyId,$userId,'operation.submit',$operationRunId,$before,$after);

            $supervisor=DB::table('production_stages as s')
                ->join('production_sections as ps','ps.id','=','s.production_section_id')
                ->join('personnel as pe','pe.id','=','ps.supervisor_personnel_id')
                ->join('users as u','u.id','=','pe.user_id')
                ->where('s.id',$context->production_stage_id)->where('u.is_active',true)->first(['u.id']);
            if($supervisor && (int)$supervisor->id!==$userId){
                Notification::send([User::query()->find((int)$supervisor->id)],new WorkflowNotification('production_operation_review_required',[
                    'operation_run_id'=>$operationRunId,'production_id'=>(int)$context->production_id
                ]));
            }
        });
    }

    public function review(int $companyId,int $operationRunId,int $reviewerId,bool $approve,?string $reason=null): void
    {
        DB::transaction(function()use($companyId,$operationRunId,$reviewerId,$approve,$reason){
            $context=$this->context($companyId,$operationRunId,true);
            if($context->status!=='pending_review') throw ValidationException::withMessages(['operation'=>'این عملیات در وضعیت بررسی نیست.']);

            $allowed=DB::table('production_stages as s')
                ->join('production_sections as ps','ps.id','=','s.production_section_id')
                ->join('personnel as p','p.id','=','ps.supervisor_personnel_id')
                ->where('s.id',$context->production_stage_id)->where('p.user_id',$reviewerId)->exists();
            if(!$allowed && !User::query()->findOrFail($reviewerId)->hasCompanyPermission($companyId,'production.operation.review')){
                throw ValidationException::withMessages(['review'=>'فقط سرپرست قسمت یا کاربر مجاز می‌تواند بررسی کند.']);
            }

            if(!$approve){
                $before=(array)$context;
                DB::table('production_operation_runs')->where('id',$operationRunId)->update(['status'=>'rejected','notes'=>trim(($context->notes??'')."\nرد: ".($reason??'')),'updated_at'=>now()]);
                $after=(array)DB::table('production_operation_runs')->where('id',$operationRunId)->first();
                $this->audit($companyId,$reviewerId,'operation.reject',$operationRunId,$before,$after);
                return;
            }

            $before=(array)$context;
            DB::table('production_operation_runs')->where('id',$operationRunId)->update(['status'=>'completed','updated_at'=>now()]);
            $after=(array)DB::table('production_operation_runs')->where('id',$operationRunId)->first();
            $this->audit($companyId,$reviewerId,'operation.approve',$operationRunId,$before,$after);
            $stageRun=DB::table('production_stage_runs')->where('id',$context->production_stage_run_id)->lockForUpdate()->first();
            $pending=DB::table('production_operation_runs')->where('production_stage_run_id',$stageRun->id)->whereIn('status',['pending','in_progress','pending_review'])->exists();
            $rejected=DB::table('production_operation_runs')->where('production_stage_run_id',$stageRun->id)->where('status','rejected')->exists();
            if(!$pending && !$rejected){
                $out=(float)DB::table('production_operation_runs')->where('production_stage_run_id',$stageRun->id)->sum('output_quantity');
                $rej=(float)DB::table('production_operation_runs')->where('production_stage_run_id',$stageRun->id)->sum('rejected_quantity');
                DB::table('production_stage_runs')->where('id',$stageRun->id)->update(['status'=>'completed','output_quantity'=>$out,'rejected_quantity'=>$rej,'completed_at'=>now(),'updated_at'=>now()]);
            }

            $productionId=(int)$context->production_id;
            $unfinished=DB::table('production_stage_runs')->where('production_id',$productionId)->where('status','!=','completed')->exists();
            if(!$unfinished){
                DB::table('productions')->where('id',$productionId)->update(['status'=>'completed','completed_at'=>now(),'updated_at'=>now()]);
            }
        });
    }

    private function audit(int $companyId,int $userId,string $action,int $id,array $before,array $after): void
    {
        DB::table('audit_trails')->insert(['company_id'=>$companyId,'user_id'=>$userId,'module'=>'production','action'=>$action,'auditable_type'=>'production_operation_runs','auditable_id'=>$id,'before'=>json_encode($before),'after'=>json_encode($after),'method'=>'SERVICE','status_code'=>200,'created_at'=>now(),'updated_at'=>now()]);
    }

    private function context(int $companyId,int $operationRunId,bool $lock=false): object
    {
        $q=DB::table('production_operation_runs as r')
            ->join('production_stage_runs as sr','sr.id','=','r.production_stage_run_id')
            ->join('production_stages as s','s.id','=','sr.production_stage_id')
            ->join('production_routes as pr','pr.id','=','s.production_route_id')
            ->join('productions as p','p.id','=','sr.production_id')
            ->where('r.id',$operationRunId)->where('p.company_id',$companyId)
            ->select('r.*','sr.production_id','sr.id as production_stage_run_id','s.id as production_stage_id','s.production_section_id');
        if($lock)$q->lockForUpdate();
        $row=$q->first();
        if(!$row) throw ValidationException::withMessages(['operation_run_id'=>'عملیات تولید متعلق به شرکت نیست.']);
        return $row;
    }

    private function validateLines(int $companyId,array $lines,string $label): void
    {
        foreach($lines as $line){
            if((float)($line['quantity']??0)<=0) throw ValidationException::withMessages([$label=>'مقدار '.$label.' باید بیشتر از صفر باشد.']);
            if(!DB::table('goods')->where('id',(int)$line['goods_id'])->where('company_id',$companyId)->where('is_active',true)->exists()) throw ValidationException::withMessages([$label=>'کالا معتبر نیست.']);
        }
    }

    private function sum(array $lines): float { return array_sum(array_map(fn($x)=>(float)$x['quantity'],$lines)); }

    private function scrapUnitCost(int $companyId,array $inputs,array $scrap): float
    {
        $goodsId=(int)$scrap['goods_id'];
        $rows=DB::table('inventory_consumption_costs')->where('company_id',$companyId)->where('goods_id',$goodsId)->orderByDesc('consumed_at')->limit(50)->get(['quantity','total_cost']);
        $qty=(float)$rows->sum('quantity'); $cost=(float)$rows->sum('total_cost');
        if($qty>0) return $cost/$qty;
        $inQty=$this->sum($inputs);
        if($inQty>0){
            $inputGoods=array_sum(array_map(fn($x)=>(float)$x['quantity']*(float)($x['unit_cost']??0),$inputs));
            if($inputGoods>0)return $inputGoods/$inQty;
        }
        return 0.0;
    }
}
