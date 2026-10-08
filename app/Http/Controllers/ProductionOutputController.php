<?php

namespace App\Http\Controllers;

use App\Services\ProductionOutputService;
use App\Support\CompanyAuthorization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class ProductionOutputController extends Controller
{
    public function index(Request $request)
    {
        $companyId = CompanyAuthorization::authorize($request, 'production.output.view');
        $rows = DB::table('production_outputs as po')
            ->join('productions as p','p.id','=','po.production_id')
            ->join('goods as g','g.id','=','po.goods_id')
            ->join('locations as l','l.id','=','po.warehouse_location_id')
            ->where('po.company_id',$companyId)
            ->select('po.*','p.number as production_number','g.name as goods_name','l.name as location_name')
            ->latest('po.id')->paginate(20);
        return view('production.outputs.index', compact('rows'));
    }

    public function create(Request $request)
    {
        $companyId = CompanyAuthorization::authorize($request, 'production.output.create');
        $productions = DB::table('productions')->where('company_id',$companyId)->where('status','completed')->orderByDesc('id')->get();
        $locations = DB::table('locations')->where('company_id',$companyId)->where('type','warehouse')->where('is_active',true)->orderBy('name')->get();
        return view('production.outputs.create', compact('productions','locations'));
    }

    public function store(Request $request, ProductionOutputService $service)
    {
        $companyId = CompanyAuthorization::authorize($request, 'production.output.create');
        $data = $request->validate([
            'production_id'=>['required','integer'],
            'order_id'=>['nullable','integer'],
            'warehouse_location_id'=>['required','integer'],
            'quantity'=>['required','numeric','gt:0'],
            'produced_at'=>['required','date'],
            'notes'=>['nullable','string'],
        ]);
        $service->create($companyId,(int)$data['production_id'],$data['order_id']??null,(int)$data['warehouse_location_id'],(float)$data['quantity'],(int)$request->user()->id,$data['produced_at'],$data['notes']??null);
        return redirect()->route('production.outputs.index')->with('success','خروجی تولید ثبت شد و برای تأیید انبار ارسال شد.');
    }

    public function confirm(Request $request, int $output, ProductionOutputService $service)
    {
        $companyId = CompanyAuthorization::authorize($request, 'production.output.confirm');
        $service->confirm($companyId,$output,(int)$request->user()->id);
        return back()->with('success','خروجی تولید تأیید شد و وارد موجودی شد.');
    }

    public function reject(Request $request, int $output, ProductionOutputService $service)
    {
        $companyId = CompanyAuthorization::authorize($request, 'production.output.confirm');
        $data = $request->validate(['rejection_reason'=>['required','string','max:1000']]);
        $service->reject($companyId,$output,(int)$request->user()->id,$data['rejection_reason']);
        return back()->with('success','خروجی تولید رد شد.');
    }

    public function receive(Request $request, int $output, ProductionOutputService $service)
    {
        $companyId = CompanyAuthorization::authorize($request, 'production.output.receive');
        $service->confirm($companyId,$output,(int)$request->user()->id);
        return back()->with('success','دریافت کالای ساخته‌شده ثبت شد.');
    }
}
