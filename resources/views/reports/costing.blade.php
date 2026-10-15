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
.costing-empty{text-align:center;padding:35px;color:#71879d;border:1px dashed #29455d;border-radius:12px;font-size:11px}
@media(max-width:800px){.costing-filters form{grid-template-columns:1fr 1fr}.costing-cards{grid-template-columns:1fr 1fr}}
@media(max-width:500px){.costing-filters form,.costing-cards{grid-template-columns:1fr}}
</style>
<div class="page-heading">
  <div><h1>گزارش بهای تمام‌شده</h1><p>ردیابی بهای تمام‌شده بر اساس گزارش‌های تولید تأییدشده و تحویل‌شده</p></div>
</div>

<div class="costing-filters">
  <form method="GET" action="{{ route('reports.costing') }}">
    <div><label for="costing-fiscal-year">سال مالی</label><select id="costing-fiscal-year" name="fiscal_year_id"><option value="">همه</option>@foreach($fiscalYears as $fy)<option value="{{ $fy->id }}" @selected((int)request('fiscal_year_id')===(int)$fy->id)>{{ $fy->name }}</option>@endforeach</select></div>
    <div><label for="costing-goods">محصول / کالا</label><select id="costing-goods" name="goods_id"><option value="">همه</option>@foreach($goods as $good)<option value="{{ $good->id }}" @selected((int)request('goods_id')===(int)$good->id)>{{ $good->code }} — {{ $good->name }}</option>@endforeach</select></div>
    <div><label for="costing-start-date">از تاریخ</label><input id="costing-start-date-picker" type="text" inputmode="none" autocomplete="off" placeholder="انتخاب تاریخ شمسی" value="{{ request('start_date') }}"><input id="costing-start-date" type="hidden" name="start_date" value="{{ request('start_date') }}"></div>
    <div><label for="costing-end-date">تا تاریخ</label><input id="costing-end-date-picker" type="text" inputmode="none" autocomplete="off" placeholder="انتخاب تاریخ شمسی" value="{{ request('end_date') }}"><input id="costing-end-date" type="hidden" name="end_date" value="{{ request('end_date') }}"></div>
    <div><label for="costing-personnel">کارگر</label><select id="costing-personnel" name="personnel_id"><option value="">همه کارگران</option>@foreach($personnel as $worker)<option value="{{ $worker->id }}" @selected((int)request('personnel_id')===(int)$worker->id)>{{ $worker->code }} — {{ $worker->name }}</option>@endforeach</select></div>
    <div><label for="costing-production-section">قسمت تولید</label><select id="costing-production-section" name="production_section_id"><option value="">همه قسمت‌ها</option>@foreach($productionSections as $section)<option value="{{ $section->id }}" @selected((int)request('production_section_id')===(int)$section->id)>{{ $section->name }}</option>@endforeach</select></div>
    <div><label for="costing-production-stage">مرحله تولید</label><select id="costing-production-stage" name="production_stage_id"><option value="">همه مراحل</option>@foreach($productionStages as $stage)<option value="{{ $stage->id }}" @selected((int)request('production_stage_id')===(int)$stage->id)>{{ $stage->route_name }} — {{ $stage->name }}</option>@endforeach</select></div>
    <div style="display:flex;align-items:end"><button type="submit">نمایش گزارش</button></div>
  </form>
</div>

<div class="costing-cards">
  <div class="costing-card"><small>مواد مصرف‌شده</small><b>{{ $formatCostingNumber($report['totals']['material_cost']) }}</b></div>
  <div class="costing-card"><small>دستمزد تأییدشده</small><b>{{ $formatCostingNumber($report['totals']['labor_cost']) }}</b></div>
  <div class="costing-card"><small>بهای تمام‌شده</small><b>{{ $formatCostingNumber($report['totals']['total_cost']) }}</b></div>
  <div class="costing-card"><small>مصرف بدون ارزش‌گذاری</small><b>{{ $formatCostingNumber($report['totals']['unvalued_material_quantity']) }}</b></div>
  <div class="costing-card"><small>تعداد کل تولید در گزارش‌های تأییدشده و تحویل‌شده</small><b>{{ $formatCostingNumber(collect($report['production_reports'])->sum('produced_quantity')) }}</b></div>
</div>

@if(!empty($report['valuation_methods']))
  <div class="costing-panel"><span style="color:#71879d;font-size:11px">روش ارزش‌گذاری:</span> {{ implode('، ',$report['valuation_methods']) }}</div>
@endif
@if($report['totals']['unvalued_material_quantity']>0)
  <div class="costing-warn">بخشی از خروج مواد هنوز ارزش‌گذاری نشده است؛ این مقدار عمداً در مبلغ بهای تمام‌شده وارد نشده است.</div>
@endif

<div class="costing-panel">
  <h2 style="font-size:14px;margin:0 0 12px">گزارش‌های تولید تأییدشده و تحویل‌شده</h2>
  <div class="costing-table-wrap">
    @if(empty($report['production_reports']))
      <div class="costing-empty">گزارش تولید تأییدشده یا تحویل‌شده‌ای برای این فیلتر پیدا نشد.</div>
    @else
      <table class="costing-table">
        <thead><tr><th>شماره گزارش</th><th>تاریخ تولید</th><th>تاریخ تحویل انبار</th><th>بخش تولید</th><th>کارگر</th><th>محصول</th><th>تعداد تولید</th><th>تعداد تحویل‌شده</th><th>ضایعات</th><th>دستمزد این گزارش</th><th>انبار</th></tr></thead>
        <tbody>
        @foreach($report['production_reports'] as $productionReport)
          <tr>
            <td>گزارش #{{ $productionReport['report_id'] }}</td>
            <td class="jalali-date" data-date="{{ $productionReport['work_date'] }}">{{ $productionReport['work_date'] }}</td>
            <td class="jalali-date" data-date="{{ $productionReport['warehouse_received_at'] }}">{{ $productionReport['warehouse_received_at'] }}</td>
            <td>{{ $productionReport['section_name'] }}</td>
            <td>{{ $productionReport['worker_name'] ?? 'ثبت نشده' }}</td>
            <td>{{ $productionReport['goods_code'] }} — {{ $productionReport['goods_name'] }}</td>
            <td>{{ $formatCostingNumber($productionReport['produced_quantity']) }}</td>
            <td>{{ $formatCostingNumber($productionReport['received_quantity']) }}</td>
            <td>{{ $formatCostingNumber($productionReport['scrap_quantity']) }}</td>
            <td>{{ $formatCostingNumber($productionReport['labor_cost']) }}</td>
            <td>{{ $productionReport['warehouse_name'] ?? '—' }}</td>
          </tr>
        @endforeach
        </tbody>
      </table>
    @endif
  </div>
  <p style="font-size:10px;color:#91a6b9;margin:12px 0 0">هزینه دستمزد گزارش بر اساس کارگر و نرخ معتبر در تاریخ کار محاسبه می‌شود. با انتخاب کارگر، تعداد تولید و دستمزد به گزارش‌های همان کارگر محدود می‌شود؛ مواد، ضایعات و هزینه‌های خرید فعلاً در سطح محصول قابل ردیابی‌اند و به کارگر مشخصی وصل نیستند. فی تمام‌شده از تقسیم جمع هزینه‌های محصول در بازه انتخابی بر تعداد تولید ثبت‌شده در گزارش‌های تأییدشده و تحویل‌شده در همان بازه به دست می‌آید.</p>
</div>

<div class="costing-panel">
  <h2 style="font-size:14px;margin:0 0 12px">خلاصه هزینه‌ها به تفکیک محصول</h2>
  <div class="costing-table-wrap">
    @if(empty($report['product_rows']))
      <div class="costing-empty">برای فیلتر انتخاب‌شده داده هزینه‌ای ثبت نشده است.</div>
    @else
      <table class="costing-table">
        <thead><tr><th>محصول</th><th>تعداد تولیدشده</th><th>مواد</th><th>دستمزد</th><th>ضایعات</th><th>هزینه مستقیم</th><th>جمع بهای تمام‌شده</th><th>فی تمام‌شده هر عدد</th><th>روش</th><th>مصرف بدون ارزش‌گذاری</th></tr></thead>
        <tbody>
        @foreach($report['product_rows'] as $row)
          <tr><td>{{ $row['goods_code'] }} — {{ $row['goods_name'] }}</td><td>{{ $formatCostingNumber($row['produced_quantity'] ?? 0) }}</td><td>{{ $formatCostingNumber($row['material_cost']) }}</td><td>{{ $formatCostingNumber($row['labor_cost']) }}</td><td>{{ $formatCostingNumber($row['scrap_cost']) }}</td><td>{{ $formatCostingNumber($row['direct_cost']) }}</td><td><strong>{{ $formatCostingNumber($row['total_cost']) }}</strong></td><td><strong>{{ isset($row['unit_cost']) && $row['unit_cost'] !== null ? $formatCostingNumber($row['unit_cost']) : '—' }}</strong></td><td>{{ implode('، ',$row['valuation_methods']) ?: '—' }}</td><td>{{ $formatCostingNumber($row['unvalued_material_quantity']) }}</td></tr>
        @endforeach
        </tbody>
      </table>
    @endif
  </div>
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
