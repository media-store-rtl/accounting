@extends('layouts.dashboard-shell')

@section('title', 'اجرای عملیات تولید')

@section('content')
<div class="page-heading"><div><h1>اجرای عملیات تولید</h1><p>پیگیری مراحل اجرا، خروجی عملیات و بررسی سرپرست</p></div><a class="btn primary" href="{{ route('production.execution.create') }}">ثبت عملیات</a></div>
<div class="panel" style="margin-bottom:16px;line-height:2">
<h2 style="font-size:14px;margin:0 0 8px">راهنمای اجرای عملیات تولید</h2>
<p style="margin:0 0 8px">این صفحه برای پیگیری عملیات ایجادشده از دستورهای تولیدِ شروع‌شده است؛ تعریف مسیر، مرحله و عملیات پایه در بخش «تولید» انجام می‌شود و ردیف‌های اجرا هنگام شروع دستور تولید ساخته می‌شوند.</p>
<ul style="margin:0;padding-right:22px">
<li>اگر فهرست خالی است، ابتدا دستور تولید را شروع کنید و مطمئن شوید مسیر آن مرحله فعال و در هر مرحله عملیات فعال دارد.</li>
<li>برای ثبت مقدار ورودی، خروجی، ضایعات و زمان پایان، از «ثبت عملیات» استفاده کنید و فرم را ارسال کنید تا برای بررسی سرپرست قرار گیرد.</li>
<li>ردیف «در انتظار بررسی» با اقدام سرپرست تأیید یا رد می‌شود؛ ردیف‌های تکمیل‌شده برای پیگیری سابقه باقی می‌مانند.</li>
</ul>
</div>
@if(session('success'))<div style="padding:12px 15px;margin-bottom:16px;border:1px solid #246451;border-radius:12px;background:#0c282b;color:#8ce8ce;font-size:12px">{{ session('success') }}</div>@endif
@if($errors->any())<div style="padding:12px 15px;margin-bottom:16px;border:1px solid #714448;border-radius:12px;background:#302126;color:#ffb8a8;font-size:12px">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
<div class="panel" style="margin-bottom:16px">
<h2 style="font-size:15px">تحویل بین قسمت‌های تولید</h2>
<p style="font-size:12px;line-height:1.9">پس از تأیید آخرین عملیات هر مرحله، مقدار خروجی برای قسمت بعد ارسال می‌شود. قسمت بعد باید مقدار دریافتی را ثبت کند؛ تا آن زمان اجرای مرحله بعد مسدود است.</p>
<div class="table-wrap"><table><thead><tr><th>دستور</th><th>از قسمت</th><th>به قسمت / مرحله</th><th>ارسالی</th><th>دریافتی</th><th>وضعیت / اقدام</th></tr></thead><tbody>
@forelse($handovers as $h)
<tr><td>{{ $h->production_number }}</td><td>{{ $h->from_section_name ?: 'قسمت تعریف‌نشده' }}</td><td>{{ $h->to_section_name ?: 'قسمت تعریف‌نشده' }} — {{ $h->to_stage_name }}</td><td>{{ $h->sent_quantity }}</td><td>{{ $h->received_quantity ?? '—' }}</td><td>
@if($h->status === 'pending')
<form method="POST" action="{{ route('production.execution.handover.receive',$h->id) }}">@csrf
<input name="received_quantity" type="number" min="0.0001" max="{{ $h->sent_quantity }}" step="0.0001" value="{{ $h->sent_quantity }}" required>
<button class="btn primary" type="submit">تأیید دریافت</button></form>
@else دریافت تأیید شد @endif
</td></tr>
@empty<tr><td colspan="6">هنوز تحویلی بین قسمت‌ها ثبت نشده است.</td></tr>
@endforelse
</tbody></table></div></div>
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