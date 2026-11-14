@extends('layouts.dashboard-shell')

@section('title', 'مدیریت اشتراک و سال‌های مالی')

@push('styles')
<style>
/* Dolibarr-inspired account overview: restrained blue, light workspace, compact panels. */
body.subscription-dashboard{background:#f4f6f8;color:#253746;font-family:"Vazirmatn","Segoe UI",Tahoma,sans-serif}
body.subscription-dashboard .app{display:block;min-height:100vh}
body.subscription-dashboard .main{min-height:100vh;background:#f4f6f8}
body.subscription-dashboard .top{min-height:66px;background:#fff;border-bottom:1px solid #d9e0e6;padding:0 30px;box-shadow:0 1px 2px rgba(25,45,60,.04)}
body.subscription-dashboard .company{gap:10px}
body.subscription-dashboard .company:before{content:"حساب کاربری";font-size:12px;font-weight:700;color:#34495e;margin-left:8px}
body.subscription-dashboard .company select{background:#fff;color:#34495e;border:1px solid #cbd5de;border-radius:4px;padding:8px 11px;font-size:12px;max-width:260px}
body.subscription-dashboard .user strong{color:#34495e;font-size:12px}
body.subscription-dashboard .user small{color:#778795;font-size:10px}
body.subscription-dashboard .avatar{background:#e8eef3;color:#34546d}
body.subscription-dashboard .logout{color:#28658c;border:1px solid #cbd8e2;border-radius:4px;padding:7px 12px;background:#fff}
body.subscription-dashboard .content{max-width:1440px;margin:0 auto;padding:28px 30px 48px}
.subscription-heading{display:flex;justify-content:space-between;align-items:flex-start;gap:18px;margin-bottom:22px}
.subscription-heading h1{font-size:23px;color:#253746;margin:0;font-weight:700}
.subscription-heading p{font-size:12px;color:#738391;margin:7px 0 0;line-height:1.9}
.subscription-kicker{font-size:10px;color:#2876a5;font-weight:700;letter-spacing:.3px;margin-bottom:6px}
.subscription-panel{background:#fff;border:1px solid #dce3e8;border-radius:5px;padding:20px;margin-bottom:18px;box-shadow:0 1px 2px rgba(20,40,55,.035)}
.subscription-panel h2{font-size:15px;color:#34495e;margin:0 0 5px}
.subscription-muted{font-size:11px;color:#7a8995;line-height:1.9}
.subscription-status{display:inline-flex;align-items:center;gap:6px;border-radius:3px;padding:5px 10px;font-size:11px;font-weight:700;border:1px solid}
.subscription-status.active{background:#eaf5ed;color:#31734a;border-color:#c7e4ce}
.subscription-status.expired{background:#fff1ef;color:#a34437;border-color:#f0d0ca}
.subscription-status.pending{background:#fff7e8;color:#8b6424;border-color:#eadbb8}
.subscription-status.neutral{background:#edf2f6;color:#526575;border-color:#d6e0e7}
.subscription-summary{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-top:18px}
.subscription-metric{border:1px solid #e0e6eb;background:#fbfcfd;border-radius:4px;padding:13px 15px;min-width:0}
.subscription-metric span{display:block;color:#7a8995;font-size:10px}
.subscription-metric strong{display:block;color:#34495e;font-size:18px;margin-top:7px;overflow-wrap:anywhere}
.subscription-details{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:0;margin-top:18px;border-top:1px solid #e7ecef}
.subscription-detail{padding:13px 12px 4px 0;min-width:0}
.subscription-detail span{display:block;color:#83919b;font-size:10px;margin-bottom:5px}
.subscription-detail strong{display:block;color:#34495e;font-size:12px;overflow-wrap:anywhere}
.subscription-table-wrap{overflow-x:auto;margin-top:16px}
.subscription-table{width:100%;border-collapse:collapse;white-space:nowrap;text-align:right;font-size:11px}
.subscription-table th{background:#f2f5f7;color:#60717e;font-weight:700;border-bottom:1px solid #dce3e8;padding:12px 13px}
.subscription-table td{color:#34495e;border-bottom:1px solid #e8edf0;padding:13px}
.subscription-table tbody tr:hover{background:#f8fafb}
.subscription-pill{display:inline-block;padding:4px 8px;border-radius:3px;font-size:10px;background:#edf2f6;color:#526575}
.subscription-pill.open{background:#eaf5ed;color:#31734a}
.subscription-pill.closed{background:#edf0f3;color:#687784}
.subscription-pill.past{background:#fff1ef;color:#a34437}
.subscription-pill.current{background:#eaf3fb;color:#28658c;margin-right:5px}
.subscription-empty{padding:28px 16px;text-align:center;border:1px dashed #cbd6de;border-radius:4px;color:#7b8994;font-size:12px;line-height:2;background:#fbfcfd}
.subscription-note{font-size:10px;color:#87939c;line-height:1.9;margin:14px 0 0}
@media(max-width:900px){.subscription-summary{grid-template-columns:repeat(2,minmax(0,1fr))}.subscription-details{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:560px){body.subscription-dashboard .top{padding:10px 14px;align-items:flex-start;flex-wrap:wrap}.subscription-heading{display:block}.subscription-heading h1{font-size:20px}body.subscription-dashboard .content{padding:20px 13px 36px}.subscription-panel{padding:15px}.subscription-summary{gap:8px}.subscription-metric{padding:11px}.subscription-metric strong{font-size:16px}.subscription-details{grid-template-columns:1fr 1fr}.subscription-detail{padding:11px 4px 3px 0}}
</style>
@endpush

@section('content')
@php
    $today = today();
    $subscriptionExpired = $subscription?->expires_at && $subscription->expires_at->lt(today());
    $subscriptionLabel = !$subscription
        ? 'ثبت نشده'
        : ($subscriptionActive ? 'فعال' : ($subscriptionExpired ? 'منقضی شده' : 'غیرفعال / در انتظار تأیید'));
    $subscriptionClass = $subscriptionActive ? 'active' : ($subscriptionExpired ? 'expired' : ($subscription ? 'pending' : 'neutral'));
    $pastFiscalYears = $fiscalYears->filter(fn ($year) => $year->ends_at && $year->ends_at->lt($today))->count();
    $openFiscalYears = $fiscalYears->where('is_closed', false)->count();
    $closedFiscalYears = $fiscalYears->where('is_closed', true)->count();
    $planName = data_get($subscription?->metadata, 'plan_name')
        ?? data_get($subscription?->metadata, 'plan.title')
        ?? null;
@endphp

<div class="subscription-heading">
    <div>
        <div class="subscription-kicker">مرکز مدیریت کسب‌وکار</div>
        <h1>اشتراک و سال‌های مالی</h1>
        <p>اطلاعات مالک، وضعیت اعتبار اشتراک و سوابق سال‌های مالی شرکت انتخاب‌شده.</p>
    </div>
    <span class="subscription-status {{ $subscriptionClass }}">{{ $subscriptionLabel }}</span>
</div>

@if(!$company)
    <div class="subscription-panel">
        <h2>هنوز کسب‌وکار فعالی برای این حساب ثبت نشده است.</h2>
        <p class="subscription-muted">پس از اتصال کسب‌وکار به حساب شما در Web2022 و Accounting، اطلاعات اشتراک و سال‌های مالی اینجا نمایش داده می‌شود.</p>
    </div>
@else
    @if($user->isAccountOwner())
    <section class="subscription-panel" aria-labelledby="subscription-title">
        <h2 id="subscription-title">اطلاعات مالک و اشتراک</h2>
        <div class="subscription-muted">سوابق زیر مربوط به عضویت فعال کاربر در همین شرکت است.</div>

        @if($can['fiscal_years'])
        <div class="subscription-summary">
            <div class="subscription-metric"><span>تعداد سال‌های مالی</span><strong>{{ number_format($fiscalYears->count()) }}</strong></div>
            <div class="subscription-metric"><span>سال‌های مالی باز</span><strong>{{ number_format($openFiscalYears) }}</strong></div>
            <div class="subscription-metric"><span>سال‌های مالی بسته</span><strong>{{ number_format($closedFiscalYears) }}</strong></div>
            <div class="subscription-metric"><span>سال‌های پایان‌یافته</span><strong>{{ number_format($pastFiscalYears) }}</strong></div>
        </div>
        @endif

        <div class="subscription-details">
            <div class="subscription-detail"><span>نام کاربر</span><strong>{{ $user->name }}</strong></div>
            <div class="subscription-detail"><span>شناسه کاربر Web2022</span><strong data-number-format="off">{{ $user->web2022_user_id ?: 'ثبت نشده' }}</strong></div>
            <div class="subscription-detail"><span>نام شرکت / کسب‌وکار</span><strong>{{ $company->name }}</strong></div>
            <div class="subscription-detail"><span>وضعیت اشتراک</span><strong><span class="subscription-status {{ $subscriptionClass }}">{{ $subscriptionLabel }}</span></strong></div>
            <div class="subscription-detail"><span>نام پلن</span><strong>{{ $planName ?: 'در اطلاعات فعلی ثبت نشده' }}</strong></div>
            <div class="subscription-detail"><span>شناسه اشتراک خارجی</span><strong data-number-format="off">{{ $subscription?->external_subscription_id ?: 'ثبت نشده' }}</strong></div>
            <div class="subscription-detail"><span>تاریخ شروع اعتبار</span><strong>@if($subscription?->starts_at)<time class="jalali-date" datetime="{{ $subscription->starts_at->format('Y-m-d') }}">{{ $subscription->starts_at->format('Y/m/d') }}</time>@else ثبت نشده @endif</strong></div>
            <div class="subscription-detail"><span>تاریخ انقضای اشتراک</span><strong>@if($subscription?->expires_at)<time class="jalali-date" datetime="{{ $subscription->expires_at->format('Y-m-d') }}">{{ $subscription->expires_at->format('Y/m/d') }}</time>@else نامشخص @endif</strong></div>
            <div class="subscription-detail"><span>آخرین اعتبارسنجی</span><strong>@if($subscription?->last_verified_at)<time class="jalali-date" datetime="{{ $subscription->last_verified_at->format('Y-m-d') }}">{{ $subscription->last_verified_at->format('Y/m/d') }}</time>@else ثبت نشده @endif</strong></div>
        </div>
    </section>
    @else
        <section class="subscription-panel"><h2>اطلاعات مالکیت اشتراک</h2><p class="subscription-muted">جزئیات مالکیت و اعتبار اشتراک فقط برای مالک اصلی حساب نمایش داده می‌شود.</p></section>
    @endif

    @if($can['fiscal_years'])
    <section class="subscription-panel" aria-labelledby="fiscal-years-title">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap">
            <div>
                <h2 id="fiscal-years-title">سوابق سال‌های مالی</h2>
                <div class="subscription-muted">نام، بازه زمانی و وضعیت هر سال مالی ثبت‌شده برای {{ $company->name }}.</div>
            </div>
            <span class="subscription-status neutral">{{ number_format($fiscalYears->count()) }} دوره ثبت‌شده</span>
        </div>

        @if($fiscalYears->isNotEmpty())
            <div class="subscription-table-wrap">
                <table class="subscription-table">
                    <thead><tr>
                        <th>نام سال مالی</th><th>کد</th><th>تاریخ شروع</th><th>تاریخ پایان</th><th>وضعیت دوره</th><th>وضعیت زمانی</th>
                    </tr></thead>
                    <tbody>
                    @foreach($fiscalYears as $year)
                        @php
                            $yearHasEnded = $year->ends_at && $year->ends_at->lt($today);
                            $yearState = $year->is_closed ? 'بسته' : 'باز';
                        @endphp
                        <tr>
                            <td><strong>{{ $year->name }}</strong>@if($fiscalYear && (int) $fiscalYear->id === (int) $year->id) <span class="subscription-pill current">انتخاب‌شده</span>@endif</td>
                            <td data-number-format="off">{{ $year->code }}</td>
                            <td>@if($year->starts_at)<time class="jalali-date" datetime="{{ $year->starts_at->format('Y-m-d') }}">{{ $year->starts_at->format('Y/m/d') }}</time>@else — @endif</td>
                            <td>@if($year->ends_at)<time class="jalali-date" datetime="{{ $year->ends_at->format('Y-m-d') }}">{{ $year->ends_at->format('Y/m/d') }}</time>@else — @endif</td>
                            <td><span class="subscription-pill {{ $year->is_closed ? 'closed' : 'open' }}">{{ $yearState }}</span></td>
                            <td><span class="subscription-pill {{ $yearHasEnded ? 'past' : 'open' }}">{{ $yearHasEnded ? 'پایان‌یافته' : 'در بازه زمانی' }}</span></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="subscription-empty">برای این شرکت هنوز سال مالی ثبت نشده است.</div>
        @endif
        <p class="subscription-note">این اطلاعات فقط از سوابق موجود Accounting برای شرکت انتخاب‌شده خوانده می‌شود. نمایش این صفحه هیچ سال مالی جدیدی ایجاد یا تغییر نمی‌دهد و سوابق سایر شرکت‌ها را در بر نمی‌گیرد.</p>
    </section>
    @endif
@endif

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.jalali-date').forEach(function (element) {
        const match = String(element.dateTime || '').match(/^(\d{4})-(\d{2})-(\d{2})$/);
        if (!match) return;
        const date = new Date(Date.UTC(Number(match[1]), Number(match[2]) - 1, Number(match[3])));
        const options = {year:'numeric', month:'2-digit', day:'2-digit', timeZone:'UTC'};
        element.textContent = new Intl.DateTimeFormat('fa-IR-u-ca-persian', options).format(date);
    });
});
</script>
@endpush
@endsection
