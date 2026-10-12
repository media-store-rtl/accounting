<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLaborEntryRequest;
use App\Services\LaborCostService;
use App\Support\CompanyAuthorization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class LaborCostController
{
    public function store(StoreLaborEntryRequest $request, LaborCostService $service): JsonResponse
    {
        $companyId = CompanyAuthorization::authorize($request, 'production.labor.create');
        $id = $service->recordShiftEntry(
            $companyId,
            (int)$request->integer('fiscal_year_id'),
            (int)$request->integer('production_section_id'),
            (int)$request->integer('goods_id'),
            (int)$request->integer('personnel_id'),
            (float)$request->input('hours_worked'),
            (float)$request->input('quantity_completed'),
            (string)$request->input('pay_basis'),
            (string)$request->input('worked_at'),
            $request->input('shift_name'),
            (int)$request->user()->id,
            $request->input('notes')
        );
        return response()->json(['id'=>$id,'status'=>'pending'],201);
    }

    public function review(Request $request, LaborCostService $service, int $laborEntry): JsonResponse
    {
        $companyId = CompanyAuthorization::authorize($request, 'production.labor.review');
        $validated=$request->validate([
            'approve'=>['required','boolean'],
            'reason'=>['required_if:approve,0','nullable','string','max:1000'],
        ]);
        $service->review($companyId,$laborEntry,(int)$request->user()->id,(bool)$validated['approve'],$validated['reason']??null);
        if (!$request->expectsJson()) {
            return back()->with('success', $validated['approve'] ? 'کارکرد تأیید شد.' : 'کارکرد با ثبت دلیل رد شد.');
        }
        return response()->json(['status'=>$validated['approve']?'approved':'rejected']);
    }

    public function setRate(Request $request, LaborCostService $service): JsonResponse
    {
        $companyId = CompanyAuthorization::authorize($request, 'production.labor.rate.manage');
        $validated=$request->validate([
            'personnel_id'=>['required','integer'],'rate_type'=>['required','in:hourly,per_unit'],
            'rate'=>['required','numeric','min:0'],'effective_from'=>['required','date'],'effective_to'=>['nullable','date','after_or_equal:effective_from'],
        ]);
        $id=$service->setRate($companyId,(int)$validated['personnel_id'],(string)$validated['rate_type'],(float)$validated['rate'],$validated['effective_from'],$validated['effective_to']??null);
        return response()->json(['id'=>$id],201);
    }
}