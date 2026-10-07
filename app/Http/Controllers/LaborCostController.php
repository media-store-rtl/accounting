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
        $id = $service->record(
            $companyId,
            (int)$request->integer('production_operation_run_id'),
            (int)$request->integer('personnel_id'),
            (string)$request->input('measure_type'),
            (float)$request->input('measure_quantity'),
            (string)$request->input('worked_at'),
            (int)$request->user()->id,
            $request->input('notes')
        );
        return response()->json(['id'=>$id,'status'=>'pending'],201);
    }

    public function review(Request $request, LaborCostService $service, int $laborEntry): JsonResponse
    {
        $companyId = CompanyAuthorization::authorize($request, 'production.labor.review');
        $validated=$request->validate(['approve'=>['required','boolean'],'reason'=>['nullable','string']]);
        $service->review($companyId,$laborEntry,(int)$request->user()->id,(bool)$validated['approve'],$validated['reason']??null);
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