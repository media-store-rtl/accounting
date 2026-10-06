<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>داشبورد | حسابداری صنعتی</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
:root{font-family:"Vazirmatn","Segoe UI",Tahoma,sans-serif;background:#07111f;color:#e9f3fb}*{box-sizing:border-box}body{margin:0;background:#07111f}.app{min-height:100vh;display:grid;grid-template-columns:270px 1fr}.sidebar{background:#091725;border-left:1px solid #1c3348;padding:25px 18px}.brand{display:flex;gap:11px;align-items:center;padding:0 8px 25px;border-bottom:1px solid #173047}.logo{width:42px;height:42px;border-radius:13px;display:grid;place-items:center;background:linear-gradient(135deg,#6ee7d0,#67b7ff);color:#04131b;font-weight:900}.brand b{display:block;font-size:14px}.brand span{font-size:9px;color:#6f879d}.nav{padding-top:24px}.nav-title{font-size:8px;color:#587087;margin:0 10px 9px}.nav a{display:flex;align-items:center;gap:10px;padding:11px 12px;border-radius:11px;text-decoration:none;color:#8ba1b5;font-size:11px;margin-bottom:4px}.nav a:hover,.nav a.active{background:#10283c;color:#dcebf6}.main{min-width:0}.top{height:76px;border-bottom:1px solid #183047;display:flex;align-items:center;justify-content:space-between;padding:0 34px;background:#081522}.company{display:flex;align-items:center;gap:12px}.company select{background:#0d2133;color:#dcebf5;border:1px solid #29465e;border-radius:10px;padding:9px 13px;font-family:inherit;font-size:11px}.user{display:flex;align-items:center;gap:12px}.avatar{width:35px;height:35px;border-radius:50%;display:grid;place-items:center;background:#15334a;color:#6ee7d0;font-weight:800}.user small{display:block;color:#71889d;font-size:8px}.user strong{font-size:11px}.logout{border:0;background:none;color:#7f97aa;font-family:inherit;cursor:pointer;font-size:10px}.content{padding:32px 34px}.welcome{display:flex;justify-content:space-between;align-items:end;gap:20px;margin-bottom:25px}.eyebrow{color:#6ee7d0;font-size:9px;font-weight:800;letter-spacing:1px}.welcome h1{font-size:29px;margin:7px 0 0;letter-spacing:-.7px}.welcome p{margin:7px 0 0;color:#71879d;font-size:11px}.grid{display:grid;grid-template-columns:repeat(4,1fr);gap:13px}.card{background:#0a1b2b;border:1px solid #1b354b;border-radius:17px;padding:19px}.card small{color:#698096;font-size:9px}.card b{display:block;font-size:18px;margin-top:10px}.active{color:#6ee7d0}.expired{color:#ff9b9b}.panel{background:#0a1b2b;border:1px solid #1b354b;border-radius:18px;padding:21px;margin-top:18px}.panel h2{font-size:14px;margin:0}.panel p,.note{font-size:10px;color:#71879d;line-height:2}.quick{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}.quick a{padding:13px;background:#0d2235;border:1px solid #1a3449;border-radius:12px;color:#b9cbd9;text-decoration:none;font-size:10px}.warning{border-color:#5a3838}.warning p{color:#ffb0b0}@media(max-width:900px){.app{grid-template-columns:1fr}.sidebar{display:none}.grid{grid-template-columns:1fr 1fr}.quick{grid-template-columns:1fr}.top,.content{padding-left:18px;padding-right:18px}}@media(max-width:560px){.grid{grid-template-columns:1fr}.welcome{display:block}}
</style>
</head>
<body>
<div class="app"><aside class="sidebar">
<div class="brand"><div class="logo">ح</div><div><b>حسابداری صنعتی</b><span>مدیریت تولید و بهای تمام‌شده</span></div></div>
<nav class="nav"><div class="nav-title">داشبورد</div><a class="active" href="{{ route('dashboard') }}">⌂ نمای کلی</a>
<div class="nav-title">تعاریف پایه</div><a href="{{ route('company.edit') }}">▣ اطلاعات شرکت</a><a href="{{ route('fiscal-years.index') }}">◫ سال‌های مالی</a><a href="#">♙ کاربران و دسترسی‌ها</a>
<div class="nav-title">عملیات</div><a href="#">▦ خرید و تأمین</a><a href="#">▤ انبار و مواد</a><a href="#">⚙ تولید و کارگاه</a><a href="#">◈ بهای تمام‌شده</a></nav>
</aside>
<main class="main"><header class="top"><div class="company">@if($company)<span style="color:#dcebf5;font-size:11px">{{ $company->name }}</span>@else<span style="color:#71879d;font-size:10px">هنوز شرکتی تعریف نشده</span>@endif</div>
<div class="user"><div><strong>{{ $user->name }}</strong><small>مدیر سیستم</small></div><div class="avatar">{{ mb_substr($user->name,0,1) }}</div><form method="POST" action="{{ route('logout') }}">@csrf<button class="logout">خروج</button></form></div></header>
<section class="content">
<div class="welcome"><div><div class="eyebrow">CONTROL CENTER</div><h1>داشبورد حسابداری صنعتی</h1><p>وضعیت راه‌اندازی، اشتراک و داده‌های پایه حساب.</p></div></div>
@if(session('subscription_error'))<div class="panel warning"><h2>اشتراک</h2><p>{{ session('subscription_error') }}</p></div>@endif
<div class="grid">
<div class="card"><small>وضعیت اشتراک</small><b class="{{ $subscription?->isActive() ? 'active' : 'expired' }}">{{ $subscription?->isActive() ? 'فعال' : 'منقضی / غیرفعال' }}</b></div>
<div class="card"><small>انقضای اشتراک</small><b>{{ $subscription?->expires_at?->format('Y-m-d') ?? 'نامشخص' }}</b></div>
<div class="card"><small>سال‌های مالی</small><b>{{ $fiscalYears->count() }}</b></div>
<div class="card"><small>شرکت</small><b>{{ $company ? 'ثبت شده' : 'ثبت نشده' }}</b></div>
</div>
<div class="panel"><h2>دسترسی سریع</h2><div class="quick">
<a href="{{ route('company.edit') }}">اطلاعات شرکت ←</a><a href="{{ route('fiscal-years.index') }}">سال‌های مالی ←</a>
@if($subscription?->isActive() && $fiscalYears->isEmpty())<a href="{{ route('fiscal-years.create') }}">ایجاد اولین سال مالی ←</a>@else<a href="{{ route('fiscal-years.index') }}">مشاهده اطلاعات قبلی ←</a>@endif
</div></div>
@if(!$company)<div class="panel"><p>برای شروع، ابتدا اطلاعات شرکت / مجموعه را تکمیل کنید.</p></div>@endif
@if($company && $fiscalYears->isEmpty())<div class="panel"><p>هنوز سال مالی ثبت نشده است. ایجاد سال مالی فقط با اشتراک فعال امکان‌پذیر است.</p></div>@endif
</section></main></div></body></html>
