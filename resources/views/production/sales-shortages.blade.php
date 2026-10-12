@extends('layouts.dashboard-shell')

@section('title', 'کسری سفارش‌های فروش')

@push('styles')
<style>
.shortage-items{min-width:240px;white-space:normal}
.shortage-items summary{cursor:pointer;color:#6ee7d0;font-weight:700;list-style-position:inside}
.shortage-items[open] summary{margin-bottom:10px}
.shortage-items table{min-width:560px;background:#081522}
.shortage-items td,.shortage-items th{white-space:normal;padding:9px}
.jalali-picker-input{width:125px;min-width:125px;padding:9px 10px;border:1px solid #29465e;border-radius:9px;background:#081522;color:#e9f3fb;font:inherit;font-size:11px}
.jalali-picker-trigger{padding:8px 10px;cursor:pointer}
.jalali-calendar{position:fixed;z-index:9999;width:280px;padding:12px;background:#0a1b2b;border:1px solid #29465e;border-radius:14px;box-shadow:0 16px 45px #0009;color:#e9f3fb;direction:rtl}
.jalali-calendar[hidden]{display:none}
.jalali-calendar-head{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:10px}
.jalali-calendar-head button{border:1px solid #29465e;border-radius:8px;background:#12314a;color:#e9f3fb;padding:5px 10px;cursor:pointer}
.jalali-calendar-title{font-weight:700;font-size:12px}
.jalali-calendar-grid{display:grid;grid-template-columns:repeat(7,minmax(0,1fr));gap:3px;text-align:center}
.jalali-calendar-grid span{padding:5px 0;color:#8198ac;font-size:10px}
.jalali-calendar-grid button{border:0;border-radius:7px;background:transparent;color:#e9f3fb;padding:7px 0;font:inherit;font-size:11px;cursor:pointer}
.jalali-calendar-grid button:hover,.jalali-calendar-grid button.selected{background:#6ee7d0;color:#04131b;font-weight:800}
.jalali-calendar-grid button:disabled{opacity:.2;cursor:default}
@media(max-width:700px){.jalali-calendar{width:270px}}
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
                <input id="production-due-display-{{ $row->id }}" class="jalali-picker-input" type="text" readonly placeholder="انتخاب تاریخ" aria-label="موعد تولید شمسی سفارش {{ $row->number }}" data-iso="{{ $row->production_due_at ? date('Y-m-d', strtotime($row->production_due_at)) : '' }}">
                <input id="production-due-{{ $row->id }}" type="hidden" value="{{ $row->production_due_at ? date('Y-m-d', strtotime($row->production_due_at)) : '' }}">
                <button class="btn jalali-picker-trigger" type="button" onclick="openJalaliPicker({{ $row->id }}, this)" aria-label="باز کردن تقویم شمسی">تقویم</button>
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

<div id="jalali-calendar" class="jalali-calendar" hidden role="dialog" aria-label="انتخاب موعد تولید با تقویم شمسی">
  <div class="jalali-calendar-head">
    <button type="button" onclick="shiftJalaliMonth(-1)" aria-label="ماه قبل">‹</button>
    <strong id="jalali-calendar-title" class="jalali-calendar-title"></strong>
    <button type="button" onclick="shiftJalaliMonth(1)" aria-label="ماه بعد">›</button>
  </div>
  <div id="jalali-calendar-grid" class="jalali-calendar-grid"></div>
</div>

<script>
const productionDueToken = '{{ csrf_token() }}';
const jalaliPartsFormatter = new Intl.DateTimeFormat('en-u-ca-persian-nu-latn', {year:'numeric',month:'numeric',day:'numeric'});
const jalaliDisplayFormatter = new Intl.DateTimeFormat('fa-IR-u-ca-persian', {year:'numeric',month:'2-digit',day:'2-digit'});
const jalaliMonthFormatter = new Intl.DateTimeFormat('fa-IR-u-ca-persian', {month:'long',year:'numeric'});
const jalaliNumberFormatter = new Intl.NumberFormat('fa-IR');
const jalaliWeekdays = ['شنبه','یکشنبه','دوشنبه','سه‌شنبه','چهارشنبه','پنجشنبه','جمعه'];
const jalaliCalendar = document.getElementById('jalali-calendar');
let activeJalaliOrderId = null;
let jalaliViewYear = null;
let jalaliViewMonth = null;

function getJalaliParts(date) {
  const parts = {};
  jalaliPartsFormatter.formatToParts(date).forEach(part => {
    if (['year','month','day'].includes(part.type)) parts[part.type] = Number(part.value);
  });
  return parts;
}
function formatJalali(date) {
  return jalaliDisplayFormatter.format(date);
}
function isoToLocalDate(iso) {
  if (!iso || !/^\d{4}-\d{2}-\d{2}$/.test(iso)) return null;
  const [year, month, day] = iso.split('-').map(Number);
  return new Date(year, month - 1, day, 12);
}
function jalaliMonthFirstDate(year, month) {
  const estimate = new Date(year + 621, month <= 6 ? 2 : 8, 1, 12);
  for (let offset = -35; offset <= 35; offset++) {
    const candidate = new Date(estimate);
    candidate.setDate(estimate.getDate() + offset);
    const p = getJalaliParts(candidate);
    if (p.year === year && p.month === month && p.day === 1) return candidate;
  }
  throw new Error('تبدیل تاریخ شمسی انجام نشد.');
}
function shiftJalaliMonth(delta) {
  jalaliViewMonth += delta;
  if (jalaliViewMonth < 1) { jalaliViewMonth = 12; jalaliViewYear--; }
  if (jalaliViewMonth > 12) { jalaliViewMonth = 1; jalaliViewYear++; }
  renderJalaliCalendar();
}
function openJalaliPicker(orderId, trigger) {
  activeJalaliOrderId = orderId;
  const selectedIso = document.getElementById('production-due-' + orderId).value;
  const selectedDate = isoToLocalDate(selectedIso) || new Date();
  const parts = getJalaliParts(selectedDate);
  jalaliViewYear = parts.year;
  jalaliViewMonth = parts.month;
  renderJalaliCalendar();
  const rect = trigger.getBoundingClientRect();
  jalaliCalendar.style.top = Math.min(rect.bottom + 6, window.innerHeight - 330) + 'px';
  jalaliCalendar.style.right = Math.max(8, window.innerWidth - rect.right) + 'px';
  jalaliCalendar.hidden = false;
}
function renderJalaliCalendar() {
  const first = jalaliMonthFirstDate(jalaliViewYear, jalaliViewMonth);
  document.getElementById('jalali-calendar-title').textContent = jalaliMonthFormatter.format(first);
  const grid = document.getElementById('jalali-calendar-grid');
  grid.replaceChildren();
  jalaliWeekdays.forEach(day => {
    const heading = document.createElement('span');
    heading.textContent = day;
    grid.appendChild(heading);
  });
  const startOffset = (first.getDay() + 1) % 7;
  for (let i = 0; i < startOffset; i++) grid.appendChild(document.createElement('span'));
  const selectedIso = activeJalaliOrderId ? document.getElementById('production-due-' + activeJalaliOrderId).value : '';
  const selected = isoToLocalDate(selectedIso);
  const selectedTime = selected ? selected.getTime() : null;
  for (let day = 1; day <= 31; day++) {
    const date = new Date(first);
    date.setDate(first.getDate() + day - 1);
    const p = getJalaliParts(date);
    if (p.year !== jalaliViewYear || p.month !== jalaliViewMonth) break;
    const button = document.createElement('button');
    button.type = 'button';
    button.textContent = jalaliNumberFormatter.format(day);
    button.className = selectedTime === date.getTime() ? 'selected' : '';
    button.addEventListener('click', () => {
      const iso = date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0') + '-' + String(date.getDate()).padStart(2, '0');
      document.getElementById('production-due-' + activeJalaliOrderId).value = iso;
      document.getElementById('production-due-display-' + activeJalaliOrderId).value = formatJalali(date);
      jalaliCalendar.hidden = true;
    });
    grid.appendChild(button);
  }
}
document.querySelectorAll('[id^="production-due-display-"]').forEach(input => {
  const date = isoToLocalDate(input.dataset.iso || '');
  if (date) input.value = formatJalali(date);
});
document.querySelectorAll('.jalali-date').forEach(el => {
  const date = isoToLocalDate((el.getAttribute('datetime') || '').slice(0, 10));
  if (date) el.textContent = formatJalali(date);
});
document.addEventListener('click', event => {
  if (!jalaliCalendar.hidden && !jalaliCalendar.contains(event.target) && !event.target.closest('.jalali-picker-trigger')) jalaliCalendar.hidden = true;
});
document.addEventListener('keydown', event => {
  if (event.key === 'Escape') jalaliCalendar.hidden = true;
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
