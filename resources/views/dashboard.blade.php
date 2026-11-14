@extends('layouts.dashboard-shell')

@section('title', 'مرکز مدیریت کسب‌وکار')

@push('styles')
<style>
body.subscription-dashboard{background:#f3f6f9;color:#243746;font-family:"Vazirmatn","Segoe UI",Tahoma,sans-serif}
body.subscription-dashboard .app{display:block;min-height:100vh}
body.subscription-dashboard .main{min-height:100vh;background:#f3f6f9}
body.subscription-dashboard .top{min-height:66px;background:#fff;border-bottom:1px solid #dce4ea;padding:0 30px;box-shadow:0 1px 2px rgba(25,45,60,.04)}
body.subscription-dashboard .company{gap:10px}
body.subscription-dashboard .company:before{content:"حساب کاربری";font-size:12px;font-weight:700;color:#34495e;margin-left:8px}
body.subscription-dashboard .company select{background:#fff;color:#34495e;border:1px solid #cbd5de;border-radius:6px;padding:8px 11px;font-size:12px;max-width:260px}
body.subscription-dashboard .user strong{color:#34495e;font-size:12px}
body.subscription-dashboard .user small{color:#778795;font-size:10px}
body.subscription-dashboard .avatar{background:#e8eef3;color:#34546d}
body.subscription-dashboard .logout{color:#28658c;border:1px solid #cbd8e2;border-radius:6px;padding:7px 12px;background:#fff}
body.subscription-dashboard .content{max-width:1480px;margin:0 auto;padding:28px 30px 48px}
.business-heading{display:flex;justify-content:space-between;align-items:flex-start;gap:18px;margin-bottom:20px}
.business-heading h1{font-size:23px;color:#253746;margin:0;font-weight:800}
.business-heading p{font-size:12px;color:#738391;margin:7px 0 0;line-height:1.9}
.business-kicker{font-size:10px;color:#2876a5;font-weight:800;letter-spacing:.3px;margin-bottom:6px}
.business-owner-strip{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin:0 0 18px;padding:11px 14px;background:#fff;border:1px solid #dce4ea;border-radius:9px;color:#637482;font-size:11px}
.business-owner-strip strong{color:#34495e}
.business-owner-strip .owner-separator{width:1px;height:18px;background:#e0e6eb}
.business-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,265px),1fr));gap:16px;align-items:stretch}
.business-card{position:relative;display:flex;flex-direction:column;min-width:0;min-height:310px;aspect-ratio:1/1;background:#fff;border:1px solid #dce4ea;border-radius:10px;padding:19px;box-shadow:0 2px 7px rgba(30,55,75,.045);overflow:hidden}
.business-card:before{content:"";position:absolute;inset:0 0 auto;height:4px;background:#8aa7bc}
.business-card.subscription-card:before{background:#2876a5}
.business-card.fiscal-card.selected:before{background:#2f8a61}
.business-card-head{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;margin-bottom:14px}
.business-card-icon{width:38px;height:38px;display:grid;place-items:center;flex:0 0 auto;border-radius:9px;background:#edf4f8;color:#28658c;font-size:19px}
.fiscal-card .business-card-icon{background:#edf6f0;color:#31734a}
.business-card h2{font-size:15px;line-height:1.7;color:#34495e;margin:0;font-weight:800}
.business-card-subtitle{font-size:10px;color:#87939c;line-height:1.8;margin-top:3px}
.business-status{display:inline-flex;align-items:center;justify-content:center;white-space:nowrap;border-radius:5px;padding:5px 9px;font-size:10px;font-weight:800;border:1px solid}
.business-status.active{background:#eaf5ed;color:#31734a;border-color:#c7e4ce}
.business-status.expired{background:#fff1ef;color:#a34437;border-color:#f0d0ca}
.business-status.pending{background:#fff7e8;color:#8b6424;border-color:#eadbb8}
.business-status.neutral{background:#edf2f6;color:#526575;border-color:#d6e0e7}
.business-card-data{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px 12px;margin:4px 0 16px}
.business-data-item{min-width:0;padding-top:8px;border-top:1px solid #edf0f2}
.business-data-item span{display:block;color:#87939c;font-size:9px;margin-bottom:4px}
.business-data-item strong{display:block;color:#34495e;font-size:11px;overflow-wrap:anywhere;line-height:1.7}
.business-card-footer{margin-top:auto;padding-top:12px}
.business-action{display:flex;align-items:center;justify-content:center;width:100%;min-height:40px;padding:9px 12px;border:1px solid #28658c;border-radius:6px;background:#28658c;color:#fff;text-decoration:none;font-family:inherit;font-size:11px;font-weight:800;cursor:pointer}
.business-action:hover{background:#1e5478;border-color:#1e5478}
.business-action.secondary{background:#fff;color:#28658c}
.business-action.secondary:hover{background:#f2f7fa}
.business-action.success{background:#31734a;border-color:#31734a;color:#fff}
.business-action:disabled,.business-action.disabled{background:#edf0f3;border-color:#e0e5e9;color:#85919a;cursor:not-allowed}
.business-card-note{font-size:9px;color:#8a969f;line-height:1.8;margin:8px 0 0}
.business-card-note a{color:#28658c}
.business-card-meta{display:flex;gap:7px;flex-wrap:wrap;margin:0 0 12px}
.business-pill{display:inline-flex;align-items:center;border-radius:4px;padding:4px 7px;background:#edf2f6;color:#526575;font-size:9px}
.business-pill.open{background:#eaf5ed;color:#31734a}
.business-pill.closed{background:#edf0f3;color:#687784}
.business-pill.selected{background:#eaf3fb;color:#28658c}
.business-empty{display:flex;align-items:center;justify-content:center;min-height:220px;border:1px dashed #cbd6de;border-radius:9px;background:#fbfcfd;color:#7b8994;font-size:12px;text-align:center;padding:20px;line-height:2}
.business-section-title{display:flex;justify-content:space-between;align-items:center;gap:12px;margin:26px 0 13px}
.business-section-title h2{font-size:15px;color:#34495e;margin:0}
.business-section-title p{font-size:10px;color:#87939c;margin:4px 0 0}
.business-count{font-size:10px;color:#647583;background:#fff;border:1px solid #dce4ea;border-radius:5px;padding:6px 9px;white-space:nowrap}
.business-footnote{font-size:10px;color:#87939c;line-height:1.9;margin:14px 0 0}
.business-date{direction:ltr;unicode-bidi:plaintext;display:inline-block}
@media(max-width:900px){body.subscription-dashboard .content{padding:22px 18px 38px}.business-card{min-height:290px}}
@media(max-width:560px){body.subscription-dashboard .top{padding:10px 14px;align-items:flex-start;flex-wrap:wrap}.business-heading{display:block}.business-heading h1{font-size:20px}body.subscription-dashboard .content{padding:20px 13px 32px}.business-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.business-card{aspect-ratio:auto;min-height:285px;padding:13px}.business-card h2{font-size:13px}.business-card-data{gap:8px}.business-data-item strong{font-size:10px}.business-card-head{gap:7px}.business-card-icon{width:32px;height:32px;font-size:16px}.business-status{font-size:9px;padding:4px 6px}}
@media(max-width:380px){.business-grid{grid-template-columns:1fr}.business-card{min-height:270px}}
</style>
@endpush

@section('content')
@php
    $today = today();
    $subscriptionExpired = $subscription?->expires_at && $subscription->expires_at->lt($today);
    $subscriptionLabel = !$subscription
        ? 'ثبت نشده'
        : ($subscriptionActive ? 'فعال' : ($subscriptionExpired ? 'منقضی شده' : 'غیرفعال / در انتظار تأیید'));
    $subscriptionClass = $subscriptionActive ? 'active' : ($subscriptionExpired ? 'expired' : ($subscription ? 'pending' : 'neutral'));
    $planName = data_get($subscription?->metadata, 'plan_name')
        ?? data_get($subscription?->metadata, 'plan.title')
        ?? null;
    $renewalBaseUrl = config('services.web2022.url');
    $renewalUrl = filled($renewalBaseUrl) ? rtrim($renewalBaseUrl, '/') . '/accounting-plan' : null;
@endphp

<div class="business-heading">
    <div>
        <div class="business-kicker">مرکز مدیریت کسب‌وکار</div>
        <h1>{{ $company?->name ?: 'مدیریت کسب‌وکار' }}</h1>
        <p>اشتراک و سال مالی موردنظر را انتخاب کنید و وارد محیط کاری شوید.</p>
    </div>
    @if($company)
        <span class="business-status {{ $subscriptionClass }}">{{ $subscriptionLabel }}</span>
    @endif
</div>

@if(!$company)
    <div class="business-empty">هنوز کسب‌وکار فعالی برای این حساب ثبت نشده است.<br>پس از اتصال کسب‌وکار به حساب شما، اطلاعات آن اینجا نمایش داده می‌شود.</div>
@else
    @if($user->isAccountOwner())
        <div class="business-owner-strip">
            <span>مالک: <strong>{{ $user->name }}</strong></span>
            @if($user->web2022_user_id)
                <span class="owner-separator"></span>
                <span>شناسه Web2022: <strong data-number-format="off">{{ $user->web2022_user_id }}</strong></span>
            @endif
            <span class="owner-separator"></span>
            <span>کسب‌وکار: <strong>{{ $company->name }}</strong></span>
        </div>

        <div class="business-grid" aria-label="کارت‌های مدیریت کسب‌وکار">
            <article class="business-card subscription-card">
                <div class="business-card-head">
                    <div>
                        <div class="business-card-icon" aria-hidden="true">◈</div>
                    </div>
                    <span class="business-status {{ $subscriptionClass }}">{{ $subscriptionLabel }}</span>
                </div>
                <h2>اشتراک کسب‌وکار</h2>
                <div class="business-card-subtitle">{{ $planName ?: 'پلن ثبت‌شده در Web2022' }}</div>
                <div class="business-card-data">
                    <div class="business-data-item"><span>شروع اعتبار</span><strong>@if($subscription?->starts_at)<time class="business-date jalali-date" datetime="{{ $subscription->starts_at->format('Y-m-d') }}">{{ $subscription->starts_at->format('Y/m/d') }}</time>@else ثبت نشده @endif</strong></div>
                    <div class="business-data-item"><span>پایان اعتبار</span><strong>@if($subscription?->expires_at)<time class="business-date jalali-date" datetime="{{ $subscription->expires_at->format('Y-m-d') }}">{{ $subscription->expires_at->format('Y/m/d') }}</time>@else نامشخص @endif</strong></div>
                    <div class="business-data-item"><span>شناسه اشتراک</span><strong data-number-format="off">{{ $subscription?->external_subscription_id ?: 'ثبت نشده' }}</strong></div>
                    <div class="business-data-item"><span>آخرین اعتبارسنجی</span><strong>@if($subscription?->last_verified_at)<time class="business-date jalali-date" datetime="{{ $subscription->last_verified_at->format('Y-m-d') }}">{{ $subscription->last_verified_at->format('Y/m/d') }}</time>@else ثبت نشده @endif</strong></div>
                </div>
                <div class="business-card-footer">
                    @if($renewalUrl)
                        <a class="business-action" href="{{ $renewalUrl }}" target="_blank" rel="noopener noreferrer">تمدید اشتراک / انتخاب پلن</a>
                    @else
                        <button class="business-action disabled" type="button" disabled>لینک خرید Web2022 تنظیم نشده</button>
                    @endif
                    <p class="business-card-note">خرید و پرداخت اشتراک در وب‌سایت Web2022 انجام می‌شود.</p>
                </div>
            </article>
        </div>
    @else
        <div class="business-empty">اطلاعات مالکیت اشتراک فقط برای مالک اصلی حساب نمایش داده می‌شود.</div>
    @endif

    @if($can['fiscal_years'])
        <div class="business-section-title">
            <div>
                <h2>سال‌های مالی</h2>
                <p>برای ورود، سال مالی موردنظر را انتخاب کنید.</p>
            </div>
            <span class="business-count">{{ number_format($fiscalYears->count()) }} سال مالی ثبت‌شده</span>
        </div>

        @if($fiscalYears->isNotEmpty())
            <div class="business-grid" aria-label="کارت‌های سال مالی">
                @foreach($fiscalYears as $year)
                    @php
                        $yearHasEnded = $year->ends_at && $year->ends_at->lt($today);
                        $yearIsSelected = $fiscalYear && (int) $fiscalYear->id === (int) $year->id;
                    @endphp
                    <article class="business-card fiscal-card {{ $yearIsSelected ? 'selected' : '' }}">
                        <div class="business-card-head">
                            <div>
                                <div class="business-card-icon" aria-hidden="true">▦</div>
                            </div>
                            @if($yearIsSelected)
                                <span class="business-status active">سال فعال</span>
                            @elseif($year->is_closed)
                                <span class="business-status neutral">بسته</span>
                            @else
                                <span class="business-status pending">باز</span>
                            @endif
                        </div>
                        <h2>{{ $year->name }}</h2>
                        <div class="business-card-subtitle">کد دوره: <span data-number-format="off">{{ $year->code }}</span></div>
                        <div class="business-card-data">
                            <div class="business-data-item"><span>تاریخ شروع</span><strong>@if($year->starts_at)<time class="business-date jalali-date" datetime="{{ $year->starts_at->format('Y-m-d') }}">{{ $year->starts_at->format('Y/m/d') }}</time>@else — @endif</strong></div>
                            <div class="business-data-item"><span>تاریخ پایان</span><strong>@if($year->ends_at)<time class="business-date jalali-date" datetime="{{ $year->ends_at->format('Y-m-d') }}">{{ $year->ends_at->format('Y/m/d') }}</time>@else — @endif</strong></div>
                        </div>
                        <div class="business-card-meta">
                            <span class="business-pill {{ $year->is_closed ? 'closed' : 'open' }}">{{ $year->is_closed ? 'دوره بسته' : 'دوره باز' }}</span>
                            <span class="business-pill {{ $yearHasEnded ? 'closed' : 'open' }}">{{ $yearHasEnded ? 'پایان‌یافته' : 'در بازه زمانی' }}</span>
                            @if($yearIsSelected)<span class="business-pill selected">انتخاب‌شده</span>@endif
                        </div>
                        <div class="business-card-footer">
                            @if(!$year->is_closed)
                                <form method="POST" action="{{ route('fiscal-years.select', $year) }}">
                                    @csrf
                                    <button class="business-action {{ $yearIsSelected ? 'success' : '' }}" type="submit">ورود به سال مالی</button>
                                </form>
                            @else
                                <button class="business-action disabled" type="button" disabled>این سال مالی بسته است</button>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <div class="business-empty">برای این کسب‌وکار هنوز سال مالی ثبت نشده است.</div>
        @endif
        <p class="business-footnote">کارت‌ها فقط سال‌های مالی ثبت‌شده برای همین شرکت را نشان می‌دهند. انتخاب یک سال، دوره فعال را تغییر می‌دهد و هیچ سال مالی جدیدی ایجاد یا ویرایش نمی‌کند.</p>
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
