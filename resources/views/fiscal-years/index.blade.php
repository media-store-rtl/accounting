@extends('layouts.dashboard-shell')

@section('title', 'سال‌های مالی | حسابداری صنعتی')

@section('content')
<div class="page-heading">
    <div>
        <h1>سال‌های مالی</h1>
        <p>مدیریت دوره‌های مالی شرکت و وضعیت باز یا بسته بودن آن‌ها.</p>
    </div>
    <a class="btn primary" href="{{ route('fiscal-years.create') }}">＋ تعریف سال مالی</a>
</div>

<style>
.fiscal-meta{color:#71879d;font-size:11px;margin:0 0 18px}
.fiscal-badge{display:inline-block;border-radius:20px;padding:5px 10px;font-size:10px;background:#12372f;color:#6ee7d0}
.fiscal-badge.closed{background:#3b2b24;color:#e7b98b}
.fiscal-actions{display:flex;align-items:center;gap:7px;flex-wrap:wrap}
.fiscal-actions form{display:inline-flex;margin:0}
.fiscal-actions .btn{white-space:nowrap;cursor:pointer;font-family:inherit}
.fiscal-empty{text-align:center;padding:34px 12px;color:#71879d;font-size:12px}
.fiscal-success{padding:12px 14px;background:#123629;border:1px solid #245443;color:#8ee7c8;border-radius:10px;margin-bottom:16px;font-size:11px}
@media(max-width:700px){.fiscal-actions{min-width:180px}.fiscal-actions .btn{font-size:10px;padding:8px 9px}}
</style>

<p class="fiscal-meta">شرکت فعال: {{ $company->name }}</p>

@if(session('success'))
    <div class="fiscal-success">{{ session('success') }}</div>
@endif

<div class="panel">
    @if($fiscalYears->isEmpty())
        <div class="fiscal-empty">
            <p>هنوز سال مالی‌ای برای این شرکت تعریف نشده است.</p>
            <a class="btn primary" href="{{ route('fiscal-years.create') }}">تعریف اولین سال مالی</a>
        </div>
    @else
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>نام سال مالی</th>
                        <th>کد</th>
                        <th>تاریخ شروع</th>
                        <th>تاریخ پایان</th>
                        <th>وضعیت</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($fiscalYears as $year)
                        <tr>
                            <td>{{ $year->name }}</td>
                            <td>{{ $year->code }}</td>
                            <td>{{ $year->starts_at?->format('Y/m/d') }}</td>
                            <td>{{ $year->ends_at?->format('Y/m/d') }}</td>
                            <td>
                                <span class="fiscal-badge {{ $year->is_closed ? 'closed' : '' }}">{{ $year->is_closed ? 'بسته' : 'باز' }}</span>
                                @if(!$year->is_closed && $currentFiscalYearId === (int) $year->id)
                                    <span class="fiscal-badge">سال جاری</span>
                                @endif
                            </td>
                            <td>
                                <div class="fiscal-actions">
                                    @if(!$year->is_closed)
                                        <a class="btn" href="{{ route('fiscal-years.edit', $year) }}">ویرایش</a>
                                        @if($currentFiscalYearId !== (int) $year->id)
                                            <form method="POST" action="{{ route('fiscal-years.select', $year) }}">
                                                @csrf
                                                <button class="btn" type="submit">فعال‌کردن</button>
                                            </form>
                                        @endif
                                        <form method="POST" action="{{ route('fiscal-years.close', $year) }}" onsubmit="return confirm('سال مالی بسته شود؟ پس از بستن، امکان ویرایش آن وجود نخواهد داشت.');">
                                            @csrf
                                            <button class="btn" type="submit">بستن سال</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
