@extends('layouts.dashboard-shell')

@section('title', 'ثبت خرید جدید')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/css/persian-datepicker.min.css">
<style>
.purchase-date-picker{box-sizing:border-box;width:100%;min-height:42px;background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit}
@media(max-width:650px){.datepicker-container{position:fixed!important;top:50%!important;left:50%!important;right:auto!important;bottom:auto!important;transform:translate(-50%,-50%)!important;z-index:10000!important;max-width:calc(100vw - 20px)!important}.datepicker-plot-area{max-width:calc(100vw - 20px)!important;box-sizing:border-box!important}}
</style>
@endpush

@section('content')
<div class="page-heading"><div><h1>ثبت خرید جدید</h1><p>ثبت خرید از تأمین‌کننده یا خرید مستقل از درخواست تأمین</p></div><a class="btn" href="{{ route('workflows.purchasing') }}">بازگشت به فهرست خریدها</a></div>
@if(session('success'))<div style="padding:12px 15px;margin-bottom:16px;border:1px solid #246451;border-radius:12px;background:#0c282b;color:#8ce8ce;font-size:12px">{{ session('success') }}</div>@endif
@if($errors->any())<div style="padding:12px 15px;margin-bottom:16px;border:1px solid #714448;border-radius:12px;background:#302126;color:#ffb8a8;font-size:12px">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
<div class="panel"><h2 style="font-size:15px;margin:0 0 16px">مشخصات خرید</h2>
<form method="POST" action="{{ route('workflows.purchasing.store') }}">@csrf
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,220px),1fr));gap:14px">
<label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">سال مالی فعال<select name="fiscal_year_id" required style="background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit">@foreach($years as $y)<option class="fiscal-year-option" value="{{ $y->id }}" data-year-name="{{ $y->name }}" data-start="{{ substr($y->starts_at,0,10) }}" data-end="{{ substr($y->ends_at,0,10) }}" @selected((string)old('fiscal_year_id', $activeFiscalYearId)===(string)$y->id)>{{ $y->name }}</option>@endforeach</select></label>
<label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">تأمین‌کننده (اختیاری)<select name="supplier_id" style="background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit"><option value="">بدون تأمین‌کننده</option>@foreach($suppliers as $s)<option value="{{ $s->id }}" @selected((string)old('supplier_id')===(string)$s->id)>{{ $s->name }}</option>@endforeach</select></label>
<label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">درخواست تأمین (اختیاری)<select name="supply_request_id" style="background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit"><option value="">بدون اتصال به درخواست</option>@foreach($requests as $s)<option value="{{ $s->id }}" @selected((string)old('supply_request_id')===(string)$s->id)>درخواست #{{ $s->id }}</option>@endforeach</select></label>
<div id="purchase-items-container" style="display:flex;flex-direction:column;gap:12px;min-width:0">
<div class="purchase-item-row" data-index="0" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,170px),1fr));gap:10px;padding:12px;border:1px solid #29465e;border-radius:10px">
<label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">کالا<select name="items[0][goods_id]" required style="background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit">@foreach($goods as $g)<option value="{{ $g->id }}" @selected((string)old('items.0.goods_id')===(string)$g->id)>{{ $g->name }}</option>@endforeach</select></label>
<label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">مقدار<input name="items[0][quantity]" type="number" min="0.0001" step="0.0001" value="{{ old('items.0.quantity') }}" required style="background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit"></label>
<label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">قیمت واحد<input name="items[0][unit_price]" type="number" min="0.0001" step="0.0001" value="{{ old('items.0.unit_price') }}" required style="background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit"></label>
<div style="display:flex;align-items:end"><button class="btn" type="button" data-remove-purchase-item disabled style="width:100%">حذف قلم</button></div>
</div></div>
<button class="btn" id="add-purchase-item" type="button" style="margin-top:10px">+ افزودن قلم کالا</button>
<label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">تاریخ خرید<input id="purchased_date_picker" type="text" class="purchase-date-picker" inputmode="none" autocomplete="off" placeholder="انتخاب تاریخ شمسی" required><input id="purchased_date_iso" type="hidden" name="purchased_at" value="{{ old('purchased_at') }}"></label>
</div><div style="margin-top:18px;display:flex;gap:10px;flex-wrap:wrap"><button class="btn primary" type="submit">ثبت خرید</button><a class="btn" href="{{ route('workflows.purchasing') }}">انصراف</a></div></form></div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/persian-date@1.1.0/dist/persian-date.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/js/persian-datepicker.min.js"></script>
<script type="module">
import { setupProductionReportDatePicker } from '/js/production-report-date-filters.js';
document.addEventListener('DOMContentLoaded', function () {
    setupProductionReportDatePicker('#purchased_date_picker', '#purchased_date_iso');
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    function toJalali(isoDate) {
        const match = String(isoDate || '').match(/^(\d{4})-(\d{2})-(\d{2})/);
        if (!match) return isoDate || '';
        const date = new Date(Date.UTC(Number(match[1]), Number(match[2]) - 1, Number(match[3])));
        return new Intl.DateTimeFormat('fa-IR-u-ca-persian', {
            year: 'numeric', month: '2-digit', day: '2-digit', timeZone: 'UTC'
        }).format(date);
    }
    document.querySelectorAll('.fiscal-year-option').forEach(function (option) {
        option.textContent = option.dataset.yearName + ' (' + toJalali(option.dataset.start) + ' تا ' + toJalali(option.dataset.end) + ')';
    });
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const container = document.getElementById('purchase-items-container');
    const addButton = document.getElementById('add-purchase-item');
    if (!container || !addButton) return;
    function refreshRows() {
        const rows = Array.from(container.querySelectorAll('.purchase-item-row'));
        rows.forEach(function (row, index) {
            row.dataset.index = String(index);
            row.querySelectorAll('[name]').forEach(function (field) {
                field.name = field.name.replace(/items\[\d+\]/, 'items[' + index + ']');
            });
            const removeButton = row.querySelector('[data-remove-purchase-item]');
            if (removeButton) removeButton.disabled = rows.length === 1;
        });
    }
    addButton.addEventListener('click', function () {
        const row = container.querySelector('.purchase-item-row').cloneNode(true);
        row.querySelectorAll('input').forEach(function (input) { input.value = ''; });
        row.querySelectorAll('select').forEach(function (select) { select.selectedIndex = 0; });
        container.appendChild(row);
        refreshRows();
    });
    container.addEventListener('click', function (event) {
        const button = event.target.closest('[data-remove-purchase-item]');
        if (!button || button.disabled) return;
        button.closest('.purchase-item-row').remove();
        refreshRows();
    });
    refreshRows();
});
</script>
@endsection
