@extends('layouts.dashboard-shell')

@section('title', 'رسیدهای خرید')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/css/persian-datepicker.min.css">
<style>
.receipt-date-picker{box-sizing:border-box;width:100%;min-height:42px;background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit}
@media(max-width:650px){.datepicker-container{position:fixed!important;top:50%!important;left:50%!important;right:auto!important;bottom:auto!important;transform:translate(-50%,-50%)!important;z-index:10000!important;max-width:calc(100vw - 20px)!important}.datepicker-plot-area{max-width:calc(100vw - 20px)!important;box-sizing:border-box!important}}
</style>
@endpush

@section('content')
<div class="page-heading"><div><h1>رسیدهای خرید</h1><p>ثبت دریافت کالا و مدیریت تأیید رسیدهای انبار</p></div></div>
@if(session('success'))<div style="padding:12px 15px;margin-bottom:16px;border:1px solid #246451;border-radius:12px;background:#0c282b;color:#8ce8ce;font-size:12px">{{ session('success') }}</div>@endif
@if($errors->any())<div style="padding:12px 15px;margin-bottom:16px;border:1px solid #714448;border-radius:12px;background:#302126;color:#ffb8a8;font-size:12px">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif

<div class="panel" style="margin-bottom:18px">
    <h2 style="font-size:15px;margin:0 0 16px">ثبت رسید جدید</h2>
    <form method="POST" action="{{ route('workflows.receipts.store') }}">@csrf
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,220px),1fr));gap:14px">
            <label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">خرید
                <select name="purchase_id" required style="background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit">@foreach($purchases as $p)<option value="{{ $p->id }}" @selected((string)old('purchase_id')===(string)$p->id)>خرید #{{ $p->id }}</option>@endforeach</select>
            </label>
            <label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">انبار
                <select name="warehouse_location_id" required style="background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit">@foreach($locations as $l)<option value="{{ $l->id }}" @selected((string)old('warehouse_location_id')===(string)$l->id)>{{ $l->name }}</option>@endforeach</select>
            </label>
            <label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">تاریخ دریافت
                <input id="received_date_picker" type="text" class="receipt-date-picker" inputmode="none" autocomplete="off" placeholder="انتخاب تاریخ شمسی" required>
                <input id="received_date_iso" type="hidden" value="{{ old('received_at') ? substr(old('received_at'), 0, 10) : '' }}">
            </label>
            <label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">ساعت دریافت
                <input id="received_time" type="time" value="{{ old('received_at') ? substr(old('received_at'), 11, 5) : '' }}" required style="background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit;color-scheme:dark">
            </label>
            <input name="received_at" type="hidden" value="{{ old('received_at') }}">
            <label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">کالا
                <select name="items[0][goods_id]" required style="background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit">@foreach($goods as $g)<option value="{{ $g->id }}" @selected((string)old('items.0.goods_id')===(string)$g->id)>{{ $g->name }}</option>@endforeach</select>
            </label>
            <label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">مقدار
                <input name="items[0][quantity]" type="number" step="0.0001" min="0.0001" value="{{ old('items.0.quantity') }}" required style="background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit">
            </label>
            <label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">توضیحات (اختیاری)
                <input name="notes" value="{{ old('notes') }}" style="background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit">
            </label>
        </div>
        <div style="margin-top:18px"><button class="btn primary" type="submit">ثبت رسید</button></div>
    </form>
</div>

<div class="panel"><h2 style="font-size:15px;margin:0 0 14px">رسیدهای ثبت‌شده</h2>
<div class="table-wrap"><table><thead><tr><th>شناسه</th><th>خرید</th><th>وضعیت</th><th>عملیات</th></tr></thead><tbody>
@forelse($rows as $row)<tr><td>#{{ $row->id }}</td><td>#{{ $row->purchase_id }}</td><td><span class="status {{ $row->status === 'approved' ? 'active' : 'inactive' }}">{{ $row->status }}</span></td><td>@if($row->status==='pending')<form method="POST" action="{{ route('workflows.receipts.approve',$row->id) }}">@csrf<button class="btn" type="submit">تأیید رسید</button></form>@else—@endif</td></tr>@empty<tr><td colspan="4" style="text-align:center;padding:28px;color:#8198ac">رسیدی برای نمایش وجود ندارد.</td></tr>@endforelse
</tbody></table></div>
@if(method_exists($rows, 'links'))<div class="pagination">{{ $rows->links() }}</div>@endif
</div>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/persian-date@1.1.0/dist/persian-date.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/js/persian-datepicker.min.js"></script>
<script type="module">
import { setupProductionReportDatePicker } from '/js/production-report-date-filters.js';
document.addEventListener('DOMContentLoaded', function () {
    setupProductionReportDatePicker('#received_date_picker', '#received_date_iso');
});
</script>
<script>
const receiptForm = document.querySelector('form[action="{{ route('workflows.receipts.store') }}"]');
if (receiptForm) {
    receiptForm.addEventListener('submit', function (event) {
        const date = document.getElementById('received_date_iso').value;
        const time = document.getElementById('received_time').value;
        if (!date || !time) {
            event.preventDefault();
            alert('لطفاً تاریخ شمسی و ساعت دریافت را انتخاب کنید.');
            return;
        }
        receiptForm.querySelector('[name="received_at"]').value = date + 'T' + time;
    });
}
</script>
@endsection