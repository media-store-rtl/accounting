<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>@yield('title', 'حسابداری صنعتی')</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
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

body{font-family:"Vazirmatn","Segoe UI",Tahoma,sans-serif;background:#07111f;color:#e9f3fb}
.dashboard-content{padding:0}
a{color:#6ee7d0}
main.main{min-width:0}
.content>.container{max-width:1400px}
.content .card,.content .table,.content .form-control,.content .form-select,.content .alert{font-family:inherit}
.content .card{background:#0a1b2b;color:#e9f3fb;border-color:#1b354b}
.content .table{color:#dcebf5;vertical-align:middle}
.content .form-control,.content .form-select{background:#0d2133;color:#dcebf5;border-color:#29465e}
.content .form-control::placeholder{color:#71879d}
.content .form-select option{background:#0d2133;color:#dcebf5}
.content .btn-primary{background:#168b83;border-color:#168b83}
.content .btn-outline-primary{color:#6ee7d0;border-color:#397e7b}
.content .text-muted{color:#8ba1b5!important}
.content .alert-success{background:#10352f;color:#b9f3dc;border-color:#285b59}
.content .alert-danger{background:#3b2028;color:#ffd2d8;border-color:#75404b}
.content .pagination .page-link{background:#0d2235;color:#dcebf5;border-color:#1b354b}
.content .pagination .active .page-link{background:#168b83;border-color:#168b83}
.top .company form{margin:0}
.top .company select{background:#0d2133;color:#dcebf5;border:1px solid #29465e;border-radius:10px;padding:9px 13px;font-family:inherit;font-size:11px;max-width:230px}
.nav a.active{background:#10283c;color:#dcebf6}
@media(max-width:900px){.content>.container{width:100%}}
</style>
</head>
<body>
@php
    $layoutUser = auth()->user();
    $layoutCompanies = $layoutUser ? $layoutUser->companies()->where('companies.is_active', true)->orderBy('companies.name')->get() : collect();
    $layoutCompanyId = (int) session('company_id');
    $layoutCompany = $layoutCompanies->firstWhere('id', $layoutCompanyId) ?? $layoutCompanies->first();
    $layoutYears = $layoutCompany ? $layoutCompany->fiscalYears()->orderByDesc('starts_at')->get() : collect();
    $layoutCan = fn (string $permission): bool => $layoutCompany && $layoutUser->hasCompanyPermission((int) $layoutCompany->id, $permission);
    $layoutActive = fn (array $patterns): bool => request()->routeIs(...$patterns);
@endphp
<div class="app">
<aside class="sidebar">
<div class="brand"><div class="logo">ح</div><div><b>حسابداری صنعتی</b><span>مدیریت تولید و بهای تمام‌شده</span></div></div>
<nav class="nav" aria-label="منوی اصلی">
<div class="nav-title">نمای کلی</div>
<a class="{{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">⌂ داشبورد</a>
@if($layoutCan('customer.view') || $layoutCan('order.view') || $layoutCan('delivery_request.view'))
<div class="nav-title">سفارش و فروش</div>
@if($layoutCan('customer.view'))<a class="{{ $layoutActive(['sales.customers.*']) ? 'active' : '' }}" href="{{ route('sales.customers.index') }}">♙ مشتریان</a>@endif
@if($layoutCan('order.view'))<a class="{{ $layoutActive(['sales.orders.*']) ? 'active' : '' }}" href="{{ route('sales.orders.index') }}">▤ سفارش‌های فروش</a>@endif
@if($layoutCan('delivery_request.view'))<a class="{{ $layoutActive(['sales.deliveries.*','sales.delivery.*']) ? 'active' : '' }}" href="{{ route('sales.deliveries.index') }}">⇢ تحویل و ارسال</a>@endif
@endif
@if($layoutCan('supply_request.view') || $layoutCan('purchase.view') || $layoutCan('inventory.view') || $layoutCan('supply_request.handover.view') || $layoutCan('purchase.receipt.create'))
<div class="nav-title">تأمین و انبار</div>
@if($layoutCan('supply_request.view'))<a class="{{ $layoutActive(['workflows.supply*']) ? 'active' : '' }}" href="{{ route('workflows.supply') }}">▤ درخواست‌های تأمین</a>@endif
@if($layoutCan('purchase.view'))<a class="{{ $layoutActive(['workflows.purchasing*']) ? 'active' : '' }}" href="{{ route('workflows.purchasing') }}">🛒 خرید</a>@endif
@if($layoutCan('inventory.view'))<a class="{{ $layoutActive(['inventory.*']) ? 'active' : '' }}" href="{{ route('inventory.index') }}">▦ موجودی انبار</a>@endif
@if($layoutCan('supply_request.handover.view'))<a class="{{ $layoutActive(['workflows.handovers*']) ? 'active' : '' }}" href="{{ route('workflows.handovers') }}">⇢ تحویل مواد</a>@endif
@if($layoutCan('purchase.receipt.create'))<a class="{{ $layoutActive(['workflows.receipts*']) ? 'active' : '' }}" href="{{ route('workflows.receipts') }}">▣ رسید خرید</a>@endif
@endif
@if($layoutCan('production.operation.view') || $layoutCan('production.output.view') || $layoutCan('production.labor.create') || $layoutCan('production.view'))
<div class="nav-title">تولید</div>
@if($layoutCan('production.view'))<a class="{{ $layoutActive(['master.index']) && request()->route('module') === 'productions' ? 'active' : '' }}" href="{{ route('master.index','productions') }}">⚙ تولیدها</a>@endif
@if($layoutCan('production.operation.view'))<a class="{{ $layoutActive(['production.execution.*']) ? 'active' : '' }}" href="{{ route('production.execution.index') }}">⚙ اجرای عملیات</a>@endif
@if($layoutCan('production.output.view'))<a class="{{ $layoutActive(['production.outputs.*']) ? 'active' : '' }}" href="{{ route('production.outputs.index') }}">▣ کالای ساخته‌شده</a>@endif
@if($layoutCan('production.labor.create'))<a class="{{ $layoutActive(['workflows.labor*']) ? 'active' : '' }}" href="{{ route('workflows.labor') }}">دستمزد تولید</a>@endif
@endif
<div class="nav-title">تعاریف و مدیریت</div>
@if($layoutCan('company.view'))<a class="{{ $layoutActive(['company.*']) ? 'active' : '' }}" href="{{ route('company.edit') }}">▤ اطلاعات مجموعه</a>@endif
@if($layoutCan('fiscal_year.view'))<a class="{{ $layoutActive(['fiscal-years.*']) ? 'active' : '' }}" href="{{ route('fiscal-years.index') }}">▣ سال‌های مالی</a>@endif
@if($layoutCan('personnel.view'))<a class="{{ $layoutActive(['personnel.*']) ? 'active' : '' }}" href="{{ route('personnel.index') }}">♟ پرسنل</a>@endif
@if($layoutCan('user.view'))<a class="{{ $layoutActive(['users.*']) ? 'active' : '' }}" href="{{ route('users.index') }}">♙ کاربران</a>@endif
@if($layoutCan('role.view'))<a class="{{ $layoutActive(['roles.*']) ? 'active' : '' }}" href="{{ route('roles.index') }}">⚿ نقش‌ها و دسترسی‌ها</a>@endif
@if($layoutCan('goods.view'))<a class="{{ $layoutActive(['master.index']) && request()->route('module') === 'goods' ? 'active' : '' }}" href="{{ route('master.index','goods') }}">▦ کالاها و کد کالا</a>@endif
@if($layoutCan('supplier.view'))<a class="{{ $layoutActive(['master.index']) && request()->route('module') === 'suppliers' ? 'active' : '' }}" href="{{ route('master.index','suppliers') }}">♙ تأمین‌کنندگان</a>@endif
@if($layoutCan('location.view'))<a class="{{ $layoutActive(['master.index']) && request()->route('module') === 'locations' ? 'active' : '' }}" href="{{ route('master.index','locations') }}">⌖ انبارها</a>@endif
@if($layoutCan('production_route.view'))<a class="{{ $layoutActive(['master.index']) && request()->route('module') === 'production-routes' ? 'active' : '' }}" href="{{ route('master.index','production-routes') }}">⇢ مسیر تولید</a>@endif
@if($layoutCan('costing.report.view'))<a class="{{ $layoutActive(['reports.costing*']) ? 'active' : '' }}" href="{{ route('reports.costing') }}">◌ بهای تمام‌شده</a>@endif
@if($layoutCan('backup.view'))<a class="{{ $layoutActive(['backups.*']) ? 'active' : '' }}" href="{{ route('backups.index') }}">▣ پشتیبان‌گیری</a>@endif
@if($layoutCan('import.excel'))<a class="{{ $layoutActive(['imports.excel.*']) ? 'active' : '' }}" href="{{ route('imports.excel.index') }}">⇧ ورود اطلاعات از Excel</a>@endif
@if($layoutCan('notification.view'))<div class="nav-title">پیام‌ها</div><a class="{{ $layoutActive(['notifications.*']) ? 'active' : '' }}" href="{{ route('notifications.index') }}">♧ اعلان‌ها</a>@endif
</nav>
</aside>
<main class="main">
<header class="top">
<div class="company">
@if($layoutCompanies->isNotEmpty())
<select aria-label="انتخاب شرکت" onchange="location.href='{{ route('dashboard') }}?company='+this.value">
@foreach($layoutCompanies as $item)<option value="{{ $item->id }}" @selected($layoutCompany?->id === $item->id)>{{ $item->name }}</option>@endforeach
</select>
@else<span style="color:#71879d;font-size:10px">هنوز شرکتی تعریف نشده</span>@endif
@if($layoutYears->isNotEmpty() && $layoutCan('fiscal_year.view'))
<form method="POST" action="{{ route('fiscal-years.select', $layoutYears->firstWhere('id', (int)session('fiscal_year_id')) ?? $layoutYears->first()) }}">
@csrf
<select aria-label="انتخاب سال مالی" onchange="this.form.action='{{ url('/fiscal-years') }}/'+this.value+'/select';this.form.submit()">
@foreach($layoutYears as $year)<option value="{{ $year->id }}" @selected((int)session('fiscal_year_id') === (int)$year->id) @disabled($year->is_closed)>{{ $year->name ?? $year->year ?? $year->id }}{{ $year->is_closed ? ' (بسته)' : '' }}</option>@endforeach
</select></form>
@elseif($layoutCompany && $layoutCan('fiscal_year.view'))<a href="{{ route('fiscal-years.create') }}" style="color:#6ee7d0;font-size:10px;text-decoration:none">+ تعریف سال مالی</a>@endif
</div>
<div class="user"><div><strong>{{ $layoutUser?->name }}</strong><small>{{ $layoutCompany?->name ?? 'بدون مجموعه' }}</small></div><div class="avatar">{{ $layoutUser ? mb_substr($layoutUser->name,0,1) : 'ح' }}</div><form method="POST" action="{{ route('logout') }}">@csrf<button class="logout">خروج</button></form></div>
</header>
<div class="content">
@if(session('success'))<div class="notice" style="margin-bottom:18px">{{ session('success') }}</div>@endif
@if(session('error'))<div class="notice warn" style="margin-bottom:18px">{{ session('error') }}</div>@endif
@if($errors->any())<div class="notice warn" style="margin-bottom:18px">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
@yield('content')
</div>
</main>
</div>
<nav class="mobile-nav" aria-label="ناوبری موبایل">
<a class="{{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><span>⌂</span>خانه</a>
@if($layoutCan('order.view'))<a class="{{ request()->routeIs('sales.orders.*') ? 'active' : '' }}" href="{{ route('sales.orders.index') }}"><span>▤</span>فروش</a>@endif
@if($layoutCan('inventory.view'))<a class="{{ request()->routeIs('inventory.*') ? 'active' : '' }}" href="{{ route('inventory.index') }}"><span>▦</span>انبار</a>@endif
@if($layoutCan('production.operation.view'))<a class="{{ request()->routeIs('production.execution.*') ? 'active' : '' }}" href="{{ route('production.execution.index') }}"><span>⚙</span>تولید</a>@endif
@if($layoutCan('notification.view'))<a class="{{ request()->routeIs('notifications.*') ? 'active' : '' }}" href="{{ route('notifications.index') }}"><span>♧</span>اعلان</a>@endif
</nav>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
