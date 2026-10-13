<?php

namespace App\\Http\\Controllers;

use App\\Services\\ProductionOutputService;
use App\\Support\\CompanyAuthorization;
use Illuminate\\Http\\Request;
use Illuminate\\Support\\Facades\\DB;

final class ProductionOutputController extends Controller
{
    public function index(Request $request)
    {
        $companyId = CompanyAuthorization::authorize($request, 'production.output.view');

        $outputRows = DB::table('production_outputs as po')
            ->join('productions as p', 'p.id', '=', 'po.production_id')
            ->join('goods as g', 'g.id', '=', 'po.goods_id')
            ->join('locations as l', 'l.id', '=', 'po.warehouse_location_id')
            ->where('po.company_id', $companyId)
            ->selectRaw("'output' as row_type, po.id as row_id, po.id as output_id, NULL as report_id, p.number as production_number, po.produced_at as row_date, NULL as section_name, g.name as goods_name, po.quantity, po.status, l.name as location_name");

        // Shift-report quantities have already been posted to inventory.
        // Include them in the same display list, but never expose output actions for them.
        $shiftRows = DB::table('production_shift_reports as r')
            ->join('goods as g', 'g.id', '=', 'r.goods_id')
            ->leftJoin('production_sections as s', 's.id', '=', 'r.production_section_id')
            ->leftJoin('locations as section_location', 'section_location.id', '=', 's.location_id')
            ->leftJoin('locations as l', 'l.id', '=', 'r.warehouse_location_id')
            ->where('r.company_id', $companyId)
            ->where('r.warehouse_quantity', '>', 0)
            ->selectRaw("'shift' as row_type, r.id as row_id, NULL as output_id, r.id as report_id, CONCAT('گزارش شیفت #', r.id) as production_number, COALESCE(r.warehouse_received_at, r.work_date) as row_date, section_location.name as section_name, g.name as goods_name, r.warehouse_quantity as quantity, r.status, l.name as location_name");

        $combinedRows = $outputRows->unionAll($shiftRows);

        $rows = DB::query()
            ->fromSub($combinedRows, 'combined_outputs')
            ->orderByDesc('row_date')
            ->orderByDesc('row_id')
            ->paginate(20)
            ->withQueryString();

        return view('production.outputs.index', compact('rows'));
    }

    public function create(Request $request)
    {
        $companyId = CompanyAuthorization::authorize($request, 'production.output.create');
        $productions = DB::table('productions as p')->join('goods as g','g.id','=','p.goods_id')
            ->where('p.company_id',$companyId)->where('p.status','completed')
            ->orderByDesc('p.id')->get(['p.id','p.number','p.goods_id','g.code as goods_code','g.name as goods_name']);
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
        $service->approve($companyId,$output,(int)$request->user()->id);
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
        $service->receive($companyId,$output,(int)$request->user()->id);
        return back()->with('success','دریافت کالای ساخته‌شده ثبت شد.');
    }
}
