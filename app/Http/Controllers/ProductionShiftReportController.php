<?php

namespace App\Http\Controllers;

use App\Services\InventoryService;
use App\Support\CompanyAuthorization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ProductionShiftReportController extends Controller
{
    public function index(Request $request)
    {
        $companyId = CompanyAuthorization::authorize($request, 'production.operation.view');
        $rows = DB::table('production_shift_reports as r')
            ->join('production_sections as s', 's.id', '=', 'r.production_section_id')
            ->leftJoin('locations as sec_location', 'sec_location.id', '=', 's.location_id')
            ->join('goods as g', 'g.id', '=', 'r.goods_id')
            ->leftJoin('personnel as worker', 'worker.id', '=', 'r.personnel_id')
            ->join('users as reporter', 'reporter.id', '=', 'r.reported_by_user_id')
            ->leftJoin('users as reviewer', 'reviewer.id', '=', 'r.reviewed_by_user_id')
            ->leftJoin('locations as warehouse', 'warehouse.id', '=', 'r.warehouse_location_id')
            ->where('r.company_id', $companyId)
            ->select('r.*', 'sec_location.name as section_name', 'g.name as goods_name', 'g.code as goods_code',
                'reporter.name as reporter_name', 'reviewer.name as reviewer_name', 'warehouse.name as warehouse_name')
            ->latest('r.work_date')->latest('r.id')->paginate(30);
        $sections = DB::table('production_sections as s')
            ->leftJoin('locations as l', 'l.id', '=', 's.location_id')
            ->where('l.company_id', $companyId)->orderBy('l.name')->get(['s.id', 'l.name']);
        $goods = DB::table('goods')->where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']);
        $canReview = $request->user()->hasCompanyPermission($companyId, 'production.operation.review');
        $canReceive = $request->user()->hasCompanyPermission($companyId, 'production.output.receive');
        $warehouses = $canReceive ? DB::table('locations')->where('company_id', $companyId)->where('type', 'warehouse')->where('is_active', true)->orderBy('name')->get(['id', 'name']) : collect();
        return view('production.shift-reports.index', compact('rows', 'sections', 'goods', 'canReview', 'canReceive', 'warehouses'));
    }

    public function store(Request $request)
    {
        $companyId = CompanyAuthorization::authorize($request, 'production.operation.create');
        $data = $request->validate([
            'production_section_id' => ['required', 'integer'],
            'goods_id' => ['required', 'integer'],
            'shift_name' => ['required', 'string', 'max:80'],
            'work_date' => ['required', 'date'],
            'produced_quantity' => ['required', 'numeric', 'gt:0'],
            'scrap_quantity' => ['nullable', 'numeric', 'gte:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $section = DB::table('production_sections as s')->join('locations as l', 'l.id', '=', 's.location_id')->where('s.id', $data['production_section_id'])->where('l.company_id', $companyId)->exists();
        $good = DB::table('goods')->where('id', $data['goods_id'])->where('company_id', $companyId)->where('is_active', true)->exists();
        if (!$section) throw ValidationException::withMessages(['production_section_id' => 'قسمت تولید معتبر نیست.']);
        if (!$good) throw ValidationException::withMessages(['goods_id' => 'کالا معتبر نیست.']);
        $scrap = (float)($data['scrap_quantity'] ?? 0);
        if ($scrap > (float)$data['produced_quantity']) throw ValidationException::withMessages(['scrap_quantity' => 'ضایعات نمی‌تواند از کل مقدار ثبت‌شده بیشتر باشد.']);

        DB::table('production_shift_reports')->insert([
            'company_id' => $companyId,
            'production_section_id' => (int)$data['production_section_id'],
            'goods_id' => (int)$data['goods_id'],
            'reported_by_user_id' => (int)$request->user()->id,
            'shift_name' => trim($data['shift_name']),
            'work_date' => $data['work_date'],
            'produced_quantity' => (float)$data['produced_quantity'],
            'scrap_quantity' => $scrap,
            'status' => 'pending',
            'notes' => $data['notes'] ?? null,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        return redirect()->route('production.shift-reports.index')->with('success', 'گزارش شیفت ثبت شد و برای تأیید سرپرست ارسال شد.');
    }

    public function review(Request $request, int $report)
    {
        $companyId = CompanyAuthorization::authorize($request, 'production.operation.review');
        $data = $request->validate(['approve' => ['required', 'boolean'], 'review_notes' => ['nullable', 'string', 'max:1000']]);
        DB::transaction(function () use ($companyId, $report, $data, $request) {
            $row = DB::table('production_shift_reports')->where('company_id', $companyId)->where('id', $report)->lockForUpdate()->first();
            if (!$row || $row->status !== 'pending') throw ValidationException::withMessages(['report' => 'این گزارش دیگر در انتظار بررسی نیست.']);
            DB::table('production_shift_reports')->where('id', $report)->where('company_id', $companyId)->update([
                'status' => $data['approve'] ? 'approved' : 'rejected',
                'reviewed_by_user_id' => (int)$request->user()->id,
                'review_notes' => $data['review_notes'] ?? null,
                'reviewed_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('audit_trails')->insert([
                'company_id' => $companyId, 'user_id' => (int)$request->user()->id, 'module' => 'production',
                'action' => $data['approve'] ? 'production_shift_report.approve' : 'production_shift_report.reject',
                'auditable_type' => 'production_shift_reports', 'auditable_id' => $report,
                'before' => json_encode((array)$row),
                'after' => json_encode((array)DB::table('production_shift_reports')->where('id', $report)->first()),
                'method' => 'SERVICE', 'status_code' => 200, 'created_at' => now(), 'updated_at' => now(),
            ]);
        });
        return back()->with('success', $data['approve'] ? 'گزارش شیفت تأیید شد؛ اکنون می‌توان مقدار تحویلی به انبار را ثبت کرد.' : 'گزارش شیفت رد شد.');
    }

    public function receive(Request $request, int $report, InventoryService $inventory)
    {
        $companyId = CompanyAuthorization::authorize($request, 'production.output.receive');
        $data = $request->validate([
            'warehouse_location_id' => ['required', 'integer'],
            'warehouse_quantity' => ['required', 'numeric', 'gt:0'],
        ]);
        DB::transaction(function () use ($companyId, $report, $data, $request, $inventory) {
            $row = DB::table('production_shift_reports')->where('company_id', $companyId)->where('id', $report)->lockForUpdate()->first();
            if (!$row || $row->status !== 'approved') throw ValidationException::withMessages(['report' => 'فقط گزارش تأییدشده با مقدار باقی‌مانده قابل تحویل به انبار است.']);
            $quantity = (float)$data['warehouse_quantity'];
            $available = max(0, (float)$row->produced_quantity - (float)$row->scrap_quantity);
            $alreadyReceived = (float)($row->warehouse_quantity ?? 0);
            if ($quantity > ($available - $alreadyReceived) + 0.0000001) throw ValidationException::withMessages(['warehouse_quantity' => 'مقدار تحویل از باقیمانده تولید سالم بیشتر است.']);
            if ($alreadyReceived > 0 && (int)$row->warehouse_location_id !== (int)$data['warehouse_location_id']) throw ValidationException::withMessages(['warehouse_location_id' => 'برای تحویل‌های یک گزارش، همان انبار قبلی را انتخاب کنید.']);
            $warehouse = DB::table('locations')->where('id', (int)$data['warehouse_location_id'])->where('company_id', $companyId)->where('type', 'warehouse')->where('is_active', true)->exists();
            if (!$warehouse) throw ValidationException::withMessages(['warehouse_location_id' => 'انبار معتبر نیست.']);
            $inventory->receiveFinishedGoods($companyId, (int)$data['warehouse_location_id'], (int)$row->goods_id, $quantity,
                'production_shift_reports', $report, ['production_section_id' => (int)$row->production_section_id, 'shift_name' => $row->shift_name, 'work_date' => $row->work_date, 'reported_by_user_id' => (int)$row->reported_by_user_id]);
            DB::table('production_shift_reports')->where('id', $report)->where('company_id', $companyId)->update([
                'warehouse_location_id' => (int)$data['warehouse_location_id'], 'warehouse_quantity' => $alreadyReceived + $quantity,
                'status' => ($alreadyReceived + $quantity >= $available - 0.0000001) ? 'received' : 'approved',
                'warehouse_received_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('audit_trails')->insert([
                'company_id' => $companyId, 'user_id' => (int)$request->user()->id, 'module' => 'production',
                'action' => 'production_shift_report.warehouse_receive', 'auditable_type' => 'production_shift_reports', 'auditable_id' => $report,
                'before' => json_encode((array)$row),
                'after' => json_encode((array)DB::table('production_shift_reports')->where('id', $report)->first()),
                'method' => 'SERVICE', 'status_code' => 200, 'created_at' => now(), 'updated_at' => now(),
            ]);
        });
        return back()->with('success', 'مقدار تحویلی ثبت شد و موجودی انبار افزایش یافت.');
    }
}
