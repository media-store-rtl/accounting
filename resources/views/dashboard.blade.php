<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>داشبورد | حسابداری صنعتی</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
:root{font-family:"Vazirmatn","Segoe UI",Tahoma,sans-serif;background:#07111f;color:#e9f3fb}
*{box-sizing:border-box}body{margin:0;background:#07111f}.app{min-height:100vh;display:grid;grid-template-columns:270px 1fr}
.sidebar{background:#091725;border-left:1px solid #1c3348;padding:22px 16px;position:sticky;top:0;height:100vh}
.brand{display:flex;gap:11px;align-items:center;padding:0 8px 22px;border-bottom:1px solid #173047}.logo{width:42px;height:42px;border-radius:13px;display:grid;place-items:center;background:linear-gradient(135deg,#6ee7d0,#67b7ff);color:#04131b;font-weight:900}.brand b{display:block;font-size:14px}.brand span{font-size:9px;color:#6f879d}
.nav{padding-top:20px}.nav-title{font-size:8px;color:#587087;margin:0 10px 8px}.nav a{display:flex;align-items:center;gap:10px;padding:11px 12px;border-radius:11px;text-decoration:none;color:#8ba1b5;font-size:11px;margin-bottom:4px}.nav a.active,.nav a:hover{background:#10283c;color:#dcebf6}
.main{min-width:0}.top{min-height:76px;border-bottom:1px solid #183047;display:flex;align-items:center;justify-content:space-between;gap:16px;padding:0 34px;background:#081522}
.company{display:flex;align-items:center;gap:12px;min-width:0}.company select{background:#0d2133;color:#dcebf5;border:1px solid #29465e;border-radius:10px;padding:9px 13px;font-family:inherit;font-size:11px;max-width:230px}
.user{display:flex;align-items:center;gap:10px;flex-shrink:0}.avatar{width:35px;height:35px;border-radius:50%;display:grid;place-items:center;background:#15334a;color:#6ee7d0;font-weight:800}.user small{display:block;color:#71889d;font-size:8px}.user strong{font-size:11px}.logout{border:0;background:none;color:#7f97aa;font-family:inherit;cursor:pointer;font-size:10px}
.content{padding:32px 34px}.welcome{display:flex;justify-content:space-between;align-items:end;gap:20px;margin-bottom:18px}.eyebrow{color:#6ee7d0;font-size:9px;font-weight:800;letter-spacing:1px}.welcome h1{font-size:29px;margin:7px 0 0;letter-spacing:-.7px}.welcome p{margin:7px 0 0;color:#71879d;font-size:11px}
.notice{margin-bottom:18px;padding:13px 16px;border:1px solid #28445d;border-radius:13px;background:#0a1b2b;color:#9eb2c4;font-size:10px;line-height:1.9}.notice strong{color:#dcebf5}.notice.warn{border-color:#5a4b2c}
.grid{display:grid;grid-template-columns:repeat(4,1fr);gap:13px}.card{background:#0a1b2b;border:1px solid #1b354b;border-radius:17px;padding:19px}.card small{color:#698096;font-size:9px}.card b{display:block;font-size:23px;margin-top:10px}.card span{display:block;margin-top:6px;color:#617b91;font-size:8px}
.section{margin-top:18px;display:grid;grid-template-columns:1.35fr .65fr;gap:18px}.panel{background:#0a1b2b;border:1px solid #1b354b;border-radius:18px;padding:21px}.panel h2{font-size:14px;margin:0}.panel p{font-size:10px;color:#71879d;line-height:2;margin:8px 0 18px}
.flow{display:grid;grid-template-columns:repeat(6,1fr);gap:9px}.step{padding:14px 8px;text-align:center;background:#0d2235;border:1px solid #1a3449;border-radius:12px;text-decoration:none;color:#dcebf5}.step strong{font-size:10px;display:block}.step span{font-size:8px;color:#617a91;display:block;margin-top:5px}.step.disabled{opacity:.48;cursor:not-allowed}
.quick a,.quick .disabled-link{display:flex;justify-content:space-between;padding:12px 0;border-bottom:1px solid #173047;color:#b9cbd9;text-decoration:none;font-size:10px}.quick a:last-child,.quick .disabled-link:last-child{border-bottom:0}.disabled-link{opacity:.45}
.modules{margin-top:18px}.module-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:10px}.module{padding:15px;border:1px solid #1b354b;border-radius:13px;background:#0d2235}.module strong{display:block;font-size:10px}.module span{display:block;color:#71879d;font-size:8px;margin-top:5px;line-height:1.8}.module.ready{border-color:#285b59}.module.pending{opacity:.62}
.empty{padding:25px;text-align:center;border:1px dashed #29455d;border-radius:13px;color:#71879d;font-size:10px;line-height:2}
.mobile-nav{display:none}
@media(max-width:1100px){.grid{grid-template-columns:1fr 1fr}.module-grid{grid-template-columns:1fr 1fr}.flow{grid-template-columns:repeat(3,1fr)}}
@media(max-width:900px){.app{display:block}.sidebar{display:none}.section{grid-template-columns:1fr}.top,.content{padding-left:18px;padding-right:18px}.mobile-nav{position:fixed;display:grid;grid-template-columns:repeat(5,1fr);bottom:0;right:0;left:0;height:68px;background:rgba(8,21,34,.97);border-top:1px solid #1b354b;z-index:50;padding-bottom:env(safe-area-inset-bottom)}.mobile-nav a{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;color:#7890a5;text-decoration:none;font-size:8px}.mobile-nav a span{font-size:18px;line-height:1}.mobile-nav a.active{color:#6ee7d0}.content{padding-bottom:90px}}
@media(max-width:560px){.top{min-height:70px;align-items:flex-start;padding-top:12px;padding-bottom:12px}.company{flex:1;flex-wrap:wrap}.company select{max-width:160px}.user>div:first-child{display:none}.grid{grid-template-columns:1fr 1fr;gap:9px}.card{padding:14px}.card b{font-size:20px}.content{padding-top:22px}.welcome{display:block;margin-bottom:18px}.welcome h1{font-size:23px}.panel{padding:16px}.flow{grid-template-columns:1fr 1fr}.module-grid{grid-template-columns:1fr}.mobile-nav{height:64px}}
</style>
</head>
<body>
<div class="app">
<aside class="sidebar">
<div class="brand"><div class="logo">ح</div><div><b>حسابداری صنعتی</b><span>مدیریت تولید و بهای تمام‌شده</span></div></div>
<nav class="nav">
<div class="nav-title">نمای کلی</div>
<a class="active" href="{{ route('dashboard') }}">⌂ داشبورد</a>
@if($can['notifications'])<a href="{{ route('notifications.index') }}">♢ اعلان‌ها</a>@endif

<div class="nav-title">سفارش و فروش</div>
@if($can['customers'])<a href="{{ route('sales.customers.index') }}">♙ مشتریان</a>@endif
@if($can['orders'])<a href="{{ route('sales.orders.index') }}">▤ سفارش‌های فروش</a>@endif
@if($can['deliveries'])<a href="{{ route('sales.deliveries.index') }}">⇢ تحویل و ارسال</a>@endif

<div class="nav-title">تأمین و خرید</div>
@if($can['supply'])<a href="{{ route('workflows.supply') }}">◈ درخواست‌های تأمین</a>@endif
@if($can['purchasing'])<a href="{{ route('workflows.purchasing') }}">▤ خریدها</a>@endif
@if($can['receipts'])<a href="{{ route('workflows.receipts') }}">⇣ رسیدهای خرید</a>@endif
@if($can['handovers'])<a href="{{ route('workflows.handovers') }}">⇢ تحویل مواد</a>@endif

<div class="nav-title">انبار</div>
@if($can['inventory'])<a href="{{ route('inventory.index') }}">▦ موجودی انبار</a>@endif
@if($can['locations'])<a href="{{ route('master.index', 'locations') }}">▣ انبارها و محل‌ها</a>@endif

<div class="nav-title">تولید</div>
@if($can['productions'])<a href="{{ route('master.index', 'productions') }}">⚙ دستورهای تولید</a>@endif
@if($can['production_execution'])<a href="{{ route('production.execution.index') }}">⚒ عملیات تولید</a>@endif
@if($can['production_outputs'])<a href="{{ route('production.outputs.index') }}">⇡ خروجی تولید</a>@endif

<div class="nav-title">اطلاعات پایه</div>
@if($can['goods'])<a href="{{ route('master.index', 'goods') }}">▦ کالاها و کد کالا</a>@endif
@if($can['suppliers'])<a href="{{ route('master.index', 'suppliers') }}">♙ تأمین‌کنندگان</a>@endif
@if($can['production_routes'])<a href="{{ route('master.index', 'production-routes') }}">⇢ مسیرهای تولید</a>@endif

<div class="nav-title">مدیریت</div>
@if($can['company'])<a href="{{ route('company.edit') }}">▤ اطلاعات مجموعه</a>@endif
@if($can['fiscal_years'])<a href="{{ route('fiscal-years.index') }}">▣ سال‌های مالی</a>@endif
@if($can['personnel'])<a href="{{ route('personnel.index') }}">♟ پرسنل</a>@endif
@if($can['users'])<a href="{{ route('users.index') }}">♙ کاربران</a>@endif
@if($can['roles'])<a href="{{ route('roles.index') }}">⚿ نقش‌ها و دسترسی‌ها</a>@endif
@if($can['costing'])<a href="{{ route('reports.costing') }}">◌ بهای تمام‌شده</a>@endif
@if($can['backups'])<a href="{{ route('backups.index') }}">▣ پشتیبان‌گیری</a>@endif
</nav>
</aside>

<main class="main">
<header class="top">
<div class="company">
@if($companies->isNotEmpty())
<select aria-label="انتخاب شرکت" onchange="location.href='{{ route('dashboard') }}?company='+this.value">
@foreach($companies as $item)<option value="{{ $item->id }}" @selected($company?->id === $item->id)>{{ $item->name }}</option>@endforeach
</select>
@else
<span style="color:#71879d;font-size:10px">هنوز شرکتی تعریف نشده</span>
@endif
@if($fiscalYears->isNotEmpty() && $can['fiscal_years'])
<form method="POST" action="{{ route('fiscal-years.select', $fiscalYear ?? $fiscalYears->first()) }}" style="margin:0">
@csrf
<select aria-label="انتخاب سال مالی" onchange="this.form.action='{{ url('/fiscal-years') }}/'+this.value+'/select';this.form.submit()">
@foreach($fiscalYears as $year)<option value="{{ $year->id }}" @selected($fiscalYear?->id === $year->id) @disabled($year->is_closed)>{{ $year->name }}{{ $year->is_closed ? ' (بسته)' : '' }}</option>@endforeach
</select>
</form>
@elseif($company && $can['fiscal_years'])
<a href="{{ route('fiscal-years.create') }}" style="color:#6ee7d0;font-size:10px;text-decoration:none">+ تعریف سال مالی</a>
@endif
</div>
<div class="user"><div><strong>{{ $user->name }}</strong><small>{{ $subscriptionStatus }}</small></div><div class="avatar">{{ mb_substr($user->name,0,1) }}</div><form method="POST" action="{{ route('logout') }}">@csrf<button class="logout">خروج</button></form></div>
</header>

<section class="content">
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
</main>
</div>

<nav class="mobile-nav" aria-label="ناوبری موبایل">
<a class="active" href="{{ route('dashboard') }}"><span>⌂</span>خانه</a>
@if($can['orders'])<a href="{{ route('sales.orders.index') }}"><span>▤</span>فروش</a>@endif
@if($can['deliveries'])<a href="{{ route('sales.deliveries.index') }}"><span>⇢</span>تحویل</a>@endif
@if($can['costing'])<a href="{{ route('reports.costing') }}"><span>◌</span>هزینه</a>@endif
@if($can['users'])<a href="{{ route('users.index') }}"><span>♙</span>کاربران</a>@endif
</nav>
</body>
</html>
