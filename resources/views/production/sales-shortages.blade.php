@extends('layouts.dashboard-shell')

@section('title', 'کسری سفارش‌های فروش')

@section('content')
<div class="page-heading">
  <div>
    <h1>کسری سفارش‌های فروش برای تولید</h1>
    <p>فقط سفارش‌های فروش دارای کسری محصول نهایی نمایش داده می‌شوند. وضعیت موجودی هنگام باز شدن صفحه دوباره بررسی می‌شود.</p>
  </div>
</div>

@if(session('success'))<div class="alert success" role="status">{{ session('success') }}</div>@endif
@if($errors->any())<div class="panel" role="alert" style="margin-bottom:16px;color:#ffb8a8">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif

<div class="panel" style="margin-bottom:16px;line-height:2">
  <p style="margin:0">این فهرست برای پیگیری کسری محصول نهایی و تعیین موعد تولید است. با تأمین موجودی، وضعیت سفارش دوباره محاسبه می‌شود و سفارشِ بدون کسری از این فهرست خارج خواهد شد. نمایش سفارش در اینجا به‌تنهایی دستور تولید یا خرید ایجاد نمی‌کند.</p>
</div>

<div class="panel">
  <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:14px">
    <div>
      <h2 style="font-size:15px;margin:0 0 5px">سفارش‌های نیازمند تولید</h2>
      <p style="font-size:11px;color:#8198ac;margin:0">جدیدترین سفارش در ابتدای فهرست قرار می‌گیرد.</p>
    </div>
    <span style="font-size:10px;color:#9eb2c4;background:#0d2235;border:1px solid #1b354b;border-radius:20px;padding:6px 10px">{{ $rows->total() }} سفارش</span>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>شماره سفارش</th>
          <th>محصول دارای کسری</th>
          <th>مقدار سفارش</th>
          <th>موجودی تخصیص‌یافته</th>
          <th>مقدار کسری</th>
          <th>موعد درخواستی</th>
          <th>موعد تولید</th>
          <th>وضعیت</th>
        </tr>
      </thead>
      <tbody>
      @forelse($rows as $row)
        @foreach($row->items as $item)
          <tr>
            <td>{{ $row->number }}</td>
            <td>
              <strong>{{ $item->goods_name }}</strong>
              @if($item->goods_code)<small style="display:block;color:#8198ac">{{ $item->goods_code }}</small>@endif
            </td>
            <td>{{ rtrim(rtrim(number_format((float)$item->quantity, 4, '.', ','), '0'), '.') ?: '0' }}</td>
            <td>{{ rtrim(rtrim(number_format((float)$item->available_quantity, 4, '.', ','), '0'), '.') ?: '0' }}</td>
            <td><strong style="color:#ffb8a8">{{ rtrim(rtrim(number_format((float)$item->shortage_quantity, 4, '.', ','), '0'), '.') ?: '0' }}</strong></td>
            <td>
              @if($row->requested_delivery_at)
                <time class="jalali-date" datetime="{{ $row->requested_delivery_at }}">{{ $row->requested_delivery_at }}</time>
              @else
                —
              @endif
            </td>
            <td>
              <form onsubmit="submitProductionDue(event, {{ $row->id }})" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin:0">
                <input id="production-due-{{ $row->id }}" type="date" lang="fa-IR" value="{{ $row->production_due_at ? IlluminateSupportCarbon::parse($row->production_due_at)->format('Y-m-d') : '' }}" required aria-label="موعد تولید سفارش {{ $row->number }}" style="min-width:145px">
                <button class="btn primary" type="submit">{{ $row->production_due_at ? 'ذخیره موعد' : 'تعیین موعد' }}</button>
              </form>
            </td>
            <td><span class="status {{ $row->status === 'awaiting_production' ? 'active' : 'inactive' }}">{{ $row->status === 'awaiting_production' ? 'در انتظار تولید' : 'نیازمند تولید' }}</span></td>
          </tr>
        @endforeach
      @empty
        <tr><td colspan="8" style="text-align:center;padding:28px;color:#8198ac">در حال حاضر سفارش فروش دارای کسری محصول برای نمایش وجود ندارد.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
  @if(method_exists($rows, 'links'))<div class="pagination">{{ $rows->links() }}</div>@endif
</div>

<script>
const productionDueToken = '{{ csrf_token() }}';

document.querySelectorAll('.jalali-date').forEach((el) => {
  const raw = (el.getAttribute('datetime') || '').slice(0, 10);
  const match = raw.match(/^(\d{4})-(\d{2})-(\d{2})$/);
  if (!match) return;
  const date = new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]));
  el.textContent = new Intl.DateTimeFormat('fa-IR-u-ca-persian', {
    year: 'numeric', month: '2-digit', day: '2-digit'
  }).format(date);
});

async function submitProductionDue(event, orderId) {
  event.preventDefault();
  const input = document.getElementById('production-due-' + orderId);
  try {
    const response = await fetch('{{ url('/production/orders') }}/' + orderId + '/due-date', {
      method: 'POST',
      headers: {'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':productionDueToken},
      body: JSON.stringify({production_due_at: input.value})
    });
    const result = await response.json();
    if (!response.ok) throw new Error(result.message || 'ثبت موعد تولید انجام نشد.');
    location.reload();
  } catch (error) {
    alert(error.message || 'خطا در ثبت موعد تولید');
  }
}
</script>
@endsection
