@extends('layouts.dashboard-shell')
@section('title', 'جزئیات گزارش تولید')
@section('content')
<style>
.detail-page{display:grid;gap:18px}.detail-head{display:flex;justify-content:space-between;align-items:flex-start;gap:14px;flex-wrap:wrap}.detail-head h1{margin:0;font-size:24px}.detail-head p{margin:7px 0 0;color:#8ba1b5;font-size:12px;line-height:1.9}.detail-btn{display:inline-flex;padding:10px 13px;border-radius:9px;border:1px solid #29465e;background:#12314a;color:#e9f3fb;text-decoration:none;font-size:12px}.detail-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}.detail-item{padding:13px;border:1px solid #20384c;border-radius:10px;background:#0b1e2e}.detail-item span{display:block;color:#8ba1b5;font-size:10px;margin-bottom:7px}.detail-item strong{font-size:13px;overflow-wrap:anywhere}.detail-table{width:100%;border-collapse:collapse}.detail-table th,.detail-table td{text-align:right;padding:11px;border-bottom:1px solid #20384c;font-size:12px}.detail-table th{color:#8ba1b5}.detail-status{display:inline-block;padding:6px 10px;border-radius:18px;background:#302b20;color:#f3d58c;font-size:11px}.detail-status.good{background:#12372f;color:#6ee7d0}.detail-status.bad{background:#3b2528;color:#ffb8a8}.detail-form{display:grid;gap:12px;max-width:700px}.detail-form input,.detail-form select,.detail-form textarea{width:100%;background:#071725;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:10px;font:inherit}.detail-form label{display:grid;gap:6px;color:#cce0ef;font-size:12px}.detail-note{color:#8ba1b5;font-size:12px;line-height:1.9}.detail-flash{padding:12px 14px;border-radius:10px;background:#12372f;color:#b8f5df;font-size:12px}
@media(max-width:650px){.detail-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.detail-head h1{font-size:21px}}@media(max-width:420px){.detail-grid{grid-template-columns:1fr}}
</style>
<div class="detail-page">
<div class="detail-head"><div><h1>جزئیات گزارش تولید #{{ $row->id }}</h1><p>اطلاعات ثبت‌شده، مواد مصرفی، ضایعات و وضعیت تحویل به انبار.</p></div><a class="detail-btn" href="{{ route('production.shift-reports.index') }}">← بازگشت به گزارش‌ها</a></div>
@if(session('success'))<div class="detail-flash">{{ session('success') }}</div>@endif
@if($errors->any())<div class="detail-flash" style="background:#3b2528;color:#ffb8a8">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
@php($labels=['pending'=>'در انتظار تأیید','approved'=>'تأییدشده؛ آماده تحویل','rejected'=>'ردشده','received'=>'تحویل کامل به انبار'])
<div class="panel" style="padding:16px"><div class="detail-grid">
<div class="detail-item"><span>تاریخ تولید</span><strong class="shift-date-display" data-iso-date="{{ $row->work_date }}">{{ $row->work_date }}</strong></div>
<div class="detail-item"><span>قسمت تولید</span><strong>{{ $row->section_name ?: '—' }}</strong></div><div class="detail-item"><span>کارگر</span><strong>{{ $row->worker_name ?: '—' }}</strong></div>
<div class="detail-item"><span>محصول / قطعه</span><strong>{{ $row->goods_name }} @if($row->goods_code)({{ $row->goods_code }})@endif</strong></div>
<div class="detail-item"><span>تعداد تولیدشده</span><strong>{{ $row->produced_quantity }}</strong></div><div class="detail-item"><span>ضایعات محصول</span><strong>{{ $row->scrap_quantity ?? 0 }}</strong></div>
<div class="detail-item"><span>تولید سالم قابل تحویل</span><strong>{{ max(0,(float)$row->produced_quantity-(float)($row->scrap_quantity ?? 0)) }}</strong></div><div class="detail-item"><span>ساعات کارکرد</span><strong>{{ $row->worked_hours ?? '—' }}</strong></div>
<div class="detail-item"><span>اضافه‌کاری / تعطیل‌کاری</span><strong>{{ $row->overtime_hours ?? 0 }} / {{ $row->holiday_hours ?? 0 }}</strong></div>
<div class="detail-item"><span>ثبت‌کننده</span><strong>{{ $row->reporter_name }}</strong></div><div class="detail-item"><span>بررسی‌کننده</span><strong>{{ $row->reviewer_name ?: '—' }}</strong></div>
<div class="detail-item"><span>وضعیت</span><strong><span class="detail-status {{ in_array($row->status,['approved','received'],true) ? 'good' : ($row->status==='rejected' ? 'bad' : '') }}">{{ $labels[$row->status] ?? $row->status }}</span></strong></div>
@if($row->warehouse_quantity !== null)<div class="detail-item"><span>تحویل‌شده به انبار</span><strong>{{ $row->warehouse_quantity }} — {{ $row->warehouse_name ?: '—' }}</strong></div>@endif
@if($row->review_notes)<div class="detail-item"><span>یادداشت بررسی</span><strong>{{ $row->review_notes }}</strong></div>@endif
@if($row->notes)<div class="detail-item"><span>توضیحات ثبت‌کننده</span><strong>{{ $row->notes }}</strong></div>@endif
</div></div>
<div class="panel" style="padding:16px"><h2 style="font-size:15px;margin:0 0 12px">مواد اولیه مصرف‌شده</h2>
@if($materialUsage->isEmpty())<p class="detail-note">برای این گزارش ماده اولیه‌ای ثبت نشده است.</p>@else<div style="overflow:auto"><table class="detail-table"><thead><tr><th>ماده اولیه</th><th>مقدار مصرف</th><th>ضایعات ماده</th></tr></thead><tbody>@foreach($materialUsage as $material)<tr><td>{{ $material->name }} @if($material->code)({{ $material->code }})@endif</td><td>{{ $material->quantity }}</td><td>{{ $material->scrap_quantity ?? 0 }}</td></tr>@endforeach</tbody></table></div>@endif
</div>
@if($row->status==='pending' && $canReview)
<div class="panel" style="padding:16px"><h2 style="font-size:15px;margin:0 0 12px">بررسی سرپرست</h2><p class="detail-note">پس از بررسی مقدار تولید، ضایعات و مواد مصرفی، گزارش را تأیید یا رد کنید.</p>
<form class="detail-form" method="POST" action="{{ route('production.shift-reports.review',$row->id) }}">@csrf
<label>یادداشت بررسی (برای رد گزارش الزامی است)<textarea name="review_notes" rows="3" maxlength="1000" placeholder="یادداشت یا دلیل رد گزارش"></textarea></label>
<div style="display:flex;gap:8px;flex-wrap:wrap"><button class="btn primary" type="submit" name="approve" value="1">تأیید گزارش</button><button class="btn" type="submit" name="approve" value="0" onclick="return confirm('گزارش رد شود؟');">رد گزارش</button></div></form></div>
@elseif($row->status==='approved' && $canReceive)
<div class="panel" style="padding:16px"><h2 style="font-size:15px;margin:0 0 12px">تحویل محصول به انبار</h2><p class="detail-note">مقدار سالم باقیمانده: {{ max(0,(float)$row->produced_quantity-(float)($row->scrap_quantity ?? 0)-(float)($row->warehouse_quantity ?? 0)) }}</p>
<form class="detail-form" method="POST" action="{{ route('production.shift-reports.receive',$row->id) }}">@csrf
<label>انبار مقصد<select name="warehouse_location_id" required><option value="">انتخاب انبار</option>@foreach($warehouses as $warehouse)<option value="{{ $warehouse->id }}" @selected((int)($row->warehouse_location_id ?? 0)===(int)$warehouse->id)>{{ $warehouse->name }}</option>@endforeach</select></label>
<label>مقدار تحویل این‌بار<input name="warehouse_quantity" type="number" min="0.0001" max="{{ max(0,(float)$row->produced_quantity-(float)($row->scrap_quantity ?? 0)-(float)($row->warehouse_quantity ?? 0)) }}" step="0.0001" required></label>
<button class="btn primary" type="submit">ثبت ورود به انبار</button></form></div>
@endif
</div>
@push('scripts')<script>
document.querySelectorAll('.shift-date-display[data-iso-date]').forEach(el=>{const raw=(el.dataset.isoDate||'').slice(0,10),m=raw.match(/^(\d{4})-(\d{2})-(\d{2})$/);if(!m)return;const date=new Date(Number(m[1]),Number(m[2])-1,Number(m[3]));el.textContent=new Intl.DateTimeFormat('fa-IR-u-ca-persian',{year:'numeric',month:'2-digit',day:'2-digit'}).format(date);});
</script>@endpush
@endsection
