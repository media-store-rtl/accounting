<?php

namespace App\Http\Controllers;

use App\Services\NotificationRecipientService;
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
        $query = DB::table('production_shift_reports as r')
            ->join('production_sections as s', 's.id', '=', 'r.production_section_id')
            ->leftJoin('locations as sec_location', 'sec_location.id', '=', 's.location_id')
            ->join('goods as g', 'g.id', '=', 'r.goods_id')
            ->leftJoin('personnel as worker', 'worker.id', '=', 'r.personnel_id')
            ->join('users as reporter', 'reporter.id', '=', 'r.reported_by_user_id')
            ->leftJoin('users as reviewer', 'reviewer.id', '=', 'r.reviewed_by_user_id')
            ->leftJoin('locations as warehouse', 'warehouse.id', '=', 'r.warehouse_location_id')
            ->where('r.company_id', $companyId);
        if ($request->filled('status') && in_array($request->input('status'), ['pending', 'approved', 'rejected', 'received'], true)) $query->where('r.status', $request->input('status'));
        if ($request->filled('section_id') && is_numeric($request->input('section_id'))) $query->where('r.production_section_id', (int) $request->input('section_id'));
        if ($request->filled('personnel_id') && is_numeric($request->input('personnel_id'))) $query->where('r.personnel_id', (int) $request->input('personnel_id'));
        if ($request->filled('from')) $query->whereDate('r.work_date', '>=', $request->input('from'));
        if ($request->filled('to')) $query->whereDate('r.work_date', '<=', $request->input('to'));
        if ($request->filled('q')) {
            $term = trim((string) $request->input('q'));
            $query->where(function ($sub) use ($term) {
                if (ctype_digit($term)) $sub->orWhere('r.id', (int) $term);
                $sub->orWhere('g.name', 'like', '%' . $term . '%')
                    ->orWhere('worker.name', 'like', '%' . $term . '%')
                    ->orWhere('sec_location.name', 'like', '%' . $term . '%')
                    ->orWhere('reporter.name', 'like', '%' . $term . '%');
            });
        }
        $rows = $query->select('r.*', 'sec_location.name as section_name', 'g.name as goods_name', 'g.code as goods_code', 'g.sellable as goods_sellable',
                'reporter.name as reporter_name', 'worker.name as worker_name', 'reviewer.name as reviewer_name', 'warehouse.name as warehouse_name')
            ->latest('r.work_date')->latest('r.id')->paginate(30)->withQueryString();
        $sections = DB::table('production_sections as s')->leftJoin('locations as l', 'l.id', '=', 's.location_id')
            ->where('l.company_id', $companyId)->orderBy('l.name')->get(['s.id', 'l.name']);
        $accountId = DB::table('companies')->where('id', $companyId)->value('account_id');
        $personnel = DB::table('personnel')->where('account_id', $accountId)->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        return view('production.shift-reports.index', compact('rows', 'sections', 'personnel'));
    }

    public function create(Request $request)
    {
        $companyId = CompanyAuthorization::authorize($request, 'production.operation.create');
        $sections = DB::table('production_sections as s')->leftJoin('locations as l', 'l.id', '=', 's.location_id')
            ->where('l.company_id', $companyId)->orderBy('l.name')->get(['s.id', 'l.name']);
        $goods = DB::table('goods')->where('company_id', $companyId)->where('is_active', true)->where('producible', true)->orderBy('name')->get(['id', 'code', 'name']);
        $accountId = DB::table('companies')->where('id', $companyId)->value('account_id');
        $personnel = DB::table('personnel as p')->join('company_user as cu', 'cu.user_id', '=', 'p.user_id')
            ->join('roles as role', 'role.id', '=', 'cu.role_id')->where('p.account_id', $accountId)->where('p.is_active', true)
            ->where('cu.company_id', $companyId)->where('cu.is_active', true)
            ->where(function ($query) {
                $query->whereIn('role.slug', ['production', 'production_worker', 'worker_production'])
                    ->orWhereIn('role.name', ['تولید', 'کارگر تولید', 'کارگرِ تولید']);
            })->orderBy('p.name')->distinct()->get(['p.id', 'p.code', 'p.name']);
        $stages = DB::table('production_stages as ps')->join('production_routes as pr', 'pr.id', '=', 'ps.production_route_id')
            ->where('pr.company_id', $companyId)->where('ps.status', 'active')->orderBy('ps.name')->get(['ps.id', 'ps.code', 'ps.name']);
        $materials = DB::table('goods')->where('company_id', $companyId)->where('is_active', true)->where('producible', true)->orderBy('name')->get(['id', 'code', 'name']);
        return view('production.shift-reports.create', compact('sections', 'goods', 'personnel', 'materials', 'stages'));
    }

    public function show(Request $request, int $report)
    {
        $companyId = CompanyAuthorization::authorize($request, 'production.operation.view');
        $row = DB::table('production_shift_reports as r')
            ->join('production_sections as s', 's.id', '=', 'r.production_section_id')
            ->leftJoin('locations as sec_location', 'sec_location.id', '=', 's.location_id')
            ->join('goods as g', 'g.id', '=', 'r.goods_id')
            ->leftJoin('personnel as worker', 'worker.id', '=', 'r.personnel_id')
            ->join('users as reporter', 'reporter.id', '=', 'r.reported_by_user_id')
            ->leftJoin('users as reviewer', 'reviewer.id', '=', 'r.reviewed_by_user_id')
            ->leftJoin('locations as warehouse', 'warehouse.id', '=', 'r.warehouse_location_id')
            ->where('r.company_id', $companyId)->where('r.id', $report)
            ->first(['r.*', 'sec_location.name as section_name', 'g.name as goods_name', 'g.code as goods_code',
                'reporter.name as reporter_name', 'worker.name as worker_name', 'reviewer.name as reviewer_name', 'warehouse.name as warehouse_name', 'g.sellable as goods_sellable']);
        abort_unless($row, 404);
        $reportedStages = DB::table('production_shift_report_stages as rs')->join('production_stages as ps', 'ps.id', '=', 'rs.production_stage_id')
            ->where('rs.company_id', $companyId)->where('rs.production_shift_report_id', $report)
            ->orderBy('rs.id')->get(['ps.id', 'ps.code', 'ps.name']);
        $materialUsage = collect();
        if (Schema::hasTable('production_shift_report_materials')) {
            $materialUsage = DB::table('production_shift_report_materials as rm')->join('goods as mg', 'mg.id', '=', 'rm.goods_id')
                ->where('rm.company_id', $companyId)->where('rm.production_shift_report_id', $report)
                ->get(['rm.goods_id', 'rm.quantity', 'rm.scrap_quantity', 'mg.name', 'mg.code']);
        }
        $canReview = $request->user()->hasCompanyPermission($companyId, 'production.operation.review');
        $canEdit = $canReview && (in_array($row->status, ['pending', 'rejected'], true) || ($row->status === 'approved' && (float) ($row->warehouse_quantity ?? 0) <= 0));
        $canReceive = $request->user()->hasCompanyPermission($companyId, 'production.output.receive');
        $warehouses = $canReceive ? DB::table('locations')->where('company_id', $companyId)->where('is_active', true)->where(function ($query) { $query->whereRaw('LOWER(TRIM(type)) = ?', ['warehouse'])
                ->orWhereIn(DB::raw('TRIM(type)'), ['مواد', 'محصول', 'ضایعات']); })->orderBy('name')->get(['id', 'name']) : collect();
        $sections = DB::table('production_sections as s')->leftJoin('locations as l', 'l.id', '=', 's.location_id')
            ->where('l.company_id', $companyId)->orderBy('l.name')->get(['s.id', 'l.name']);
        $goods = DB::table('goods')->where('company_id', $companyId)->where('is_active', true)->where('producible', true)->orderBy('name')->get(['id', 'code', 'name']);
        $accountId = DB::table('companies')->where('id', $companyId)->value('account_id');
        $personnel = DB::table('personnel')->where('account_id', $accountId)->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $stages = DB::table('production_stages as ps')->join('production_routes as pr', 'pr.id', '=', 'ps.production_route_id')
            ->where('pr.company_id', $companyId)->where('ps.status', 'active')->orderBy('ps.name')->get(['ps.id', 'ps.code', 'ps.name']);
        $materials = $goods;
        return view('production.shift-reports.show', compact('row', 'materialUsage', 'reportedStages', 'canReview', 'canEdit', 'canReceive', 'warehouses', 'sections', 'goods', 'personnel', 'stages', 'materials'));
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
            'stages' => ['required', 'array', 'min:1'],
            'stages.*.production_stage_id' => ['required', 'integer'],
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
        $stageRows = [];
        foreach ($data['stages'] as $line) {
            $stageId = (int) ($line['production_stage_id'] ?? 0);
            $validStage = DB::table('production_stages as ps')->join('production_routes as pr', 'pr.id', '=', 'ps.production_route_id')
                ->where('ps.id', $stageId)->where('pr.company_id', $companyId)->where('ps.status', 'active')->exists();
            if (!$validStage) throw ValidationException::withMessages(['stages' => 'یکی از مراحل انتخاب‌شده معتبر یا فعال نیست.']);
            $stageRows[] = $stageId;
        }
        if (count($stageRows) !== count(array_unique($stageRows))) {
            throw ValidationException::withMessages(['stages' => 'یک مرحله را در یک گزارش بیش از یک بار انتخاب نکنید.']);
        }
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
        if (count($materialRows) < 1) {
            throw ValidationException::withMessages(['materials' => 'ثبت گزارش تولید بدون حداقل یک ماده اولیه و مقدار مصرف آن امکان‌پذیر نیست.']);
        }

        if (count($materialRows) < 1) {
            throw ValidationException::withMessages(['materials' => 'گزارش باید حداقل یک ماده اولیه با مقدار مصرف معتبر داشته باشد.']);
        }

        $scrap = (float) ($data['scrap_quantity'] ?? 0);
        if ($scrap > (float) $data['produced_quantity']) {
            throw ValidationException::withMessages(['scrap_quantity' => 'ضایعات محصول نمی‌تواند از تعداد تولیدشده بیشتر باشد.']);
        }

        $reportId = DB::transaction(function () use ($companyId, $data, $request, $materialRows, $stageRows, $scrap) {
            $reportId = DB::table('production_shift_reports')->insertGetId([
                'company_id' => $companyId,
                'production_section_id' => (int) $data['production_section_id'],
                'goods_id' => (int) $data['goods_id'],
                'personnel_id' => (int) $data['personnel_id'],
                'reported_by_user_id' => (int) $request->user()->id,
                'shift_name' => 'کارکرد ' . (float) $data['worked_hours'] . ' ساعت',
                'worked_hours' => (float) $data['worked_hours'],
                'overtime_hours' => (float) ($data['overtime_hours'] ?? 0),
                'holiday_hours' => (float) ($data['holiday_hours'] ?? 0),
                'work_date' => $data['work_date'],
                'produced_quantity' => (float) $data['produced_quantity'],
                'scrap_quantity' => $scrap,
                'status' => 'pending',
                'notes' => $data['notes'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($stageRows as $stageId) {
                DB::table('production_shift_report_stages')->insert([
                    'company_id' => $companyId,
                    'production_shift_report_id' => $reportId,
                    'production_stage_id' => $stageId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            foreach ($materialRows as $line) {
                DB::table('production_shift_report_materials')->insert([
                    'company_id' => $companyId,
                    'production_shift_report_id' => $reportId,
                    'goods_id' => $line['goods_id'],
                    'quantity' => $line['quantity'],
                    'scrap_quantity' => $line['scrap_quantity'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return $reportId;
        });

        $reportDetails = DB::table('production_shift_reports as r')
            ->join('production_sections as s', 's.id', '=', 'r.production_section_id')
            ->leftJoin('locations as l', 'l.id', '=', 's.location_id')
            ->join('goods as g', 'g.id', '=', 'r.goods_id')
            ->leftJoin('personnel as p', 'p.id', '=', 'r.personnel_id')
            ->where('r.company_id', $companyId)->where('r.id', $reportId)
            ->first(['r.id', 'r.work_date', 'r.produced_quantity', 'r.scrap_quantity',
                'l.name as section_name', 'g.name as goods_name', 'p.name as worker_name']);

        app(NotificationRecipientService::class)->send(
            $companyId,
            'production.shift_report_pending',
            [
                'report_id' => $reportId,
                'work_date' => $reportDetails->work_date ?? $data['work_date'],
                'section_name' => $reportDetails->section_name ?? '',
                'worker_name' => $reportDetails->worker_name ?? '',
                'goods_name' => $reportDetails->goods_name ?? '',
                'produced_quantity' => (float) ($reportDetails->produced_quantity ?? $data['produced_quantity']),
                'scrap_quantity' => (float) ($reportDetails->scrap_quantity ?? $scrap),
            ],
            (int) $request->user()->id,
            'production.operation.review'
        );

        return redirect()->route('production.shift-reports.index')->with('success', 'گزارش شیفت ثبت شد و برای تأیید سرپرست ارسال شد.');
    }

    public function update(Request $request, int $report)
    {
        $companyId = CompanyAuthorization::authorize($request, 'production.operation.review');
        $data = $request->validate([
            'production_section_id' => ['required', 'integer'],
            'goods_id' => ['required', 'integer'],
            'personnel_id' => ['required', 'integer'],
            'worked_hours' => ['required', 'numeric', 'gt:0', 'lte:24'],
            'overtime_hours' => ['nullable', 'numeric', 'gte:0', 'lte:24'],
            'holiday_hours' => ['nullable', 'numeric', 'gte:0', 'lte:24'],
            'work_date' => ['required', 'date'],
            'produced_quantity' => ['required', 'numeric', 'gt:0'],
            'scrap_quantity' => ['nullable', 'numeric', 'gte:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'stages' => ['sometimes', 'array', 'min:1'],
            'stages.*.production_stage_id' => ['required', 'integer'],
            'materials' => ['nullable', 'array'],
            'materials.*.goods_id' => ['nullable', 'integer'],
            'materials.*.quantity' => ['nullable', 'numeric', 'gt:0'],
            'materials.*.scrap_quantity' => ['nullable', 'numeric', 'gte:0'],
        ]);

        $sectionValid = DB::table('production_sections as s')->join('locations as l', 'l.id', '=', 's.location_id')
            ->where('s.id', $data['production_section_id'])->where('l.company_id', $companyId)->exists();
        $goodValid = DB::table('goods')->where('id', $data['goods_id'])->where('company_id', $companyId)
            ->where('is_active', true)->where('producible', true)->exists();
        $accountId = DB::table('companies')->where('id', $companyId)->value('account_id');
        $workerValid = DB::table('personnel')->where('id', $data['personnel_id'])->where('account_id', $accountId)->where('is_active', true)->exists();
        if (!$sectionValid) throw ValidationException::withMessages(['production_section_id' => 'قسمت تولید معتبر نیست.']);
        if (!$goodValid) throw ValidationException::withMessages(['goods_id' => 'محصول انتخاب‌شده معتبر نیست.']);
        if (!$workerValid) throw ValidationException::withMessages(['personnel_id' => 'کارگر انتخاب‌شده معتبر نیست.']);

        $stagesSubmitted = $request->has('stages');
        $stageIds = $stagesSubmitted ? [] : DB::table('production_shift_report_stages')
            ->where('company_id', $companyId)->where('production_shift_report_id', $report)
            ->orderBy('id')->pluck('production_stage_id')->map(fn ($id) => (int) $id)->all();
        if ($stagesSubmitted && empty($data['stages'])) {
            throw ValidationException::withMessages(['stages' => 'حداقل یک مرحله باید انتخاب شود؛ اگر قصد تغییر مراحل را ندارید، تیک‌ها را دست‌کاری نکنید.']);
        }
        foreach (($data['stages'] ?? []) as $line) {
            $stageId = (int) ($line['production_stage_id'] ?? 0);
            $valid = DB::table('production_stages as ps')->join('production_routes as pr', 'pr.id', '=', 'ps.production_route_id')
                ->where('ps.id', $stageId)->where('pr.company_id', $companyId)->where('ps.status', 'active')->exists();
            if (!$valid) throw ValidationException::withMessages(['stages' => 'یکی از مراحل انتخاب‌شده معتبر یا فعال نیست.']);
            $stageIds[] = $stageId;
        }
        if (count($stageIds) < 1) {
            throw ValidationException::withMessages(['stages' => 'حداقل یک مرحله باید در گزارش ثبت شده باشد.']);
        }
        if (count($stageIds) !== count(array_unique($stageIds))) {
            throw ValidationException::withMessages(['stages' => 'یک مرحله را در یک گزارش بیش از یک بار انتخاب نکنید.']);
        }

        $materialRows = [];
        foreach (($data['materials'] ?? []) as $line) {
            $materialId = (int) ($line['goods_id'] ?? 0);
            $quantity = $line['quantity'] ?? null;
            if ($materialId === 0 && ($quantity === null || $quantity === '')) continue;
            if ($materialId === 0 || $quantity === null || $quantity === '') {
                throw ValidationException::withMessages(['materials' => 'برای هر ماده اولیه، هم ماده و هم مقدار مصرف را مشخص کنید.']);
            }
            $validMaterial = DB::table('goods')->where('id', $materialId)->where('company_id', $companyId)
                ->where('is_active', true)->where('producible', true)->exists();
            if (!$validMaterial) throw ValidationException::withMessages(['materials' => 'یکی از مواد اولیه انتخاب‌شده معتبر نیست.']);
            $materialScrap = (float) ($line['scrap_quantity'] ?? 0);
            if ($materialScrap > (float) $quantity) {
                throw ValidationException::withMessages(['materials' => 'ضایعات ماده اولیه نمی‌تواند از مقدار مصرف آن بیشتر باشد.']);
            }
            $materialRows[] = ['goods_id' => $materialId, 'quantity' => (float) $quantity, 'scrap_quantity' => $materialScrap];
        }

        $scrap = (float) ($data['scrap_quantity'] ?? 0);
        if ($scrap > (float) $data['produced_quantity']) {
            throw ValidationException::withMessages(['scrap_quantity' => 'ضایعات محصول نمی‌تواند از تعداد تولیدشده بیشتر باشد.']);
        }

        DB::transaction(function () use ($companyId, $report, $data, $request, $stageIds, $materialRows, $scrap) {
            $row = DB::table('production_shift_reports')->where('company_id', $companyId)->where('id', $report)->lockForUpdate()->first();
            if (!$row || !(in_array($row->status, ['pending', 'rejected'], true) || ($row->status === 'approved' && (float) ($row->warehouse_quantity ?? 0) <= 0))) {
                throw ValidationException::withMessages(['report' => 'گزارش در انتظار تأیید یا ردشده قابل ویرایش است. گزارش تأییدشده فقط تا قبل از هرگونه تحویل به انبار قابل اصلاح است؛ پس از ثبت ورود به انبار، برای جلوگیری از مغایرت موجودی باید اصلاح جداگانه انجام شود.']);
            }

            $before = [
                'report' => (array) $row,
                'stages' => DB::table('production_shift_report_stages')->where('company_id', $companyId)->where('production_shift_report_id', $report)->get()->toArray(),
                'materials' => DB::table('production_shift_report_materials')->where('company_id', $companyId)->where('production_shift_report_id', $report)->get()->toArray(),
            ];
            DB::table('production_shift_reports')->where('company_id', $companyId)->where('id', $report)->update([
                'production_section_id' => (int) $data['production_section_id'],
                'goods_id' => (int) $data['goods_id'],
                'personnel_id' => (int) $data['personnel_id'],
                'shift_name' => 'کارکرد ' . (float) $data['worked_hours'] . ' ساعت',
                'worked_hours' => (float) $data['worked_hours'],
                'overtime_hours' => (float) ($data['overtime_hours'] ?? 0),
                'holiday_hours' => (float) ($data['holiday_hours'] ?? 0),
                'work_date' => $data['work_date'],
                'produced_quantity' => (float) $data['produced_quantity'],
                'scrap_quantity' => $scrap,
                'notes' => $data['notes'] ?? null,
                'status' => 'pending',
                'reviewed_by_user_id' => null,
                'review_notes' => null,
                'reviewed_at' => null,
                'updated_at' => now(),
            ]);

            DB::table('production_shift_report_stages')->where('company_id', $companyId)->where('production_shift_report_id', $report)->delete();
            foreach ($stageIds as $stageId) {
                DB::table('production_shift_report_stages')->insert([
                    'company_id' => $companyId, 'production_shift_report_id' => $report,
                    'production_stage_id' => $stageId, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            DB::table('production_shift_report_materials')->where('company_id', $companyId)->where('production_shift_report_id', $report)->delete();
            foreach ($materialRows as $line) {
                DB::table('production_shift_report_materials')->insert([
                    'company_id' => $companyId, 'production_shift_report_id' => $report,
                    'goods_id' => $line['goods_id'], 'quantity' => $line['quantity'],
                    'scrap_quantity' => $line['scrap_quantity'], 'created_at' => now(), 'updated_at' => now(),
                ]);
            }

            $after = [
                'report' => (array) DB::table('production_shift_reports')->where('company_id', $companyId)->where('id', $report)->first(),
                'stages' => DB::table('production_shift_report_stages')->where('company_id', $companyId)->where('production_shift_report_id', $report)->get()->toArray(),
                'materials' => DB::table('production_shift_report_materials')->where('company_id', $companyId)->where('production_shift_report_id', $report)->get()->toArray(),
            ];
            DB::table('audit_trails')->insert([
                'company_id' => $companyId, 'user_id' => (int) $request->user()->id, 'module' => 'production',
                'action' => 'production_shift_report.update', 'auditable_type' => 'production_shift_reports', 'auditable_id' => $report,
                'before' => json_encode($before), 'after' => json_encode($after),
                'method' => 'SERVICE', 'status_code' => 200, 'created_at' => now(), 'updated_at' => now(),
            ]);
        });

        return redirect()->route('production.shift-reports.show', $report)->with('success', 'اطلاعات گزارش اصلاح شد و برای تأیید دوباره در صف بررسی قرار گرفت.');
    }

    public function review(Request $request, int $report)
    {
        $companyId = CompanyAuthorization::authorize($request, 'production.operation.review');
        $data = $request->validate([
            'approve' => ['required', 'boolean'],
            'review_notes' => ['nullable', 'string', 'max:1000'],
        ]);
        if (!(bool) $data['approve'] && blank($data['review_notes'] ?? null)) {
            throw ValidationException::withMessages(['review_notes' => 'برای رد گزارش، دلیل رد را وارد کنید.']);
        }
        DB::transaction(function () use ($companyId, $report, $data, $request) {
            $row = DB::table('production_shift_reports')->where('company_id', $companyId)->where('id', $report)->lockForUpdate()->first();
            if (!$row || $row->status !== 'pending') throw ValidationException::withMessages(['report' => 'این گزارش دیگر در انتظار بررسی نیست.']);

            if ((bool) $data['approve']) {
                $materialCount = DB::table('production_shift_report_materials')
                    ->where('company_id', $companyId)->where('production_shift_report_id', $report)
                    ->whereNotNull('goods_id')->where('quantity', '>', 0)->count();
                if ($materialCount < 1) {
                    throw ValidationException::withMessages(['materials' => 'تأیید گزارش بدون حداقل یک ماده اولیه با مقدار مصرف معتبر امکان‌پذیر نیست؛ گزارش باید اصلاح شود.']);
                }

                $goods = DB::table('goods')->where('company_id', $companyId)->where('id', $row->goods_id)->first(['producible']);
                if (!$goods || !(bool) $goods->producible) {
                    throw ValidationException::withMessages(['report' => 'کالای این گزارش معتبر نیست یا به‌عنوان کالای تولیدشدنی تعریف نشده است.']);
                }
            }

            DB::table('production_shift_reports')->where('id', $report)->where('company_id', $companyId)->update([
                'status' => $data['approve'] ? 'approved' : 'rejected',
                'reviewed_by_user_id' => (int) $request->user()->id,
                'review_notes' => $data['review_notes'] ?? null,
                'reviewed_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('audit_trails')->insert([
                'company_id' => $companyId, 'user_id' => (int) $request->user()->id, 'module' => 'production',
                'action' => $data['approve'] ? 'production_shift_report.approve' : 'production_shift_report.reject',
                'auditable_type' => 'production_shift_reports', 'auditable_id' => $report,
                'before' => json_encode((array) $row),
                'after' => json_encode((array) DB::table('production_shift_reports')->where('id', $report)->first()),
                'method' => 'SERVICE', 'status_code' => 200, 'created_at' => now(), 'updated_at' => now(),
            ]);
        });
        if ((bool) $data['approve']) {
            $details = DB::table('production_shift_reports as r')->join('goods as g', 'g.id', '=', 'r.goods_id')
                ->leftJoin('locations as l', 'l.id', '=', 'r.warehouse_location_id')
                ->where('r.company_id', $companyId)->where('r.id', $report)
                ->first(['r.id', 'r.work_date', 'r.produced_quantity', 'r.scrap_quantity', 'g.name as goods_name', 'g.sellable', 'l.name as warehouse_name']);
            if ($details && (bool) $details->sellable) {
                app(NotificationRecipientService::class)->send($companyId, 'production.shift_report_warehouse_receipt', [
                    'report_id' => $report, 'work_date' => $details->work_date ?? '', 'goods_name' => $details->goods_name ?? '',
                    'prepared_quantity' => max(0, (float) ($details->produced_quantity ?? 0) - (float) ($details->scrap_quantity ?? 0)),
                    'warehouse_name' => $details->warehouse_name ?? '', 'url' => route('production.outputs.index'),
                ], (int) $request->user()->id, 'production.output.receive');
            }
        }
        return back()->with('success', $data['approve']
            ? 'گزارش تولید تأیید شد و برای تأیید دریافت به سرپرست انبار اعلان ارسال شد.'
            : 'گزارش شیفت رد شد.');
    }

    public function receive(Request $request, int $report)
    {
        abort(410, 'ثبت ورود این گزارش فقط از صفحه خروجی تولید و پس از تأیید سرپرست انبار مجاز است.');
    }
}
