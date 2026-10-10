@extends('layouts.app')
@section('title', 'داشبورد | حسابداری صنعتی')
@section('content')
<section class="dashboard-content">
<div class="welcome">
<div><div class="eyebrow">CONTROL CENTER</div><h1>داشبورد حسابداری صنعتی</h1><p>وضعیت واقعی زنجیره سفارش، تأمین، انبار، تولید و هزینه‌های ثبت‌شده.</p></div>
</div>

@if(!$company)
<div class="notice warn"><strong>راه‌اندازی اولیه:</strong> این حساب هنوز شرکت فعالی ندارد. ابتدا اطلاعات شرکت باید ایجاد/تکمیل شود.</div>
@elseif(!$subscriptionActive)
<div class="notice warn"><strong>اشتراک:</strong> وضعیت اشتراک این مجموعه فعال نیست. داده‌های قبلی قابل مشاهده‌اند، اما ایجاد سال مالی و عملیات نیازمند اشتراک فعال باید محدود بماند.</div>
@elseif(!$fiscalYear)
<div class="notice warn"><strong>سال مالی:</strong> برای این مجموعه سال مالی باز وجود ندارد.</div>
@endif

<div class="grid">
<div class="card"><small>موجودی تعدادی انبار</small><b>{{ number_format($stats['inventory_quantity'], 2, '.', ',') }}</b><span>جمع موجودی ثبت‌شده</span></div>
<div class="card"><small>سفارش‌های باز</small><b>{{ number_format($stats['open_orders']) }}</b><span>غیر از سفارش‌های تکمیل/لغو‌شده</span></div>
<div class="card"><small>کسری‌های تأمین</small><b>{{ number_format($stats['shortage_requests']) }}</b><span>نیازمند پیگیری تدارکات</span></div>
<div class="card"><small>تولیدهای جاری</small><b>{{ number_format($stats['active_productions']) }}</b><span>تولیدهای تکمیل‌نشده</span></div>
</div>


@if($can['goods'])
<div class="panel" style="margin-top:18px">
<div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
<div><h2>کالاها و کد کالا</h2><p style="margin-bottom:0">تعداد کالاهای فعال: {{ number_format($goodsCount) }} — آخرین کالاهای ثبت‌شده در این مجموعه</p></div>
<a href="{{ route('master.index', 'goods') }}" style="color:#6ee7d0;text-decoration:none;font-size:10px;white-space:nowrap">مشاهده همه کالاها ←</a>
</div>
@if($goods->isNotEmpty())
<div style="overflow-x:auto;margin-top:14px">
<table style="width:100%;border-collapse:collapse;font-size:10px;text-align:right">
<thead><tr style="color:#71879d;border-bottom:1px solid #1b354b"><th style="padding:10px">کد کالا</th><th style="padding:10px">نام کالا</th></tr></thead>
<tbody>
@foreach($goods as $good)
<tr style="border-bottom:1px solid #173047">
<td style="padding:11px 10px;color:#6ee7d0;font-weight:700;white-space:nowrap">{{ $good->code }}</td>
<td style="padding:11px 10px">{{ $good->name }}</td>
</tr>
@endforeach
</tbody></table>
</div>
@else
<div class="empty" style="margin-top:14px">هنوز کالای فعالی ثبت نشده است. از بخش کالاها می‌توانید کالای جدید اضافه کنید.</div>
@endif
</div>
@endif

<div class="section">
<div class="panel">
<h2>زنجیره اصلی پروژه</h2>
<p>ترتیب زیر مطابق تعریف پروژه است. فقط بخش‌هایی که در نسخه فعلی route و رابط عملیاتی دارند قابل ورود مستقیم هستند.</p>
<div class="flow">
@if($can['orders'])<a class="step" href="{{ route('sales.orders.index') }}"><strong>۱. سفارش</strong><span>مشتری و اقلام</span></a>@else<div class="step disabled"><strong>۱. سفارش</strong><span>بدون مجوز مشاهده</span></div>@endif
<div class="step disabled"><strong>۲. تأمین</strong><span>Backend موجود؛ UI مستقل هنوز ندارد</span></div>
<div class="step disabled"><strong>۳. انبار</strong><span>گردش موجودی در Backend</span></div>
<div class="step disabled"><strong>۴. تولید</strong><span>Workflow در حال تکمیل</span></div>
<div class="step disabled"><strong>۵. کالای ساخته‌شده</strong><span>Receipt در Backend</span></div>
@if($can['deliveries'])<a class="step" href="{{ route('sales.deliveries.index') }}"><strong>۶. تحویل</strong><span>درخواست و خروج</span></a>@else<div class="step disabled"><strong>۶. تحویل</strong><span>بدون مجوز مشاهده</span></div>@endif
</div>
</div>

