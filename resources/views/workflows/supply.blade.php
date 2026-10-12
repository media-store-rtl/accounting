@extends('layouts.dashboard-shell')

@section('title', 'درخواست مواد اولیه')

@section('content')
@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/css/persian-datepicker.min.css">
@endpush
<div class="page-heading">
    <div>
        <h1>درخواست مواد اولیه برای تولید</h1>
        <p>ثبت درخواست مواد اولیه برای انبار و مشاهده وضعیت درخواست‌ها</p>
    </div>
</div>

@if(session('success'))
    <div style="padding:12px 15px;margin-bottom:16px;border:1px solid #246451;border-radius:12px;background:#0c282b;color:#8ce8ce;font-size:12px">
        {{ session('success') }}
    </div>
@endif

@if($errors->any())
    <div style="padding:12px 15px;margin-bottom:16px;border:1px solid #714448;border-radius:12px;background:#302126;color:#ffb8a8;font-size:12px">
        <strong>لطفاً موارد زیر را بررسی کنید:</strong>
        <ul style="margin:8px 0 0;padding-right:20px">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="panel" style="margin-bottom:18px">
    <div style="margin-bottom:16px">
        <h2 style="font-size:15px;margin:0 0 6px">ثبت درخواست جدید</h2>
        <p style="font-size:11px;color:#8198ac;margin:0">مواد اولیه موردنیاز تولید را برای بررسی و تحویل توسط انبار ثبت کنید.</p>
    </div>

    <div style="padding:13px 15px;margin-bottom:18px;border:1px solid #29465e;border-radius:12px;background:#0d2235;color:#c5d6e4;font-size:11px;line-height:2">
        <strong style="color:#6ee7d0">راهنمای ثبت درخواست</strong>
        <ol style="margin:6px 0 0;padding-right:20px">
            <li>سال مالی فعال مجموعه را انتخاب کنید.</li>
            <li>اگر درخواست برای دستور تولید یا سفارش مشخصی است، شماره آن را انتخاب کنید؛ این دو مورد اختیاری‌اند.</li>
            <li>نام ماده اولیه موردنیاز را از فهرست کالاها انتخاب و مقدار لازم را وارد کنید.</li>
            <li>تاریخی را تعیین کنید که مواد باید حداکثر تا آن روز به تولید برسند.</li>
            <li>روی «ارسال درخواست مواد به انبار» بزنید تا درخواست برای بررسی ثبت شود.</li>
        </ol>
        <p style="margin:7px 0 0;color:#ffcf8b">توجه: در حال حاضر هر درخواست فقط یک نوع کالا دارد. اگر چند ماده مختلف لازم دارید، برای هرکدام یک درخواست جدا ثبت کنید.</p>
    </div>

    <form method="POST" action="{{ route('workflows.supply.store') }}" style="margin-top:20px">
        @csrf
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,220px),1fr));gap:18px 14px">
            <label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">
                سال مالی
                <small style="color:#71879d;font-size:10px;font-weight:400">سال مالی فعال مجموعه را انتخاب کنید.</small>
                <select name="fiscal_year_id" required style="width:100%;background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit">
                    @foreach($years as $y)
                        <option value="{{ $y->id }}" @selected((string) old('fiscal_year_id') === (string) $y->id)>{{ $y->year ?? $y->id }}</option>
                    @endforeach
                </select>
            </label>

            <label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">
                تولید (اختیاری)
                <small style="color:#71879d;font-size:10px;font-weight:400">اگر شماره دستور تولید را دارید، انتخاب کنید.</small>
                <select name="production_id" style="width:100%;background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit">
                    <option value="">—</option>
                    @foreach($productions as $p)
                        <option value="{{ $p->id }}" @selected((string) old('production_id') === (string) $p->id)>{{ $p->number }}</option>
                    @endforeach
                </select>
            </label>

            <label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">
                سفارش (اختیاری)
                <small style="color:#71879d;font-size:10px;font-weight:400">اگر مواد برای سفارش مشتری مشخصی است، آن را انتخاب کنید.</small>
                <select name="order_id" style="width:100%;background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit">
                    <option value="">—</option>
                    @foreach($orders as $o)
                        <option value="{{ $o->id }}" @selected((string) old('order_id') === (string) $o->id)>{{ $o->number }}</option>
                    @endforeach
                </select>
            </label>

            <label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">
                نام ماده اولیه
                <small style="color:#71879d;font-size:10px;font-weight:400">مثلاً پارچه، نخ یا دکمه؛ کالای موردنیاز را انتخاب کنید.</small>
                <select name="items[0][goods_id]" required style="width:100%;background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit">
                    @foreach($goods as $g)
                        <option value="{{ $g->id }}" @selected((string) old('items.0.goods_id') === (string) $g->id)>{{ $g->name }}</option>
                    @endforeach
                </select>
            </label>

            <label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">
                مقدار موردنیاز
                <small style="color:#71879d;font-size:10px;font-weight:400">مقدار لازم را بر اساس واحد تعریف‌شده برای کالا وارد کنید.</small>
                <input name="items[0][quantity]" type="number" min="0.0001" step="0.0001" value="{{ old('items.0.quantity') }}" required style="width:100%;background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit">
            </label>

            <label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">
                تاریخ موردنیاز
                <small style="color:#71879d;font-size:10px;font-weight:400">مواد باید حداکثر تا این تاریخ به تولید برسند.</small>
                <input id="needed_at_picker" type="text" inputmode="none" autocomplete="off" placeholder="انتخاب تاریخ شمسی" value="" required aria-describedby="needed_at_help" style="width:100%;background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit">
                <input id="needed_at" name="needed_at" type="hidden" value="{{ old('needed_at') }}">
                <small id="needed_at_help" style="color:#71879d;font-size:10px;font-weight:400">تاریخ را از تقویم شمسی انتخاب کنید.</small>
            </label>
        </div>

        <div style="margin-top:18px">
            <button type="submit" class="btn primary">ارسال درخواست مواد به انبار</button>
        </div>
    </form>
