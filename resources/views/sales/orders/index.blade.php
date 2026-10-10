@extends('layouts.dashboard-shell')

@section('title', 'سفارش‌های فروش | حسابداری صنعتی')

@section('content')
<div class="page-heading">
  <div>
    <h1>سفارش‌های فروش</h1>
    <p>مدیریت سفارش‌ها، موعدهای تحویل و وضعیت تولید</p>
  </div>
</div>
<div class="panel">
  <div class="actions">
    <a class="btn primary" href="{{ route('sales.orders.create') }}">+ سفارش جدید</a>
    <a class="btn" href="{{ route('sales.customers.index') }}">مشتریان</a>
    <a class="btn" href="{{ route('sales.deliveries.index') }}">تحویل‌ها</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>شماره</th><th>مشتری</th><th>موعد درخواستی</th><th>موعد تولید</th><th>وضعیت</th><th>جزئیات</th></tr>
      </thead>
      <tbody>
        @forelse($orders as $o)
          <tr>
            <td>{{ $o->number }}</td>
            <td>{{ $o->customer_name }}</td>
            <td>{{ $o->requested_delivery_at }}</td>
            <td>{{ $o->production_due_at ?? '—' }}</td>
            <td><span class="status">{{ $o->status }}</span></td>
            <td><a class="btn" href="{{ route('sales.orders.show', $o->id) }}">مشاهده جزئیات</a></td>
          </tr>
        @empty
          <tr><td colspan="6" style="text-align:center;color:#71879d;padding:28px">هنوز سفارشی ثبت نشده است.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div class="pagination">{{ $orders->links() }}</div>
</div>
@endsection
