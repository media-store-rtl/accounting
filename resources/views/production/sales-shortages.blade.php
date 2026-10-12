@extends('layouts.dashboard-shell')

@section('title', 'کسری سفارش‌های فروش')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/css/persian-datepicker.min.css">
<style>
.shortage-items{min-width:240px;white-space:normal}
.shortage-items summary{cursor:pointer;color:#6ee7d0;font-weight:700;list-style-position:inside}
.shortage-items[open] summary{margin-bottom:10px}
.shortage-items table{min-width:560px;background:#081522}
.shortage-items td,.shortage-items th{white-space:normal;padding:9px}
.jalali-picker-input{width:125px;min-width:125px;padding:9px 10px;border:1px solid #29465e;border-radius:9px;background:#081522;color:#e9f3fb;font:inherit;font-size:11px}
.jalali-picker-input{cursor:pointer}
/* Match the sales-order Jalali picker positioning on narrow screens. */
@media(max-width:650px){.datepicker-container{position:fixed!important;top:50%!important;left:50%!important;right:auto!important;bottom:auto!important;transform:translate(-50%,-50%)!important;z-index:10000!important;max-width:calc(100vw - 20px)!important}.datepicker-plot-area{max-width:calc(100vw - 20px)!important;box-sizing:border-box!important}}
</style>
@endpush

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
  <p style="margin:0">این فهرست برای پیگیری کسری محصول نهایی و تعیین موعد تولید است. جزئیات کالاها فقط با باز کردن «اقلام دارای کسری» نمایش داده می‌شود تا فهرست سفارش‌ها کوتاه بماند. با تأمین موجودی، سفارشِ بدون کسری از این فهرست خارج خواهد شد. نمایش سفارش در اینجا به‌تنهایی دستور تولید یا خرید ایجاد نمی‌کند.</p>
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
          <th>اقلام دارای کسری</th>
          <th>مجموع کسری</th>
          <th>موعد درخواستی</th>
          <th>موعد تولید (شمسی)</th>
          <th>وضعیت</th>
        </tr>
      </thead>
      <tbody>
      @forelse($rows as $row)
        <tr>
          <td>{{ $row->number }}</td>
          <td>
            <details class="shortage-items">
              <summary>{{ $row->items->count() }} قلم — مشاهده جزئیات</summary>
              <div class="table-wrap">
                <table>
                  <thead><tr><th>کالا</th><th>مقدار سفارش</th><th>موجودی تخصیص‌یافته</th><th>مقدار کسری</th></tr></thead>
                  <tbody>
                  @foreach($row->items as $item)
                    <tr>
                      <td><strong>{{ $item->goods_name }}</strong>@if($item->goods_code)<small style="display:block;color:#8198ac">{{ $item->goods_code }}</small>@endif</td>
                      <td>{{ rtrim(rtrim(number_format((float)$item->quantity, 4, '.', ','), '0'), '.') ?: '0' }}</td>
                      <td>{{ rtrim(rtrim(number_format((float)$item->available_quantity, 4, '.', ','), '0'), '.') ?: '0' }}</td>
                      <td><strong style="color:#ffb8a8">{{ rtrim(rtrim(number_format((float)$item->shortage_quantity, 4, '.', ','), '0'), '.') ?: '0' }}</strong></td>
                    </tr>
                  @endforeach
                  </tbody>
                </table>
              </div>
            </details>
          </td>
          <td><strong style="color:#ffb8a8">{{ rtrim(rtrim(number_format((float)$row->total_shortage, 4, '.', ','), '0'), '.') ?: '0' }}</strong></td>
          <td>
            @if($row->requested_delivery_at)
              <time class="jalali-date" datetime="{{ $row->requested_delivery_at }}">{{ $row->requested_delivery_at }}</time>
            @else
              —
            @endif
          </td>
          <td>
            <form onsubmit="submitProductionDue(event, {{ $row->id }})" style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;margin:0">
              <div style="display:flex;align-items:center;gap:5px">
                <input id="production-due-display-{{ $row->id }}" class="jalali-picker-input" type="text" inputmode="none" autocomplete="off" placeholder="انتخاب تاریخ شمسی" aria-label="موعد تولید شمسی سفارش {{ $row->number }}" value="{{ $row->production_due_at ? date('Y/m/d', strtotime($row->production_due_at)) : '' }}">
                <input id="production-due-{{ $row->id }}" type="hidden" value="{{ $row->production_due_at ? date('Y-m-d', strtotime($row->production_due_at)) : '' }}">
              </div>
              <button class="btn primary" type="submit">{{ $row->production_due_at ? 'ذخیره موعد' : 'تعیین موعد' }}</button>
            </form>
          </td>
          <td><span class="status {{ $row->status === 'awaiting_production' ? 'active' : 'inactive' }}">{{ $row->status === 'awaiting_production' ? 'در انتظار تولید' : 'نیازمند تولید' }}</span></td>
        </tr>
      @empty
        <tr><td colspan="6" style="text-align:center;padding:28px;color:#8198ac">در حال حاضر سفارش فروش دارای کسری محصول برای نمایش وجود ندارد.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
  @if(method_exists($rows, 'links'))<div class="pagination">{{ $rows->links() }}</div>@endif
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/persian-date@1.1.0/dist/persian-date.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/js/persian-datepicker.min.js"></script>
<script>
const productionDueToken = '{{ csrf_token() }}';

document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('[id^="production-due-display-"]').forEach(function (picker) {
    const orderId = picker.id.replace('production-due-display-', '');
    const isoField = document.getElementById('production-due-' + orderId);
    if (!isoField || !window.jQuery || !jQuery.fn.persianDatepicker) return;

    jQuery(picker).persianDatepicker({
      calendarType: 'persian',
      initialValue: Boolean(isoField.value),
      initialValueType: 'gregorian',
      format: 'YYYY/MM/DD',
      altField: '#production-due-' + orderId,
      altFormat: 'YYYY-MM-DD',
      altFieldFormatter: function (unixDate) {
        const date = new Date(unixDate);
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return year + '-' + month + '-' + day;
      },
      calendar: { persian: { locale: 'fa' } },
      toolbox: { calendarSwitch: { enabled: false }, todayButton: { enabled: true, text: { fa: 'امروز' } } }
    });
  });

  document.querySelectorAll('.jalali-date').forEach(function (el) {
    const raw = (el.getAttribute('datetime') || '').slice(0, 10);
    const match = raw.match(/^(\d{4})-(\d{2})-(\d{2})$/);
    if (!match) return;
    const date = new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]), 12);
    el.textContent = new Intl.DateTimeFormat('fa-IR-u-ca-persian', {
      year: 'numeric', month: '2-digit', day: '2-digit'
    }).format(date);
  });
});

async function submitProductionDue(event, orderId) {
  event.preventDefault();
  const input = document.getElementById('production-due-' + orderId);
  if (!input.value) {
    alert('لطفاً موعد تولید را از تقویم شمسی انتخاب کنید.');
    return;
  }
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