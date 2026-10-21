@extends('layouts.dashboard-shell')

@section('title', 'ثبت درخواست مواد اولیه')

@section('content')
@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/css/persian-datepicker.min.css">
@endpush

<style>
.supply-create{max-width:1080px;margin-inline:auto}
.supply-intro{display:flex;align-items:flex-start;gap:14px;margin-bottom:20px}
.supply-intro-mark{display:grid;place-items:center;flex:0 0 44px;height:44px;border:1px solid #24536a;border-radius:13px;background:#0b2031;color:#6ee7d0;font-size:22px}
.supply-intro h1{margin:0 0 6px;font-size:22px;line-height:1.6}
.supply-intro p{margin:0;color:#91a8ba;font-size:12px;line-height:1.9}
.supply-guide{padding:15px 18px;margin-bottom:18px;border:1px solid #24465a;border-radius:14px;background:linear-gradient(135deg,#0c2638,#0b1e2d);color:#c6d8e5}
.supply-guide-title{display:flex;align-items:center;gap:8px;color:#6ee7d0;font-size:12px;font-weight:800;margin-bottom:7px}
.supply-guide ul{margin:0;padding-right:19px;line-height:2.1;font-size:11px}
.supply-form-panel{padding:22px}
.supply-section-title{grid-column:1/-1;display:flex;align-items:center;gap:9px;margin:5px 0 0;padding-bottom:10px;border-bottom:1px solid #20394d;color:#e5f0f7;font-size:13px;font-weight:800}
.supply-section-number{display:grid;place-items:center;width:25px;height:25px;border-radius:8px;background:#12364a;color:#6ee7d0;font-size:11px}
.supply-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px 16px}
.supply-field{display:flex;flex-direction:column;gap:7px;min-width:0;color:#c4d4e0;font-size:12px}
.supply-field small{color:#8299ac;font-size:10px;font-weight:400;line-height:1.8}
.supply-field select,.supply-field input{box-sizing:border-box;width:100%;min-width:0;min-height:43px;background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:10px;padding:10px 12px;font:inherit;outline:none}
.supply-field select:focus,.supply-field input:focus{border-color:#6ee7d0;box-shadow:0 0 0 3px #6ee7d018}
.supply-field option{background:#0d2235;color:#e9f3fb}
.supply-actions{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-top:24px;padding-top:18px;border-top:1px solid #20394d}
.supply-submit{border:0;border-radius:10px;padding:12px 18px;background:#6ee7d0;color:#06151d;font-family:inherit;font-size:12px;font-weight:800;cursor:pointer}
.supply-submit:disabled{opacity:.5;cursor:not-allowed}
.supply-cancel{color:#9ab0c1;text-decoration:none;font-size:12px}
.supply-error{padding:13px 16px;margin-bottom:16px;border:1px solid #714448;border-radius:12px;background:#302126;color:#ffb8a8;font-size:12px;line-height:2}
.supply-notice{padding:12px 14px;margin-bottom:16px;border:1px solid #71582d;border-radius:11px;background:#2b2518;color:#ffcf8b;font-size:11px;line-height:1.9}
@media(max-width:680px){.supply-form-panel{padding:16px}.supply-grid{grid-template-columns:minmax(0,1fr);gap:15px}.supply-section-title{grid-column:auto}.supply-intro h1{font-size:19px}}
</style>

<div class="supply-create">
    <div class="supply-intro">
        <div class="supply-intro-mark" aria-hidden="true">＋</div>
        <div>
            <h1>ثبت درخواست مواد اولیه</h1>
            <p>ماده اولیه، مرحله تولید یا مقصد مصرف و مقدار موردنیاز را مشخص کنید تا درخواست برای بررسی موجودی به انبار ارسال شود.</p>
        </div>
    </div>

    @if(session('success'))
        <div style="padding:12px 15px;margin-bottom:16px;border:1px solid #246451;border-radius:12px;background:#0c282b;color:#8ce8ce;font-size:12px">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="supply-error" role="alert">
            <strong>درخواست ثبت نشد؛ لطفاً این موارد را بررسی کنید:</strong>
            <ul style="margin:6px 0 0;padding-right:20px">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="supply-guide">
        <div class="supply-guide-title"><span aria-hidden="true">ⓘ</span> قبل از ثبت درخواست</div>
        <ul>
            <li>برای اتصال درخواست به فرایند تولید، مرحله تولید، قسمت تولید یا سفارش مشتری را مشخص کنید.</li>
            <li>نام کالا و مقدار را با توجه به واحد اندازه‌گیری آن بررسی کنید.</li>
            <li>تاریخ موردنیاز یعنی آخرین روزی که مواد باید به تولید برسند.</li>
        </ul>
    </div>

    @if($activeFiscalYearId <= 0)
        <div class="supply-notice" role="alert">برای ثبت درخواست، ابتدا یک سال مالی باز برای مجموعه تعریف کنید. انتخاب سال مالی در این فرم لازم نیست و سیستم از سال مالی فعال مجموعه استفاده می‌کند.</div>
    @endif

    <div class="panel supply-form-panel">
        <form method="POST" action="{{ route('workflows.supply.store') }}">
            @csrf
            <input type="hidden" name="fiscal_year_id" value="{{ $activeFiscalYearId }}">

            <div class="supply-grid">
                <div class="supply-section-title"><span class="supply-section-number">۱</span> مرحله تولید و مقصد مصرف</div>

                <label class="supply-field" for="production_section_id">
                    قسمت تولید
                    <small>محل یا بخشی که مواد را مصرف می‌کند؛ مثل برش یا دوخت.</small>
                    <select id="production_section_id" name="production_section_id">
                        <option value="">انتخاب قسمت تولید (اختیاری)</option>
                        @foreach($sections as $section)
                            <option value="{{ $section->id }}" @selected((string) old('production_section_id') === (string) $section->id)>{{ $section->name }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="supply-field" for="production_stage_id">
                    مرحله تولید
                    <small>فقط مراحل تعریف‌شده در مسیر تولید نمایش داده می‌شوند؛ هر تعریف مرحله یک‌بار در فهرست می‌آید.</small>
                    <select id="production_stage_id" name="production_stage_id">
                        <option value="">انتخاب مرحله تولید (اختیاری)</option>
                        @foreach($productionStages as $stage)
                            <option value="{{ $stage->id }}" @selected((string) old('production_stage_id') === (string) $stage->id)>{{ $stage->name }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="supply-field" for="order_id">
                    سفارش مشتری
                    <small>اگر درخواست برای سفارش مشخصی است، شماره سفارش و مشتری را تطبیق دهید.</small>
                    <select id="order_id" name="order_id">
                        <option value="">انتخاب سفارش مشتری (اختیاری)</option>
                        @foreach($orders as $o)
                            <option value="{{ $o->id }}" @selected((string) old('order_id') === (string) $o->id)>سفارش {{ $o->number }} — مشتری: {{ $o->customer_name }}</option>
                        @endforeach
                    </select>
                </label>

                <div class="supply-section-title"><span class="supply-section-number">۲</span> مشخصات ماده اولیه</div>

                <label class="supply-field" for="goods_id">
                    نام ماده اولیه
                    <small>کالا را با کد و نام آن بررسی و انتخاب کنید.</small>
                    <select id="goods_id" name="items[0][goods_id]" required>
                        @foreach($goods as $g)
                            <option value="{{ $g->id }}" @selected((string) old('items.0.goods_id') === (string) $g->id)>{{ $g->code }} — {{ $g->name }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="supply-field" for="quantity">
                    مقدار موردنیاز
                    <small>مقدار را بر اساس واحد تعریف‌شده برای کالا وارد کنید.</small>
                    <input id="quantity" name="items[0][quantity]" type="number" min="0.0001" step="0.0001" value="{{ old('items.0.quantity') }}" placeholder="مثلاً ۲۵" required>
                </label>

                <div class="supply-section-title"><span class="supply-section-number">۳</span> زمان تأمین</div>

                <label class="supply-field" for="needed_at_picker">
                    تاریخ موردنیاز
                    <small>مواد باید حداکثر تا این تاریخ به تولید برسند.</small>
                    <input id="needed_at_picker" type="text" inputmode="none" autocomplete="off" placeholder="انتخاب تاریخ شمسی" value="" required aria-describedby="needed_at_help">
                    <input id="needed_at" name="needed_at" type="hidden" value="{{ old('needed_at') }}">
                    <small id="needed_at_help">تاریخ را از تقویم شمسی انتخاب کنید.</small>
                </label>
            </div>

            <div class="supply-notice" style="margin-top:20px;margin-bottom:0">در هر درخواست فقط یک نوع کالا ثبت می‌شود. برای درخواست چند ماده متفاوت، هر کالا را در یک درخواست جداگانه ثبت کنید.</div>

            <div class="supply-actions">
                <button class="supply-submit" type="submit" @disabled($activeFiscalYearId <= 0)>ارسال درخواست به انبار</button>
                <a class="supply-cancel" href="{{ route('workflows.supply') }}">بازگشت به فهرست درخواست‌ها</a>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/persian-date@1.1.0/dist/persian-date.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/js/persian-datepicker.min.js"></script>
<script type="module">
import { setupProductionReportDatePicker } from '/js/production-report-date-filters.js';
document.addEventListener('DOMContentLoaded', function () {
    setupProductionReportDatePicker('#needed_at_picker', '#needed_at');
});
</script>
@endsection
