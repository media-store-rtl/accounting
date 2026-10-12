@extends('layouts.dashboard-shell')

@section('title', 'سفارش‌های فروش | حسابداری صنعتی')

@section('content')
@php
$statusLabels = [
    'production_required' => 'نیازمند تأمین / تولید',
    'awaiting_production' => 'در انتظار تولید',
    'ready_for_delivery' => 'آماده تحویل',
    'delivery_requested' => 'درخواست تحویل ثبت‌شده',
    'issued' => 'خارج‌شده از انبار',
    'completed' => 'تحویل تکمیل‌شده',
    'cancelled' => 'لغوشده',
];
@endphp
<div class="page-heading">
  <div>
    <h1>سفارش‌های فروش</h1>
    <p>مدیریت سفارش‌ها، موعدهای تحویل و وضعیت تولید</p>
  </div>
</div>
<div class="panel">
  @if(session('success'))<div class="alert success" role="status">{{ session('success') }}</div>@endif
  <div class="actions">
    <a class="btn primary" href="{{ route('sales.orders.create') }}">+ سفارش جدید</a>
    <a class="btn" href="{{ route('sales.deliveries.index') }}">تحویل‌ها</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr><th>شماره</th><th>مشتری</th><th>موعد درخواستی</th><th>موعد تولید</th><th>وضعیت</th><th>عملیات</th></tr>
      </thead>
      <tbody>
        @forelse($orders as $o)
          <tr>
            <td>{{ $o->number }}</td>
            <td>{{ $o->customer_name }}</td>
            <td><time class="jalali-date" datetime="{{ $o->requested_delivery_at }}">{{ $o->requested_delivery_at }}</time></td>
            <td>@if($o->production_due_at)<time class="jalali-date" datetime="{{ $o->production_due_at }}">{{ $o->production_due_at }}</time>@else — @endif</td>
            <td><span class="status">{{ $statusLabels[$o->status] ?? 'در حال بررسی' }}</span></td>
            <td style="white-space:nowrap"><a class="btn" href="{{ route('sales.orders.show', $o->id) }}">مشاهده جزئیات</a>
              @if(in_array($o->status, ['checking_stock','production_required','awaiting_production','ready_for_delivery'], true))
                <form method="POST" action="{{ route('sales.orders.cancel', $o->id) }}" style="display:inline" onsubmit="return confirm('آیا از لغو این سفارش مطمئن هستید؟');">@csrf
                  <button class="btn" type="submit" style="border-color:#8d3e3e;color:#ffb8b8">لغو سفارش</button>
                </form>
              @endif
            </td>
          </tr>
        @empty
          <tr><td colspan="7" style="text-align:center;color:#71879d;padding:28px">هنوز سفارشی ثبت نشده است.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div class="pagination">{{ $orders->links() }}</div>
</div>
<script>
document.querySelectorAll('.jalali-date').forEach((el) => {
  const raw = (el.getAttribute('datetime') || '').slice(0, 10);
  const match = raw.match(/^(\d{4})-(\d{2})-(\d{2})$/);
  if (!match) return;
  const date = new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]));
  el.textContent = new Intl.DateTimeFormat('fa-IR-u-ca-persian', {year:'numeric',month:'2-digit',day:'2-digit'}).format(date);
});
</script>
@endsection
