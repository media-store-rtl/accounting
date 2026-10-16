@extends('layouts.dashboard-shell')

@section('title', 'تحویل مواد')

@section('content')
<div class="page-heading"><div><h1>تحویل مواد به تولید</h1><p>ثبت و پیگیری تحویل مواد از انبار به واحد تولید</p></div></div>
@if(session('success'))<div style="padding:12px 15px;margin-bottom:16px;border:1px solid #246451;border-radius:12px;background:#0c282b;color:#8ce8ce;font-size:12px">{{ session('success') }}</div>@endif
@if($errors->any())<div style="padding:12px 15px;margin-bottom:16px;border:1px solid #714448;border-radius:12px;background:#302126;color:#ffb8a8;font-size:12px">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
<div class="panel" style="margin-bottom:18px"><h2 style="font-size:15px;margin:0 0 16px">ثبت تحویل جدید</h2>
<form method="POST" action="{{ route('workflows.handovers.store') }}">@csrf
@if($locations->isEmpty())
<div role="alert" style="padding:12px 14px;margin-bottom:14px;border:1px solid #8b6732;border-radius:10px;background:#332b1d;color:#f6d28b;font-size:12px">هیچ انبار فعال برای این شرکت پیدا نشد. در بخش «انبارها و محل‌ها» یک محل با نوع «انبار» ایجاد یا فعال کنید.</div>
@endif
@if($requests->isEmpty())
<div role="alert" style="padding:12px 14px;margin-bottom:14px;border:1px solid #8b6732;border-radius:10px;background:#332b1d;color:#f6d28b;font-size:12px">درخواست تأمین قابل تحویل وجود ندارد؛ ابتدا وضعیت درخواست را به «خریداری‌شده» یا «آماده تحویل» برسانید.</div>
@endif
@if($users->isEmpty() || $goods->isEmpty())
<div role="alert" style="padding:12px 14px;margin-bottom:14px;border:1px solid #8b6732;border-radius:10px;background:#332b1d;color:#f6d28b;font-size:12px">{{ $users->isEmpty() ? 'کاربر فعال برای این شرکت وجود ندارد.' : '' }}{{ $users->isEmpty() && $goods->isEmpty() ? ' ' : '' }}{{ $goods->isEmpty() ? 'کالای فعال برای انتخاب وجود ندارد.' : '' }}</div>
@endif
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,240px),1fr));gap:16px;align-items:start">
<label style="display:flex;flex-direction:column;gap:8px;color:#c0d0df;font-size:12px;font-weight:600">درخواست تأمین
<select name="supply_request_id" required @disabled($requests->isEmpty()) style="box-sizing:border-box;width:100%;min-height:44px;background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit">
<option value="">انتخاب درخواست تأمین</option>
@foreach($requests as $x)<option value="{{ $x->id }}" @selected((string)old('supply_request_id')===(string)$x->id)>درخواست #{{ $x->id }} — {{ match($x->status) {'stock_available'=>'موجود در انبار','purchased'=>'خریداری‌شده','partially_supplied'=>'تحویل جزئی',default=>$x->status} }}</option>@endforeach
</select></label>
<label style="display:flex;flex-direction:column;gap:8px;color:#c0d0df;font-size:12px;font-weight:600">انبار
<select name="warehouse_location_id" required @disabled($locations->isEmpty()) style="box-sizing:border-box;width:100%;min-height:44px;background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit">
<option value="">انتخاب انبار</option>
@foreach($locations as $x)<option value="{{ $x->id }}" @selected((string)old('warehouse_location_id')===(string)$x->id)>{{ $x->name }}</option>@endforeach
</select></label>
<label style="display:flex;flex-direction:column;gap:8px;color:#c0d0df;font-size:12px;font-weight:600">تحویل‌دهنده
<select name="delivered_by_user_id" required @disabled($users->isEmpty()) style="box-sizing:border-box;width:100%;min-height:44px;background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit">
<option value="">انتخاب تحویل‌دهنده</option>
@foreach($users as $x)<option value="{{ $x->id }}" @selected((string)old('delivered_by_user_id')===(string)$x->id)>{{ $x->name }}</option>@endforeach
</select></label>
<label style="display:flex;flex-direction:column;gap:8px;color:#c0d0df;font-size:12px;font-weight:600">تحویل‌گیرنده
<select name="received_by_user_id" required @disabled($users->isEmpty()) style="box-sizing:border-box;width:100%;min-height:44px;background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit">
<option value="">انتخاب تحویل‌گیرنده</option>
@foreach($users as $x)<option value="{{ $x->id }}" @selected((string)old('received_by_user_id')===(string)$x->id)>{{ $x->name }}</option>@endforeach
</select></label>
<label style="display:flex;flex-direction:column;gap:8px;color:#c0d0df;font-size:12px;font-weight:600">زمان تحویل
<input id="handed_over_date_picker" type="text" inputmode="none" autocomplete="off" placeholder="انتخاب تاریخ شمسی" value="{{ old('handed_over_at') ? substr(old('handed_over_at'), 0, 10) : now()->toDateString() }}" required style="box-sizing:border-box;width:100%;min-height:44px;background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit"><input id="handed_over_date_iso" type="hidden" value="{{ old('handed_over_at') ? substr(old('handed_over_at'), 0, 10) : now()->toDateString() }}"><div style="display:flex;align-items:center;gap:8px"><span>ساعت</span><input id="handed_over_time" type="time" value="{{ old('handed_over_at') ? substr(old('handed_over_at'), 11, 5) : now()->format('H:i') }}" required style="box-sizing:border-box;width:100%;min-height:40px;background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:8px;font:inherit"></div><input id="handed_over_at" name="handed_over_at" type="hidden" value="{{ old('handed_over_at') }}"></label>
<label style="display:flex;flex-direction:column;gap:8px;color:#c0d0df;font-size:12px;font-weight:600">کالا
<select name="items[0][goods_id]" required @disabled($goods->isEmpty()) style="box-sizing:border-box;width:100%;min-height:44px;background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit">
<option value="">انتخاب کالا</option>
@foreach($goods as $x)<option value="{{ $x->id }}" @selected((string)old('items.0.goods_id')===(string)$x->id)>{{ $x->name }}</option>@endforeach
</select></label>
<label style="display:flex;flex-direction:column;gap:8px;color:#c0d0df;font-size:12px;font-weight:600">مقدار تحویل
<input name="items[0][quantity]" type="number" step="0.0001" min="0.0001" value="{{ old('items.0.quantity') }}" required style="box-sizing:border-box;width:100%;min-height:44px;background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit"></label>
<label style="display:flex;flex-direction:column;gap:8px;color:#c0d0df;font-size:12px;font-weight:600">توضیحات (اختیاری)
<input name="notes" value="{{ old('notes') }}" style="box-sizing:border-box;width:100%;min-height:44px;background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit"></label>
</div><div style="margin-top:20px"><button class="btn primary" type="submit" @disabled($locations->isEmpty() || $requests->isEmpty() || $users->isEmpty() || $goods->isEmpty())>ثبت تحویل</button></div></form></div>
<div class="panel"><h2 style="font-size:15px;margin:0 0 14px">تحویل‌های ثبت‌شده</h2><div class="table-wrap"><table><thead><tr><th>شناسه</th><th>درخواست</th><th>وضعیت</th><th>زمان تحویل</th></tr></thead><tbody>
@forelse($rows as $x)<tr><td>#{{ $x->id }}</td><td>#{{ $x->supply_request_id }}</td><td><span class="status {{ in_array($x->status, ['completed','approved','delivered'], true) ? 'active' : 'inactive' }}">{{ $x->status }}</span></td><td>{{ $x->handed_over_at }}</td></tr>@empty<tr><td colspan="4" style="text-align:center;padding:28px;color:#8198ac">تحویلی برای نمایش وجود ندارد.</td></tr>@endforelse
</tbody></table></div>@if(method_exists($rows, 'links'))<div class="pagination">{{ $rows->links() }}</div>@endif</div>
@push('scripts')
<script type="module">
import { setupProductionReportDatePicker } from '/js/production-report-date-filters.js';
const picker = document.getElementById('handed_over_date_picker');
const isoDate = document.getElementById('handed_over_date_iso');
const time = document.getElementById('handed_over_time');
const dateTime = document.getElementById('handed_over_at');
const form = picker?.closest('form');
if (picker && isoDate && time && dateTime && form) {
    setupProductionReportDatePicker('#handed_over_date_picker', '#handed_over_date_iso');
    form.addEventListener('submit', function (event) {
        if (!/^\d{4}-\d{2}-\d{2}$/.test(isoDate.value) || !time.value) {
            event.preventDefault();
            picker.setCustomValidity('تاریخ شمسی و ساعت تحویل را انتخاب کنید.');
            picker.reportValidity();
            return;
        }
        picker.setCustomValidity('');
        dateTime.value = isoDate.value + ' ' + time.value;
    });
    picker.addEventListener('change', () => picker.setCustomValidity(''));
}
</script>
@endpush
@endsection