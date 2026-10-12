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
        $handovers=DB::table('production_stage_handovers as h')
            ->join('productions as p','p.id','=','h.production_id')
            ->join('production_stage_runs as fsr','fsr.id','=','h.from_stage_run_id')
            ->join('production_stages as fs','fs.id','=','fsr.production_stage_id')
            ->leftJoin('production_sections as fsec','fsec.id','=','fs.production_section_id')
            ->join('production_stage_runs as tsr','tsr.id','=','h.to_stage_run_id')
            ->join('production_stages as ts','ts.id','=','tsr.production_stage_id')
            ->leftJoin('production_sections as tsec','tsec.id','=','ts.production_section_id')
            ->where('h.company_id',$companyId)
            ->select('h.*','p.number as production_number','fsec.name as from_section_name','tsec.name as to_section_name','ts.name as to_stage_name')
            ->latest('h.id')->limit(50)->get();
        return view('production.execution.index',compact('rows','handovers'));
    }

    public function create(Request $request)
    {
        $companyId=CompanyAuthorization::authorize($request,'production.operation.create');
        $runs=DB::table('production_operation_runs as r')
            ->join('production_stage_runs as sr','sr.id','=','r.production_stage_run_id')
            ->join('production_operations as o','o.id','=','r.production_operation_id')
            ->join('production_stages as s','s.id','=','sr.production_stage_id')
            ->join('productions as p','p.id','=','sr.production_id')
            ->where('p.company_id',$companyId)->where('p.status','in_progress')->whereIn('r.status',['pending','in_progress'])
            ->whereIn('sr.status',['pending','in_progress'])
            ->select('r.id','p.number','o.name','sr.id as stage_run_id','sr.production_id')
            ->orderBy('sr.id')->orderBy('r.sequence')->get()
            ->filter(function($run) {
                $previous=DB::table('production_stage_runs')->where('production_id',$run->production_id)->where('id','<',$run->stage_run_id)->orderByDesc('id')->first();
                return !$previous || DB::table('production_stage_handovers')->where('from_stage_run_id',$previous->id)->where('to_stage_run_id',$run->stage_run_id)->where('status','received')->exists();
            })->values();
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

    public function receiveHandover(Request $request,int $handover,ProductionExecutionService $service)
    {
        $companyId=CompanyAuthorization::authorize($request,'production.operation.create');
        $data=$request->validate([
            'received_quantity'=>['required','numeric','gt:0'],
            'notes'=>['nullable','string','max:1000'],
        ]);
        $service->receiveHandover($companyId,$handover,(int)$request->user()->id,(float)$data['received_quantity'],$data['notes']??null);
        return back()->with('success','دریافت مقدار در قسمت بعدی ثبت شد؛ اجرای مرحله اکنون مجاز است.');
    }

    public function review(Request $request,int $run,ProductionExecutionService $service)
    {
        $companyId=CompanyAuthorization::authorize($request,'production.operation.review');
        $data=$request->validate(['approve'=>['required','boolean'],'reason'=>['nullable','string','max:1000']]);
        $service->review($companyId,$run,(int)$request->user()->id,(bool)$data['approve'],$data['reason']??null);
        return back()->with('success',$data['approve']?'عملیات تأیید شد.':'عملیات رد شد.');
    }
}
