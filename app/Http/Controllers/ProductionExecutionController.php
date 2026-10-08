<?php

namespace App\Http\Controllers;

use App\Services\ProductionExecutionService;
use App\Support\CompanyAuthorization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class ProductionExecutionController extends Controller
{
    public function index(Request $request)
    {
        $companyId=CompanyAuthorization::authorize($request,'production.operation.view');
        $rows=DB::table('production_operation_runs as r')
            ->join('production_operations as o','o.id','=','r.production_operation_id')
            ->join('production_stage_runs as sr','sr.id','=','r.production_stage_run_id')
            ->join('production_stages as s','s.id','=','sr.production_stage_id')
            ->join('productions as p','p.id','=','sr.production_id')
            ->where('p.company_id',$companyId)
            ->select('r.*','o.name as operation_name','s.name as stage_name','p.number as production_number')
            ->latest('r.id')->paginate(20);
        return view('production.execution.index',compact('rows'));
    }

    public function create(Request $request)
    {
        $companyId=CompanyAuthorization::authorize($request,'production.operation.create');
        $runs=DB::table('production_operation_runs as r')
            ->join('production_stage_runs as sr','sr.id','=','r.production_stage_run_id')
            ->join('production_operations as o','o.id','=','r.production_operation_id')
            ->join('productions as p','p.id','=','sr.production_id')
            ->where('p.company_id',$companyId)->whereIn('r.status',['pending','in_progress'])
            ->select('r.id','p.number','o.name')->orderByDesc('r.id')->get();
        $goods=DB::table('goods')->where('company_id',$companyId)->where('is_active',true)->orderBy('name')->get(['id','code','name']);
        return view('production.execution.create',compact('runs','goods'));
    }

    public function store(Request $request,ProductionExecutionService $service)
    {
        $companyId=CompanyAuthorization::authorize($request,'production.operation.create');
        $data=$request->validate([
            'production_operation_run_id'=>['required','integer'],
            'completed_at'=>['nullable','date'],
            'notes'=>['nullable','string'],
            'inputs'=>['nullable','array'],
            'inputs.*.goods_id'=>['required_with:inputs','integer'],
            'inputs.*.quantity'=>['required_with:inputs','numeric','gt:0'],
            'inputs.*.source_inventory_movement_id'=>['nullable','integer'],
            'outputs'=>['nullable','array'],
            'outputs.*.goods_id'=>['required_with:outputs','integer'],
            'outputs.*.quantity'=>['required_with:outputs','numeric','gt:0'],
            'outputs.*.output_type'=>['nullable','in:product,byproduct'],
            'scraps'=>['nullable','array'],
            'scraps.*.goods_id'=>['required_with:scraps','integer'],
            'scraps.*.quantity'=>['required_with:scraps','numeric','gt:0'],
            'scraps.*.reason'=>['nullable','string','max:255'],
        ]);
        $service->submit($companyId,(int)$data['production_operation_run_id'],$data['inputs']??[],$data['outputs']??[],$data['scraps']??[],(int)$request->user()->id,$data['completed_at']??null,$data['notes']??null);
        return redirect()->route('production.execution.index')->with('success','عملیات تولید ثبت و برای تأیید ارسال شد.');
    }

    public function review(Request $request,int $run,ProductionExecutionService $service)
    {
        $companyId=CompanyAuthorization::authorize($request,'production.operation.review');
        $data=$request->validate(['approve'=>['required','boolean'],'reason'=>['nullable','string','max:1000']]);
        $service->review($companyId,$run,(int)$request->user()->id,(bool)$data['approve'],$data['reason']??null);
        return back()->with('success',$data['approve']?'عملیات تأیید شد.':'عملیات رد شد.');
    }
}