<div class="panel quick">
<h2>دسترسی سریع</h2>
@if($can['customers'])<a href="{{ route('sales.customers.index') }}">مشتریان <span>←</span></a>@endif
@if($can['orders'])<a href="{{ route('sales.orders.index') }}">سفارش‌های فروش <span>←</span></a>@endif
@if($can['deliveries'])<a href="{{ route('sales.deliveries.index') }}">تحویل‌ها <span>←</span></a>@endif
@if($can['costing'])<a href="{{ route('reports.costing') }}">گزارش بهای تمام‌شده <span>←</span></a>@endif
@if($can['goods'])<a href="{{ route('master.index', 'goods') }}">کالاها و کد کالا <span>←</span></a>@endif
@if($can['backups'])<a href="{{ route('backups.index') }}">پشتیبان‌گیری <span>←</span></a>@endif
@if($can['company'])<a href="{{ route('company.edit') }}">اطلاعات مجموعه <span>←</span></a>@endif
@if($can['fiscal_years'])<a href="{{ route('fiscal-years.index') }}">سال‌های مالی <span>←</span></a>@endif
@if(!$can['customers'] && !$can['orders'] && !$can['deliveries'] && !$can['costing'] && !$can['backups'] && !$can['fiscal_years'])
<div class="empty">برای کاربر جاری دسترسی عملیاتی قابل نمایش ثبت نشده است.</div>
@endif
</div>
</div>

<div class="panel modules" id="modules">
<h2>وضعیت حوزه‌های اصلی</h2>
<p>این بخش وضعیت واقعی implementation را نشان می‌دهد و قابلیت‌هایی که هنوز صفحه مستقل ندارند به‌صورت ساختگی لینک نشده‌اند.</p>
<div class="module-grid">
<div class="module ready"><strong>سفارش و فروش</strong><span>مشتری، ثبت سفارش، کنترل موجودی، درخواست تحویل و تحویل.</span></div>
<div class="module ready"><strong>تأمین و خرید</strong><span>درخواست تأمین، کسری، خرید، هزینه مستقیم و رسید خرید در Backend.</span></div>
<div class="module ready"><strong>انبار و گردش کالا</strong><span>موجودی، ورود/خروج و ارزش‌گذاری مواد در Backend.</span></div>
<div class="module pending"><strong>تولید</strong><span>Schema و بخشی از سرویس‌ها موجود است؛ UI و workflow کامل هنوز نیازمند تکمیل است.</span></div>
<div class="module pending"><strong>کالای ساخته‌شده</strong><span>Output و Receipt وجود دارد؛ صفحه عملیاتی مستقل هنوز اضافه نشده است.</span></div>
<div class="module pending"><strong>بهای تمام‌شده</strong><span>گزارش فعلی مواد و دستمزد را جمع می‌کند؛ ضایعات و سایر اجزای تعریف‌شده هنوز کامل نیستند.</span></div>
<div class="module pending"><strong>Notification و Audit</strong><span>Notification workflow برای چند رویداد وجود دارد؛ Audit Trail مستقل هنوز تکمیل نشده است.</span></div>
<div class="module ready"><strong>Backup و Import</strong><span>سرویس‌های Backup و Excel Import وجود دارند؛ رابط کاربری مستقل باید تکمیل شود.</span></div>
</div>
</div>

<div class="section" style="grid-template-columns:1fr">
<div class="panel">
<h2>هزینه‌های ثبت‌شده در سال جاری</h2>
<p>این ارقام عمداً با عنوان «بهای تمام‌شده کامل» نمایش داده نمی‌شوند؛ چون در نسخه فعلی گزارش، مواد و دستمزد پیاده شده‌اند و ضایعات/سربار هنوز کامل نشده‌اند.</p>
<div class="grid" style="grid-template-columns:repeat(3,1fr)">
<div class="card"><small>مواد مصرف‌شده ارزش‌گذاری‌شده</small><b>{{ number_format($stats['material_cost'], 0, '.', ',') }}</b></div>
<div class="card"><small>دستمزد تأییدشده</small><b>{{ number_format($stats['labor_cost'], 0, '.', ',') }}</b></div>
<div class="card"><small>جمع فعلی قابل‌ردگیری</small><b>{{ number_format($stats['material_cost'] + $stats['labor_cost'], 0, '.', ',') }}</b></div>
</div>
</div>
</div>

@if($stats['pending_finished_goods'] > 0)
<div class="panel" style="margin-top:18px"><div class="notice" style="margin:0"><strong>کالای ساخته‌شده:</strong> {{ number_format($stats['pending_finished_goods']) }} رسید کالای ساخته‌شده در انتظار تأیید انبار است.</div></div>
@endif

@if(!$company)
<div class="panel" style="margin-top:18px"><div class="empty">برای شروع، ابتدا شرکت/مجموعه را در ساختار حساب ایجاد کنید.</div></div>
@elseif($fiscalYears->isEmpty())
<div class="panel" style="margin-top:18px"><div class="empty">برای این شرکت هنوز سال مالی تعریف نشده است.</div></div>
@elseif(!$fiscalYear)
<div class="panel" style="margin-top:18px"><div class="empty">هیچ سال مالی باز و قابل استفاده‌ای وجود ندارد.</div></div>
@endif
</section>
@endsection
