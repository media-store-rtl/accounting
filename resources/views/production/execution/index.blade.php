@extends('layouts.dashboard-shell')

@section('title', 'اجرای عملیات تولید')

@section('content')
<div class="page-heading"><div><h1>اجرای عملیات تولید</h1><p>پیگیری مراحل اجرا، خروجی عملیات و بررسی سرپرست</p></div><a class="btn primary" href="{{ route('production.execution.create') }}">ثبت عملیات</a></div>
@if(session('success'))<div style="padding:12px 15px;margin-bottom:16px;border:1px solid #246451;border-radius:12px;background:#0c282b;color:#8ce8ce;font-size:12px">{{ session('success') }}</div>@endif
@if($errors->any())<div style="padding:12px 15px;margin-bottom:16px;border:1px solid #714448;border-radius:12px;background:#302126;color:#ffb8a8;font-size:12px">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
<div class="panel"><div class="table-wrap"><table><thead><tr><th>تولید</th><th>مرحله</th><th>عملیات</th><th>ورودی</th><th>خروجی</th><th>ضایعات</th><th>وضعیت</th><th>اقدام</th></tr></thead><tbody>
@forelse($rows as $x)
<tr><td>{{ $x->production_number }}</td><td>{{ $x->stage_name }}</td><td>{{ $x->operation_name }}</td><td>{{ $x->input_quantity ?? '—' }}</td><td>{{ $x->output_quantity ?? '—' }}</td><td>{{ $x->rejected_quantity ?? 0 }}</td><td><span class="status {{ $x->status === 'completed' ? 'active' : 'inactive' }}">{{ $x->status }}</span></td><td>
@if($x->status==='pending_review')
<form method="POST" action="{{ route('production.execution.review',$x->id) }}" style="display:inline-flex;gap:5px;margin:2px">@csrf<input type="hidden" name="approve" value="1"><button class="btn" type="submit">تأیید</button></form>
<form method="POST" action="{{ route('production.execution.review',$x->id) }}" style="display:inline-flex;gap:5px;margin:2px">@csrf<input type="hidden" name="approve" value="0"><input type="hidden" name="reason" value="رد توسط سرپرست"><button class="btn" type="submit" style="background:#3b2528;border-color:#63343b;color:#ffb8a8">رد</button></form>
@else—@endif
</td></tr>
@empty<tr><td colspan="8" style="text-align:center;padding:28px;color:#8198ac">عملیاتی برای نمایش ثبت نشده است.</td></tr>@endforelse
</tbody></table></div>@if(method_exists($rows, 'links'))<div class="pagination">{{ $rows->links() }}</div>@endif</div>
@endsection