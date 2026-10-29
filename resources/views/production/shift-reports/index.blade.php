@extends('layouts.dashboard-shell')

@section('title', 'کارکرد تولید | حسابداری صنعتی')

@section('content')
@php
$statusLabels = [
    'pending' => 'در انتظار تأیید',
    'approved' => 'تأییدشده',
    'rejected' => 'ردشده',
    'received' => 'تحویل کامل به انبار',
];
@endphp
<div class="page-heading">
  <div>
    <h1>کارکرد تولید</h1>
    <p>ثبت، جست‌وجو و پیگیری کارکرد شیفت‌های تولید و وضعیت تحویل محصول</p>
  </div>
</div>
<div class="panel">
  @if(session('success'))<div class="alert success" role="status">{{ session('success') }}</div>@endif

  <div class="actions">
    <a class="btn primary" href="{{ route('production.shift-reports.create') }}">+ ثبت کارکرد جدید</a>
  </div>

  <form method="GET" action="{{ route('production.shift-reports.index') }}" style="margin-bottom:18px">
    <div style="display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:10px;align-items:end">
      <label>جست‌وجو (شماره، محصول، کارگر یا قسمت)<input name="q" value="{{ request('q') }}" placeholder="مثلاً محصول یا ۱۲۳"></label>
      <label>وضعیت<select name="status"><option value="">همه وضعیت‌ها</option>@foreach($statusLabels as $key=>$label)<option value="{{ $key }}" @selected(request('status')===$key)>{{ $label }}</option>@endforeach</select></label>
      <label>قسمت تولید<select name="section_id"><option value="">همه قسمت‌ها</option>@foreach($sections as $section)<option value="{{ $section->id }}" @selected((string)request('section_id')===(string)$section->id)>{{ $section->name ?: 'قسمت '.$section->id }}</option>@endforeach</select></label>
      <label>کارگر<select name="personnel_id"><option value="">همه کارگران</option>@foreach($personnel as $worker)<option value="{{ $worker->id }}" @selected((string)request('personnel_id')===(string)$worker->id)>{{ $worker->name }}</option>@endforeach</select></label>
      <label>از تاریخ<input id="from_picker" type="text" inputmode="none" autocomplete="off" placeholder="انتخاب تاریخ شمسی" value="{{ request('from') }}"><input id="from" name="from" type="hidden" value="{{ request('from') }}"></label>
      <label>تا تاریخ<input id="to_picker" type="text" inputmode="none" autocomplete="off" placeholder="انتخاب تاریخ شمسی" value="{{ request('to') }}"><input id="to" name="to" type="hidden" value="{{ request('to') }}"></label>
    </div>
    <div class="actions" style="margin-top:12px">
      <button class="btn primary" type="submit">اعمال فیلتر</button>
      <a class="btn" href="{{ route('production.shift-reports.index') }}">پاک‌کردن فیلترها</a>
    </div>
  </form>

  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>شماره</th><th>تاریخ / کارکرد</th><th>قسمت</th><th>کارگر</th><th>محصول</th><th>تولید سالم</th><th>وضعیت</th><th>ثبت‌کننده</th><th>عملیات</th></tr>
      </thead>
      <tbody>
        @forelse($rows as $row)
          <tr>
            <td>#{{ $row->id }}</td>
            <td><time class="jalali-date" datetime="{{ $row->work_date }}">{{ $row->work_date }}</time><div style="color:#71879d;font-size:11px;margin-top:4px">کارکرد: {{ $row->worked_hours ?? '—' }} ساعت</div></td>
            <td>{{ $row->section_name ?: '—' }}</td>
            <td>{{ $row->worker_name ?: '—' }}</td>
            <td>{{ $row->goods_name }}@if($row->goods_code)<div style="color:#71879d;font-size:11px;margin-top:4px">{{ $row->goods_code }}</div>@endif</td>
            <td><span class="production-quantity-display" data-quantity="{{ max(0,(float)$row->produced_quantity-(float)($row->scrap_quantity ?? 0)) }}"></span><div style="color:#71879d;font-size:11px;margin-top:4px">تولید: <span class="production-quantity-display" data-quantity="{{ $row->produced_quantity }}"></span> / ضایعات: <span class="production-quantity-display" data-quantity="{{ $row->scrap_quantity ?? 0 }}"></span></div></td>
            <td><span class="status">{{ $row->status==='approved' && !(bool)($row->goods_sellable ?? false) ? 'تأییدشده؛ نیازمند تعیین مسیر انتقال' : ($statusLabels[$row->status] ?? $row->status) }}</span></td>
            <td>{{ $row->reporter_name }}</td>
            <td style="white-space:nowrap"><a class="btn" href="{{ route('production.shift-reports.show',$row->id) }}">مشاهده جزئیات</a></td>
          </tr>
        @empty
          <tr><td colspan="9" style="text-align:center;color:#71879d;padding:28px">هنوز کارکرد تولیدی با این مشخصات ثبت نشده است.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  @if(method_exists($rows,'links'))<div class="pagination">{{ $rows->links() }}</div>@endif
</div>
@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/css/persian-datepicker.min.css">
<style>
@media(max-width:1100px){.panel form > div:first-child{grid-template-columns:repeat(3,minmax(0,1fr))!important}}
@media(max-width:650px){.panel form > div:first-child{grid-template-columns:repeat(2,minmax(0,1fr))!important}}
</style>
@endpush
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/persian-date@1.1.0/dist/persian-date.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/js/persian-datepicker.min.js"></script>
<script type="module">
import { setupProductionReportDatePicker } from '/js/production-report-date-filters.js';
document.addEventListener('DOMContentLoaded', function () {
  setupProductionReportDatePicker('#from_picker', '#from');
  setupProductionReportDatePicker('#to_picker', '#to');
});
</script>
<script>
document.querySelectorAll('.production-quantity-display[data-quantity]').forEach(el=>{const value=Number(el.dataset.quantity);if(Number.isFinite(value))el.textContent=new Intl.NumberFormat('fa-IR',{useGrouping:false,maximumFractionDigits:4}).format(value);});
document.querySelectorAll('.jalali-date').forEach((el) => {
  const raw = (el.getAttribute('datetime') || '').slice(0, 10);
  const match = raw.match(/^(\d{4})-(\d{2})-(\d{2})$/);
  if (!match) return;
  const date = new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]));
  el.textContent = new Intl.DateTimeFormat('fa-IR-u-ca-persian', {year:'numeric',month:'2-digit',day:'2-digit'}).format(date);
});
</script>
@endpush
@endsection
