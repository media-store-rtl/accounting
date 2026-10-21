@extends('layouts.dashboard-shell')
@section('title','ویرایش خرید')
@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/css/persian-datepicker.min.css">
<style>.purchase-date-picker{box-sizing:border-box;width:100%;min-height:42px;background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit}</style>
@endpush
@section('content')
<div class="page-heading"><div><h1>ویرایش خرید #{{ $purchase->id }}</h1><p>خرید فقط تا پیش از ثبت رسید انبار قابل ویرایش است.</p></div><a class="btn" href="{{ route('workflows.purchasing') }}">بازگشت به خریدها</a></div>
@if($errors->any())<div class="panel" style="margin-bottom:16px;color:#ffb8a8">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
<form class="panel" method="POST" action="{{ route('workflows.purchasing.update',$purchase->id) }}">@csrf @method('PUT')
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px">
<label>تأمین‌کننده<select name="supplier_id" style="display:block;width:100%;padding:10px;margin-top:6px;background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:8px"><option value="">بدون تأمین‌کننده</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}" @selected(old('supplier_id',$purchase->supplier_id)==$supplier->id)>{{ $supplier->name }}</option>@endforeach</select></label>
<label>شماره فاکتور<input name="invoice_number" value="{{ old('invoice_number',$purchase->invoice_number) }}" style="display:block;width:100%;padding:10px;margin-top:6px;background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:8px"></label>
<label>تاریخ خرید<input id="purchase_edit_date" data-purchase-date-picker data-iso-target="purchase_edit_date_iso" type="text" class="purchase-date-picker" inputmode="none" autocomplete="off" placeholder="انتخاب تاریخ شمسی" value="" required><input id="purchase_edit_date_iso" type="hidden" name="purchased_at" value="{{ old('purchased_at',substr($purchase->purchased_at,0,10)) }}"></label>
<label>تاریخ فاکتور<input id="purchase_edit_invoice_date" data-purchase-date-picker data-iso-target="purchase_edit_invoice_date_iso" type="text" class="purchase-date-picker" inputmode="none" autocomplete="off" placeholder="انتخاب تاریخ شمسی (اختیاری)" value=""><input id="purchase_edit_invoice_date_iso" type="hidden" name="invoice_date" value="{{ old('invoice_date',$purchase->invoice_date) }}"></label>
</div>
<h2 style="font-size:15px;margin-top:24px">اقلام خرید</h2>
@foreach($purchase->items as $i=>$item)<div style="padding:14px 0;border-bottom:1px solid #29465e"><strong>{{ $item->goods_name }}</strong><input type="hidden" name="items[{{ $i }}][id]" value="{{ $item->id }}"><input type="hidden" name="items[{{ $i }}][goods_id]" value="{{ $item->goods_id }}"><div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin-top:10px"><label>مقدار<input type="number" min="0.0001" step="0.0001" required name="items[{{ $i }}][quantity]" value="{{ old('items.'.$i.'.quantity',$item->quantity) }}" style="display:block;width:100%;padding:10px;margin-top:6px;background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:8px"></label><label>قیمت واحد<input type="number" min="0.0001" step="0.0001" required name="items[{{ $i }}][unit_price]" value="{{ old('items.'.$i.'.unit_price',$item->unit_price) }}" style="display:block;width:100%;padding:10px;margin-top:6px;background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:8px"></label></div></div>@endforeach
<h2 style="font-size:15px;margin-top:24px">هزینه‌های مستقیم</h2>
@foreach($purchase->direct_costs as $i=>$cost)<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px;padding:10px 0"><label>نوع هزینه<input name="direct_costs[{{ $i }}][type]" required value="{{ $cost->type }}" style="display:block;width:100%;padding:10px;margin-top:6px;background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:8px"></label><label>شرح<input name="direct_costs[{{ $i }}][description]" value="{{ $cost->description }}" style="display:block;width:100%;padding:10px;margin-top:6px;background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:8px"></label><label>مبلغ<input type="number" min="0.0001" step="0.0001" name="direct_costs[{{ $i }}][amount]" required value="{{ $cost->amount }}" style="display:block;width:100%;padding:10px;margin-top:6px;background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:8px"></label></div>@endforeach
<label style="display:block;margin-top:16px">یادداشت<textarea name="notes" rows="3" style="display:block;width:100%;padding:10px;margin-top:6px;background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:8px">{{ old('notes',$purchase->notes) }}</textarea></label>
<button class="btn primary" type="submit" style="margin-top:18px">ذخیره تغییرات خرید</button>
</form>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script><script src="https://cdn.jsdelivr.net/npm/persian-date@1.1.0/dist/persian-date.min.js"></script><script src="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/js/persian-datepicker.min.js"></script>
<script type="module">
import { setupProductionReportDatePicker } from '/js/production-report-date-filters.js';
document.addEventListener('DOMContentLoaded',function(){
 document.querySelectorAll('[data-purchase-date-picker]').forEach(function(picker){setupProductionReportDatePicker('#'+picker.id,'#'+picker.dataset.isoTarget);});
 const values=[['purchase_edit_date_iso','purchase_edit_date'],['purchase_edit_invoice_date_iso','purchase_edit_invoice_date']];
 values.forEach(([hiddenId,visibleId])=>{const hidden=document.getElementById(hiddenId),visible=document.getElementById(visibleId);if(hidden&&visible&&hidden.value){const m=hidden.value.match(/^(\d{4})-(\d{2})-(\d{2})/);if(m){const date=new Date(Date.UTC(+m[1],+m[2]-1,+m[3]));visible.value=new Intl.DateTimeFormat('fa-IR-u-ca-persian',{year:'numeric',month:'2-digit',day:'2-digit',timeZone:'UTC'}).format(date);}}});
});
</script>
@endsection