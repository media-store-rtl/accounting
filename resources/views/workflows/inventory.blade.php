@extends('layouts.dashboard-shell')

@section('title', 'موجودی انبار')

@section('content')
<div class="page-heading"><div><h1>موجودی انبار</h1><p>بررسی موجودی کالاها، نقاط سفارش و اصلاح مقادیر انبار</p></div></div>
@if(session('success'))<div style="padding:12px 15px;margin-bottom:16px;border:1px solid #246451;border-radius:12px;background:#0c282b;color:#8ce8ce;font-size:12px">{{ session('success') }}</div>@endif
@if($errors->any())<div style="padding:12px 15px;margin-bottom:16px;border:1px solid #714448;border-radius:12px;background:#302126;color:#ffb8a8;font-size:12px">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
<div class="panel"><div class="table-wrap"><table><thead><tr><th>کد کالا</th><th>کالا</th><th>انبار</th><th>موجودی</th><th>نقطه سفارش</th><th>عملیات</th></tr></thead><tbody>
@forelse($rows as $row)
<tr><td>{{ $row->goods_code }}</td><td>{{ $row->goods_name }}</td><td>{{ $row->location_name }}</td>
<td><span class="status {{ (float)($row->reorder_point ?? 0)>0 && (float)$row->quantity <= (float)$row->reorder_point ? 'inactive' : 'active' }}">{{ $row->quantity }}</span></td><td>{{ $row->reorder_point ?? 0 }}</td><td style="min-width:280px;white-space:normal">
@if(auth()->user()->hasCompanyPermission((int)session('company_id'),'inventory.reorder_point.manage'))
<form method="POST" action="{{ route('inventory.reorder-point',$row->id) }}" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;margin-bottom:7px">@csrf<input name="reorder_point" type="number" min="0" step="0.0001" value="{{ $row->reorder_point ?? 0 }}" aria-label="نقطه سفارش" style="width:110px;background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:8px;padding:8px;font:inherit"><button class="btn" type="submit">ذخیره نقطه سفارش</button></form>
@endif
@if(auth()->user()->hasCompanyPermission((int)session('company_id'),'inventory.adjust'))
<form method="POST" action="{{ route('inventory.adjust',$row->id) }}" style="display:flex;gap:6px;align-items:center;flex-wrap:wrap">@csrf<input name="quantity" type="number" min="0" step="0.0001" value="{{ $row->quantity }}" aria-label="موجودی جدید" style="width:110px;background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:8px;padding:8px;font:inherit"><input name="reason" required placeholder="علت اصلاح" aria-label="علت اصلاح" style="width:130px;background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:8px;padding:8px;font:inherit"><button class="btn" type="submit">اصلاح موجودی</button></form>
@endif
</td></tr>
@empty<tr><td colspan="6" style="text-align:center;padding:28px;color:#8198ac">موجودی برای نمایش ثبت نشده است.</td></tr>@endforelse
</tbody></table></div>@if(method_exists($rows, 'links'))<div class="pagination">{{ $rows->links() }}</div>@endif</div>
@endsection