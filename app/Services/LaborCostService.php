<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class LaborCostService
{
    private const MEASURE_TYPES = ['hours', 'quantity'];
    private const RATE_TYPES = ['hourly', 'per_unit'];

    public function setRate(
        int $companyId,
        int $personnelId,
        string $rateType,
        float $rate,
        string $effectiveFrom,
        ?string $effectiveTo = null
    ): int {
        if (!in_array($rateType, self::RATE_TYPES, true) || $rate < 0) {
            throw ValidationException::withMessages(['rate' => 'نوع یا نرخ دستمزد نامعتبر است.']);
        }
        $personnel = DB::table('personnel')->where('id', $personnelId)->where('account_id', DB::table('companies')->where('id', $companyId)->value('account_id'))->where('is_active', true)->first();
        if (!$personnel) throw ValidationException::withMessages(['personnel_id' => 'پرسنل فعال شرکت معتبر نیست.']);

        return DB::transaction(function () use ($companyId, $personnelId, $rateType, $rate, $effectiveFrom, $effectiveTo) {
            $overlap = DB::table('personnel_labor_rates')
                ->where('company_id', $companyId)
                ->where('personnel_id', $personnelId)
                ->where('rate_type', $rateType)
                ->where('effective_from', '<=', $effectiveTo ?? '9999-12-31')
                ->where(function ($q) use ($effectiveFrom) {
                    $q->whereNull('effective_to')->orWhere('effective_to', '>=', $effectiveFrom);
                })
                ->exists();
            if ($overlap) throw ValidationException::withMessages(['effective_from' => 'بازه نرخ دستمزد با نرخ موجود هم‌پوشانی دارد.']);

            return DB::table('personnel_labor_rates')->insertGetId([
                'company_id'=>$companyId,'personnel_id'=>$personnelId,'rate_type'=>$rateType,'rate'=>$rate,
                'effective_from'=>$effectiveFrom,'effective_to'=>$effectiveTo,'created_at'=>now(),'updated_at'=>now(),
            ]);
        });
    }

    public function record(
        int $companyId,
        int $productionOperationRunId,
        int $personnelId,
        string $measureType,
        float $measureQuantity,
        string $workedAt,
        int $createdByUserId,
        ?string $notes = null
    ): int {
        if (!in_array($measureType, self::MEASURE_TYPES, true) || $measureQuantity <= 0) {
            throw ValidationException::withMessages(['measure_quantity' => 'نوع یا مقدار کارکرد نامعتبر است.']);
        }

        $context = DB::table('production_operation_runs as r')
            ->join('production_stage_runs as sr', 'sr.id', '=', 'r.production_stage_run_id')
            ->join('productions as p', 'p.id', '=', 'sr.production_id')
            ->where('r.id', $productionOperationRunId)
            ->where('p.company_id', $companyId)
            ->select('r.id','sr.production_stage_id','p.id as production_id','p.fiscal_year_id')
            ->first();
        if (!$context) throw ValidationException::withMessages(['production_operation_run_id' => 'عملیات تولید متعلق به شرکت نیست.']);

        $accountId = DB::table('companies')->where('id', $companyId)->value('account_id');
        if (!DB::table('personnel')->where('id',$personnelId)->where('account_id',$accountId)->where('is_active',true)->exists()) {
            throw ValidationException::withMessages(['personnel_id' => 'پرسنل فعال شرکت معتبر نیست.']);
        }

        $rateType = $measureType === 'hours' ? 'hourly' : 'per_unit';
        $rate = DB::table('personnel_labor_rates')
            ->where('company_id',$companyId)->where('personnel_id',$personnelId)->where('rate_type',$rateType)
            ->where('effective_from','<=',substr($workedAt,0,10))
            ->where(function($q) use ($workedAt){ $q->whereNull('effective_to')->orWhere('effective_to','>=',substr($workedAt,0,10)); })
            ->orderByDesc('effective_from')->orderByDesc('id')->value('rate');
        if ($rate === null) throw ValidationException::withMessages(['measure_quantity' => 'برای تاریخ کارکرد نرخ دستمزد معتبر ثبت نشده است.']);

        return DB::table('production_labor_entries')->insertGetId([
            'company_id'=>$companyId,'fiscal_year_id'=>$context->fiscal_year_id,'production_id'=>$context->production_id,
            'production_stage_id'=>$context->production_stage_id,'production_operation_run_id'=>$productionOperationRunId,
            'personnel_id'=>$personnelId,'measure_type'=>$measureType,'measure_quantity'=>$measureQuantity,
            'unit_rate'=>(float)$rate,'total_cost'=>$measureQuantity*(float)$rate,'worked_at'=>$workedAt,
            'status'=>'pending','created_by_user_id'=>$createdByUserId,'notes'=>$notes,'created_at'=>now(),'updated_at'=>now(),
        ]);
    }

    public function review(int $companyId, int $entryId, int $reviewerUserId, bool $approve, ?string $reason = null): void
    {
        DB::transaction(function () use ($companyId,$entryId,$reviewerUserId,$approve,$reason) {
            $entry=DB::table('production_labor_entries')->where('id',$entryId)->where('company_id',$companyId)->lockForUpdate()->first();
            if(!$entry || $entry->status!=='pending') throw ValidationException::withMessages(['entry'=>'کارکرد در وضعیت قابل بررسی نیست.']);
            if($approve){
                DB::table('production_labor_entries')->where('id',$entryId)->update(['status'=>'approved','reviewed_by_user_id'=>$reviewerUserId,'reviewed_at'=>now(),'updated_at'=>now()]);
                DB::table('production_labor_costs')->insert([
                    'labor_entry_id'=>$entry->id,'company_id'=>$entry->company_id,'fiscal_year_id'=>$entry->fiscal_year_id,
                    'production_id'=>$entry->production_id,'production_stage_id'=>$entry->production_stage_id,
                    'production_operation_run_id'=>$entry->production_operation_run_id,'personnel_id'=>$entry->personnel_id,
                    'measure_type'=>$entry->measure_type,'measure_quantity'=>$entry->measure_quantity,'unit_rate'=>$entry->unit_rate,
                    'total_cost'=>$entry->total_cost,'worked_at'=>$entry->worked_at,'approved_at'=>now(),
                    'approved_by_user_id'=>$reviewerUserId,'created_at'=>now(),'updated_at'=>now(),
                ]);
            } else {
                DB::table('production_labor_entries')->where('id',$entryId)->update(['status'=>'rejected','reviewed_by_user_id'=>$reviewerUserId,'reviewed_at'=>now(),'rejection_reason'=>$reason,'updated_at'=>now()]);
            }
        });
    }
}