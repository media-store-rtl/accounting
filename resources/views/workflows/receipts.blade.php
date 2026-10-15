@extends('layouts.dashboard-shell')

@section('title', 'رسیدهای خرید')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/css/persian-datepicker.min.css">
<style>
.receipt-guide{display:grid;gap:8px;line-height:1.9;font-size:12px;color:#c5d5e3}
.receipt-guide ol{margin:0;padding-right:22px}
.receipt-guide a{color:#6ee7d0}
.receipt-notice{padding:12px 14px;margin:0 0 14px;border:1px solid #6b5531;border-radius:12px;background:#2b2519;color:#f5d79a;font-size:11px;line-height:1.9}
.receipt-field{display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px;min-width:0}
.receipt-control{width:100%;min-height:42px;background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit}
.receipt-date-input{cursor:pointer}
@media(max-width:600px){.receipt-guide{font-size:11px}}
@media(max-width:650px){.datepicker-container{position:fixed!important;top:50%!important;left:50%!important;right:auto!important;bottom:auto!important;transform:translate(-50%,-50%)!important;z-index:10000!important;max-width:calc(100vw - 20px)!important}.datepicker-plot-area{max-width:calc(100vw - 20px)!important;box-sizing:border-box!important}}
</style>
@endpush

@section('content')
<div class="page-heading"><div><h1>رسیدهای خرید</h1><p>ثبت دریافت واقعی کالا، سپس تأیید رسید برای افزایش موجودی انبار</p></div></div>

@if(session('success'))<div style="padding:12px 15px;margin-bottom:16px;border:1px solid #246451;border-radius:12px;background:#0c282b;color:#8ce8ce;font-size:12px">{{ session('success') }}</div>@endif
@if($errors->any())<div style="padding:12px 15px;margin-bottom:16px;border:1px solid #714448;border-radius:12px;background:#302126;color:#ffb8a8;font-size:12px">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif

<div class="panel" style="margin-bottom:18px">
    <h2 style="font-size:15px;margin:0 0 10px">راهنمای ثبت رسید</h2>
    <div class="receipt-guide">
        <ol>
            <li>ابتدا در بخش <a href="{{ route('workflows.purchasing') }}">خریدها</a> یک خرید ثبت کنید. فقط خریدهای دریافت‌نشده یا دریافت‌شده به‌صورت ناقص در این فهرست قابل انتخاب‌اند.</li>
            <li>برای ورود کالا، یک انبار فعال با نوع «انبار (warehouse)» لازم است؛ انبارها را در <a href="{{ route('master.index', 'locations') }}">انبارها و محل‌ها</a> مدیریت کنید.</li>
            <li>کالا باید جزو همان خرید باشد. مقدار رسید نمی‌تواند از مقدار خریدِ باقی‌مانده بیشتر شود.</li>
            <li>تاریخ را با تقویم شمسی انتخاب کنید و مقدار را با رقم فارسی یا انگلیسی وارد کنید؛ سامانه آن را پیش از ارسال به قالب استاندارد تبدیل می‌کند.</li>
            <li>ثبت رسید به‌تنهایی موجودی را تغییر نمی‌دهد. پس از بررسی، روی «تأیید رسید» بزنید تا موجودی انبار به‌روزرسانی شود.</li>
        </ol>
    </div>
</div>

@if($purchases->isEmpty())
    <div class="receipt-notice">خرید قابل دریافت پیدا نشد. اگر هنوز خریدی ثبت نکرده‌اید، ابتدا از بخش <a href="{{ route('workflows.purchasing') }}" style="color:#6ee7d0">خریدها</a> خرید ایجاد کنید. اگر خرید وجود دارد، وضعیت آن را بررسی کنید؛ خریدهای لغوشده یا کاملاً دریافت‌شده در این فهرست نمایش داده نمی‌شوند.</div>
@endif
@if($locations->isEmpty())
    <div class="receipt-notice">انبار فعالی با نوع «warehouse» برای این شرکت پیدا نشد. در بخش <a href="{{ route('master.index', 'locations') }}" style="color:#6ee7d0">انبارها و محل‌ها</a> یک محل فعال با نوع «warehouse» تعریف کنید؛ محل‌های تولید یا محل‌های غیرفعال به‌عنوان انبار دریافت خرید پذیرفته نمی‌شوند.</div>
@endif

<div class="panel" style="margin-bottom:18px">
    <h2 style="font-size:15px;margin:0 0 16px">ثبت رسید جدید</h2>
    <form id="purchase-receipt-form" method="POST" action="{{ route('workflows.receipts.store') }}">@csrf
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,220px),1fr));gap:14px">
            <label class="receipt-field">خرید
                <select id="receipt-purchase" name="purchase_id" required class="receipt-control">
                    <option value="">انتخاب خرید</option>
                    @foreach($purchases as $p)
                        <option value="{{ $p->id }}" @selected((string)old('purchase_id')===(string)$p->id)>خرید #{{ $p->id }} — {{ ['draft'=>'پیش‌نویس','approved'=>'تأییدشده','purchased'=>'خریداری‌شده','partially_received'=>'دریافت ناقص'][$p->status] ?? $p->status }}</option>
                    @endforeach
                </select>
            </label>
            <label class="receipt-field">انبار مقصد
                <select name="warehouse_location_id" id="receipt-warehouse" required class="receipt-control">
                    <option value="">انتخاب انبار</option>
                    @foreach($locations as $l)<option value="{{ $l->id }}" @selected((string)old('warehouse_location_id')===(string)$l->id)>{{ $l->name }}</option>@endforeach
                </select>
            </label>
            <label class="receipt-field">تاریخ دریافت (شمسی)
                <input id="receipt-date-jalali" type="text" inputmode="none" autocomplete="off" placeholder="انتخاب تاریخ شمسی" value="{{ old('received_at') ? substr(old('received_at'),0,10) : now()->toDateString() }}" required class="receipt-control receipt-date-input">
                <input id="receipt-date-gregorian" type="hidden" value="{{ old('received_at') ? substr(old('received_at'),0,10) : now()->toDateString() }}">
                <input id="receipt-date-time" name="received_at" type="hidden" value="{{ old('received_at', now()->format('Y-m-d\\TH:i')) }}">
            </label>
            <label class="receipt-field">ساعت دریافت
                <input id="receipt-time" type="time" value="{{ old('received_at') ? substr(old('received_at'),11,5) : now()->format('H:i') }}" required class="receipt-control" style="color-scheme:dark">
            </label>
            <label class="receipt-field">کالا
                <select name="items[0][goods_id]" id="receipt-goods" required class="receipt-control">
                    <option value="">ابتدا خرید را انتخاب کنید</option>
                </select>
            </label>
            <label class="receipt-field">مقدار دریافتی
                <input name="items[0][quantity]" type="text" inputmode="decimal" data-normalize-number value="{{ old('items.0.quantity') }}" placeholder="مثلاً ۱۲٫۵" required class="receipt-control">
            </label>
            <label class="receipt-field">توضیحات (اختیاری)
                <input name="notes" value="{{ old('notes') }}" class="receipt-control">
            </label>
        </div>

        @foreach($purchases as $p)
            <template id="receipt-purchase-items-{{ $p->id }}">
                @foreach($p->items as $item)
                    <option value="{{ $item->goods_id }}" @selected((string)old('items.0.goods_id')===(string)$item->goods_id)>{{ $item->goods_name }} — مقدار خرید: {{ $item->quantity }}</option>
                @endforeach
            </template>
        @endforeach

        <div style="margin-top:18px"><button id="receipt-submit" class="btn primary" type="submit" {{ $purchases->isEmpty() || $locations->isEmpty() ? 'disabled' : '' }}>ثبت رسید</button></div>
    </form>
