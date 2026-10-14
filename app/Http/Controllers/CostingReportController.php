<?php

namespace App\Http\Controllers;

use App\Services\CostCalculationService;
use App\Services\CostingReportService;
use App\Support\CompanyAuthorization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class CostingReportController extends Controller
{
    public function calculate(Request $request, CostCalculationService $service)
    {
        $companyId=CompanyAuthorization::authorize($request,'costing.report.view');
        $data=$request->validate(['fiscal_year_id'=>'nullable|integer','order_id'=>'nullable|integer','goods_id'=>'nullable|integer']);
        $result=$service->calculate($companyId,(int)$request->user()->id,$data['fiscal_year_id']??null,$data['order_id']??null,$data['goods_id']??null);
        return $request->expectsJson() ? response()->json(['data'=>$result]) : back()->with('success','محاسبه بهای تمام‌شده ثبت و قابل ردیابی شد.');
    }

    public function index(Request $request, CostingReportService $service)
    {
        $companyId=CompanyAuthorization::authorize($request,'costing.report.view');
        $data=$request->validate([
            'fiscal_year_id'=>'nullable|integer',
            'order_id'=>'nullable|integer',
            'goods_id'=>'nullable|integer',
            'start_date'=>'nullable|date',
            'end_date'=>'nullable|date|after_or_equal:start_date',
            'personnel_id'=>'nullable|integer',
        ]);
        $personnelId=$data['personnel_id']??null;
        if($personnelId!==null) abort_unless(DB::table('personnel')->where('id',$personnelId)->where('account_id',$request->user()->account_id)->exists(),422,'کارگر انتخاب‌شده معتبر نیست.');
        $report=$service->generate($companyId,$data['fiscal_year_id']??null,$data['order_id']??null,$data['goods_id']??null,$data['start_date']??null,$data['end_date']??null,$personnelId);

        if($request->expectsJson()) return response()->json(['data'=>$report]);

        $fiscalYears=DB::table('fiscal_years')->where('company_id',$companyId)->orderByDesc('id')->get();
        $goods=DB::table('goods')->where('company_id',$companyId)->where('is_active',true)->orderBy('name')->get(['id','code','name']);
        $personnel=DB::table('personnel')->where('account_id',$request->user()->account_id)->where('is_active',true)->orderBy('name')->get(['id','name','code']);
        return view('reports.costing',compact('report','fiscalYears','goods','personnel'));
    }
}
