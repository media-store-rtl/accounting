@extends('layouts.dashboard-shell')
@section('title', 'گزارش‌های تولید')
@section('content')
@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/css/persian-datepicker.min.css">
@endpush
<style>
.report-page{display:grid;gap:18px}.report-head{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap}.report-head h1{margin:0;font-size:24px}.report-head p{margin:7px 0 0;color:#8ba1b5;font-size:12px;line-height:1.9}.report-actions{display:flex;gap:8px;flex-wrap:wrap}.report-btn{display:inline-flex;align-items:center;justify-content:center;border:1px solid #29465e;border-radius:9px;background:#12314a;color:#e9f3fb;padding:10px 13px;text-decoration:none;font-size:12px}.report-btn.primary{background:#126b5a;border-color:#1b8b74}.report-filter{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:10px;align-items:end}.report-filter label{display:grid;gap:6px;font-size:11px;color:#cce0ef}.report-filter input,.report-filter select{min-width:0;width:100%;background:#071725;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:10px;font:inherit}.report-table{width:100%;border-collapse:collapse;min-width:850px}.report-table th,.report-table td{padding:12px 10px;border-bottom:1px solid #20384c;text-align:right;vertical-align:top;font-size:12px}.report-table th{color:#8ba1b5;font-weight:600}.report-muted{display:block;color:#8ba1b5;font-size:10px;line-height:1.8;margin-top:4px}.report-status{display:inline-block;border-radius:20px;padding:5px 9px;background:#302b20;color:#f3d58c;font-size:10px;white-space:nowrap}.report-status.good{background:#12372f;color:#6ee7d0}.report-status.bad{background:#3b2528;color:#ffb8a8}.report-link{color:#6ee7d0;text-decoration:none;white-space:nowrap}.report-flash{padding:12px 14px;border-radius:10px;background:#12372f;color:#b8f5df;font-size:12px}.report-empty{padding:24px;text-align:center;color:#8ba1b5}.report-pagination{margin-top:15px;overflow:auto}
@media(max-width:900px){.report-filter{grid-template-columns:repeat(3,minmax(0,1fr))}}@media(max-width:560px){.report-filter{grid-template-columns:repeat(2,minmax(0,1fr))}.report-head h1{font-size:21px}}
</style>
<div class="report-page">
  <div class="report-head"><div><h1>گزارش‌های تولید</h1><p>سوابق تولید، وضعیت بررسی سرپرست و تحویل محصول به انبار را جست‌وجو و پیگیری کنید.</p></div><div class="report-actions"><a class="report-btn primary" href="{{ route('production.shift-reports.create') }}">+ ثبت تولید شیفت</a></div></div>
  @if(session('success'))<div class="report-flash">{{ session('success') }}</div>@endif
  <div class="panel" style="padding:16px"><form method="GET" action="{{ route('production.shift-reports.index') }}" class="report-filter">
    <label>جست‌وجو (شماره، محصول، کارگر یا قسمت)<input name="q" value="{{ request('q') }}" placeholder="مثلاً محصول یا ۱۲۳"></label>
    <label>وضعیت<select name="status"><option value="">همه وضعیت‌ها</option>@foreach(['pending'=>'در انتظار تأیید','approved'=>'تأییدشده','rejected'=>'ردشده','received'=>'تحویل کامل به انبار'] as $key=>$label)<option value="{{ $key }}" @selected(request('status')===$key)>{{ $label }}</option>@endforeach</select></label>
    <label>قسمت تولید<select name="section_id"><option value="">همه قسمت‌ها</option>@foreach($sections as $section)<option value="{{ $section->id }}" @selected((string)request('section_id')===(string)$section->id)>{{ $section->name ?: 'قسمت '.$section->id }}</option>@endforeach</select></label>
    <label>کارگر<select name="personnel_id"><option value="">همه کارگران</option>@foreach($personnel as $worker)<option value="{{ $worker->id }}" @selected((string)request('personnel_id')===(string)$worker->id)>{{ $worker->name }}</option>@endforeach</select></label>
    <label>از تاریخ<input id="from_picker" type="text" inputmode="none" autocomplete="off" placeholder="انتخاب تاریخ شمسی" value="{{ request('from') }}"><input id="from" name="from" type="hidden" value="{{ request('from') }}"></label><label>تا تاریخ<input id="to_picker" type="text" inputmode="none" autocomplete="off" placeholder="انتخاب تاریخ شمسی" value="{{ request('to') }}"><input id="to" name="to" type="hidden" value="{{ request('to') }}"></label>
    <div class="report-actions" style="grid-column:1/-1"><button class="report-btn primary" type="submit">اعمال فیلتر</button><a class="report-btn" href="{{ route('production.shift-reports.index') }}">پاک‌کردن فیلترها</a></div>
  </form></div>
  <div class="panel" style="padding:0;overflow:hidden"><div style="padding:16px 18px;border-bottom:1px solid #20384c"><strong>آخرین گزارش‌ها</strong><span class="report-muted">جزئیات مواد اولیه و ضایعات در صفحه هر گزارش نمایش داده می‌شود.</span></div>
  <div style="overflow:auto"><table class="report-table"><thead><tr><th>شماره</th><th>تاریخ / کارکرد</th><th>قسمت</th><th>کارگر</th><th>محصول</th><th>تولید سالم</th><th>وضعیت</th><th>ثبت‌کننده</th><th>جزئیات</th></tr></thead><tbody>
  @forelse($rows as $row)
    @php($labels=['pending'=>'در انتظار تأیید','approved'=>'تأییدشده','rejected'=>'ردشده','received'=>'تحویل کامل به انبار'])
    <tr><td>#{{ $row->id }}</td><td><span class="shift-date-display" data-iso-date="{{ $row->work_date }}">{{ $row->work_date }}</span><span class="report-muted">کارکرد: {{ $row->worked_hours ?? '—' }} ساعت</span></td><td>{{ $row->section_name ?: '—' }}</td><td>{{ $row->worker_name ?: '—' }}</td><td>{{ $row->goods_name }}@if($row->goods_code)<span class="report-muted">{{ $row->goods_code }}</span>@endif</td><td><span class="production-quantity-display" data-quantity="{{ max(0,(float)$row->produced_quantity-(float)($row->scrap_quantity ?? 0)) }}"></span><span class="report-muted">تولید: <span class="production-quantity-display" data-quantity="{{ $row->produced_quantity }}"></span> / ضایعات: <span class="production-quantity-display" data-quantity="{{ $row->scrap_quantity ?? 0 }}"></span></span></td><td><span class="report-status {{ in_array($row->status,['approved','received'],true) ? 'good' : ($row->status==='rejected' ? 'bad' : '') }}">{{ $row->status==='approved' && !(bool)($row->goods_sellable ?? false) ? 'تأییدشده؛ نیازمند تعیین مسیر انتقال' : ($labels[$row->status] ?? $row->status) }}</span></td><td>{{ $row->reporter_name }}</td><td><a class="report-link" href="{{ route('production.shift-reports.show',$row->id) }}">مشاهده جزئیات ←</a></td></tr>
  @empty<tr><td colspan="9" class="report-empty">گزارشی با این مشخصات پیدا نشد.</td></tr>@endforelse
  </tbody></table></div>
  @if(method_exists($rows,'links'))<div class="report-pagination" style="padding:0 16px 16px">{{ $rows->links() }}</div>@endif
  </div>
</div>
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
document.querySelectorAll('.production-quantity-display[data-quantity]').forEach(el=>{const value=Number(el.dataset.quantity);if(!Number.isFinite(value))return;el.textContent=new Intl.NumberFormat('fa-IR',{useGrouping:false,maximumFractionDigits:4}).format(value);});
document.querySelectorAll('.shift-date-display[data-iso-date]').forEach(el=>{const raw=(el.dataset.isoDate||'').slice(0,10),m=raw.match(/^(\d{4})-(\d{2})-(\d{2})$/);if(!m)return;const date=new Date(Number(m[1]),Number(m[2])-1,Number(m[3]));el.textContent=new Intl.DateTimeFormat('fa-IR-u-ca-persian',{year:'numeric',month:'2-digit',day:'2-digit'}).format(date);});
</script>@endpush
@endsection
