@extends('layouts.dashboard-shell')

@section('title', 'رسیدهای خرید')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/css/persian-datepicker.min.css">
<style>
.receipt-date-picker{box-sizing:border-box;width:100%;min-height:42px;background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit}
.receipt-field{box-sizing:border-box;width:100%;background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit}
.receipt-items{display:grid;gap:10px;margin-top:10px}
.receipt-item-row{display:grid;grid-template-columns:minmax(180px,2fr) minmax(120px,1fr) auto;gap:10px;align-items:end;padding:12px;border:1px solid #29465e;border-radius:10px}
@media(max-width:650px){.receipt-item-row{grid-template-columns:1fr}.datepicker-container{position:fixed!important;top:50%!important;left:50%!important;right:auto!important;bottom:auto!important;transform:translate(-50%,-50%)!important;z-index:10000!important;max-width:calc(100vw - 20px)!important}.datepicker-plot-area{max-width:calc(100vw - 20px)!important;box-sizing:border-box!important}}
</style>
@endpush

@section('content')
<div class="page-heading"><div><h1>رسیدهای خرید</h1><p>انبار در این بخش فقط مقدار واقعی دریافت‌شده را ثبت و در صورت نیاز اصلاح می‌کند؛ ریالی‌کردن و تأیید نهایی رسید در بخش تدارکات انجام می‌شود.</p></div></div>
@if(session('success'))<div style="padding:12px 15px;margin-bottom:16px;border:1px solid #246451;border-radius:12px;background:#0c282b;color:#8ce8ce;font-size:12px">{{ session('success') }}</div>@endif
@if(session('error'))<div role="alert" style="padding:12px 15px;margin-bottom:16px;border:1px solid #714448;border-radius:12px;background:#302126;color:#ffb8a8;font-size:12px">{{ session('error') }}</div>@endif
@if($errors->any())<div role="alert" style="padding:12px 15px;margin-bottom:16px;border:1px solid #714448;border-radius:12px;background:#302126;color:#ffb8a8;font-size:12px">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif

@if($canCreateReceipt)
<div class="panel" style="margin-bottom:18px">
<h2 style="font-size:15px;margin:0 0 8px">ثبت رسید اولیه توسط انبار</h2>
<p style="font-size:12px;color:#9eb2c4;line-height:1.9;margin:0 0 16px">در این مرحله خرید و قیمت ثبت نمی‌شود. فقط کالاهایی را وارد کنید که واقعاً تحویل گرفته‌اید. پس از ثبت رسید، اعلان برای تدارکات ارسال می‌شود و موجودی تا زمان نهایی‌سازی تغییر نمی‌کند.</p>
@php $receiptItems=old('items',[['goods_id'=>'','quantity'=>'']]); if(!is_array($receiptItems)||count($receiptItems)===0)$receiptItems=[['goods_id'=>'','quantity'=>'']]; $nextReceiptItemIndex=max(array_map('intval',array_keys($receiptItems)))+1; @endphp
<form method="POST" action="{{ route('workflows.receipts.store') }}" id="receipt-create-form">@csrf
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,220px),1fr));gap:14px">
<label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">انبار<select name="warehouse_location_id" class="receipt-field" required>@foreach($locations as $l)<option value="{{ $l->id }}" @selected((string)old('warehouse_location_id')===(string)$l->id)>{{ $l->name }}</option>@endforeach</select></label>
<label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">تاریخ دریافت<input id="received_date_picker" type="text" class="receipt-date-picker" inputmode="none" autocomplete="off" placeholder="انتخاب تاریخ شمسی" required><input id="received_date_iso" type="hidden" value="{{ old('received_at') ? substr(old('received_at'),0,10) : '' }}"></label>
<label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">ساعت دریافت<input id="received_time" type="time" value="{{ old('received_at') ? substr(old('received_at'),11,5) : '' }}" required class="receipt-field" style="color-scheme:dark"></label>
<input name="received_at" id="received_at" type="hidden" value="{{ old('received_at') }}">
<label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">توضیحات (اختیاری)<input name="notes" value="{{ old('notes') }}" class="receipt-field"></label>
</div>
<div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;margin-top:22px"><h3 style="font-size:14px;margin:0;color:#e9f3fb">اقلامی که واقعاً تحویل گرفته شده‌اند</h3><button class="btn" type="button" id="add-receipt-item">+ افزودن کالا</button></div>
<div id="receipt-items-list" class="receipt-items">@foreach($receiptItems as $index=>$item)<div class="receipt-item-row" data-receipt-item><label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">کالا<select name="items[{{ $index }}][goods_id]" class="receipt-field" required><option value="">انتخاب کالا</option>@foreach($goods as $g)<option value="{{ $g->id }}" @selected((string)old('items.'.$index.'.goods_id',$item['goods_id']??'')===(string)$g->id)>{{ $g->name }}</option>@endforeach</select></label><label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">مقدار دریافتی<input name="items[{{ $index }}][quantity]" class="receipt-field" type="number" min="0.0001" step="0.0001" value="{{ old('items.'.$index.'.quantity',$item['quantity']??'') }}" required></label><button class="btn" type="button" data-remove-receipt-item>حذف قلم</button></div>@endforeach</div>
@error('warehouse_location_id')<p style="color:#ffb8a8;font-size:11px">{{ $message }}</p>@enderror
<div style="margin-top:18px"><button class="btn primary" type="submit">ثبت رسید اولیه</button></div>
</form></div>
@endif

