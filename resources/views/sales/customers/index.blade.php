@extends('layouts.dashboard-shell')

@section('title', 'مشتریان | حسابداری صنعتی')

@section('content')
<div class="page-heading">
    <div>
        <h1>مشتریان</h1>
        <p>مدیریت فهرست مشتریان مجموعه</p>
    </div>
</div>

@if(session('success'))
    <div class="panel" style="margin-bottom:16px;color:#6ee7d0">{{ session('success') }}</div>
@endif

<div class="panel">
    <div class="actions">
        <a class="btn primary" href="{{ route('sales.customers.create') }}">+ مشتری جدید</a>
        <a class="btn" href="{{ route('sales.orders.index') }}">سفارش‌های فروش ←</a>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>کد</th>
                    <th>نام</th>
                    <th>تلفن</th>
                    <th>ایمیل</th>
                    <th>وضعیت</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customers as $c)
                    <tr>
                        <td>{{ $c->code }}</td>
                        <td>{{ $c->name }}</td>
                        <td>{{ $c->phone ?: '—' }}</td>
                        <td>{{ $c->email ?: '—' }}</td>
                        <td>
                            <span class="status {{ $c->is_active ? 'active' : 'inactive' }}">
                                {{ $c->is_active ? 'فعال' : 'غیرفعال' }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align:center;color:#71879d;padding:28px">هنوز مشتری‌ای ثبت نشده است.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pagination">{{ $customers->links() }}</div>
</div>
@endsection
