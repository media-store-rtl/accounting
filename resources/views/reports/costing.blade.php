@extends('layouts.dashboard-shell')

@section('title', 'گزارش بهای تمام‌شده')

@section('content')
@php
  $formatCostingNumber = static fn ($value) => rtrim(rtrim(number_format((float) ($value ?? 0), 2, '.', ','), '0'), '.');
@endphp
@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/css/persian-datepicker.min.css">
@endpush
<style>
.costing-filters,.costing-panel,.costing-card{background:#0a1b2b;border:1px solid #1b354b;border-radius:17px;padding:18px}
.costing-filters{margin-bottom:16px}
.costing-filters form{display:grid;grid-template-columns:repeat(8,minmax(0,1fr));gap:10px}
.costing-filters label{display:block;color:#7890a5;font-size:10px;margin-bottom:6px}
.costing-filters select,.costing-filters input,.costing-filters button{width:100%;background:#0d2235;color:#dcebf6;border:1px solid #29465e;border-radius:10px;padding:10px;font-family:inherit;font-size:11px}
.costing-filters input{direction:ltr;text-align:right;cursor:pointer}
.costing-filters button{cursor:pointer;background:#153b52}
.costing-cards{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:10px;margin-bottom:16px}
.costing-card small{color:#71879d;font-size:10px}.costing-card b{display:block;font-size:19px;margin-top:8px;overflow-wrap:anywhere}
.costing-panel{margin-bottom:16px}
.costing-warn{background:#2c2413;color:#e5c87c;border:1px solid #5c4a25;border-radius:10px;padding:11px;font-size:11px;margin-bottom:12px}
.costing-table-wrap{overflow-x:auto}.costing-table{width:100%;border-collapse:collapse;min-width:900px}
.costing-table th,.costing-table td{padding:11px 9px;border-bottom:1px solid #183047;text-align:right;font-size:11px}
.costing-main-table{min-width:1200px}.costing-action-form{margin:0}.costing-action-button{background:#126b5a;border:1px solid #1b8b74;border-radius:8px;color:#e9fff9;padding:7px 10px;font:inherit;cursor:pointer;white-space:nowrap}.costing-action-button:hover{background:#16816c}.costing-main-table .costing-expand-btn{background:#153b52;border:1px solid #315570;border-radius:8px;color:#cdeaff;padding:7px 10px;font:inherit;cursor:pointer;white-space:nowrap}.costing-main-table .costing-expand-btn[aria-expanded="true"]{background:#24516b}.costing-detail-row>td{padding:0!important;background:#071522}.costing-detail-container{padding:16px;border-bottom:1px solid #29465e}.costing-summary-row td{background:#102a3e;color:#fff!important;font-weight:700;border-top:2px solid #3c6078}.costing-status{display:inline-block;padding:4px 7px;border-radius:7px;font-size:10px;white-space:nowrap}.costing-status.complete{background:#153b2e;color:#a7e3c6}.costing-status.incomplete{background:#3b3018;color:#f0d58a}.costing-bottom-summary{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:10px;margin-top:14px}.costing-bottom-summary>div{background:#0d2235;border:1px solid #29465e;border-radius:10px;padding:12px}.costing-bottom-summary small{display:block;color:#91a6b9;font-size:10px}.costing-bottom-summary strong{display:block;margin-top:6px;font-size:13px;overflow-wrap:anywhere}@media(max-width:700px){.costing-bottom-summary{grid-template-columns:1fr 1fr}}
.costing-table th{color:#71879d;font-weight:500}.costing-table td{color:#dcebf6}
.costing-row-details{min-width:94px}.costing-row-details summary{cursor:pointer;color:#9dd8ff;white-space:nowrap;list-style-position:inside}
.costing-row-details[open] summary{margin-bottom:10px}
.costing-report-list{display:grid;gap:14px}.costing-report-card{padding:16px;background:#0d2235;border:1px solid #29465e;border-radius:14px}.costing-report-heading{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:14px;padding-bottom:10px;border-bottom:1px solid #29465e}.costing-report-heading h3{font-size:13px;margin:0}.costing-report-heading span{font-size:11px;color:#9dd8ff}.costing-section-title{font-size:12px;margin:14px 0 8px;color:#dcebf6}
.costing-detail-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;margin-bottom:14px}
.costing-detail-item small{display:block;color:#7890a5;font-size:10px;margin-bottom:4px}.costing-detail-item strong{font-size:11px;font-weight:500;overflow-wrap:anywhere}
.costing-detail-costs{width:100%;border-collapse:collapse}.costing-detail-costs th,.costing-detail-costs td{padding:8px 5px;border-bottom:1px solid #183047;text-align:right;font-size:11px}
.costing-detail-material-wrap{overflow-x:auto;margin:8px 0}.costing-detail-materials{width:100%;min-width:560px;border-collapse:collapse}.costing-detail-materials th,.costing-detail-materials td{padding:7px 5px;border-bottom:1px solid #183047;text-align:right;font-size:10px;white-space:nowrap}.costing-detail-materials th{color:#7890a5;font-weight:500}
.costing-detail-note{color:#91a6b9;font-size:10px;line-height:1.9;margin:10px 0 0}
.costing-empty{text-align:center;padding:35px;color:#71879d;border:1px dashed #29455d;border-radius:12px;font-size:11px}
@media(max-width:700px){.costing-detail-grid{grid-template-columns:1fr 1fr}}
@media(max-width:800px){.costing-filters form{grid-template-columns:1fr 1fr}.costing-cards{grid-template-columns:1fr 1fr}}
@media(max-width:500px){.costing-filters form,.costing-cards{grid-template-columns:1fr}}
</style>
<div class="page-heading">
  <div><h1>گزارش بهای تمام‌شده</h1><p>ردیابی بهای تمام‌شده بر اساس گزارش‌های تولید تأییدشده و تحویل‌شده</p></div>
</div>

@if(session('success'))
  <div class="costing-panel" role="status" style="border-color:#24634b;color:#a7e3c6">{{ session('success') }}</div>
@endif
@if(session('error'))
  <div class="costing-warn" role="alert">{{ session('error') }}</div>
@endif

<div class="costing-filters">
  <form method="GET" action="{{ route('reports.costing') }}">
    <div><label for="costing-fiscal-year">سال مالی</label><select id="costing-fiscal-year" name="fiscal_year_id"><option value="">همه</option>@foreach($fiscalYears as $fy)<option value="{{ $fy->id }}" @selected((int)request('fiscal_year_id')===(int)$fy->id)>{{ $fy->name }}</option>@endforeach</select></div>
    <div><label for="costing-goods">محصول / کالا</label><select id="costing-goods" name="goods_id"><option value="">همه</option>@foreach($goods as $good)<option value="{{ $good->id }}" @selected((int)request('goods_id')===(int)$good->id)>{{ $good->code }} — {{ $good->name }}</option>@endforeach</select></div>
    <div><label for="costing-start-date">از تاریخ</label><input id="costing-start-date-picker" type="text" inputmode="none" autocomplete="off" placeholder="انتخاب تاریخ شمسی" value="{{ request('start_date') }}"><input id="costing-start-date" type="hidden" name="start_date" value="{{ request('start_date') }}"></div>
    <div><label for="costing-end-date">تا تاریخ</label><input id="costing-end-date-picker" type="text" inputmode="none" autocomplete="off" placeholder="انتخاب تاریخ شمسی" value="{{ request('end_date') }}"><input id="costing-end-date" type="hidden" name="end_date" value="{{ request('end_date') }}"></div>
    <div><label for="costing-personnel">کارگر</label><select id="costing-personnel" name="personnel_id"><option value="">همه کارگران</option>@foreach($personnel as $worker)<option value="{{ $worker->id }}" @selected((int)request('personnel_id')===(int)$worker->id)>{{ $worker->code }} — {{ $worker->name }}</option>@endforeach</select></div>
    <div><label for="costing-production-section">قسمت تولید</label><select id="costing-production-section" name="production_section_id"><option value="">همه قسمت‌ها</option>@foreach($productionSections as $section)<option value="{{ $section->id }}" @selected((int)request('production_section_id')===(int)$section->id)>{{ $section->name }}</option>@endforeach</select></div>
    <div><label for="costing-production-stage">مرحله تولید</label><select id="costing-production-stage" name="production_stage_id"><option value="">همه مراحل</option>@foreach($productionStages as $stage)<option value="{{ $stage->id }}" @selected((int)request('production_stage_id')===(int)$stage->id)>{{ $stage->name }}</option>@endforeach</select></div>
    <div><label for="costing-valuation-method">روش محاسبه همین گزارش</label><select id="costing-valuation-method" name="valuation_method"><option value="fifo" @selected($valuationMethod === 'fifo')>FIFO — اولین ورود، اولین خروج</option><option value="weighted_average" @selected($valuationMethod === 'weighted_average')>میانگین موزون</option></select></div>
    <div style="display:flex;align-items:end"><button type="submit">نمایش گزارش</button></div>
  </form>
</div>

<div class="costing-panel" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
  <strong style="font-size:12px;margin-left:8px">خروجی اکسل با فیلترهای فعلی:</strong>
  <a href="{{ route('reports.costing.export', array_merge(request()->query(), ['format' => 'summary'])) }}" style="display:inline-block;background:#153b52;border:1px solid #315570;border-radius:9px;color:#dcebf6;padding:10px 14px;text-decoration:none;font-size:11px">دانلود اکسل خلاصه</a>
  <a href="{{ route('reports.costing.export', array_merge(request()->query(), ['format' => 'full'])) }}" style="display:inline-block;background:#24516b;border:1px solid #47718c;border-radius:9px;color:#dcebf6;padding:10px 14px;text-decoration:none;font-size:11px">دانلود اکسل کامل (شیت‌های جدا)</a>
</div>

@if($report['totals']['unvalued_material_quantity']>0 || ($report['totals']['production_report_unvalued_material_quantity'] ?? 0)>0)
  <div class="costing-warn">مقدار بدون ارزش‌گذاری در دو شاخص جدا نمایش داده می‌شود: خروج انبارِ بدون ارزش‌گذاری و مصرف ثبت‌شده در گزارش تولید که هنوز با حواله/ارزش‌گذاری تطبیق کامل ندارد. این مقادیر برای جلوگیری از دوباره‌شماری به مبلغ بهای تمام‌شده اضافه نمی‌شوند. <strong>تا زمان رفع مغایرت، فی تمام‌شده هر واحد ممکن است ناقص باشد و نباید به‌عنوان بهای قطعی استفاده شود.</strong></div>
@endif

<div class="costing-panel">
  <h2 style="font-size:14px;margin:0 0 8px">گزارش‌های تولید تأییدشده و تحویل‌شده</h2>
  <p style="font-size:10px;color:#91a6b9;margin:0 0 12px">برای مشاهده ریز مواد و هزینه‌های هر گزارش، روی «جزئیات» همان ردیف بزنید. جمع کل فقط یک‌بار در انتهای جدول محاسبه می‌شود.</p>
  <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin:0 0 12px">
    <button type="button" class="costing-action-button" data-costing-refresh-all data-endpoint="{{ route('reports.costing.production-reports.refresh-all') }}">بروزرسانی گزارش‌ها</button>
    <span data-costing-refresh-result role="status" style="font-size:10px;color:#91a6b9">با کلیک، تمام گزارش‌های تأییدشده و تحویل‌شده شرکت دوباره با مصرف انبار و لایه‌های قیمت بررسی می‌شوند.</span>
  </div>
  @if(empty($report['production_reports']))
    <div class="costing-empty">گزارش تولید تأییدشده یا تحویل‌شده‌ای برای این فیلتر پیدا نشد.</div>
  @else
    @php
      $costingReports = collect($report['production_reports']);
      $costingProducts = collect($report['product_rows']);
      $costingProducedTotal = $costingReports->sum('produced_quantity');
      $costingReceivedTotal = $costingReports->sum('received_quantity');
      $costingScrapTotal = $costingReports->sum('scrap_quantity');
      $costingLaborTotal = $costingReports->sum('labor_cost');
      $costingMaterialDetailTotal = $costingReports->sum(fn ($r) => collect($r['materials'] ?? [])->sum('material_cost'));
      $costingUnvaluedTotal = $costingReports->sum(fn ($r) => collect($r['materials'] ?? [])->sum('unvalued_quantity'));
      $costingAggregateMaterialTotal = $costingProducts->sum('material_cost');
      $costingAggregateLaborTotal = $costingProducts->sum('labor_cost');
      $costingAggregateScrapTotal = $costingProducts->sum('scrap_cost');
      $costingAggregateDirectTotal = $costingProducts->sum('direct_cost');
      $costingAggregateTotal = $costingProducts->sum('total_cost');
      $costingUnitTotal = $costingProducedTotal > 0 ? $costingAggregateTotal / $costingProducedTotal : null;
    @endphp
    <div class="costing-table-wrap">
      <table class="costing-table costing-main-table">
        <thead>
          <tr>
            <th>شماره گزارش</th><th>تاریخ تولید</th><th>محصول</th><th>مقدار تولید</th><th>تحویل‌شده</th><th>هزینه مواد ارزش‌گذاری‌شده</th><th>دستمزد</th><th>ضایعات محصول</th><th>جمع مواد + دستمزد*</th><th>وضعیت</th><th>جزئیات</th><th>عملیات</th>
          </tr>
        </thead>
        <tbody>
          @foreach($report['production_reports'] as $productionReport)
            @php
              $reportMaterials = collect($productionReport['materials'] ?? []);
              $reportMaterialCost = $reportMaterials->sum('material_cost');
              $reportUnvaluedQuantity = $reportMaterials->sum('unvalued_quantity');
              $reportRecordedCost = $reportMaterialCost + (float) ($productionReport['labor_cost'] ?? 0);
              $reportCostComplete = $reportUnvaluedQuantity <= 0;
              $detailId = 'costing-detail-' . $productionReport['report_id'];
            @endphp
            <tr>
              <td>#{{ $productionReport['report_id'] }}</td>
              <td class="jalali-date" data-date="{{ $productionReport['work_date'] }}">{{ $productionReport['work_date'] }}</td>
              <td>{{ $productionReport['goods_code'] }} — {{ $productionReport['goods_name'] }}</td>
              <td>{{ $formatCostingNumber($productionReport['produced_quantity']) }} {{ $productionReport['unit_symbol'] ?? $productionReport['unit_name'] ?? '' }}</td>
              <td>{{ $formatCostingNumber($productionReport['received_quantity']) }}</td>
              <td>{{ $formatCostingNumber($reportMaterialCost) }} ریال</td>
              <td>{{ $formatCostingNumber($productionReport['labor_cost']) }} ریال</td>
              <td>{{ $formatCostingNumber($productionReport['scrap_quantity']) }}</td>
              <td>{{ $formatCostingNumber($reportRecordedCost) }} ریال</td>
              <td><span class="costing-status {{ $reportCostComplete ? 'complete' : 'incomplete' }}">{{ $reportCostComplete ? 'ارزش‌گذاری مواد کامل' : 'ناقص: ' . $formatCostingNumber($reportUnvaluedQuantity) . ' واحد بدون ارزش' }}</span></td>
              <td><button class="costing-expand-btn" type="button" aria-expanded="false" aria-controls="{{ $detailId }}" data-costing-toggle="{{ $detailId }}">مشاهده جزئیات</button></td>
              <td>
                <button type="button" class="costing-action-button" data-costing-refresh-row data-endpoint="{{ route('reports.costing.production-reports.value', $productionReport['report_id']) }}">بروزرسانی</button>
                <span data-costing-refresh-row-result="{{ $productionReport['report_id'] }}" role="status" style="display:block;font-size:9px;color:#91a6b9;margin-top:4px"></span>
              </td>
            </tr>
            <tr id="{{ $detailId }}" class="costing-detail-row" hidden>
              <td colspan="12">
                <div class="costing-detail-container">
                  <div class="costing-report-heading">
                    <h3>جزئیات گزارش #{{ $productionReport['report_id'] }}</h3>
                    <span>تاریخ تحویل: <span class="jalali-date" data-date="{{ $productionReport['warehouse_received_at'] }}">{{ $productionReport['warehouse_received_at'] ?? 'ثبت نشده' }}</span></span>
                  </div>
                  <div class="costing-detail-grid">
                    <div class="costing-detail-item"><small>کارگر</small><strong>{{ $productionReport['worker_name'] ?? 'ثبت نشده' }}</strong></div>
                    <div class="costing-detail-item"><small>محصول</small><strong>{{ $productionReport['goods_code'] }} — {{ $productionReport['goods_name'] }}</strong></div>
                    <div class="costing-detail-item"><small>واحد محصول</small><strong>{{ $productionReport['unit_symbol'] ?? $productionReport['unit_name'] ?? '—' }}</strong></div>
                    <div class="costing-detail-item"><small>بخش تولید</small><strong>{{ $productionReport['section_name'] ?? '—' }}</strong></div>
                    <div class="costing-detail-item"><small>انبار مقصد</small><strong>{{ $productionReport['warehouse_name'] ?? '—' }}</strong></div>
                    <div class="costing-detail-item"><small>مرحله تولید</small><strong>{{ !empty($productionReport['stage_names']) ? implode('، ', $productionReport['stage_names']) : 'مرحله‌ای برای این گزارش ثبت/برگردانده نشده است' }}</strong></div>
                    <div class="costing-detail-item"><small>مقدار تولید</small><strong>{{ $formatCostingNumber($productionReport['produced_quantity']) }}</strong></div>
                    <div class="costing-detail-item"><small>تحویل‌شده</small><strong>{{ $formatCostingNumber($productionReport['received_quantity']) }}</strong></div>
                    <div class="costing-detail-item"><small>ضایعات محصول</small><strong>{{ $formatCostingNumber($productionReport['scrap_quantity']) }}</strong></div>
                  </div>

                  <h4 class="costing-section-title">ریز مواد مصرفی همین گزارش</h4>
                  @if($reportMaterials->isNotEmpty())
                    <div class="costing-detail-material-wrap">
                      <table class="costing-detail-materials">
                        <thead><tr><th>کد و نام ماده</th><th>مقدار مصرف</th><th>ضایعات ماده</th><th>واحد</th><th>مقدار ارزش‌گذاری‌شده</th><th>نرخ واحد (ریال)</th><th>مبلغ ارزش‌گذاری‌شده (ریال)</th><th>مقدار بدون ارزش</th><th>وضعیت</th></tr></thead>
                        <tbody>
                          @foreach($reportMaterials as $material)
                            <tr>
                              <td>{{ $material['material_code'] }} — {{ $material['material_name'] }}</td>
                              <td>{{ $formatCostingNumber($material['quantity']) }}</td>
                              <td>{{ $formatCostingNumber($material['scrap_quantity']) }}</td>
                              <td>{{ $material['unit_symbol'] ?? $material['unit_name'] ?? '—' }}</td>
                              <td>{{ $formatCostingNumber($material['valued_quantity']) }}</td>
                              <td>{{ $material['unit_cost'] !== null ? $formatCostingNumber($material['unit_cost']) : '—' }}</td>
                              <td>{{ $formatCostingNumber($material['material_cost']) }}</td>
                              <td>{{ $formatCostingNumber($material['unvalued_quantity']) }}</td>
                              <td>{{ $material['cost_complete'] ? 'کامل' : 'ناقص؛ نیازمند تطبیق/ارزش‌گذاری' }}</td>
                            </tr>
                          @endforeach
                          <tr class="costing-summary-row"><td>جمع مواد این گزارش</td><td>{{ $formatCostingNumber($reportMaterials->sum('quantity')) }}</td><td>{{ $formatCostingNumber($reportMaterials->sum('scrap_quantity')) }}</td><td>—</td><td>{{ $formatCostingNumber($reportMaterials->sum('valued_quantity')) }}</td><td>—</td><td>{{ $formatCostingNumber($reportMaterialCost) }}</td><td>{{ $formatCostingNumber($reportUnvaluedQuantity) }}</td><td>{{ $reportCostComplete ? 'کامل' : 'ناقص' }}</td></tr>
                        </tbody>
                      </table>
                    </div>
                    <p class="costing-detail-note">هزینه مواد فقط بر اساس مقدار ارزش‌گذاری‌شده است. مقادیر فاقد تطبیق یا ارزش‌گذاری در هزینه منظور نشده‌اند و تا رفع مغایرت، مبلغ نهایی نیست.</p>
                  @else
                    <p class="costing-detail-note">برای این گزارش ریز مواد مصرفی ثبت نشده است.</p>
                  @endif

                  <h4 class="costing-section-title">خلاصه هزینه همین گزارش</h4>
                  <div class="costing-table-wrap">
                    <table class="costing-detail-costs">
                      <thead><tr><th>شرح هزینه</th><th>مبلغ</th></tr></thead>
                      <tbody>
                        <tr><td>مواد ارزش‌گذاری‌شده</td><td>{{ $formatCostingNumber($reportMaterialCost) }} ریال</td></tr>
                        <tr><td>دستمزد محاسبه‌شده</td><td>{{ $formatCostingNumber($productionReport['labor_cost']) }} ریال</td></tr>
                        <tr class="costing-summary-row"><td>جمع هزینه‌های قابل انتساب به این گزارش*</td><td>{{ $formatCostingNumber($reportRecordedCost) }} ریال</td></tr>
                      </tbody>
                    </table>
                  </div>
                  <p class="costing-detail-note">* این جمع شامل مواد ارزش‌گذاری‌شده و دستمزد این گزارش است. هزینه‌های خرید مستقیم و سربار، در صورت وجود، در این ردیف سرشکن نشده‌اند. بهای قطعی فقط پس از رفع مغایرت مواد قابل تعیین است.</p>
                </div>
              </td>
            </tr>
          @endforeach
          <tr class="costing-summary-row">
            <td>جمع کل</td><td>—</td><td>همه محصولات</td><td>{{ $formatCostingNumber($costingProducedTotal) }}</td><td>{{ $formatCostingNumber($costingReceivedTotal) }}</td><td>{{ $formatCostingNumber($costingAggregateMaterialTotal) }} ریال</td><td>{{ $formatCostingNumber($costingAggregateLaborTotal) }} ریال</td><td>{{ $formatCostingNumber($costingScrapTotal) }}</td><td>{{ $formatCostingNumber($costingAggregateTotal) }} ریال</td><td>{{ $costingUnvaluedTotal > 0 ? 'موقت؛ مواد بدون ارزش' : 'بر اساس داده‌های ثبت‌شده' }}</td><td>—</td><td>—</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="costing-bottom-summary">
      <div><small>مجموع مقدار تولید</small><strong>{{ $formatCostingNumber($costingProducedTotal) }}</strong></div>
      <div><small>مجموع مواد ارزش‌گذاری‌شده</small><strong>{{ $formatCostingNumber($costingAggregateMaterialTotal) }} ریال</strong></div>
      <div><small>مجموع دستمزد</small><strong>{{ $formatCostingNumber($costingAggregateLaborTotal) }} ریال</strong></div>
      <div><small>جمع بهای تمام‌شده فعلی</small><strong>{{ $formatCostingNumber($costingAggregateTotal) }} ریال</strong></div>
      <div><small>بهای تمام‌شده هر واحد{{ $costingUnvaluedTotal > 0 ? ' (موقت)' : '' }}</small><strong>{{ $costingProducedTotal > 0 ? $formatCostingNumber($costingAggregateTotal / $costingProducedTotal) : '—' }} ریال</strong></div>
    </div>
    @if($costingUnvaluedTotal > 0 || ($report['totals']['unvalued_material_quantity'] ?? 0) > 0 || ($report['totals']['production_report_unvalued_material_quantity'] ?? 0) > 0)
      <div class="costing-warn" style="margin-top:14px;margin-bottom:0">جمع بالا بر مبنای مبالغ فعلاً ارزش‌گذاری‌شده است. مقدار بدون ارزش‌گذاری: {{ $formatCostingNumber($costingUnvaluedTotal) }} واحد در ریز گزارش‌های تولید. این مقدار در هزینه وارد نشده است؛ بنابراین بهای تمام‌شده و فی هر واحد تا زمان رفع مغایرت قطعی نیست.</div>
    @endif
  @endif
  <p style="font-size:10px;color:#91a6b9;margin:12px 0 0">دستمزد هر گزارش بر اساس کارگر و نرخ معتبر در تاریخ کار محاسبه می‌شود. خلاصه پایین جدول مربوط به گزارش‌های منطبق با فیلترهای انتخابی است؛ جزئیات هر گزارش از همان ردیف قابل بازشدن است.</p>
</div>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/persian-date@1.1.0/dist/persian-date.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/js/persian-datepicker.min.js"></script>
<script type="module">
import { setupProductionReportDatePicker } from '/js/production-report-date-filters.js';

document.addEventListener('DOMContentLoaded', function () {
    setupProductionReportDatePicker('#costing-start-date-picker', '#costing-start-date');
    setupProductionReportDatePicker('#costing-end-date-picker', '#costing-end-date');

    document.querySelectorAll('[data-costing-toggle]').forEach(function (button) {
        button.addEventListener('click', function () {
            const row = document.getElementById(button.dataset.costingToggle);
            if (!row) return;
            const expanded = button.getAttribute('aria-expanded') === 'true';
            button.setAttribute('aria-expanded', String(!expanded));
            row.hidden = expanded;
            button.textContent = expanded ? 'مشاهده جزئیات' : 'بستن جزئیات';
        });
    });

    document.querySelectorAll('.jalali-date').forEach(function (element) {
        const value = element.dataset.date;
        if (!value) return;
        const match = String(value).match(/^(\d{4})-(\d{2})-(\d{2})/);
        if (!match) return;
        const timestamp = new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]), 12).getTime();
        if (Number.isNaN(timestamp)) return;
        try {
            const date = new window.persianDate(timestamp);
            element.textContent = date.format('YYYY/MM/DD');
        } catch (error) {
            // Keep the server value if conversion is unavailable.
        }
    });
});
</script>
@endsection
