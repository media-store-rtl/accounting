@extends('layouts.dashboard-shell')

@section('title', 'کسری سفارش‌های فروش')

@section('content')
<div class="page-heading">
  <div>
    <h1>کسری سفارش‌های فروش برای تولید</h1>
    <p>فقط سفارش‌های فروش دارای کسری محصول نهایی نمایش داده می‌شوند؛ ثبت سفارش و مقدار کسری از اطلاعات فروش خوانده می‌شود.</p>
  </div>
</div>

<div class="panel" style="margin-bottom:16px;line-height:2">
  <p style="margin:0">این فهرست برای پیگیری کسری سفارش مشتری است، نه ثبت درخواست مواد اولیه. نمایش سفارش در اینجا به‌تنهایی دستور تولید یا خرید ایجاد نمی‌کند. برای مشاهده جزئیات سفارش از پیوند هر ردیف استفاده کنید.</p>
</div>

<div class="panel">
  <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:14px">
    <div>
      <h2 style="font-size:15px;margin:0 0 5px">سفارش‌های نیازمند تولید</h2>
      <p style="font-size:11px;color:#8198ac;margin:0">مبنای نمایش، کسری ثبت‌شده برای اقلام سفارش فروش است.</p>
    </div>
    <span style="font-size:10px;color:#9eb2c4;background:#0d2235;border:1px solid #1b354b;border-radius:20px;padding:6px 10px">{{ $rows->total() }} سفارش</span>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>شماره سفارش</th>
          <th>مشتری</th>
          <th>محصول دارای کسری</th>
          <th>مقدار سفارش</th>
          <th>موجودی تخصیص‌یافته</th>
          <th>مقدار کسری</th>
          <th>موعد درخواستی</th>
          <th>موعد تولید</th>
          <th>وضعیت</th>
          <th>جزئیات</th>
        </tr>
      </thead>
      <tbody>
      @forelse($rows as $row)
        @foreach($row->items as $item)
          <tr>
            <td>{{ $row->number }}</td>
            <td>{{ $row->customer_name }}</td>
            <td>
              <strong>{{ $item->goods_name }}</strong>
              @if($item->goods_code)<small style="display:block;color:#8198ac">{{ $item->goods_code }}</small>@endif
            </td>
            <td>{{ rtrim(rtrim(number_format((float)$item->quantity, 4, '.', ','), '0'), '.') ?: '0' }}</td>
            <td>{{ rtrim(rtrim(number_format((float)$item->available_quantity, 4, '.', ','), '0'), '.') ?: '0' }}</td>
            <td><strong style="color:#ffb8a8">{{ rtrim(rtrim(number_format((float)$item->shortage_quantity, 4, '.', ','), '0'), '.') ?: '0' }}</strong></td>
            <td>{{ $row->requested_delivery_at ? \Illuminate\Support\Carbon::parse($row->requested_delivery_at)->format('Y-m-d') : '—' }}</td>
            <td>{{ $row->production_due_at ? \Illuminate\Support\Carbon::parse($row->production_due_at)->format('Y-m-d') : 'تعیین نشده' }}</td>
            <td><span class="status {{ $row->status === 'awaiting_production' ? 'active' : 'inactive' }}">{{ $row->status === 'awaiting_production' ? 'در انتظار تولید' : 'نیازمند تولید' }}</span></td>
            <td><a class="btn" href="{{ route('sales.orders.show', $row->id) }}">مشاهده سفارش</a></td>
          </tr>
        @endforeach
      @empty
        <tr><td colspan="10" style="text-align:center;padding:28px;color:#8198ac">در حال حاضر سفارش فروش دارای کسری محصول برای نمایش وجود ندارد.</td></tr>
      @endforelse
      </tbody>
    </table>
  </div>
  @if(method_exists($rows, 'links'))<div class="pagination">{{ $rows->links() }}</div>@endif
</div>
@endsection
