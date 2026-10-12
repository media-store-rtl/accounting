@extends('layouts.dashboard-shell')

@section('title', 'موعدهای تولید')

@section('content')
<div class="page-heading">
  <div><h1>موعدهای تولید</h1><p>مدیریت موعد قابل تحویل سفارش‌هایی که به تولید نیاز دارند</p></div>
</div>

@if(session('success'))<div style="padding:12px 15px;margin-bottom:16px;border:1px solid #246451;border-radius:12px;background:#0c282b;color:#8ce8ce;font-size:12px">{{ session('success') }}</div>@endif
@if($errors->any())<div style="padding:12px 15px;margin-bottom:16px;border:1px solid #714448;border-radius:12px;background:#302126;color:#ffb8a8;font-size:12px">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif

<div class="panel" style="margin-bottom:16px;line-height:2">
  <h2 style="font-size:14px;margin:0 0 8px">راهنمای موعد تولید</h2>
  <p style="margin:0">این صفحه مخصوص بخش تولید است. سفارش‌هایی که کمبود موجودی دارند در این فهرست نمایش داده می‌شوند. موعد قابل تحویل تولید را برای هر سفارش ثبت کنید؛ اطلاعات سفارش و بخش فروش تغییر نمی‌کند.</p>
</div>

<div class="panel">
  <div class="table-wrap">
    <table>
      <thead><tr><th>شماره سفارش</th><th>مشتری</th><th>وضعیت تولید</th><th>موعد درخواستی مشتری</th><th>موعد قابل تحویل تولید</th><th>اقدام</th></tr></thead>
      <tbody>
      @forelse($rows as $row)
        <tr>
          <td>{{ $row->number }}</td>
          <td>{{ $row->customer_name }}</td>
          <td><span class="status {{ $row->status === 'awaiting_production' ? 'active' : 'inactive' }}">{{ $row->status === 'awaiting_production' ? 'در انتظار تولید' : 'نیازمند تولید' }}</span></td>
          <td>{{ $row->requested_delivery_at ? \Illuminate\Support\Carbon::parse($row->requested_delivery_at)->format('Y-m-d') : '—' }}</td>
          <td>
            <form onsubmit="submitProductionDue(event, {{ $row->id }})" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin:0">
              <input id="production-due-{{ $row->id }}" type="date" lang="fa-IR" value="{{ $row->production_due_at ? \Illuminate\Support\Carbon::parse($row->production_due_at)->format('Y-m-d') : '' }}" required aria-label="موعد قابل تحویل تولید سفارش {{ $row->number }}" style="min-width:145px">
              <button class="btn primary" type="submit">{{ $row->production_due_at ? 'به‌روزرسانی موعد' : 'ثبت موعد تولید' }}</button>
            </form>
          </td>
          <td><a class="btn" href="{{ route('sales.orders.show', $row->id) }}">مشاهده سفارش</a></td>
        </tr>
      @empty
        <tr><td colspan="6" style="text-align:center;padding:28px;color:#8198ac">سفارشی در انتظار تعیین موعد تولید نیست.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
  @if(method_exists($rows, 'links'))<div class="pagination">{{ $rows->links() }}</div>@endif
</div>

<script>
const productionDueToken = '{{ csrf_token() }}';
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
