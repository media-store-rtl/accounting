@extends('layouts.dashboard-shell')

@section('title', 'خرید')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/css/persian-datepicker.min.css">
<style>
.purchase-date-picker{box-sizing:border-box;width:100%;min-height:42px;background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit}
@media(max-width:650px){.datepicker-container{position:fixed!important;top:50%!important;left:50%!important;right:auto!important;bottom:auto!important;transform:translate(-50%,-50%)!important;z-index:10000!important;max-width:calc(100vw - 20px)!important}.datepicker-plot-area{max-width:calc(100vw - 20px)!important;box-sizing:border-box!important}}
</style>
@endpush

@section('content')
<div class="page-heading"><div><h1>خریدها</h1><p>ثبت خرید از تأمین‌کنندگان و پیگیری وضعیت سفارش‌های خرید</p></div></div>
@if(session('success'))<div style="padding:12px 15px;margin-bottom:16px;border:1px solid #246451;border-radius:12px;background:#0c282b;color:#8ce8ce;font-size:12px">{{ session('success') }}</div>@endif
@if($errors->any())<div style="padding:12px 15px;margin-bottom:16px;border:1px solid #714448;border-radius:12px;background:#302126;color:#ffb8a8;font-size:12px">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
<div class="panel" style="margin-bottom:18px"><h2 style="font-size:15px;margin:0 0 16px">ثبت خرید جدید</h2>
<form method="POST" action="{{ route('workflows.purchasing.store') }}">@csrf
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,220px),1fr));gap:14px">
<label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">سال مالی فعال<select name="fiscal_year_id" required style="background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit">@foreach($years as $y)<option class="fiscal-year-option" value="{{ $y->id }}" data-year-name="{{ $y->name }}" data-start="{{ substr($y->starts_at,0,10) }}" data-end="{{ substr($y->ends_at,0,10) }}" @selected((string)old('fiscal_year_id', $activeFiscalYearId)===(string)$y->id)>{{ $y->name }}</option>@endforeach</select></label>
<label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">تأمین‌کننده (اختیاری)<select name="supplier_id" style="background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit"><option value="">بدون تأمین‌کننده</option>@foreach($suppliers as $s)<option value="{{ $s->id }}" @selected((string)old('supplier_id')===(string)$s->id)>{{ $s->name }}</option>@endforeach</select></label>
<label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">درخواست تأمین (اختیاری)<select name="supply_request_id" style="background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit"><option value="">بدون اتصال به درخواست</option>@foreach($requests as $s)@if($s->status==='purchasing')<option value="{{ $s->id }}" @selected((string)old('supply_request_id')===(string)$s->id)>درخواست #{{ $s->id }}</option>@endif @endforeach</select></label>
<label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">کالا<select name="items[0][goods_id]" required style="background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit">@foreach($goods as $g)<option value="{{ $g->id }}" @selected((string)old('items.0.goods_id')===(string)$g->id)>{{ $g->name }}</option>@endforeach</select></label>
<label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">مقدار<input name="items[0][quantity]" type="number" min="0.0001" step="0.0001" value="{{ old('items.0.quantity') }}" required style="background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit"></label>
<label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">قیمت واحد<input name="items[0][unit_price]" type="number" min="0.0001" step="0.0001" value="{{ old('items.0.unit_price') }}" required style="background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit"></label>
<label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">تاریخ خرید<input id="purchased_date_picker" type="text" class="purchase-date-picker" inputmode="none" autocomplete="off" placeholder="انتخاب تاریخ شمسی" required><input id="purchased_date_iso" type="hidden" name="purchased_at" value="{{ old('purchased_at') }}"></label>
</div><div style="margin-top:18px"><button class="btn primary" type="submit">ثبت خرید</button></div></form></div>
<div class="panel"><h2 style="font-size:15px;margin:0 0 14px">خریدهای ثبت‌شده</h2><div class="table-wrap"><table><thead><tr><th>شناسه</th><th>مبلغ</th><th>وضعیت خرید</th></tr></thead><tbody>
@forelse($rows as $r)<tr><td>#{{ $r->id }}</td><td>{{ $r->total_amount }}</td><td><span class="status {{ in_array($r->status, ['approved','received','completed'], true) ? 'active' : 'inactive' }}">{{ $r->status }}</span></td></tr>@empty<tr><td colspan="3" style="text-align:center;padding:28px;color:#8198ac">خریدی برای نمایش وجود ندارد.</td></tr>@endforelse
</tbody></table></div>@if(method_exists($rows, 'links'))<div class="pagination">{{ $rows->links() }}</div>@endif</div>
<div class="panel" style="margin-top:18px"><h2 style="font-size:15px;margin:0 0 8px">درخواست‌های ارجاع‌شده از انبار</h2><p style="font-size:11px;color:#8198ac;margin:0 0 14px">پس از تأیید کسری توسط انبار، درخواست اینجا می‌آید. روی «شروع خرید» بزنید؛ سپس در همان فرم، مقدار و قیمت واحد را وارد کنید تا خرید و وضعیت درخواست هم‌زمان ثبت شوند. ورود کالا فقط با ثبت و تأیید رسید انبار انجام می‌شود.</p><div class="table-wrap"><table><thead><tr><th>درخواست</th><th>اقلام و کسری</th><th>تاریخ نیاز</th><th>وضعیت</th><th>اقدام</th></tr></thead><tbody>@forelse($requests as $request)<tr><td>#{{ $request->id }}</td><td>@foreach($request->items as $item)<div>{{ $item->goods_name }} — مقدار درخواستی {{ number_format((float)$item->requested_quantity,4,'.',',') }}، کسری {{ number_format((float)$item->shortage_quantity,4,'.',',') }}</div>@endforeach</td><td><time class="persian-display-date" datetime="{{ $request->needed_at }}">{{ $request->needed_at }}</time></td><td>{{ ['shortage_pending'=>'در انتظار تدارکات','purchasing'=>'در حال خرید','purchased'=>'خریداری شده','partially_supplied'=>'بخشی تأمین شده'][$request->status] ?? $request->status }}</td><td>
@if(in_array($request->status,['shortage_pending','partially_supplied'],true))
<form method="POST" action="{{ route('workflows.purchasing.supply-status',$request->id) }}">@csrf<input type="hidden" name="status" value="purchasing"><button class="btn primary" type="submit">{{ $request->status==='partially_supplied'?'خرید کسری باقی‌مانده':'شروع خرید' }}</button></form>
@elseif($request->status==='purchasing')
@php($remainingItems = $request->items->filter(fn($item) => (float)$item->shortage_quantity - (float)$item->supplied_quantity > 0)->values())
@if($remainingItems->isNotEmpty())
<details style="min-width:280px"><summary class="btn primary" style="cursor:pointer;list-style-position:inside">ثبت خرید + قیمت واحد</summary>
<form method="POST" action="{{ route('workflows.purchasing.store') }}" style="margin-top:12px;padding:12px;border:1px solid #29465e;border-radius:10px;min-width:280px">@csrf
<input type="hidden" name="fiscal_year_id" value="{{ $activeFiscalYearId }}"><input type="hidden" name="supply_request_id" value="{{ $request->id }}">
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px;margin-bottom:12px">
<label style="display:flex;flex-direction:column;gap:5px;color:#9eb2c4;font-size:11px">تاریخ خرید<input id="purchased_request_date_picker_{{ $request->id }}" data-purchase-date-picker data-iso-target="purchased_request_date_iso_{{ $request->id }}" type="text" class="purchase-date-picker" inputmode="none" autocomplete="off" placeholder="انتخاب تاریخ شمسی" value="" required style="background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:8px;padding:9px;font:inherit"><input id="purchased_request_date_iso_{{ $request->id }}" type="hidden" name="purchased_at" value="{{ now()->toDateString() }}"></label>
<label style="display:flex;flex-direction:column;gap:5px;color:#9eb2c4;font-size:11px">تأمین‌کننده (اختیاری)<select name="supplier_id" style="background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:8px;padding:9px;font:inherit"><option value="">بدون تأمین‌کننده</option>@foreach($suppliers as $supplier)<option value="{{ $supplier->id }}">{{ $supplier->name }}</option>@endforeach</select></label>
</div>
@foreach($remainingItems as $index=>$item)
<div style="padding:10px 0;border-top:1px solid #29465e">
<div style="font-size:12px;color:#e9f3fb;margin-bottom:8px">{{ $item->goods_name }} — کسری باقی‌مانده: {{ number_format(max(0,(float)$item->shortage_quantity-(float)$item->supplied_quantity),4,'.',',') }}</div>
<input type="hidden" name="items[{{ $index }}][goods_id]" value="{{ $item->goods_id }}">
<label style="display:flex;flex-direction:column;gap:5px;color:#9eb2c4;font-size:11px">مقدار خرید (می‌تواند بیشتر از کسری درخواست باشد)<input name="items[{{ $index }}][quantity]" type="number" min="0.0001" step="0.0001" value="{{ max(0,(float)$item->shortage_quantity-(float)$item->supplied_quantity) }}" required style="background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:8px;padding:9px;font:inherit"><small style="color:#8198ac;line-height:1.8">مقدار را بر اساس سفارش واقعی وارد کنید؛ بیشتر بودن از کسری درخواست مجاز است.</small></label>
<label style="display:flex;flex-direction:column;gap:5px;color:#9eb2c4;font-size:11px;margin-top:8px">قیمت واحد<input name="items[{{ $index }}][unit_price]" type="number" min="0.0001" step="0.0001" required placeholder="قیمت واقعی هر واحد" style="background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:8px;padding:9px;font:inherit"></label>
</div>
@endforeach
<button class="btn primary" type="submit" style="margin-top:10px">ثبت خرید و ثبت وضعیت «خریداری‌شده»</button>
</form></details>
@else<span style="color:#9eb2c4">کسری باقی‌مانده‌ای برای خرید وجود ندارد.</span>@endif
@else<span style="color:#6ee7d0">{{ $request->status==='fulfilled'?'تأمین و دریافت کامل شده':'خرید ثبت شده؛ منتظر رسید انبار' }}</span>@endif
</td></tr>@empty<tr><td colspan="5" style="text-align:center;padding:24px;color:#8198ac">درخواست ارجاع‌شده‌ای وجود ندارد.</td></tr>@endforelse</tbody></table></div></div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/persian-date@1.1.0/dist/persian-date.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/js/persian-datepicker.min.js"></script>
<script type="module">
import { setupProductionReportDatePicker } from '/js/production-report-date-filters.js';
document.addEventListener('DOMContentLoaded', function () {
    setupProductionReportDatePicker('#purchased_date_picker', '#purchased_date_iso');
    document.querySelectorAll('[data-purchase-date-picker]').forEach(function (picker) {
        setupProductionReportDatePicker('#' + picker.id, '#' + picker.dataset.isoTarget);
    });
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
    document.querySelectorAll('.persian-display-date').forEach(function (element) {
        element.textContent = toJalali(element.dateTime);
    });
});
</script>
@endsection