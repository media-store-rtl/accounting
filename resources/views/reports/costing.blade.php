@extends('layouts.dashboard-shell')

@section('title', 'گزارش بهای تمام‌شده')

@section('content')
<style>
.costing-filters,.costing-panel,.costing-card{background:#0a1b2b;border:1px solid #1b354b;border-radius:17px;padding:18px}
.costing-filters{margin-bottom:16px}
.costing-filters form{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}
.costing-filters label{display:block;color:#7890a5;font-size:10px;margin-bottom:6px}
.costing-filters select,.costing-filters button{width:100%;background:#0d2235;color:#dcebf6;border:1px solid #29465e;border-radius:10px;padding:10px;font-family:inherit;font-size:11px}
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
  <div><h1>گزارش بهای تمام‌شده</h1><p>ردیابی بهای تمام‌شده بر اساس گزارش تولید تحویل‌شده به انبار</p></div>
</div>

<div class="costing-filters">
  <form method="GET" action="{{ route('reports.costing') }}">
    <div><label for="costing-fiscal-year">سال مالی</label><select id="costing-fiscal-year" name="fiscal_year_id"><option value="">همه</option>@foreach($fiscalYears as $fy)<option value="{{ $fy->id }}" @selected((int)request('fiscal_year_id')===(int)$fy->id)>{{ $fy->name }}</option>@endforeach</select></div>
    <div><label for="costing-goods">محصول / کالا</label><select id="costing-goods" name="goods_id"><option value="">همه</option>@foreach($goods as $good)<option value="{{ $good->id }}" @selected((int)request('goods_id')===(int)$good->id)>{{ $good->code }} — {{ $good->name }}</option>@endforeach</select></div>
    <div style="display:flex;align-items:end"><button type="submit">نمایش گزارش</button></div>
  </form>
</div>

<div class="costing-cards">
  <div class="costing-card"><small>مواد مصرف‌شده</small><b>{{ number_format($report['totals']['material_cost'],4) }}</b></div>
  <div class="costing-card"><small>دستمزد تأییدشده</small><b>{{ number_format($report['totals']['labor_cost'],4) }}</b></div>
  <div class="costing-card"><small>بهای تمام‌شده</small><b>{{ number_format($report['totals']['total_cost'],4) }}</b></div>
  <div class="costing-card"><small>مصرف بدون ارزش‌گذاری</small><b>{{ number_format($report['totals']['unvalued_material_quantity'],4) }}</b></div>
  <div class="costing-card"><small>تعداد کل تولید ثبت‌شده در گزارش‌های تحویل‌شده</small><b>{{ number_format(collect($report['production_reports'])->sum('produced_quantity'),4) }}</b></div>
</div>

@if(!empty($report['valuation_methods']))
  <div class="costing-panel"><span style="color:#71879d;font-size:11px">روش ارزش‌گذاری:</span> {{ implode('، ',$report['valuation_methods']) }}</div>
@endif
@if($report['totals']['unvalued_material_quantity']>0)
  <div class="costing-warn">بخشی از خروج مواد هنوز ارزش‌گذاری نشده است؛ این مقدار عمداً در مبلغ بهای تمام‌شده وارد نشده است.</div>
@endif

<div class="costing-panel">
  <h2 style="font-size:14px;margin:0 0 12px">گزارش‌های تولید تحویل‌شده به انبار</h2>
  <div class="costing-table-wrap">
    @if(empty($report['production_reports']))
      <div class="costing-empty">گزارش تولیدی که تحویل انبار آن ثبت نهایی شده باشد، برای این فیلتر پیدا نشد.</div>
    @else
      <table class="costing-table">
        <thead><tr><th>شماره گزارش</th><th>تاریخ تولید</th><th>تاریخ تحویل انبار</th><th>بخش تولید</th><th>کارگر</th><th>محصول</th><th>تعداد تولید</th><th>تعداد تحویل‌شده</th><th>ضایعات</th><th>دستمزد این گزارش</th><th>انبار</th></tr></thead>
        <tbody>
        @foreach($report['production_reports'] as $productionReport)
          <tr>
            <td>گزارش #{{ $productionReport['report_id'] }}</td>
            <td>{{ $productionReport['work_date'] }}</td>
            <td>{{ $productionReport['warehouse_received_at'] }}</td>
            <td>{{ $productionReport['section_name'] }}</td>
            <td>{{ $productionReport['worker_name'] }}</td>
            <td>{{ $productionReport['goods_code'] }} — {{ $productionReport['goods_name'] }}</td>
            <td>{{ number_format($productionReport['produced_quantity'],4) }}</td>
            <td>{{ number_format($productionReport['received_quantity'],4) }}</td>
            <td>{{ number_format($productionReport['scrap_quantity'],4) }}</td>
            <td>{{ number_format($productionReport['labor_cost'],4) }}</td>
            <td>{{ $productionReport['warehouse_name'] ?? '—' }}</td>
          </tr>
        @endforeach
        </tbody>
      </table>
    @endif
  </div>
  <p style="font-size:10px;color:#91a6b9;margin:12px 0 0">هزینه دستمزد در این جدول بر اساس کارگر و نرخ معتبر در تاریخ کار محاسبه شده است. مواد، ضایعات و هزینه‌های خرید در بخش تجمیعی محصول نمایش داده می‌شوند، چون در داده فعلی به شماره گزارش تحویل انبار وصل نشده‌اند.</p>
</div>

<div class="costing-panel">
  <h2 style="font-size:14px;margin:0 0 12px">خلاصه هزینه‌ها به تفکیک محصول</h2>
  <div class="costing-table-wrap">
    @if(empty($report['product_rows']))
      <div class="costing-empty">برای فیلتر انتخاب‌شده داده هزینه‌ای ثبت نشده است.</div>
    @else
      <table class="costing-table">
        <thead><tr><th>محصول</th><th>مواد</th><th>دستمزد</th><th>ضایعات</th><th>جمع</th><th>روش</th><th>مصرف بدون ارزش‌گذاری</th></tr></thead>
        <tbody>
        @foreach($report['product_rows'] as $row)
          <tr><td>{{ $row['goods_code'] }} — {{ $row['goods_name'] }}</td><td>{{ number_format($row['material_cost'],4) }}</td><td>{{ number_format($row['labor_cost'],4) }}</td><td>{{ number_format($row['scrap_cost'],4) }}</td><td><strong>{{ number_format($row['total_cost'],4) }}</strong></td><td>{{ implode('، ',$row['valuation_methods']) ?: '—' }}</td><td>{{ number_format($row['unvalued_material_quantity'],4) }}</td></tr>
        @endforeach
        </tbody>
      </table>
    @endif
  </div>
</div>
@endsection
