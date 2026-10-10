<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'حسابداری صنعتی')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: Vazirmatn, sans-serif; background: #f5f7fb; }
        .navbar-brand { font-weight: 800; }
        .container { max-width: 1400px; }
        .table { vertical-align: middle; }
        @media (max-width: 576px) {
            h1 { font-size: 1.4rem; }
            .table { font-size: .82rem; }
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg bg-dark navbar-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="{{ route('dashboard') }}">حسابداری صنعتی</a>
        <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#nav">☰</button>
        <div id="nav" class="collapse navbar-collapse">
            <div class="navbar-nav">
                <a class="nav-link" href="{{ route('dashboard') }}">داشبورد</a>

                @if (auth()->user()->hasCompanyPermission((int) session('company_id'), 'notification.view'))
                    <a class="nav-link" href="{{ route('notifications.index') }}">اعلان‌ها</a>
                @endif

                @if (auth()->user()->hasCompanyPermission((int) session('company_id'), 'supply_request.view'))
                    <a class="nav-link" href="{{ route('workflows.supply') }}">تأمین</a>
                @endif

                @if (auth()->user()->hasCompanyPermission((int) session('company_id'), 'inventory.view'))
                    <a class="nav-link" href="{{ route('inventory.index') }}">انبار</a>
                @endif

                @if (auth()->user()->hasCompanyPermission((int) session('company_id'), 'production.operation.view'))
                    <a class="nav-link" href="{{ route('production.execution.index') }}">عملیات تولید</a>
                @endif

                @if (auth()->user()->hasCompanyPermission(session('company_id'), 'supplier.view'))
                    <a class="nav-link" href="{{ route('master.index', 'suppliers') }}">تأمین‌کنندگان</a>
                @endif

                @if (auth()->user()->hasCompanyPermission(session('company_id'), 'goods.view'))
                    <a class="nav-link" href="{{ route('master.index', 'goods') }}">کالاها</a>
                @endif

                @if (auth()->user()->hasCompanyPermission(session('company_id'), 'location.view'))
                    <a class="nav-link" href="{{ route('master.index', 'locations') }}">انبارها</a>
                @endif

                @if (auth()->user()->hasCompanyPermission(session('company_id'), 'production_route.view'))
                    <a class="nav-link" href="{{ route('master.index', 'production-routes') }}">مسیر تولید</a>
                @endif

                @if (auth()->user()->hasCompanyPermission(session('company_id'), 'production.view'))
                    <a class="nav-link" href="{{ route('master.index', 'productions') }}">تولید</a>
                @endif

                <a class="nav-link" href="{{ route('sales.orders.index') }}">سفارش‌ها</a>
                <a class="nav-link" href="{{ route('sales.deliveries.index') }}">تحویل</a>
            </div>
        </div>
    </div>
</nav>

<main>
    @yield('content')
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
