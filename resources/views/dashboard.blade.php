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
:root{font-family:"Vazirmatn","Segoe UI",Tahoma,sans-serif;background:#07111f;color:#e9f3fb}*{box-sizing:border-box}body{margin:0;background:#07111f}.app{min-height:100vh;display:grid;grid-template-columns:270px 1fr}.sidebar{background:#091725;border-left:1px solid #1c3348;padding:25px 18px}.brand{display:flex;gap:11px;align-items:center;padding:0 8px 25px;border-bottom:1px solid #173047}.logo{width:42px;height:42px;border-radius:13px;display:grid;place-items:center;background:linear-gradient(135deg,#6ee7d0,#67b7ff);color:#04131b;font-weight:900}.brand b{display:block;font-size:14px}.brand span{font-size:9px;color:#6f879d}.nav{padding-top:24px}.nav-title{font-size:8px;color:#587087;margin:0 10px 9px}.nav a{display:flex;align-items:center;gap:10px;padding:11px 12px;border-radius:11px;text-decoration:none;color:#8ba1b5;font-size:11px;margin-bottom:4px}.nav a.active,.nav a:hover{background:#10283c;color:#dcebf6}.main{min-width:0}.top{height:76px;border-bottom:1px solid #183047;display:flex;align-items:center;justify-content:space-between;padding:0 34px;background:#081522}.company{display:flex;align-items:center;gap:12px}.company select{background:#0d2133;color:#dcebf5;border:1px solid #29465e;border-radius:10px;padding:9px 13px;font-family:inherit;font-size:11px}.user{display:flex;align-items:center;gap:12px}.avatar{width:35px;height:35px;border-radius:50%;display:grid;place-items:center;background:#15334a;color:#6ee7d0;font-weight:800}.user small{display:block;color:#71889d;font-size:8px}.user strong{font-size:11px}.logout{border:0;background:none;color:#7f97aa;font-family:inherit;cursor:pointer;font-size:10px}.content{padding:32px 34px}.welcome{display:flex;justify-content:space-between;align-items:end;gap:20px;margin-bottom:25px}.eyebrow{color:#6ee7d0;font-size:9px;font-weight:800;letter-spacing:1px}.welcome h1{font-size:29px;margin:7px 0 0;letter-spacing:-.7px}.welcome p{margin:7px 0 0;color:#71879d;font-size:11px}.grid{display:grid;grid-template-columns:repeat(4,1fr);gap:13px}.card{background:#0a1b2b;border:1px solid #1b354b;border-radius:17px;padding:19px}.card small{color:#698096;font-size:9px}.card b{display:block;font-size:24px;margin-top:10px}.section{margin-top:18px;display:grid;grid-template-columns:1.35fr .65fr;gap:18px}.panel{background:#0a1b2b;border:1px solid #1b354b;border-radius:18px;padding:21px}.panel h2{font-size:14px;margin:0}.panel p{font-size:10px;color:#71879d;line-height:2;margin:8px 0 18px}.flow{display:grid;grid-template-columns:repeat(4,1fr);gap:9px}.step{padding:14px 8px;text-align:center;background:#0d2235;border:1px solid #1a3449;border-radius:12px}.step strong{font-size:10px;display:block}.step span{font-size:8px;color:#617a91;display:block;margin-top:5px}.empty{padding:25px;text-align:center;border:1px dashed #29455d;border-radius:13px;color:#71879d;font-size:10px}.quick a{display:flex;justify-content:space-between;padding:12px 0;border-bottom:1px solid #173047;color:#b9cbd9;text-decoration:none;font-size:10px}.quick a:last-child{border-bottom:0}.subscription{display:flex;align-items:center;gap:10px;padding:11px 14px;border-radius:12px;margin-bottom:18px;font-size:10px}.subscription.active{background:#0d2b2a;border:1px solid #1b5a50;color:#8fe5d4}.subscription.inactive{background:#2b1b12;border:1px solid #654025;color:#f2c28d}.subscription a{margin-right:auto;color:inherit;font-weight:800;text-decoration:none;border:1px solid currentColor;border-radius:8px;padding:6px 10px}.mobile-nav{display:none}@media(max-width:900px){.app{grid-template-columns:1fr}.sidebar{display:none}.mobile-nav{display:block}.grid{grid-template-columns:1fr 1fr}.section{grid-template-columns:1fr}.top,.content{padding-left:18px;padding-right:18px}}@media(max-width:560px){.grid{grid-template-columns:1fr}.welcome{display:block}.flow{grid-template-columns:1fr 1fr}.company select{max-width:150px}}
</style>
</head>
<body>
<div class="app">
<aside class="sidebar">
<div class="brand"><div class="logo">ح</div><div><b>حسابداری صنعتی</b><span>مدیریت تولید و بهای تمام‌شده</span></div></div>
<nav class="nav">
<div class="nav-title">داشبورد</div>
<a class="active" href="/dashboard">⌂ نمای کلی</a>
<div class="nav-title">عملیات</div>
<a href="#">▦ خرید و تأمین</a><a href="#">▤ انبار و مواد</a><a href="#">⚙ تولید و کارگاه</a><a href="#">◈ بهای تمام‌شده</a>
<div class="nav-title">مدیریت</div>
<a href="#">▣ شرکت‌ها و دوره مالی</a><a href="#">♙ کاربران و دسترسی‌ها</a><a href="#">◌ گزارش‌ها</a>
</nav>
</aside>
<main class="main">
<header class="top">
<div class="company">
@if($companies->isNotEmpty())
<select onchange="location.href='/dashboard?company='+this.value">
@foreach($companies as $item)<option value="{{ $item->id }}" @selected($company?->id === $item->id)>{{ $item->name }}</option>@endforeach
</select>
@else
<span style="color:#71879d;font-size:10px">هنوز شرکتی تعریف نشده</span>
@endif
@if($fiscalYears->isNotEmpty())<span style="color:#71879d;font-size:10px">{{ $fiscalYears->first()->name }}</span>@endif
</div>
<div class="user"><div><strong>{{ $user->name }}</strong><small>مدیر سیستم</small></div><div class="avatar">{{ mb_substr($user->name,0,1) }}</div><form method="POST" action="/logout">@csrf<button class="logout">خروج</button></form></div>
</header>
<section class="content">
@if($subscriptionActive)
<div class="subscription active"><strong>اشتراک فعال</strong><span>دسترسی به عملیات حسابداری فعال است.</span><span>تا {{ $subscription->expires_at?->format('Y/m/d H:i') }}</span></div>
@else
<div class="subscription inactive"><strong>اشتراک {{ $subscriptionStatus === 'expired' ? 'منقضی شده' : 'فعال نیست' }}</strong><span>اطلاعات و گزارش‌های قبلی شما محفوظ است؛ برای ادامه عملیات اشتراک را تمدید کنید.</span><a href="{{ $renewalUrl }}">تمدید اشتراک</a></div>
@endif
<div class="welcome"><div><div class="eyebrow">CONTROL CENTER</div><h1>داشبورد حسابداری صنعتی</h1><p>نمای کلی عملیات شرکت و مسیر هزینه از خرید تا تولید.</p></div></div>
<div class="grid">
<div class="card"><small>موجودی انبار</small><b>۰</b></div><div class="card"><small>سفارش‌های خرید</small><b>۰</b></div><div class="card"><small>تولید جاری</small><b>۰</b></div><div class="card"><small>بهای تمام‌شده</small><b>۰</b></div>
</div>
<div class="section">
<div class="panel"><h2>مسیر عملیاتی</h2><p>ساختار اولیه سیستم آماده است. بعد از تعریف شرکت، دوره مالی و اطلاعات پایه، عملیات واقعی از همین مسیر وارد می‌شود.</p><div class="flow"><div class="step"><strong>خرید</strong><span>تأمین و سفارش</span></div><div class="step"><strong>انبار</strong><span>دریافت و گردش</span></div><div class="step"><strong>تولید</strong><span>مصرف و عملیات</span></div><div class="step"><strong>هزینه</strong><span>محاسبه نهایی</span></div></div></div>
<div class="panel quick"><h2>دسترسی سریع</h2><a href="#">تعریف شرکت <span>←</span></a><a href="#">دوره مالی <span>←</span></a><a href="#">کاربران <span>←</span></a><a href="#">نقش‌ها و دسترسی‌ها <span>←</span></a></div>
</div>
@if(!$company)<div class="panel" style="margin-top:18px"><div class="empty">برای شروع، مدیر سیستم باید شرکت و دوره مالی را تعریف کند.</div></div>@endif
</section>
</main>
</div>
</body>
</html>
