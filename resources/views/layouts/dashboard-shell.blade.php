<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>@yield('title', 'حسابداری صنعتی')</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
:root{font-family:"Vazirmatn","Segoe UI",Tahoma,sans-serif;background:#07111f;color:#e9f3fb}
*{box-sizing:border-box}body{margin:0;background:#07111f;color:#e9f3fb}.app{min-height:100vh;display:grid;grid-template-columns:270px 1fr}
.sidebar{background:#091725;border-left:1px solid #1c3348;padding:22px 16px;position:sticky;top:0;height:100vh;overflow-y:auto}
.brand{display:flex;gap:11px;align-items:center;padding:0 8px 22px;border-bottom:1px solid #173047}.logo{width:42px;height:42px;border-radius:13px;display:grid;place-items:center;background:linear-gradient(135deg,#6ee7d0,#67b7ff);color:#04131b;font-weight:900}.brand b{display:block;font-size:14px}.brand span{font-size:9px;color:#6f879d}
.nav{padding-top:20px}.nav-title{font-size:8px;color:#587087;margin:0 10px 8px}.nav a{display:flex;align-items:center;gap:10px;padding:11px 12px;border-radius:11px;text-decoration:none;color:#8ba1b5;font-size:11px;margin-bottom:4px}.nav a.active,.nav a:hover{background:#10283c;color:#dcebf6}
.main{min-width:0}.top{min-height:76px;border-bottom:1px solid #183047;display:flex;align-items:center;justify-content:space-between;gap:16px;padding:0 34px;background:#081522}
.company{display:flex;align-items:center;gap:12px;min-width:0}.company select{background:#0d2133;color:#dcebf5;border:1px solid #29465e;border-radius:10px;padding:9px 13px;font-family:inherit;font-size:11px;max-width:230px}
.user{display:flex;align-items:center;gap:10px;flex-shrink:0}.avatar{width:35px;height:35px;border-radius:50%;display:grid;place-items:center;background:#15334a;color:#6ee7d0;font-weight:800}.user small{display:block;color:#71889d;font-size:8px}.user strong{font-size:11px}.logout{border:0;background:none;color:#7f97aa;font-family:inherit;cursor:pointer;font-size:10px}
.content{padding:32px 34px}.page-heading{display:flex;justify-content:space-between;align-items:center;gap:20px;margin-bottom:18px}.page-heading h1{font-size:25px;margin:0}.page-heading p{margin:7px 0 0;color:#71879d;font-size:11px}
.panel{background:#0a1b2b;border:1px solid #1b354b;border-radius:18px;padding:21px}.actions{display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px}.btn{display:inline-flex;align-items:center;gap:6px;padding:10px 14px;border-radius:9px;background:#12314a;color:#e9f3fb;text-decoration:none;border:1px solid #29465e;font-size:11px;font-family:inherit}.btn.primary{background:#6ee7d0;color:#04131b;border-color:#6ee7d0;font-weight:800}
.table-wrap{overflow-x:auto}table{width:100%;border-collapse:collapse;font-size:11px;text-align:right;white-space:nowrap}th{color:#71879d;font-weight:600;background:#0d2235}th,td{padding:13px 12px;border-bottom:1px solid #1b354b}tbody tr:hover{background:#0d2235}.status{display:inline-block;border-radius:20px;padding:4px 9px;font-size:10px}.status.active{color:#6ee7d0;background:#12372f}.status.inactive{color:#ffb8a8;background:#3b2528}
.pagination{margin-top:18px;color:#9eb2c4;font-size:11px}.pagination nav{display:flex;justify-content:space-between;gap:12px}.pagination a,.pagination span[aria-current]{color:#6ee7d0}
.mobile-nav{display:none}
@media(max-width:900px){.app{display:block}.sidebar{display:none}.top,.content{padding-left:18px;padding-right:18px}.mobile-nav{position:fixed;display:grid;grid-template-columns:repeat(5,1fr);bottom:0;right:0;left:0;height:68px;background:rgba(8,21,34,.97);border-top:1px solid #1b354b;z-index:50;padding-bottom:env(safe-area-inset-bottom)}.mobile-nav a{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;color:#7890a5;text-decoration:none;font-size:8px}.mobile-nav a span{font-size:18px;line-height:1}.mobile-nav a.active{color:#6ee7d0}.content{padding-bottom:90px}}
@media(max-width:560px){.top{min-height:70px;align-items:flex-start;padding-top:12px;padding-bottom:12px}.company{flex:1;flex-wrap:wrap}.company select{max-width:160px}.user>div:first-child{display:none}.content{padding-top:22px}.page-heading{align-items:flex-start}.page-heading h1{font-size:22px}.panel{padding:13px}.mobile-nav{height:64px}}
</style>
@stack('styles')
</head>
<body>
@php
$user = auth()->user();
$companies = $user->companies()->where('companies.is_active', true)->orderBy('companies.name')->get();
$company = $companies->firstWhere('id', (int) session('company_id'));
$fiscalYears = $company?->fiscalYears()->orderByDesc('starts_at')->get() ?? collect();
$fiscalYear = $fiscalYears->firstWhere('id', (int) session('fiscal_year_id'));
if (!isset($can) || !is_array($can)) {
    $permissions = ['company'=>'company.view','customers'=>'customer.view','orders'=>'order.view','deliveries'=>'delivery_request.view','personnel'=>'personnel.view','users'=>'user.view','roles'=>'role.view','fiscal_years'=>'fiscal_year.view','costing'=>'costing.report.view','backups'=>'backup.view','goods'=>'goods.view','notifications'=>'notification.view','supply'=>'supply_request.view','purchasing'=>'purchase.view','inventory'=>'inventory.view','production_execution'=>'production.operation.view','suppliers'=>'supplier.view','locations'=>'location.view','production_routes'=>'production_route.view','productions'=>'production.view','production_outputs'=>'production.output.view','handovers'=>'supply_request.handover.view','receipts'=>'purchase.receipt.create'];
    $can = [];
    foreach ($permissions as $key => $permission) { $can[$key] = (bool) ($company && $user->hasCompanyPermission((int) $company->id, $permission)); }
}
@endphp
<div class="app">
<aside class="sidebar">
<div class="brand"><div class="logo">ح</div><div><b>حسابداری صنعتی</b><span>مدیریت تولید و بهای تمام‌شده</span></div></div>
<nav class="nav">
<div class="nav-title">نمای کلی</div><a class="{{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">⌂ داشبورد</a>
@if($can['notifications'])<a class="{{ request()->routeIs('notifications.*') ? 'active' : '' }}" href="{{ route('notifications.index') }}">♢ اعلان‌ها</a>@endif
<div class="nav-title">سفارش و فروش</div>
@if($can['customers'])<a class="{{ request()->routeIs('sales.customers.*') ? 'active' : '' }}" href="{{ route('sales.customers.index') }}">♙ مشتریان</a>@endif
@if($can['orders'])<a class="{{ request()->routeIs('sales.orders.*') ? 'active' : '' }}" href="{{ route('sales.orders.index') }}">▤ سفارش‌های فروش</a>@endif
@if($can['deliveries'])<a class="{{ request()->routeIs('sales.deliveries.*') ? 'active' : '' }}" href="{{ route('sales.deliveries.index') }}">⇢ تحویل و ارسال</a>@endif
<div class="nav-title">تأمین و خرید</div>
<a class="{{ request()->routeIs('workflows.supply') ? 'active' : '' }}" href="{{ route('workflows.supply') }}">◈ درخواست‌های تأمین</a>
<a class="{{ request()->routeIs('workflows.purchasing') ? 'active' : '' }}" href="{{ route('workflows.purchasing') }}">▤ خریدها</a>
<a class="{{ request()->routeIs('workflows.receipts') ? 'active' : '' }}" href="{{ route('workflows.receipts') }}">⇣ رسیدهای خرید</a>
<a class="{{ request()->routeIs('workflows.handovers') ? 'active' : '' }}" href="{{ route('workflows.handovers') }}">⇢ تحویل مواد</a>
<div class="nav-title">انبار</div>
<a class="{{ request()->routeIs('inventory.*') ? 'active' : '' }}" href="{{ route('inventory.index') }}">▦ موجودی انبار</a>
<a class="{{ request()->routeIs('master.*') && request()->route('module') === 'locations' ? 'active' : '' }}" href="{{ route('master.index', 'locations') }}">▣ انبارها و محل‌ها</a>
<div class="nav-title">تولید</div>
<a class="{{ request()->routeIs('master.*') && request()->route('module') === 'productions' ? 'active' : '' }}" href="{{ route('master.index', 'productions') }}">⚙ دستورهای تولید</a>
<a class="{{ request()->routeIs('production.execution.*') ? 'active' : '' }}" href="{{ route('production.execution.index') }}">⚒ عملیات تولید</a>
<a class="{{ request()->routeIs('production.outputs.*') ? 'active' : '' }}" href="{{ route('production.outputs.index') }}">⇡ خروجی تولید</a>
<div class="nav-title">اطلاعات پایه</div>
<a class="{{ request()->routeIs('master.*') && request()->route('module') === 'goods' ? 'active' : '' }}" href="{{ route('master.index', 'goods') }}">▦ کالاها و کد کالا</a>
<a class="{{ request()->routeIs('master.*') && request()->route('module') === 'suppliers' ? 'active' : '' }}" href="{{ route('master.index', 'suppliers') }}">♙ تأمین‌کنندگان</a>
<a class="{{ request()->routeIs('master.*') && request()->route('module') === 'production-routes' ? 'active' : '' }}" href="{{ route('master.index', 'production-routes') }}">⇢ مسیرهای تولید</a>
<div class="nav-title">مدیریت</div>
@if($can['company'])<a class="{{ request()->routeIs('company.*') ? 'active' : '' }}" href="{{ route('company.edit') }}">▤ اطلاعات مجموعه</a>@endif
@if($can['fiscal_years'])<a class="{{ request()->routeIs('fiscal-years.*') ? 'active' : '' }}" href="{{ route('fiscal-years.index') }}">▣ سال‌های مالی</a>@endif
@if($can['personnel'])<a class="{{ request()->routeIs('personnel.*') ? 'active' : '' }}" href="{{ route('personnel.index') }}">♟ پرسنل</a>@endif
@if($can['users'])<a class="{{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">♙ کاربران</a>@endif
@if($can['roles'])<a class="{{ request()->routeIs('roles.*') ? 'active' : '' }}" href="{{ route('roles.index') }}">⚿ نقش‌ها و دسترسی‌ها</a>@endif
@if($can['costing'])<a class="{{ request()->routeIs('reports.costing*') ? 'active' : '' }}" href="{{ route('reports.costing') }}">◌ بهای تمام‌شده</a>@endif
@if($can['backups'])<a class="{{ request()->routeIs('backups.*') ? 'active' : '' }}" href="{{ route('backups.index') }}">▣ پشتیبان‌گیری</a>@endif
</nav>
</aside>
<main class="main">
<header class="top">
<div class="company">
@if($companies->isNotEmpty())
<select aria-label="انتخاب شرکت" onchange="location.href='{{ route('dashboard') }}?company='+this.value">
@foreach($companies as $item)<option value="{{ $item->id }}" @selected($company?->id === $item->id)>{{ $item->name }}</option>@endforeach
</select>
@else<span style="color:#71879d;font-size:10px">هنوز شرکتی تعریف نشده</span>@endif
@if($fiscalYears->isNotEmpty() && $can['fiscal_years'])
<form method="POST" action="{{ route('fiscal-years.select', $fiscalYear ?? $fiscalYears->first()) }}" style="margin:0">@csrf
<select aria-label="انتخاب سال مالی" onchange="this.form.action='{{ url('/fiscal-years') }}/'+this.value+'/select';this.form.submit()">
@foreach($fiscalYears as $year)<option value="{{ $year->id }}" @selected($fiscalYear?->id === $year->id) @disabled($year->is_closed)>{{ $year->name }}{{ $year->is_closed ? ' (بسته)' : '' }}</option>@endforeach
</select></form>
@elseif($company && $can['fiscal_years'])<a href="{{ route('fiscal-years.create') }}" style="color:#6ee7d0;font-size:10px;text-decoration:none">+ تعریف سال مالی</a>@endif
</div>
<div class="user"><div><strong>{{ $user->name }}</strong><small>{{ $company?->name ?? 'حسابداری صنعتی' }}</small></div><div class="avatar">{{ mb_substr($user->name,0,1) }}</div><form method="POST" action="{{ route('logout') }}">@csrf<button class="logout">خروج</button></form></div>
</header>
<section class="content">@yield('content')</section>
</main>
</div>
<nav class="mobile-nav" aria-label="ناوبری موبایل">
<a class="{{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}"><span>⌂</span>خانه</a>
@if($can['orders'])<a class="{{ request()->routeIs('sales.orders.*') ? 'active' : '' }}" href="{{ route('sales.orders.index') }}"><span>▤</span>فروش</a>@endif
@if($can['deliveries'])<a class="{{ request()->routeIs('sales.deliveries.*') ? 'active' : '' }}" href="{{ route('sales.deliveries.index') }}"><span>⇢</span>تحویل</a>@endif
@if($can['costing'])<a class="{{ request()->routeIs('reports.costing*') ? 'active' : '' }}" href="{{ route('reports.costing') }}"><span>◌</span>هزینه</a>@endif
@if($can['users'])<a class="{{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}"><span>♙</span>کاربران</a>@endif
</nav>
</body>
</html>