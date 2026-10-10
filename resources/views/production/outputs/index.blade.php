@extends('layouts.dashboard-shell')

@section('title', 'خروجی تولید')

@section('content')
<div class="page-heading"><div><h1>خروجی تولید و کالای ساخته‌شده</h1><p>بررسی خروجی‌های تولید، تأیید و ورود کالای ساخته‌شده به انبار</p></div></div>
@if(session('success'))<div style="padding:12px 15px;margin-bottom:16px;border:1px solid #246451;border-radius:12px;background:#0c282b;color:#8ce8ce;font-size:12px">{{ session('success') }}</div>@endif
@if($errors->any())<div style="padding:12px 15px;margin-bottom:16px;border:1px solid #714448;border-radius:12px;background:#302126;color:#ffb8a8;font-size:12px">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
<div class="panel"><div class="table-wrap"><table><thead><tr><th>تولید</th><th>کالا</th><th>مقدار</th><th>وضعیت</th><th>عملیات</th></tr></thead><tbody>
@forelse($rows as $r)
<tr><td>{{ $r->production_number }}</td><td>{{ $r->goods_name }}</td><td>{{ $r->quantity }}</td><td><span class="status {{ in_array($r->status, ['confirmed','received'], true) ? 'active' : 'inactive' }}">{{ $r->status }}</span></td><td style="white-space:normal">
@if($r->status==='pending')
<form method="POST" action="{{ route('production.outputs.confirm',$r->id) }}" style="display:inline-block;margin:2px">@csrf<button class="btn" type="submit">تأیید</button></form>
<form method="POST" action="{{ route('production.outputs.reject',$r->id) }}" style="display:inline-flex;gap:5px;flex-wrap:wrap;margin:2px">@csrf<input name="rejection_reason" required placeholder="دلیل رد" style="background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:8px;padding:8px;font:inherit"><button class="btn" type="submit" style="background:#3b2528;border-color:#63343b;color:#ffb8a8">رد</button></form>
@elseif($r->status==='confirmed')
<form method="POST" action="{{ route('production.outputs.receive',$r->id) }}" style="display:inline-block;margin:2px">@csrf<button class="btn primary" type="submit">ورود به انبار</button></form>
@else—@endif
</td></tr>
@empty<tr><td colspan="5" style="text-align:center;padding:28px;color:#8198ac">خروجی‌ای برای نمایش ثبت نشده است.</td></tr>@endforelse
</tbody></table></div>@if(method_exists($rows, 'links'))<div class="pagination">{{ $rows->links() }}</div>@endif</div>
@endsection