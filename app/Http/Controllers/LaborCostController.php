<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLaborEntryRequest;
use App\Services\LaborCostService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class LaborCostController
{
    public function store(StoreLaborEntryRequest $request, LaborCostService $service): JsonResponse
    {
        $companyId = (int) $request->user()->current_company_id;
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
        $validated=$request->validate(['approve'=>['required','boolean'],'reason'=>['nullable','string']]);
        $service->review((int)$request->user()->current_company_id,$laborEntry,(int)$request->user()->id,(bool)$validated['approve'],$validated['reason']??null);
        return response()->json(['status'=>$validated['approve']?'approved':'rejected']);
    }

    public function setRate(Request $request, LaborCostService $service): JsonResponse
    {
        $validated=$request->validate([
            'personnel_id'=>['required','integer'],'rate_type'=>['required','in:hourly,per_unit'],
            'rate'=>['required','numeric','min:0'],'effective_from'=>['required','date'],'effective_to'=>['nullable','date','after_or_equal:effective_from'],
        ]);
        $id=$service->setRate((int)$request->user()->current_company_id,(int)$validated['personnel_id'],(string)$validated['rate_type'],(float)$validated['rate'],$validated['effective_from'],$validated['effective_to']??null);
        return response()->json(['id'=>$id],201);
    }
}