<?php

namespace App\Http\Controllers;

use App\Services\InventoryService;
use App\Support\CompanyAuthorization;
use Illuminate\Support\Facades\Schema;
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
                'reporter.name as reporter_name', 'worker.name as worker_name', 'reviewer.name as reviewer_name', 'warehouse.name as warehouse_name')
            ->latest('r.work_date')->latest('r.id')->paginate(30);
        $sections = DB::table('production_sections as s')
            ->leftJoin('locations as l', 'l.id', '=', 's.location_id')
            ->where('l.company_id', $companyId)->orderBy('l.name')->get(['s.id', 'l.name']);
        $goods = DB::table('goods')->where('company_id', $companyId)->where('is_active', true)->where('producible', true)->orderBy('name')->get(['id', 'code', 'name']);
        $accountId = DB::table('companies')->where('id', $companyId)->value('account_id');
        $personnel = DB::table('personnel as p')
            ->join('company_user as cu', 'cu.user_id', '=', 'p.user_id')
            ->join('roles as role', 'role.id', '=', 'cu.role_id')
            ->where('p.account_id', $accountId)->where('p.is_active', true)
            ->where('cu.company_id', $companyId)->where('cu.is_active', true)
            ->where(function ($query) {
                $query->whereIn('role.slug', ['production', 'production_worker', 'worker_production'])
                    ->orWhereIn('role.name', ['تولید', 'کارگر تولید', 'کارگرِ تولید']);
            })
            ->orderBy('p.name')->distinct()->get(['p.id', 'p.code', 'p.name']);
        $materials = DB::table('goods')->where('company_id', $companyId)->where('is_active', true)
            ->where('producible', true)->orderBy('name')->get(['id', 'code', 'name']);
        $canReview = $request->user()->hasCompanyPermission($companyId, 'production.operation.review');
        $canReceive = $request->user()->hasCompanyPermission($companyId, 'production.output.receive');
        $warehouses = $canReceive ? DB::table('locations')->where('company_id', $companyId)->where('type', 'warehouse')->where('is_active', true)->orderBy('name')->get(['id', 'name']) : collect();
        $materialUsage = collect();
        if (Schema::hasTable('production_shift_report_materials')) {
            $reportIds = $rows->getCollection()->pluck('id');
            if ($reportIds->isNotEmpty()) {
                $materialUsage = DB::table('production_shift_report_materials as rm')
                    ->join('goods as mg', 'mg.id', '=', 'rm.goods_id')
                    ->where('rm.company_id', $companyId)
                    ->whereIn('rm.production_shift_report_id', $reportIds)
                    ->get(['rm.production_shift_report_id', 'rm.quantity', 'rm.scrap_quantity', 'mg.name', 'mg.code'])
                    ->groupBy('production_shift_report_id');
            }
        }
        return view('production.shift-reports.index', compact('rows', 'sections', 'goods', 'personnel', 'materials', 'materialUsage', 'canReview', 'canReceive', 'warehouses'));
    }

    public function store(Request $request)
    {
        $companyId = CompanyAuthorization::authorize($request, 'production.operation.create');
        $data = $request->validate([
            'production_section_id' => ['required', 'integer'],
            'goods_id' => ['required', 'integer'],
            'personnel_id' => ['required', 'integer'],
            'worked_hours' => ['required', 'numeric', 'gt:0', 'lte:24'],
            'overtime_hours' => ['nullable', 'numeric', 'gte:0', 'lte:24'],
            'holiday_hours' => ['nullable', 'numeric', 'gte:0', 'lte:24'],
            'materials' => ['nullable', 'array'],
            'materials.*.goods_id' => ['nullable', 'integer'],
            'materials.*.quantity' => ['nullable', 'numeric', 'gt:0'],
            'materials.*.scrap_quantity' => ['nullable', 'numeric', 'gte:0'],
            'work_date' => ['required', 'date'],
            'produced_quantity' => ['required', 'numeric', 'gt:0'],
            'scrap_quantity' => ['nullable', 'numeric', 'gte:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $section = DB::table('production_sections as s')->join('locations as l', 'l.id', '=', 's.location_id')->where('s.id', $data['production_section_id'])->where('l.company_id', $companyId)->exists();
        $good = DB::table('goods')->where('id', $data['goods_id'])->where('company_id', $companyId)->where('is_active', true)->where('producible', true)->exists();
        $accountId = DB::table('companies')->where('id', $companyId)->value('account_id');
        $worker = DB::table('personnel')->where('id', $data['personnel_id'])->where('account_id', $accountId)->where('is_active', true)->exists();
        if (!$section) throw ValidationException::withMessages(['production_section_id' => 'قسمت تولید معتبر نیست.']);
        if (!$good) throw ValidationException::withMessages(['goods_id' => 'کالا معتبر نیست.']);
        if (!$worker) throw ValidationException::withMessages(['personnel_id' => 'فقط پرسنل فعال دارای نقش تولید را انتخاب کنید.']);
        $materialRows = [];
        foreach (($data['materials'] ?? []) as $line) {
            $materialId = (int) ($line['goods_id'] ?? 0);
            $materialQuantity = $line['quantity'] ?? null;
            if ($materialId === 0 && ($materialQuantity === null || $materialQuantity === '')) continue;
            if ($materialId === 0 || $materialQuantity === null || $materialQuantity === '') {
                throw ValidationException::withMessages(['materials' => 'برای هر ماده اولیه، هم انتخاب ماده و هم مقدار مصرف را وارد کنید.']);
            }
            $validMaterial = DB::table('goods')->where('id', $materialId)->where('company_id', $companyId)
                ->where('is_active', true)->where('producible', true)->exists();
            if (!$validMaterial) throw ValidationException::withMessages(['materials' => 'یکی از مواد اولیه انتخاب‌شده معتبر نیست.']);
            $materialScrap = (float) ($line['scrap_quantity'] ?? 0);
            if ($materialScrap > (float) $materialQuantity) {
                throw ValidationException::withMessages(['materials' => 'ضایعات هر ماده اولیه نمی‌تواند از مقدار مصرف آن بیشتر باشد.']);
            }
            $materialRows[] = ['goods_id' => $materialId, 'quantity' => (float) $materialQuantity, 'scrap_quantity' => $materialScrap];
        }
        $scrap = (float) ($data['scrap_quantity'] ?? 0);
        if ($scrap > (float) $data['produced_quantity']) {
            throw ValidationException::withMessages(['scrap_quantity' => 'ضایعات محصول نمی‌تواند از تعداد تولیدشده بیشتر باشد.']);
        }

        $reportId = DB::table('production_shift_reports')->insertGetId([
            'company_id' => $companyId,
            'production_section_id' => (int)$data['production_section_id'],
            'goods_id' => (int)$data['goods_id'],
            'personnel_id' => (int)$data['personnel_id'],
            'reported_by_user_id' => (int)$request->user()->id,
            'shift_name' => 'کارکرد ' . (float)$data['worked_hours'] . ' ساعت',
            'worked_hours' => (float)$data['worked_hours'],
            'overtime_hours' => (float)($data['overtime_hours'] ?? 0),
            'holiday_hours' => (float)($data['holiday_hours'] ?? 0),
            'work_date' => $data['work_date'],
            'produced_quantity' => (float)$data['produced_quantity'],
            'scrap_quantity' => $scrap,
            'status' => 'pending',
            'notes' => $data['notes'] ?? null,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        foreach ($materialRows as $line) {
            DB::table('production_shift_report_materials')->insert([
                'company_id' => $companyId,
                'production_shift_report_id' => $reportId,
                'goods_id' => $line['goods_id'],
                'quantity' => $line['quantity'],
                'scrap_quantity' => $line['scrap_quantity'],
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
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
