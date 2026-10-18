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
.costing-table th{color:#71879d;font-weight:500}.costing-table td{color:#dcebf6}
.costing-row-details{min-width:94px}.costing-row-details summary{cursor:pointer;color:#9dd8ff;white-space:nowrap;list-style-position:inside}
.costing-row-details[open] summary{margin-bottom:10px}
.costing-detail-panel{min-width:300px;max-width:760px;padding:14px;background:#0d2235;border:1px solid #29465e;border-radius:12px;white-space:normal}
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

<div class="costing-panel" style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap">
  <div>
    <h2 style="font-size:13px;margin:0 0 6px">روش پیش‌فرض ارزش‌گذاری مصرف‌های آینده</h2>
    <p class="costing-detail-note" style="margin:0">این انتخاب برای ارزش‌گذاری‌های بعدی و مصرف‌های بدون بهای ثبت‌شده استفاده می‌شود. هزینه‌های تاریخیِ قبلاً ثبت‌شده بازنویسی نمی‌شوند و روش ثبت‌شدهٔ خود را حفظ می‌کنند.</p>
  </div>
  <form method="POST" action="{{ route('reports.costing.valuation-method') }}" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
    @csrf
    <select name="method" aria-label="روش ارزش‌گذاری" style="background:#0d2235;color:#dcebf6;border:1px solid #29465e;border-radius:10px;padding:10px;font-family:inherit">
      <option value="fifo" @selected($valuationMethod === 'fifo')>FIFO — اولین ورود، اولین خروج</option>
      <option value="weighted_average" @selected($valuationMethod === 'weighted_average')>میانگین موزون</option>
    </select>
    <button type="submit" style="background:#153b52;color:#dcebf6;border:1px solid #29465e;border-radius:10px;padding:10px 14px;cursor:pointer;font-family:inherit">ذخیره روش</button>
  </form>
</div>

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

<div class="costing-panel">
  <h2 style="font-size:14px;margin:0 0 12px">خلاصه بهای تمام‌شده محصول</h2>
  <p class="costing-detail-note" style="margin:0 0 12px">برای هر محصول، مقدار و مبلغ جداگانه نمایش داده می‌شود. مقدار مواد از ریز مواد گزارش‌های تولید تحویل‌شده به دست می‌آید؛ هزینه‌های بدون مبنای مقداری مستقل با «—» مشخص شده‌اند.</p>
  <div class="costing-table-wrap">
    @if(empty($report['product_rows']))
      <div class="costing-empty">برای فیلتر انتخاب‌شده داده هزینه‌ای ثبت نشده است.</div>
    @else
      <table class="costing-table">
        <thead><tr><th>محصول</th><th>شرح هزینه</th><th>مقدار</th><th>واحد مقدار</th><th>مبلغ (ریال)</th></tr></thead>
        <tbody>
        @foreach($report['product_rows'] as $row)
          @php
            $matchingReports = collect($report['production_reports'])->where('goods_id', $row['goods_id']);
            $materialQuantity = $matchingReports->sum(fn ($productionReport) => collect($productionReport['materials'] ?? [])->sum('quantity'));
            $scrapQuantity = $matchingReports->sum('scrap_quantity');
            $quantityUnit = $row['unit_symbol'] ?? $row['unit_name'] ?? 'واحد';
            $productName = ($row['goods_code'] ?? '') . ' — ' . ($row['goods_name'] ?? 'محصول');
          @endphp
          <tr><td rowspan="6">{{ $productName }}</td><td>مواد مصرفی</td><td>{{ $formatCostingNumber($materialQuantity) }}</td><td>واحد ماده (طبق ریز مواد)</td><td>{{ $formatCostingNumber($row['material_cost'] ?? 0) }}</td></tr>
          <tr><td>دستمزد تجمیعی محصول</td><td>—</td><td>—</td><td>{{ $formatCostingNumber($row['labor_cost'] ?? 0) }}</td></tr>
          <tr><td>هزینه ضایعات</td><td>{{ $formatCostingNumber($scrapQuantity) }}</td><td>{{ $quantityUnit }}</td><td>{{ $formatCostingNumber($row['scrap_cost'] ?? 0) }}</td></tr>
          <tr><td>هزینه‌های مستقیم خرید</td><td>—</td><td>—</td><td>{{ $formatCostingNumber($row['direct_cost'] ?? 0) }}</td></tr>
          <tr><td><strong>جمع بهای تمام‌شده محصول</strong></td><td>{{ $formatCostingNumber($row['produced_quantity'] ?? 0) }}</td><td>{{ $quantityUnit }} تولیدشده</td><td><strong>{{ $formatCostingNumber($row['total_cost'] ?? 0) }}</strong></td></tr>
          <tr><td>فی تمام‌شده هر واحد</td><td>۱</td><td>{{ $quantityUnit }}</td><td>{{ isset($row['unit_cost']) && $row['unit_cost'] !== null ? $formatCostingNumber($row['unit_cost']) : '—' }}</td></tr>
        @endforeach
        </tbody>
      </table>
    @endif
  </div>
</div>

@if(!empty($report['valuation_methods']))
  <div class="costing-panel"><span style="color:#71879d;font-size:11px">روش محاسبه این گزارش:</span> {{ $valuationMethod === 'weighted_average' ? 'میانگین موزون' : 'FIFO' }} <span style="color:#91a6b9;font-size:10px">— محاسبه تاریخی فقط در گزارش انجام می‌شود و موجودی و سوابق ارزش‌گذاری ثبت‌شده را تغییر نمی‌دهد.</span></div>
@endif
@if($report['totals']['unvalued_material_quantity']>0 || ($report['totals']['production_report_unvalued_material_quantity'] ?? 0)>0)
  <div class="costing-warn">مقدار بدون ارزش‌گذاری در دو شاخص جدا نمایش داده می‌شود: خروج انبارِ بدون ارزش‌گذاری و مصرف ثبت‌شده در گزارش تولید که هنوز با حواله/ارزش‌گذاری تطبیق کامل ندارد. این مقادیر برای جلوگیری از دوباره‌شماری به مبلغ بهای تمام‌شده اضافه نمی‌شوند. <strong>تا زمان رفع مغایرت، فی تمام‌شده هر واحد ممکن است ناقص باشد و نباید به‌عنوان بهای قطعی استفاده شود.</strong></div>
@endif

<div class="costing-panel">
  <h2 style="font-size:14px;margin:0 0 12px">گزارش‌های تولید تأییدشده و تحویل‌شده</h2>
  <p style="font-size:10px;color:#91a6b9;margin:0 0 12px">برای خلوت ماندن جدول، اطلاعات تکمیلی هر گزارش با بازکردن «جزئیات» نمایش داده می‌شود.</p>
  <div class="costing-table-wrap">
    @if(empty($report['production_reports']))
      <div class="costing-empty">گزارش تولید تأییدشده یا تحویل‌شده‌ای برای این فیلتر پیدا نشد.</div>
    @else
      <table class="costing-table">
        <thead><tr><th>جزئیات</th><th>شماره گزارش</th><th>تاریخ تولید</th><th>محصول</th><th>بخش تولید</th><th>تعداد تولید</th><th>تحویل‌شده</th><th>ضایعات</th><th>دستمزد گزارش (ریال)</th></tr></thead>
        <tbody>
        @foreach($report['production_reports'] as $productionReport)
          @php($productCost = collect($report['product_rows'])->firstWhere('goods_id', $productionReport['goods_id']))
          <tr>
            <td>
              <details class="costing-row-details">
                <summary>جزئیات</summary>
                <div class="costing-detail-panel">
                  <div class="costing-detail-grid">
                    <div class="costing-detail-item"><small>تاریخ تحویل انبار</small><strong class="jalali-date" data-date="{{ $productionReport['warehouse_received_at'] }}">{{ $productionReport['warehouse_received_at'] ?? 'ثبت نشده' }}</strong></div>
                    <div class="costing-detail-item"><small>کارگر</small><strong>{{ $productionReport['worker_name'] ?? 'ثبت نشده' }}</strong></div>
                    <div class="costing-detail-item"><small>واحد محصول</small><strong>{{ $productionReport['unit_symbol'] ?? $productionReport['unit_name'] ?? '—' }}</strong></div>
                    <div class="costing-detail-item"><small>انبار مقصد</small><strong>{{ $productionReport['warehouse_name'] ?? '—' }}</strong></div>
                    <div class="costing-detail-item"><small>مرحله تولید</small><strong>{{ !empty($productionReport['stage_names']) ? implode('، ', $productionReport['stage_names']) : 'مرحله‌ای برای این گزارش ثبت/برگردانده نشده است' }}</strong></div>
                    <div class="costing-detail-item"><small>تعداد محصول سالم</small><strong>{{ $formatCostingNumber(max(0, (float) $productionReport['produced_quantity'] - (float) $productionReport['scrap_quantity'])) }}</strong></div>
                  </div>
                  <h3 style="font-size:12px;margin:0 0 8px">هزینه مختص این گزارش</h3>
                  <table class="costing-detail-costs">
                    <tbody>
                      <tr><td>دستمزد محاسبه‌شده بر اساس نرخ معتبر کارگر</td><td>{{ $formatCostingNumber($productionReport['labor_cost']) }} ریال</td></tr>
                    </tbody>
                  </table>
                  <h3 style="font-size:12px;margin:14px 0 8px">ریز مواد مصرفی این گزارش</h3>
                  @if(!empty($productionReport['materials']))
                    <div class="costing-detail-material-wrap">
                      <table class="costing-detail-materials">
                        <thead><tr><th>ماده</th><th>مقدار مصرف</th><th>ضایعات ماده</th><th>واحد</th><th>مقدار ارزش‌گذاری‌شده</th><th>نرخ میانگین (ریال)</th><th>مبلغ ارزش‌گذاری‌شده (ریال)</th><th>وضعیت</th></tr></thead>
                        <tbody>
                          @foreach($productionReport['materials'] as $material)
                            <tr>
                              <td>{{ $material['material_code'] }} — {{ $material['material_name'] }}</td>
                              <td>{{ $formatCostingNumber($material['quantity']) }}</td>
                              <td>{{ $formatCostingNumber($material['scrap_quantity']) }}</td>
                              <td>{{ $material['unit_symbol'] ?? $material['unit_name'] ?? '—' }}</td>
                              <td>{{ $formatCostingNumber($material['valued_quantity']) }}</td>
                              <td>{{ $material['unit_cost'] !== null ? $formatCostingNumber($material['unit_cost']) : '—' }}</td>
                              <td>{{ $formatCostingNumber($material['material_cost']) }}</td>
                              <td>{{ $material['cost_complete'] ? 'کامل' : 'ناقص: بدون تطبیق/ارزش‌گذاری ' . $formatCostingNumber($material['unvalued_quantity']) }}</td>
                            </tr>
                          @endforeach
                        </tbody>
                      </table>
                    </div>
                    <p class="costing-detail-note">مقدار ارزش‌گذاری‌شده از حواله‌های منطبق با قسمت تولید، ماده، سفارش و تاریخ تخصیص می‌یابد. مبلغ نشان‌داده‌شده فقط بخش ارزش‌گذاری‌شده است؛ مقدار بدون تطبیق یا ارزش‌گذاری در وضعیت مشخص می‌شود.</p>
                  @else
                    <p class="costing-detail-note">برای این گزارش ریز مواد مصرفی ثبت نشده است.</p>
                  @endif
                  <h3 style="font-size:12px;margin:14px 0 8px">خلاصه هزینه محصول در بازه انتخابی</h3>
                  @if($productCost)
                    <table class="costing-detail-costs">
                      <tbody>
                        <tr><td>مواد مصرفی</td><td>{{ $formatCostingNumber($productCost['material_cost'] ?? 0) }} ریال</td></tr>
                        <tr><td>دستمزد تجمیعی محصول</td><td>{{ $formatCostingNumber($productCost['labor_cost'] ?? 0) }} ریال</td></tr>
                        <tr><td>هزینه ضایعات</td><td>{{ $formatCostingNumber($productCost['scrap_cost'] ?? 0) }} ریال</td></tr>
                        <tr><td>هزینه‌های مستقیم خرید</td><td>{{ $formatCostingNumber($productCost['direct_cost'] ?? 0) }} ریال</td></tr>
                        <tr><td><strong>جمع بهای تمام‌شده محصول</strong></td><td><strong>{{ $formatCostingNumber($productCost['total_cost'] ?? 0) }} ریال</strong></td></tr>
                        <tr><td>فی تمام‌شده هر واحد</td><td>{{ isset($productCost['unit_cost']) && $productCost['unit_cost'] !== null ? $formatCostingNumber($productCost['unit_cost']) . ' ریال' : '—' }}</td></tr>
                      </tbody>
                    </table>
                  @else
                    <p class="costing-detail-note">برای این محصول در بازه انتخابی هزینه‌ای ثبت نشده است.</p>
                  @endif
                  <p class="costing-detail-note">ریز مواد بالا فقط تفکیک بخشی از هزینه مواد موجود است و دوباره به جمع هزینه محصول اضافه نمی‌شود. حواله‌هایی که به همین قسمت، ماده، سفارش و تاریخ قابل اتصال نباشند یا ارزش‌گذاری نشده باشند، به‌صورت مقدار ناقص نشان داده می‌شوند. سایر هزینه‌های مواد و هزینه‌های مستقیم خرید همچنان در سطح محصول تجمیع می‌شوند و لزوماً هزینه اختصاصی این گزارش یا کارگر نیستند. سربار تولید نیز هنوز به‌صورت مستقل در این گزارش ثبت/محاسبه نمی‌شود.</p>
                </div>
              </details>
            </td>
            <td>گزارش #{{ $productionReport['report_id'] }}</td>
            <td class="jalali-date" data-date="{{ $productionReport['work_date'] }}">{{ $productionReport['work_date'] }}</td>
            <td>{{ $productionReport['goods_code'] }} — {{ $productionReport['goods_name'] }}</td>
            <td>{{ $productionReport['section_name'] ?? '—' }}</td>
            <td>{{ $formatCostingNumber($productionReport['produced_quantity']) }}</td>
            <td>{{ $formatCostingNumber($productionReport['received_quantity']) }}</td>
            <td>{{ $formatCostingNumber($productionReport['scrap_quantity']) }}</td>
            <td>{{ $formatCostingNumber($productionReport['labor_cost']) }}</td>
          </tr>
        @endforeach
        </tbody>
      </table>
    @endif
  </div>
  <p style="font-size:10px;color:#91a6b9;margin:12px 0 0">دستمزد هر گزارش بر اساس کارگر و نرخ معتبر در تاریخ کار محاسبه می‌شود. با انتخاب کارگر، تعداد تولید و دستمزد به گزارش‌های همان کارگر محدود می‌شود؛ مواد، ضایعات و هزینه‌های خرید فعلاً در سطح محصول قابل ردیابی‌اند. فی تمام‌شده در خلاصه محصول از تقسیم جمع هزینه‌های محصول بر تعداد تولید ثبت‌شده در گزارش‌های تأییدشده و تحویل‌شده در بازه انتخابی به دست می‌آید.</p>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/persian-date@1.1.0/dist/persian-date.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/js/persian-datepicker.min.js"></script>
<script type="module">
import { setupProductionReportDatePicker } from '/js/production-report-date-filters.js';

document.addEventListener('DOMContentLoaded', function () {
    setupProductionReportDatePicker('#costing-start-date-picker', '#costing-start-date');
    setupProductionReportDatePicker('#costing-end-date-picker', '#costing-end-date');

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
