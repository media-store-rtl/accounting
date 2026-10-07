<!doctype html>
<html lang="fa" dir="rtl">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>داشبورد | حسابداری صنعتی</title></head>
<body>
<nav><a href="{{ route('dashboard') }}">داشبورد</a> <a href="{{ route('company.edit') }}">اطلاعات شرکت</a> <a href="{{ route('fiscal-years.index') }}">سال‌های مالی</a></nav>
<main>
<h1>داشبورد حسابداری صنعتی</h1>
@if(session('subscription_error'))<div>{{ session('subscription_error') }}</div>@endif
<section><strong>وضعیت اشتراک:</strong> {{ $subscription?->isActive() ? 'فعال' : 'منقضی / غیرفعال' }}</section>
<section><strong>انقضای اشتراک:</strong> {{ $subscription?->expires_at?->format('Y-m-d') ?? 'نامشخص' }}</section>
<section><strong>سال‌های مالی:</strong> {{ $fiscalYears->count() }}</section>
<section><strong>شرکت:</strong> {{ $company ? 'ثبت شده' : 'ثبت نشده' }}</section>
@if($subscription?->isActive() && $fiscalYears->isEmpty())<a href="{{ route('fiscal-years.create') }}">ایجاد اولین سال مالی</a>@else<a href="{{ route('fiscal-years.index') }}">مشاهده اطلاعات قبلی</a>@endif
</main>
</body></html>