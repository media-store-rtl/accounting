@extends('layouts.dashboard-shell')

@section('title', 'دستمزد پرسنل')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/css/persian-datepicker.min.css">
<style>
    .labor-rates-page { display: grid; gap: 18px; }
    .labor-rates-page .page-heading { align-items: flex-start; margin-bottom: 0; }
    .labor-rates-page .personnel-meta { display: flex; align-items: center; flex-wrap: wrap; gap: 8px; margin-top: 10px; color: #91a7ba; font-size: 11px; }
    .labor-rates-page .meta-chip { display: inline-flex; align-items: center; gap: 6px; padding: 5px 10px; border: 1px solid #29465e; border-radius: 999px; background: #0d2235; color: #cce0ef; }
    .labor-rates-page .section-heading { display: flex; align-items: flex-start; justify-content: space-between; gap: 14px; margin-bottom: 16px; }
    .labor-rates-page .section-heading h2 { margin: 0; font-size: 16px; }
    .labor-rates-page .section-heading p { margin: 6px 0 0; color: #8198ac; font-size: 11px; line-height: 1.9; }
    .labor-rates-page .section-icon { display: grid; place-items: center; flex: 0 0 38px; width: 38px; height: 38px; border: 1px solid #29465e; border-radius: 12px; background: #10283c; color: #6ee7d0; font-size: 18px; }
    .labor-rates-page .section-title { display: flex; align-items: flex-start; gap: 12px; }
    .labor-rates-page .notice { display: flex; gap: 10px; align-items: flex-start; margin-bottom: 18px; padding: 12px 14px; border: 1px solid #29465e; border-radius: 12px; background: #0d2235; color: #a9bfd0; font-size: 11px; line-height: 1.9; }
    .labor-rates-page .notice strong { color: #dcebf6; }
    .labor-rates-page .form-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; }
    .labor-rates-page .field { display: grid; align-content: start; gap: 7px; min-width: 0; color: #b9cbd9; font-size: 11px; }
    .labor-rates-page .field .required { color: #ff9e8b; }
    .labor-rates-page input, .labor-rates-page select { width: 100%; min-width: 0; min-height: 42px; padding: 10px 11px; border: 1px solid #29465e; border-radius: 10px; outline: none; background: #071725; color: #e9f3fb; font: inherit; color-scheme: dark; }
    .labor-rates-page input:focus, .labor-rates-page select:focus { border-color: #6ee7d0; box-shadow: 0 0 0 3px rgba(110,231,208,.09); }
    .labor-rates-page .field-hint { color: #71879d; font-size: 10px; line-height: 1.7; }
    .labor-rates-page .form-footer { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-top: 18px; padding-top: 16px; border-top: 1px solid #1b354b; }
    .labor-rates-page .form-footer p { margin: 0; color: #71879d; font-size: 10px; line-height: 1.8; }
    .labor-rates-page .btn.primary { cursor: pointer; font-family: inherit; }
    .labor-rates-page .flash { padding: 12px 15px; border-radius: 12px; font-size: 12px; line-height: 1.9; }
    .labor-rates-page .flash-success { border: 1px solid #246451; background: #0c282b; color: #8ce8ce; }
    .labor-rates-page .flash-error { border: 1px solid #714448; background: #302126; color: #ffb8a8; }
    .labor-rates-page .flash-error ul { margin: 0; padding-right: 20px; }
    .labor-rates-page .history-count { display: inline-flex; align-items: center; justify-content: center; min-width: 28px; height: 25px; padding: 0 8px; border-radius: 8px; background: #10283c; color: #a9bfd0; font-size: 10px; }
    .labor-rates-page .table-wrap { border: 1px solid #1b354b; border-radius: 12px; }
    .labor-rates-page table { min-width: 680px; }
    .labor-rates-page tbody tr:last-child td { border-bottom: 0; }
    .labor-rates-page .rate-amount { font-variant-numeric: tabular-nums; font-weight: 700; color: #e9f3fb; }
    .labor-rates-page .date-value { color: #b9cbd9; font-variant-numeric: tabular-nums; }
    .labor-rates-page .empty-state { padding: 32px 18px; text-align: center; white-space: normal; }
    .labor-rates-page .empty-state-icon { display: grid; place-items: center; width: 44px; height: 44px; margin: 0 auto 12px; border: 1px solid #29465e; border-radius: 14px; background: #0d2235; color: #8ba1b5; font-size: 20px; }
    .labor-rates-page .empty-state strong { display: block; margin-bottom: 6px; color: #cce0ef; font-size: 12px; }
    .labor-rates-page .empty-state p { margin: 0; color: #71879d; font-size: 11px; line-height: 1.9; }
    .labor-rates-page .status.future { color: #a8d4ff; background: #142d45; }
    .labor-rates-page .status.expired { color: #ffb8a8; background: #3b2528; }
    /* Keep the Jalali date picker above panels and inside the viewport, like the order form. */
    .datepicker-container { z-index: 10000 !important; }
    @media (max-width: 650px) {
        .datepicker-container { position: fixed !important; top: 50% !important; left: 50% !important; right: auto !important; bottom: auto !important; transform: translate(-50%, -50%) !important; max-width: calc(100vw - 20px) !important; }
        .datepicker-plot-area { max-width: calc(100vw - 20px) !important; box-sizing: border-box !important; }
    }
    @media (max-width: 1050px) { .labor-rates-page .form-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 560px) {
        .labor-rates-page .page-heading { flex-direction: column; }
        .labor-rates-page .page-heading > .btn { width: 100%; justify-content: center; }
        .labor-rates-page .panel { padding: 15px; }
        .labor-rates-page .form-grid { grid-template-columns: minmax(0, 1fr); }
        .labor-rates-page .form-footer { align-items: stretch; flex-direction: column; }
        .labor-rates-page .form-footer .btn { justify-content: center; }
    }
</style>
@endpush

@section('content')
<div class="labor-rates-page">
    <header class="page-heading">
        <div>
            <h1>دستمزد پرسنل</h1>
            <div class="personnel-meta">
                <span class="meta-chip">{{ $personnel->name }}</span>
                <span class="meta-chip">کد پرسنلی: {{ $personnel->code }}</span>
            </div>
        </div>
        <a class="btn" href="{{ route('personnel.index') }}">→ بازگشت به فهرست پرسنل</a>
    </header>

    @if(session('success'))
        <div class="flash flash-success" role="status">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="flash flash-error" role="alert">
            <strong>ثبت اطلاعات انجام نشد. موارد زیر را بررسی کنید:</strong>
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="panel">
        <div class="section-heading">
            <div class="section-title">
                <span class="section-icon" aria-hidden="true">＋</span>
                <div>
                    <h2>ثبت نرخ جدید</h2>
                    <p>نرخ دستمزد را همراه با بازهٔ اعتبار آن تعریف کنید.</p>
                </div>
            </div>
        </div>

        <div class="notice">
            <span aria-hidden="true">ⓘ</span>
            <div><strong>نکته:</strong> نرخ فقط در بازهٔ اعتبار مشخص‌شده اعمال می‌شود. سوابق قبلی برای حفظ محاسبات تاریخی باقی می‌مانند؛ بازه‌های هم‌پوشان قابل ثبت نیستند.</div>
        </div>

        <form method="POST" action="{{ route('personnel.labor-rates.store', $personnel) }}">
            @csrf
            <div class="form-grid">
                <label class="field">
                    <span>نوع دستمزد <span class="required">*</span></span>
                    <select name="rate_type" required>
                        <option value="regular" @selected(old('rate_type', 'regular') === 'regular')>کار عادی (شیفت ۸ ساعته)</option>
                        <option value="overtime" @selected(old('rate_type') === 'overtime')>اضافه‌کاری</option>
                        <option value="holiday" @selected(old('rate_type') === 'holiday')>تعطیل‌کاری</option>
                    </select>
                    <span class="field-hint">نرخ را جداگانه برای کار عادی، اضافه‌کاری یا تعطیل‌کاری ثبت کنید.</span>
                </label>

                <label class="field">
                    <span>مبلغ نرخ <span class="required">*</span></span>
                    <input name="rate" type="number" min="0" step="0.000001" inputmode="decimal" value="{{ old('rate') }}" placeholder="مثلاً 250000" required>
                    <span class="field-hint">مبلغ را به واحد پول ثبت‌شده در سامانه وارد کنید.</span>
                </label>

                <label class="field">
                    <span>شروع اعتبار <span class="required">*</span></span>
                    <input id="effective_from_picker" type="text" inputmode="none" autocomplete="off" placeholder="انتخاب تاریخ شمسی" value="{{ old('effective_from', now()->toDateString()) }}" readonly required aria-describedby="effective_from_help">
                    <input id="effective_from" name="effective_from" type="hidden" value="{{ old('effective_from', now()->toDateString()) }}">
                    <span id="effective_from_help" class="field-hint">اولین روز اعمال این نرخ را از تقویم شمسی انتخاب کنید.</span>
                </label>

                <label class="field">
                    <span>پایان اعتبار</span>
                    <input id="effective_to_picker" type="text" inputmode="none" autocomplete="off" placeholder="انتخاب تاریخ شمسی (اختیاری)" value="{{ old('effective_to') }}" readonly aria-describedby="effective_to_help">
                    <input id="effective_to" name="effective_to" type="hidden" value="{{ old('effective_to') }}">
                    <span id="effective_to_help" class="field-hint">در صورت خالی‌بودن، تاریخ پایان تعیین نمی‌شود.</span>
                </label>
            </div>

            <div class="form-footer">
                <p>موارد ستاره‌دار الزامی هستند. پیش از ثبت، تاریخ شروع و پایان را بررسی کنید.</p>
                <button class="btn primary" type="submit">ثبت نرخ دستمزد <span aria-hidden="true">←</span></button>
            </div>
        </form>
    </section>

    <section class="panel">
        <div class="section-heading">
            <div class="section-title">
                <span class="section-icon" aria-hidden="true">≡</span>
                <div>
                    <h2>سوابق نرخ دستمزد</h2>
                    <p>نرخ‌های ثبت‌شده برای این پرسنل، از جدیدترین بازه به قدیمی‌ترین.</p>
                </div>
            </div>
            <span class="history-count">{{ $rates->count() }} مورد</span>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>نوع دستمزد</th>
                        <th>مبلغ نرخ</th>
                        <th>شروع اعتبار</th>
                        <th>پایان اعتبار</th>
                        <th>وضعیت زمانی</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rates as $rate)
                        <tr>
                            <td>{{ ['regular' => 'کار عادی (شیفت ۸ ساعته)', 'hourly' => 'کار عادی (شیفت ۸ ساعته)', 'overtime' => 'اضافه‌کاری', 'holiday' => 'تعطیل‌کاری', 'per_unit' => 'به‌ازای هر واحد تولید'][$rate->rate_type] ?? $rate->rate_type }}</td>
                            <td class="rate-amount">{{ number_format((float) $rate->rate, 2) }}</td>
                            <td class="date-value"><time class="jalali-date" data-gregorian-date="{{ $rate->effective_from }}">{{ $rate->effective_from }}</time></td>
                            <td class="date-value">@if($rate->effective_to)<time class="jalali-date" data-gregorian-date="{{ $rate->effective_to }}">{{ $rate->effective_to }}</time>@else بدون تاریخ پایان @endif</td>
                            <td>
                                @if($rate->effective_from <= now()->toDateString() && (!$rate->effective_to || $rate->effective_to >= now()->toDateString()))
                                    <span class="status active">معتبر امروز</span>
                                @elseif($rate->effective_from > now()->toDateString())
                                    <span class="status future">در انتظار شروع</span>
                                @else
                                    <span class="status expired">پایان‌یافته</span>
                                @endif
                            </td>
                            <td>
                                <details>
                                    <summary class="btn" style="cursor:pointer; white-space:nowrap;">ویرایش</summary>
                                    <form method="POST" action="{{ route('personnel.labor-rates.update', [$personnel, $rate->id]) }}" style="min-width:220px; display:grid; gap:8px; padding:10px;">
                                        @csrf
                                        @method('PUT')
                                        <label class="field">نوع دستمزد
                                            <select name="rate_type" required>
                                                <option value="regular" @selected($rate->rate_type === 'regular')>کار عادی</option>
                                                <option value="hourly" @selected($rate->rate_type === 'hourly')>کار عادی (قدیمی)</option>
                                                <option value="overtime" @selected($rate->rate_type === 'overtime')>اضافه‌کاری</option>
                                                <option value="holiday" @selected($rate->rate_type === 'holiday')>تعطیل‌کاری</option>
                                                <option value="per_unit" @selected($rate->rate_type === 'per_unit')>به‌ازای هر واحد تولید</option>
                                            </select>
                                        </label>
                                        <label class="field">مبلغ نرخ
                                            <input name="rate" type="number" min="0" step="0.000001" value="{{ $rate->rate }}" required>
                                        </label>
                                        <label class="field">شروع اعتبار
                                            <input id="rate_effective_from_picker_{{ $rate->id }}" type="text" inputmode="none" autocomplete="off" placeholder="انتخاب تاریخ شمسی" value="{{ $rate->effective_from }}" readonly required>
                                            <input id="rate_effective_from_{{ $rate->id }}" name="effective_from" type="hidden" value="{{ $rate->effective_from }}">
                                        </label>
                                        <label class="field">پایان اعتبار
                                            <input id="rate_effective_to_picker_{{ $rate->id }}" type="text" inputmode="none" autocomplete="off" placeholder="انتخاب تاریخ شمسی (اختیاری)" value="{{ $rate->effective_to }}" readonly>
                                            <input id="rate_effective_to_{{ $rate->id }}" name="effective_to" type="hidden" value="{{ $rate->effective_to }}">
                                        </label>
                                        <button class="btn primary" type="submit">ذخیره تغییرات</button>
                                    </form>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty-state">
                                <div class="empty-state-icon" aria-hidden="true">≡</div>
                                <strong>هنوز نرخی ثبت نشده است</strong>
                                <p>پس از ثبت اولین نرخ از فرم بالا، سوابق دستمزد این پرسنل در این بخش نمایش داده می‌شود.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/persian-date@1.1.0/dist/persian-date.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/js/persian-datepicker.min.js"></script>
<script type="module">
import { setupProductionReportDatePicker } from '/js/production-report-date-filters.js';

document.addEventListener('DOMContentLoaded', function () {
    setupProductionReportDatePicker('#effective_from_picker', '#effective_from');
    setupProductionReportDatePicker('#effective_to_picker', '#effective_to');

    document.querySelectorAll('.jalali-date[data-gregorian-date]').forEach(function (element) {
        const match = element.dataset.gregorianDate.match(/^(\\d{4})-(\\d{2})-(\\d{2})$/);
        if (!match || typeof window.persianDate !== 'function') return;

        const timestamp = new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3])).getTime();
        const jalali = new window.persianDate(timestamp).toCalendar('persian');
        element.textContent = jalali.format('YYYY/MM/DD');
    });

    document.querySelectorAll('[id^="rate_effective_from_picker_"]').forEach(function (picker) {
        const id = picker.id.replace('rate_effective_from_picker_', '');
        setupProductionReportDatePicker('#' + picker.id, '#rate_effective_from_' + id);
    });

    document.querySelectorAll('[id^="rate_effective_to_picker_"]').forEach(function (picker) {
        const id = picker.id.replace('rate_effective_to_picker_', '');
        setupProductionReportDatePicker('#' + picker.id, '#rate_effective_to_' + id);
    });
});
</script>
@endsection
