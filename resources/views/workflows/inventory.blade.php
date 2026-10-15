@extends('layouts.dashboard-shell')

@section('title', 'موجودی انبار')

@section('content')
<div class="page-heading"><div><h1>موجودی انبار</h1><p>بررسی موجودی کالاها و نقاط سفارش</p></div></div>
@if(session('success'))<div style="padding:12px 15px;margin-bottom:16px;border:1px solid #246451;border-radius:12px;background:#0c282b;color:#8ce8ce;font-size:12px">{{ session('success') }}</div>@endif
@if($errors->any())<div style="padding:12px 15px;margin-bottom:16px;border:1px solid #714448;border-radius:12px;background:#302126;color:#ffb8a8;font-size:12px">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
<div class="panel">
<form method="GET" action="{{ route('inventory.index') }}" style="display:flex;gap:10px;align-items:end;flex-wrap:wrap;padding:16px;border-bottom:1px solid #29465e">
 <div style="display:flex;flex-direction:column;gap:6px;min-width:220px">
  <label for="inventory-location-filter">فیلتر انبار / محل</label>
  <select id="inventory-location-filter" name="location_id" style="background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:8px;padding:9px;font:inherit">
   <option value="">همه انبارها (نمایش هر کالا یک‌بار)</option>
   @foreach($locations as $location)
    <option value="{{ $location->id }}" @selected((string)$locationId === (string)$location->id)>{{ $location->name }}</option>
   @endforeach
  </select>
 </div>
 <button class="btn" type="submit">اعمال فیلتر</button>
 @if($locationId !== null)<a class="btn" href="{{ route('inventory.index') }}">حذف فیلتر</a>@endif
</form>
@if($locationId === null)<p style="padding:0 16px;color:#9db1c3;font-size:12px">موجودی در حالت «همه انبارها» جمع موجودی هر کالا در تمام محل‌های فعال است. برای دیدن یا ویرایش نقطه سفارش، یک انبار / محل را انتخاب کنید.</p>@endif
<div class="table-wrap"><table><thead><tr><th>کد کالا</th><th>کالا</th><th>انبار</th><th>موجودی</th><th>نقطه سفارش</th><th>عملیات</th></tr></thead><tbody>
@forelse($rows as $row)
<tr><td>{{ $row->goods_code }}</td><td>{{ $row->goods_name }}</td><td>{{ $row->location_name }}</td>
<td><span class="status {{ $row->reorder_point !== null && (float)$row->reorder_point>0 && (float)$row->quantity <= (float)$row->reorder_point ? 'inactive' : 'active' }}">{{ $row->quantity }}</span></td><td>{{ $row->reorder_point !== null ? $row->reorder_point : '—' }}</td><td style="min-width:280px;white-space:normal">
@if($locationId !== null && auth()->user()->hasCompanyPermission((int)session('company_id'),'inventory.reorder_point.manage'))
<form method="POST" action="{{ route('inventory.reorder-point',[$row->location_id,$row->goods_id]) }}" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;margin-bottom:7px">@csrf<input name="reorder_point" type="number" min="0" step="0.0001" value="{{ $row->reorder_point ?? 0 }}" aria-label="نقطه سفارش" style="width:110px;background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:8px;padding:8px;font:inherit"><button class="btn" type="submit">ذخیره نقطه سفارش</button></form>
@elseif($locationId === null && auth()->user()->hasCompanyPermission((int)session('company_id'),'inventory.reorder_point.manage'))
<span style="color:#8198ac;font-size:12px">برای تنظیم نقطه سفارش، انبار را انتخاب کنید.</span>
@endif
</td></tr>
@empty<tr><td colspan="6" style="text-align:center;padding:28px;color:#8198ac">موجودی برای نمایش ثبت نشده است.</td></tr>@endforelse
</tbody></table></div>@if(method_exists($rows, 'links'))<div class="pagination">{{ $rows->appends(request()->only('location_id'))->links() }}</div>@endif</div>
@endsection