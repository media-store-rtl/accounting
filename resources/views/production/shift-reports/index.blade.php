@extends('layouts.dashboard-shell')

@section('title', 'گزارش تولید شیفت')

@section('content')
<div class="page-heading"><div><h1>ثبت تولید شیفت</h1><p>ثبت محصول، قسمت، شیفت، تولید و ضایعات بدون نیاز به دستور تولید</p></div></div>
@if(session('success'))<div style="padding:12px 15px;margin-bottom:16px;border:1px solid #246451;border-radius:12px;background:#0c282b;color:#8ce8ce;font-size:12px">{{ session('success') }}</div>@endif
@if($errors->any())<div style="padding:12px 15px;margin-bottom:16px;border:1px solid #714448;border-radius:12px;background:#302126;color:#ffb8a8;font-size:12px">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif

<div class="panel" style="margin-bottom:18px">
<h2 style="font-size:15px;margin:0 0 8px">ثبت گزارش پایان شیفت</h2>
<p style="font-size:12px;line-height:1.9">اپراتور یا مسئول شیفت، مقدار تولید و ضایعات را ثبت می‌کند. پس از تأیید سرپرست، مقدار قابل‌تحویل به انبار جداگانه ثبت می‌شود؛ ثبت گزارش به‌تنهایی موجودی انبار را تغییر نمی‌دهد.</p>
<form method="POST" action="{{ route('production.shift-reports.store') }}" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,210px),1fr));gap:12px">
@csrf
<label>قسمت تولید<select class="form-select" name="production_section_id" required><option value="">انتخاب قسمت</option>@foreach($sections as $s)<option value="{{ $s->id }}" @selected((string)old('production_section_id')===(string)$s->id)>{{ $s->name ?: 'قسمت '.$s->id }}</option>@endforeach</select></label>
<label>محصول / قطعه<select class="form-select" name="goods_id" required><option value="">انتخاب کالا</option>@foreach($goods as $g)<option value="{{ $g->id }}" @selected((string)old('goods_id')===(string)$g->id)>{{ $g->code ? $g->code.' — ' : '' }}{{ $g->name }}</option>@endforeach</select></label>
<label>شیفت<input class="form-input" name="shift_name" maxlength="80" value="{{ old('shift_name') }}" placeholder="مثلاً صبح، عصر یا شب" required></label>
<label>تاریخ شیفت<input class="form-input" type="date" name="work_date" value="{{ old('work_date', now()->toDateString()) }}" required></label>
<label>تعداد تولیدشده<input class="form-input" type="number" min="0.0001" step="0.0001" name="produced_quantity" value="{{ old('produced_quantity') }}" required></label>
<label>ضایعات<input class="form-input" type="number" min="0" step="0.0001" name="scrap_quantity" value="{{ old('scrap_quantity', '0') }}"></label>
<label style="grid-column:1/-1">توضیحات<textarea class="form-input" name="notes" rows="2" maxlength="2000">{{ old('notes') }}</textarea></label>
<div><button class="btn primary" type="submit">ثبت گزارش برای تأیید</button></div>
</form></div>

<div class="panel"><h2 style="font-size:15px">گزارش‌های ثبت‌شده</h2>
<div class="table-wrap"><table><thead><tr><th>تاریخ / شیفت</th><th>قسمت</th><th>محصول</th><th>تولید</th><th>ضایعات</th><th>ثبت‌کننده</th><th>وضعیت</th><th>اقدام</th></tr></thead><tbody>
@forelse($rows as $r)
<tr><td>{{ $r->work_date }}<br>{{ $r->shift_name }}</td><td>{{ $r->section_name ?: '—' }}</td><td>{{ $r->goods_code ? $r->goods_code.' — ' : '' }}{{ $r->goods_name }}</td><td>{{ $r->produced_quantity }}</td><td>{{ $r->scrap_quantity }}</td><td>{{ $r->reporter_name }}</td><td>
@php($labels=['pending'=>'در انتظار تأیید','approved'=>'تأییدشده؛ آماده تحویل','rejected'=>'ردشده','received'=>'تحویل انبار شد'])
<span class="status {{ in_array($r->status,['approved','received'],true) ? 'active' : 'inactive' }}">{{ $labels[$r->status] ?? $r->status }}</span>
@if($r->review_notes)<div style="font-size:11px;margin-top:5px">{{ $r->review_notes }}</div>@endif
@if($r->status==='received')<div style="font-size:11px;margin-top:5px">تحویل: {{ $r->warehouse_quantity }} — {{ $r->warehouse_name ?: 'انبار' }}</div>@endif
</td><td style="min-width:190px">
@if($r->status==='pending' && $canReview)
<form method="POST" action="{{ route('production.shift-reports.review',$r->id) }}" style="display:grid;gap:6px;margin-bottom:6px">@csrf
<input type="hidden" name="approve" value="1"><input class="form-input" name="review_notes" placeholder="یادداشت سرپرست (اختیاری)"><button class="btn primary" type="submit">تأیید گزارش</button></form>
<form method="POST" action="{{ route('production.shift-reports.review',$r->id) }}" style="display:grid;gap:6px">@csrf
<input type="hidden" name="approve" value="0"><input class="form-input" name="review_notes" placeholder="دلیل رد گزارش" required><button class="btn" type="submit">رد گزارش</button></form>
@elseif($r->status==='approved' && $canReceive)
<form method="POST" action="{{ route('production.shift-reports.receive',$r->id) }}" style="display:grid;gap:6px">@csrf
<select class="form-select" name="warehouse_location_id" required><option value="">انتخاب انبار</option>@foreach($warehouses as $w)<option value="{{ $w->id }}">{{ $w->name }}</option>@endforeach</select>
<input class="form-input" name="warehouse_quantity" type="number" min="0.0001" max="{{ max(0,(float)$r->produced_quantity-(float)$r->scrap_quantity) }}" step="0.0001" placeholder="مقدار تحویلی" required>
<button class="btn primary" type="submit">ثبت ورود به انبار</button></form>
@else—@endif
</td></tr>
@empty<tr><td colspan="8" style="text-align:center;padding:24px;color:#8198ac">هنوز گزارش شیفتی ثبت نشده است.</td></tr>@endforelse
</tbody></table></div>
@if(method_exists($rows,'links'))<div class="pagination">{{ $rows->links() }}</div>@endif
</div>
@endsection