<div class="panel"><h2 style="font-size:15px;margin:0 0 14px">رسیدهای ثبت‌شده</h2><p style="font-size:11px;color:#8198ac;margin:0 0 14px;line-height:1.9">تأیید و نهایی‌سازی از این صفحه انجام نمی‌شود؛ رسیدهای در انتظار اقدام تدارکات در بخش «خریدها» نمایش داده می‌شوند.</p>
<div class="table-wrap"><table><thead><tr><th>شناسه</th><th>خرید</th><th>اقلام و مقدار</th><th>وضعیت</th><th>عملیات انبار</th></tr></thead><tbody>
@forelse($rows as $row)<tr><td>#{{ $row->id }}</td><td>{{ $row->purchase_id ? '#'.$row->purchase_id : 'هنوز ثبت نشده' }}</td><td>@forelse($row->items as $item)<div>{{ $item->goods_name }} — {{ number_format((float)$item->quantity,4,'.',',') }}</div>@empty—@endforelse</td><td><span class="status {{ $row->status === 'approved' ? 'active' : 'inactive' }}">{{ $row->status === 'pending' ? ($row->purchase_id ? 'در انتظار تأیید تدارکات' : 'در انتظار ریالی‌شدن تدارکات') : ($row->status === 'approved' ? 'نهایی و ثبت‌شده' : $row->status) }}</span></td><td>@if($row->status==='pending' && $canCreateReceipt)<a class="btn" href="{{ route('workflows.receipts.edit',$row->id) }}">ویرایش رسید</a>@elseif($row->status==='pending')<span style="color:#9eb2c4;font-size:11px">در انتظار اقدام تدارکات</span>@else<span title="رسید نهایی‌شده به دلیل ثبت گردش موجودی و بهای تمام‌شده قابل ویرایش نیست." style="color:#9eb2c4;font-size:11px">قفل‌شده پس از نهایی‌سازی</span>@endif</td></tr>@empty<tr><td colspan="5" style="text-align:center;padding:28px;color:#8198ac">رسیدی برای نمایش وجود ندارد.</td></tr>@endforelse
</tbody></table></div>@if(method_exists($rows,'links'))<div class="pagination">{{ $rows->links() }}</div>@endif</div>

@if($canCreateReceipt)
<template id="receipt-item-template"><div class="receipt-item-row" data-receipt-item><label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">کالا<select name="items[__INDEX__][goods_id]" class="receipt-field" required><option value="">انتخاب کالا</option>@foreach($goods as $g)<option value="{{ $g->id }}">{{ $g->name }}</option>@endforeach</select></label><label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">مقدار دریافتی<input name="items[__INDEX__][quantity]" class="receipt-field" type="number" min="0.0001" step="0.0001" required></label><button class="btn" type="button" data-remove-receipt-item>حذف قلم</button></div></template>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script><script src="https://cdn.jsdelivr.net/npm/persian-date@1.1.0/dist/persian-date.min.js"></script><script src="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/js/persian-datepicker.min.js"></script>
<script type="module">import { setupProductionReportDatePicker } from '/js/production-report-date-filters.js'; document.addEventListener('DOMContentLoaded',function(){setupProductionReportDatePicker('#received_date_picker','#received_date_iso');});</script>
<script>document.addEventListener('DOMContentLoaded',function(){const form=document.getElementById('receipt-create-form');if(!form)return;const list=document.getElementById('receipt-items-list');const template=document.getElementById('receipt-item-template');const addButton=document.getElementById('add-receipt-item');let nextIndex={{ $nextReceiptItemIndex ?? 1 }};function refreshRows(){const rows=Array.from(list.querySelectorAll('[data-receipt-item]'));rows.forEach(row=>{row.querySelector('[data-remove-receipt-item]').disabled=rows.length===1;});}addButton.addEventListener('click',function(){list.insertAdjacentHTML('beforeend',template.innerHTML.replaceAll('__INDEX__',String(nextIndex++)));refreshRows();});list.addEventListener('click',function(event){const button=event.target.closest('[data-remove-receipt-item]');if(button&&list.querySelectorAll('[data-receipt-item]').length>1){button.closest('[data-receipt-item]').remove();refreshRows();}});form.addEventListener('submit',function(event){const date=document.getElementById('received_date_iso').value;const time=document.getElementById('received_time').value;if(!date||!time){event.preventDefault();alert('لطفاً تاریخ شمسی و ساعت دریافت را انتخاب کنید.');return;}document.getElementById('received_at').value=date+'T'+time;});refreshRows();});</script>
@endif
@endsection