</div>

<div class="panel">
    <h2 style="font-size:15px;margin:0 0 14px">رسیدهای ثبت‌شده</h2>
    <div class="table-wrap"><table><thead><tr><th>شناسه</th><th>خرید</th><th>تاریخ دریافت</th><th>انبار</th><th>مقدار کل</th><th>وضعیت</th><th>عملیات</th></tr></thead><tbody>
    @forelse($rows as $row)
        <tr>
            <td><span class="receipt-digits">#{{ $row->id }}</span></td>
            <td><span class="receipt-digits">#{{ $row->purchase_id }}</span></td>
            <td><time class="receipt-jalali-datetime" datetime="{{ $row->received_at }}">{{ $row->received_at }}</time></td>
            <td>{{ $row->warehouse_name ?: '—' }}</td>
            <td><span class="receipt-number">{{ $row->total_quantity }}</span></td>
            <td><span class="status {{ $row->status === 'approved' ? 'active' : 'inactive' }}">{{ ['pending'=>'در انتظار تأیید','approved'=>'تأییدشده','rejected'=>'ردشده'][$row->status] ?? $row->status }}</span></td>
            <td>@if($row->status==='pending')<form method="POST" action="{{ route('workflows.receipts.approve',$row->id) }}">@csrf<button class="btn" type="submit" onclick="return confirm('رسید تأیید شود و موجودی انبار افزایش یابد؟')">تأیید رسید</button></form>@else—@endif</td>
        </tr>
    @empty
        <tr><td colspan="7" style="text-align:center;padding:28px;color:#8198ac">هنوز رسیدی ثبت نشده است. پس از ثبت خرید، می‌توانید دریافت کالا را از فرم بالا ثبت کنید.</td></tr>
    @endforelse
    </tbody></table></div>
    @if(method_exists($rows, 'links'))<div class="pagination">{{ $rows->links() }}</div>@endif
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/persian-date@1.1.0/dist/persian-date.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/js/persian-datepicker.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('purchase-receipt-form');
    const purchase = document.getElementById('receipt-purchase');
    const warehouse = document.getElementById('receipt-warehouse');
    const goods = document.getElementById('receipt-goods');
    const submit = document.getElementById('receipt-submit');
    const datePicker = document.getElementById('receipt-date-jalali');
    const gregorianDate = document.getElementById('receipt-date-gregorian');
    const dateTime = document.getElementById('receipt-date-time');
    const time = document.getElementById('receipt-time');
    const oldGoodsId = @json((string) old('items.0.goods_id', ''));

    function syncGoods() {
        const template = document.getElementById('receipt-purchase-items-' + purchase.value);
        goods.replaceChildren(new Option('انتخاب کالا از همین خرید', ''));
        if (template) goods.append(template.content.cloneNode(true));
        if (oldGoodsId && Array.from(goods.options).some(option => option.value === oldGoodsId)) goods.value = oldGoodsId;
        submit.disabled = !purchase.value || !warehouse.value || goods.options.length < 2;
    }
    purchase.addEventListener('change', syncGoods);
    warehouse.addEventListener('change', syncGoods);
    syncGoods();

    if (datePicker && gregorianDate && window.jQuery && jQuery.fn.persianDatepicker) {
        jQuery(datePicker).persianDatepicker({
            calendarType: 'persian',
            initialValue: Boolean(gregorianDate.value),
            initialValueType: 'gregorian',
            format: 'YYYY/MM/DD',
            altField: '#receipt-date-gregorian',
            altFormat: 'YYYY-MM-DD',
            autoClose: true,
            observer: true,
            toolbox: {calendarSwitch: {enabled: false}, todayButton: {enabled: true, text: {fa: 'امروز'}}, submitButton: {enabled: false}}
        });
    }

    function normalizeDigits(value) {
        return String(value).replace(/[۰-۹٠-٩]/g, digit => {
            const code = digit.charCodeAt(0);
            return String(code >= 0x06f0 && code <= 0x06f9 ? code - 0x06f0 : code - 0x0660);
        }).replace(/[٫٬،]/g, match => match === '٫' ? '.' : ',').replace(/,/g, '').trim();
    }
    document.querySelectorAll('[data-normalize-number]').forEach(input => {
        input.addEventListener('input', () => { input.value = normalizeDigits(input.value); });
    });

    form.addEventListener('submit', function (event) {
        const date = (gregorianDate.value || '').slice(0, 10);
        const timeValue = normalizeDigits(time.value || '');
        if (!/^\d{4}-\d{2}-\d{2}$/.test(date) || !/^\d{2}:\d{2}$/.test(timeValue)) {
            event.preventDefault();
            alert('تاریخ شمسی و ساعت دریافت را انتخاب کنید.');
            return;
        }
        time.value = timeValue;
        dateTime.value = date + 'T' + timeValue;
        document.querySelectorAll('[data-normalize-number]').forEach(input => { input.value = normalizeDigits(input.value); });
    });

    function persianDigits(value) {
        return String(value).replace(/\d/g, digit => '۰۱۲۳۴۵۶۷۸۹'[Number(digit)]);
    }
    document.querySelectorAll('.receipt-digits').forEach(el => { el.textContent = persianDigits(el.textContent); });
    document.querySelectorAll('.receipt-number').forEach(el => {
        const value = Number(String(el.textContent).replace(/,/g, ''));
        if (Number.isFinite(value)) el.textContent = value.toLocaleString('fa-IR', {maximumFractionDigits: 4});
    });
    document.querySelectorAll('.receipt-jalali-datetime').forEach(el => {
        const raw = (el.getAttribute('datetime') || '').replace(' ', 'T');
        const match = raw.match(/^(\d{4})-(\d{2})-(\d{2})(?:T(\d{2}):(\d{2}))?/);
        if (!match) return;
        const date = new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]), Number(match[4] || 0), Number(match[5] || 0));
        const options = {year:'numeric', month:'2-digit', day:'2-digit'};
        if (match[4]) { options.hour='2-digit'; options.minute='2-digit'; options.hourCycle='h23'; }
        el.textContent = new Intl.DateTimeFormat('fa-IR-u-ca-persian', options).format(date);
    });
});
</script>
@endsection
