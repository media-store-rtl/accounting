@extends('layouts.dashboard-shell')

@section('title', 'ویرایش رسید انبار')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/css/persian-datepicker.min.css">
<style>
.receipt-date-picker{box-sizing:border-box;width:100%;min-height:42px;background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit}
@media(max-width:650px){.datepicker-container{position:fixed!important;top:50%!important;left:50%!important;right:auto!important;bottom:auto!important;transform:translate(-50%,-50%)!important;z-index:10000!important;max-width:calc(100vw - 20px)!important}.datepicker-plot-area{max-width:calc(100vw - 20px)!important;box-sizing:border-box!important}}
</style>
@endpush

@section('content')
<div class="page-heading"><div><h1>ویرایش رسید انبار #{{ $receipt->id }}</h1><p>فقط رسیدهای در انتظار تأیید قابل ویرایش‌اند.</p></div><a class="btn" href="{{ route('workflows.receipts') }}">بازگشت به رسیدها</a></div>
@if($errors->any())<div role="alert" style="padding:12px 15px;margin-bottom:16px;border:1px solid #714448;border-radius:12px;background:#302126;color:#ffb8a8;font-size:12px">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
<div class="panel">
    <div style="padding:12px 14px;margin-bottom:16px;border:1px solid #65552c;border-radius:10px;background:#292719;color:#f2d58b;font-size:12px;line-height:1.9">ویرایش این رسید تا پیش از تأیید امکان‌پذیر است. پس از تأیید، رسید به گردش موجودی و بهای تمام‌شده متصل می‌شود و برای حفظ سوابق مالی قفل خواهد شد.</div>
    <form method="POST" action="{{ route('workflows.receipts.update',$receipt->id) }}" id="receipt-edit-form">
        @csrf
        @method('PUT')
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,220px),1fr));gap:14px">
            <label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">وضعیت خرید
                <input value="{{ $receipt->purchase_id ? 'خرید #'.$receipt->purchase_id : 'رسید اولیه؛ خرید پس از تکمیل تدارکات ساخته می‌شود' }}" disabled style="background:#142a3b;color:#9eb2c4;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit">
            </label>
            <label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">انبار
                <select name="warehouse_location_id" required style="background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit">
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}" @selected((string)old('warehouse_location_id',$receipt->warehouse_location_id)===(string)$location->id)>{{ $location->name }}</option>
                    @endforeach
                </select>
            </label>
            <label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">تاریخ دریافت
                <input id="received_date_picker" type="text" class="receipt-date-picker" inputmode="none" autocomplete="off" placeholder="انتخاب تاریخ شمسی" required>
                <input id="received_date_iso" type="hidden" value="{{ old('received_at') ? substr(old('received_at'),0,10) : substr($receipt->received_at,0,10) }}">
            </label>
            <label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">ساعت دریافت
                <input id="received_time" type="time" value="{{ old('received_at') ? substr(old('received_at'),11,5) : substr($receipt->received_at,11,5) }}" required style="background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit;color-scheme:dark">
            </label>
            <input name="received_at" id="received_at" type="hidden" value="{{ old('received_at',$receipt->received_at) }}">
            <label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">توضیحات (اختیاری)
                <input name="notes" value="{{ old('notes',$receipt->notes) }}" style="background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit">
            </label>
        </div>
        <h2 style="font-size:14px;margin:22px 0 12px">اقلام رسید</h2>
        <div class="table-wrap"><table><thead><tr><th>کالا</th><th>مقدار دریافتی</th></tr></thead><tbody>
            @foreach($receipt->items as $item)
            <tr>
                <td>{{ $item->goods_name }}<input type="hidden" name="items[{{ $loop->index }}][id]" value="{{ $item->id }}"><input type="hidden" name="items[{{ $loop->index }}][goods_id]" value="{{ $item->goods_id }}"></td>
                <td><input name="items[{{ $loop->index }}][quantity]" type="number" step="0.0001" min="0.0001" required value="{{ old('items.'.$loop->index.'.quantity',$item->quantity) }}" style="width:150px;max-width:100%;background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:10px;font:inherit"></td>
            </tr>
            @endforeach
        </tbody></table></div>
        <div style="margin-top:18px;display:flex;gap:9px;flex-wrap:wrap"><button class="btn primary" type="submit">ذخیره تغییرات رسید</button><a class="btn" href="{{ route('workflows.receipts') }}">انصراف</a></div>
    </form>
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
document.getElementById('receipt-edit-form')?.addEventListener('submit', function (event) {
    const date = document.getElementById('received_date_iso').value;
    const time = document.getElementById('received_time').value;
    if (!date || !time) {
        event.preventDefault();
        alert('لطفاً تاریخ شمسی و ساعت دریافت را انتخاب کنید.');
        return;
    }
    document.getElementById('received_at').value = date + 'T' + time;
});
</script>
@endsection
