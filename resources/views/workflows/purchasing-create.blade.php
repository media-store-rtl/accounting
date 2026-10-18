@extends('layouts.dashboard-shell')

@section('title', 'ثبت خرید جدید')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/css/persian-datepicker.min.css">
<style>
.purchase-date-picker,.purchase-field{box-sizing:border-box;width:100%;min-height:42px;background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit}
.purchase-label{display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px;min-width:0}
.purchase-meta-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,220px),1fr));gap:14px}
.purchase-items-heading{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin:22px 0 12px}
.purchase-items-list{display:flex;flex-direction:column;gap:12px}
.purchase-item-row{display:grid;grid-template-columns:repeat(3,minmax(0,1fr)) auto;align-items:end;gap:12px;padding:14px;border:1px solid #29465e;border-radius:12px;background:rgba(13,34,53,.45)}
.purchase-item-title{grid-column:1/-1;color:#e9f3fb;font-size:12px;font-weight:700}
.purchase-item-actions{display:flex;align-items:center;justify-content:center;min-height:42px}
.purchase-remove-item{white-space:nowrap}
@media(max-width:700px){.purchase-item-row{grid-template-columns:1fr 1fr}.purchase-item-title{grid-column:1/-1}.purchase-item-actions{grid-column:1/-1;justify-content:flex-start}.purchase-item-row .purchase-label:first-of-type{grid-column:1/-1}}
@media(max-width:650px){.datepicker-container{position:fixed!important;top:50%!important;left:50%!important;right:auto!important;bottom:auto!important;transform:translate(-50%,-50%)!important;z-index:10000!important;max-width:calc(100vw - 20px)!important}.datepicker-plot-area{max-width:calc(100vw - 20px)!important;box-sizing:border-box!important}}
</style>
@endpush

@section('content')
<div class="page-heading"><div><h1>ثبت خرید جدید</h1><p>ثبت خرید از تأمین‌کننده یا خرید مستقل از درخواست تأمین</p></div><a class="btn" href="{{ route('workflows.purchasing') }}">بازگشت به فهرست خریدها</a></div>
@if(session('success'))<div style="padding:12px 15px;margin-bottom:16px;border:1px solid #246451;border-radius:12px;background:#0c282b;color:#8ce8ce;font-size:12px">{{ session('success') }}</div>@endif
@if($errors->any())<div style="padding:12px 15px;margin-bottom:16px;border:1px solid #714448;border-radius:12px;background:#302126;color:#ffb8a8;font-size:12px">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
@php
    $purchaseItems = old('items', [['goods_id' => '', 'quantity' => '', 'unit_price' => '']]);
    if (!is_array($purchaseItems) || count($purchaseItems) === 0) {
        $purchaseItems = [['goods_id' => '', 'quantity' => '', 'unit_price' => '']];
    }
    $nextPurchaseItemIndex = max(array_map('intval', array_keys($purchaseItems))) + 1;
@endphp
<div class="panel">
    <h2 style="font-size:15px;margin:0 0 16px">مشخصات خرید</h2>
    <form method="POST" action="{{ route('workflows.purchasing.store') }}">
        @csrf
        <div class="purchase-meta-grid">
            <label class="purchase-label">تأمین‌کننده (اختیاری)
                <select name="supplier_id" class="purchase-field">
                    <option value="">بدون تأمین‌کننده</option>
                    @foreach($suppliers as $s)<option value="{{ $s->id }}" @selected((string)old('supplier_id')===(string)$s->id)>{{ $s->name }}</option>@endforeach
                </select>
            </label>
            <label class="purchase-label">درخواست تأمین (اختیاری)
                <select name="supply_request_id" class="purchase-field">
                    <option value="">بدون اتصال به درخواست</option>
                    @foreach($requests as $s)<option value="{{ $s->id }}" @selected((string)old('supply_request_id')===(string)$s->id)>درخواست #{{ $s->id }}</option>@endforeach
                </select>
            </label>
        </div>

        <section aria-labelledby="purchase-items-title">
            <div class="purchase-items-heading">
                <div>
                    <h3 id="purchase-items-title" style="font-size:14px;margin:0 0 5px;color:#e9f3fb">اقلام خرید</h3>
                    <p style="font-size:11px;color:#9eb2c4;margin:0">برای هر کالا، مقدار و قیمت واحد را جداگانه وارد کنید.</p>
                </div>
                <button type="button" class="btn" id="add-purchase-item">+ افزودن کالا</button>
            </div>
            <div id="purchase-items-list" class="purchase-items-list">
                @foreach($purchaseItems as $index => $item)
                    <div class="purchase-item-row" data-purchase-item>
                        <div class="purchase-item-title">قلم <span data-item-number>{{ $loop->iteration }}</span></div>
                        <label class="purchase-label">کالا
                            <select name="items[{{ $index }}][goods_id]" class="purchase-field" required>
                                <option value="">انتخاب کالا</option>
                                @foreach($goods as $g)<option value="{{ $g->id }}" @selected((string)old('items.'.$index.'.goods_id', $item['goods_id'] ?? '')===(string)$g->id)>{{ $g->name }}</option>@endforeach
                            </select>
                        </label>
                        <label class="purchase-label">مقدار
                            <input name="items[{{ $index }}][quantity]" class="purchase-field" type="number" min="0.0001" step="0.0001" value="{{ old('items.'.$index.'.quantity', $item['quantity'] ?? '') }}" required>
                        </label>
                        <label class="purchase-label">قیمت واحد
                            <input name="items[{{ $index }}][unit_price]" class="purchase-field" type="number" min="0.0001" step="0.0001" value="{{ old('items.'.$index.'.unit_price', $item['unit_price'] ?? '') }}" required>
                        </label>
                        <div class="purchase-item-actions"><button type="button" class="btn purchase-remove-item" data-remove-item>حذف این قلم</button></div>
                    </div>
                @endforeach
            </div>
        </section>

        <div style="max-width:360px;margin-top:18px">
            <label class="purchase-label">تاریخ خرید
                <input id="purchased_date_picker" type="text" class="purchase-date-picker" inputmode="none" autocomplete="off" placeholder="انتخاب تاریخ شمسی" required>
                <input id="purchased_date_iso" type="hidden" name="purchased_at" value="{{ old('purchased_at') }}">
            </label>
        </div>
        <div style="margin-top:22px;display:flex;gap:10px;flex-wrap:wrap">
            <button class="btn primary" type="submit">ثبت خرید</button>
            <a class="btn" href="{{ route('workflows.purchasing') }}">انصراف</a>
        </div>
    </form>
</div>

<template id="purchase-item-template">
    <div class="purchase-item-row" data-purchase-item>
        <div class="purchase-item-title">قلم <span data-item-number></span></div>
        <label class="purchase-label">کالا
            <select name="items[__INDEX__][goods_id]" class="purchase-field" required>
                <option value="">انتخاب کالا</option>
                @foreach($goods as $g)<option value="{{ $g->id }}">{{ $g->name }}</option>@endforeach
            </select>
        </label>
        <label class="purchase-label">مقدار
            <input name="items[__INDEX__][quantity]" class="purchase-field" type="number" min="0.0001" step="0.0001" required>
        </label>
        <label class="purchase-label">قیمت واحد
            <input name="items[__INDEX__][unit_price]" class="purchase-field" type="number" min="0.0001" step="0.0001" required>
        </label>
        <div class="purchase-item-actions"><button type="button" class="btn purchase-remove-item" data-remove-item>حذف این قلم</button></div>
    </div>
</template>

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
    const list = document.getElementById('purchase-items-list');
    const template = document.getElementById('purchase-item-template');
    const addButton = document.getElementById('add-purchase-item');
    let nextIndex = {{ $nextPurchaseItemIndex }};

    function refreshItemRows() {
        const rows = Array.from(list.querySelectorAll('[data-purchase-item]'));
        rows.forEach(function (row, index) {
            row.querySelector('[data-item-number]').textContent = String(index + 1);
            const removeButton = row.querySelector('[data-remove-item]');
            removeButton.disabled = rows.length === 1;
            removeButton.setAttribute('aria-label', 'حذف قلم ' + (index + 1));
        });
    }

    addButton.addEventListener('click', function () {
        const html = template.innerHTML.replaceAll('__INDEX__', String(nextIndex++));
        list.insertAdjacentHTML('beforeend', html);
        refreshItemRows();
        const rows = list.querySelectorAll('[data-purchase-item]');
        rows[rows.length - 1].querySelector('select').focus();
    });

    list.addEventListener('click', function (event) {
        const removeButton = event.target.closest('[data-remove-item]');
        if (!removeButton || list.querySelectorAll('[data-purchase-item]').length <= 1) return;
        removeButton.closest('[data-purchase-item]').remove();
        refreshItemRows();
    });

    refreshItemRows();
});
</script>
@endsection