</div>

<div class="panel">
    <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:14px">
        <div>
            <h2 style="font-size:15px;margin:0 0 5px">درخواست‌های ثبت‌شده</h2>
            <p style="font-size:11px;color:#8198ac;margin:0">فهرست درخواست‌های مواد اولیه مجموعه</p>
        </div>
        <span style="font-size:10px;color:#9eb2c4;background:#0d2235;border:1px solid #1b354b;border-radius:20px;padding:6px 10px">{{ $rows->total() }} درخواست</span>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>شناسه</th>
                    <th>اقلام درخواست</th>
                    @if($canReviewStock)<th>موجودی انبار</th>@endif
                    <th>وضعیت</th>
                    <th>تاریخ نیاز</th>
                    @if($canReviewStock)<th>اقدام انبار</th>@endif
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $r)
                    <tr>
                        <td>#{{ $r->id }}</td>
                        <td>
                            @foreach($r->items as $item)
                                <div style="margin-bottom:5px">{{ $item->goods_name }} — درخواست: {{ rtrim(rtrim(number_format((float)$item->requested_quantity, 4, '.', ','), '0'), '.') ?: '0' }}</div>
                                @if($canReviewStock && $r->status !== 'checking_stock')
                                    <small style="color:#9eb2c4">قابل تأمین: {{ rtrim(rtrim(number_format((float)$item->available_quantity, 4, '.', ','), '0'), '.') ?: '0' }} | کسری: {{ rtrim(rtrim(number_format((float)$item->shortage_quantity, 4, '.', ','), '0'), '.') ?: '0' }}</small>
                                @endif
                            @endforeach
                        </td>
                        @if($canReviewStock)
                            <td>
                                @foreach($r->items as $item)
                                    <div style="margin-bottom:5px">{{ $item->goods_name }}: <strong>{{ rtrim(rtrim(number_format((float)($item->warehouse_quantity ?? 0), 4, '.', ','), '0'), '.') ?: '0' }}</strong></div>
                                @endforeach
                            </td>
                        @endif
                        <td><span class="status {{ in_array($r->status, ['stock_available', 'partially_supplied', 'supplied', 'fulfilled', 'completed'], true) ? 'active' : 'inactive' }}">{{ ['checking_stock'=>'در انتظار بررسی انبار','shortage_pending'=>'کسری تأییدشده؛ در انتظار تدارکات','stock_available'=>'موجودی کافی؛ آماده تحویل','rejected'=>'ردشده توسط انبار','purchasing'=>'در حال خرید','purchased'=>'خریداری شده','partially_supplied'=>'بخشی تحویل شده','fulfilled'=>'تحویل کامل','cancelled'=>'لغوشده'][$r->status] ?? $r->status }}</span></td>
                        <td>
                            @if($r->needed_at)
                                <time class="jalali-date" datetime="{{ $r->needed_at }}">{{ $r->needed_at }}</time>
                            @else
                                —
                            @endif
                        </td>
                        @if($canReviewStock)
                            <td>
                                @if($r->status === 'checking_stock')
                                    <form method="POST" action="{{ route('workflows.supply.stock-review', $r->id) }}" style="min-width:190px">
                                        @csrf
                                        <label style="display:block;margin-bottom:7px;font-size:10px;color:#9eb2c4">یادداشت انباردار (اختیاری)</label>
                                        <input name="warehouse_note" maxlength="1000" placeholder="توضیح بررسی موجودی" style="width:100%;box-sizing:border-box;margin-bottom:8px;background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:8px;padding:8px;font:inherit">
                                        <button type="submit" class="btn primary" onclick="return confirm('موجودی انبار بررسی شده و نتیجه برای درخواست ثبت شود؟')">تأیید نتیجه بررسی</button>
                                    </form>
                                @else
                                    <span style="color:#8198ac;font-size:10px">بررسی انجام شده</span>
                                @endif
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="{{ $canReviewStock ? 6 : 4 }}" style="text-align:center;padding:28px;color:#8198ac">درخواستی برای نمایش وجود ندارد.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(method_exists($rows, 'links'))
        <div class="pagination">{{ $rows->links() }}</div>
    @endif
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/persian-date@1.1.0/dist/persian-date.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/js/persian-datepicker.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.jalali-date[datetime]').forEach(function (element) {
        const value = element.getAttribute('datetime');
        const match = value && value.match(/^(\d{4})-(\d{2})-(\d{2})/);
        if (!match) return;
        const date = new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]), 12);
        element.textContent = new Intl.DateTimeFormat('fa-IR-u-ca-persian', {
            year: 'numeric', month: '2-digit', day: '2-digit'
        }).format(date);
    });
    const picker = document.getElementById('needed_at_picker');
    const isoField = document.getElementById('needed_at');
    if (!picker || !isoField || !window.jQuery || !jQuery.fn.persianDatepicker) return;
    const oldIso = isoField.value;
    if (oldIso && /^\d{4}-\d{2}-\d{2}$/.test(oldIso)) {
        const parts = oldIso.split('-').map(Number);
        picker.value = new Intl.DateTimeFormat('fa-IR-u-ca-persian', {year:'numeric',month:'2-digit',day:'2-digit'}).format(new Date(parts[0],parts[1]-1,parts[2],12));
    }
    jQuery(picker).persianDatepicker({
        calendarType:'persian', initialValue:Boolean(oldIso), initialValueType:'gregorian',
        format:'YYYY/MM/DD', altField:'#needed_at', altFormat:'YYYY-MM-DD',
        altFieldFormatter:function(unixDate) {
            const date = new Date(unixDate);
            return date.getFullYear()+'-'+String(date.getMonth()+1).padStart(2,'0')+'-'+String(date.getDate()).padStart(2,'0');
        },
        calendar:{persian:{locale:'fa'}},
        toolbox:{calendarSwitch:{enabled:false},todayButton:{enabled:true,text:{fa:'امروز'}}}
    });
});
</script>
@endsection
