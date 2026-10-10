@extends('layouts.dashboard-shell')

@section('title', 'جزئیات سفارش ' . $row->number . ' | حسابداری صنعتی')

@push('styles')
<style>
.order-summary{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin-bottom:18px}
.order-fact{padding:14px 16px;background:#0d2235;border:1px solid #1b354b;border-radius:12px;min-width:0}
.order-fact span{display:block;color:#71879d;font-size:10px;margin-bottom:7px}
.order-fact strong{display:block;font-size:13px;overflow-wrap:anywhere}
.order-section-title{font-size:14px;margin:0 0 15px}
.order-forms{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
.order-form{padding:16px;background:#0d2235;border:1px solid #1b354b;border-radius:12px}
.order-form h3{font-size:12px;margin:0 0 12px}
.order-form label{display:block;color:#8ba1b5;font-size:10px;margin:10px 0 6px}
.order-form input{display:block;width:100%;min-width:0;padding:10px;border:1px solid #29465e;border-radius:9px;background:#081522;color:#e9f3fb;font:inherit;font-size:11px}
.order-form .btn{margin-top:12px;cursor:pointer}
.order-status{display:inline-block;padding:5px 10px;border-radius:20px;background:#12372f;color:#6ee7d0;font-size:10px}
.order-table td{white-space:normal}
@media(max-width:650px){.order-summary,.order-forms{grid-template-columns:1fr}.order-fact{padding:12px}.order-table{min-width:620px}}
</style>
@endpush

@section('content')
<div class="page-heading">
  <div>
    <h1>جزئیات سفارش {{ $row->number }}</h1>
    <p>اطلاعات مشتری، وضعیت تأمین کالاها و برنامه‌ریزی تحویل سفارش</p>
  </div>
  <a class="btn" href="{{ route('sales.orders.index') }}">بازگشت به سفارش‌ها</a>
</div>

<div class="panel" style="margin-bottom:16px">
  <div class="order-summary">
    <div class="order-fact">
      <span>شماره سفارش</span>
      <strong>{{ $row->number }}</strong>
    </div>
    <div class="order-fact">
      <span>مشتری</span>
      <strong>{{ $row->customer_name }}</strong>
    </div>
    <div class="order-fact">
      <span>وضعیت سفارش</span>
      <strong><span class="order-status" id="status">{{ $row->status }}</span></strong>
    </div>
    <div class="order-fact">
      <span>موعد درخواستی مشتری</span>
      <strong>{{ $row->requested_delivery_at ?? 'ثبت نشده' }}</strong>
    </div>
    <div class="order-fact">
      <span>موعد تولید</span>
      <strong>{{ $row->production_due_at ?? 'ثبت نشده' }}</strong>
    </div>
  </div>

  <h2 class="order-section-title">اقلام سفارش</h2>
  <div class="table-wrap">
    <table class="order-table">
      <thead>
        <tr>
          <th>کالا</th>
          <th>مقدار سفارش</th>
          <th>موجودی قابل استفاده</th>
          <th>مقدار کسری</th>
          <th>وضعیت تأمین</th>
        </tr>
      </thead>
      <tbody>
        @forelse($items as $i)
          <tr>
            <td>{{ $i->goods_name }}</td>
            <td>{{ $i->quantity }}</td>
            <td>{{ $i->available_quantity }}</td>
            <td>{{ $i->shortage_quantity }}</td>
            <td><span class="status">{{ $i->fulfillment_status }}</span></td>
          </tr>
        @empty
          <tr><td colspan="5" style="text-align:center;color:#71879d;padding:24px">اقلامی برای این سفارش ثبت نشده است.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<div class="panel">
  <div class="actions">
    <button class="btn primary" type="button" onclick="refreshOrder()">بررسی مجدد موجودی</button>
  </div>

  <div class="order-forms">
    @if(in_array($row->status, ['production_required', 'awaiting_production']))
      <form class="order-form" onsubmit="setDue(event)">
        <h3>ثبت موعد تولید</h3>
        <label for="due">موعد قابل تحویل تولید</label>
        <input id="due" type="date" required>
        <button class="btn primary" type="submit">ثبت موعد تولید</button>
      </form>
    @endif

    @if($row->status === 'ready_for_delivery')
      <form class="order-form" onsubmit="requestDelivery(event)">
        <h3>درخواست تحویل سفارش</h3>
        <label for="scheduled">زمان برنامه‌ریزی‌شده تحویل</label>
        <input id="scheduled" type="datetime-local" required>
        <label for="recipient">شخص تحویل‌گیرنده</label>
        <input id="recipient" placeholder="نام شخص تحویل‌گیرنده" required>
        <label for="vehicle">وسیله حمل</label>
        <input id="vehicle" placeholder="وسیله حمل (اختیاری)">
        <button class="btn primary" type="submit">ثبت درخواست تحویل</button>
      </form>
    @endif
  </div>
</div>

<script>
const token = '{{ csrf_token() }}';

async function call(url, data = {}) {
  const response = await fetch(url, {
    method: 'POST',
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': token
    },
    body: JSON.stringify(data)
  });
  const result = await response.json();
  if (!response.ok) throw Error(result.message || 'خطا در انجام درخواست');
  return result;
}

async function refreshOrder() {
  try {
    const result = await call('{{ route('sales.orders.refresh', $row->id) }}');
    document.getElementById('status').textContent = result.data.status;
    location.reload();
  } catch (error) {
    alert(error.message);
  }
}

async function setDue(event) {
  event.preventDefault();
  try {
    await call('{{ route('sales.orders.production-due', $row->id) }}', {
      production_due_at: document.getElementById('due').value
    });
    location.reload();
  } catch (error) {
    alert(error.message);
  }
}

async function requestDelivery(event) {
  event.preventDefault();
  try {
    await call('{{ route('sales.orders.delivery-request', $row->id) }}', {
      scheduled_at: document.getElementById('scheduled').value,
      recipient_name: document.getElementById('recipient').value,
      vehicle: document.getElementById('vehicle').value
    });
    location.reload();
  } catch (error) {
    alert(error.message);
  }
}
</script>
@endsection
