@extends('layouts.dashboard-shell')
@section('title', 'جزئیات گزارش تولید')
@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/css/persian-datepicker.min.css">
<style>
.detail-page{display:grid;gap:18px}.detail-head{display:flex;justify-content:space-between;align-items:flex-start;gap:14px;flex-wrap:wrap}.detail-head h1{margin:0;font-size:24px}.detail-head p{margin:7px 0 0;color:#8ba1b5;font-size:12px;line-height:1.9}.detail-btn{display:inline-flex;padding:10px 13px;border-radius:9px;border:1px solid #29465e;background:#12314a;color:#e9f3fb;text-decoration:none;font-size:12px}.detail-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}.detail-item{padding:13px;border:1px solid #20384c;border-radius:10px;background:#0b1e2e}.detail-item span{display:block;color:#8ba1b5;font-size:10px;margin-bottom:7px}.detail-item strong{font-size:13px;overflow-wrap:anywhere}.detail-table{width:100%;border-collapse:collapse}.detail-table th,.detail-table td{text-align:right;padding:11px;border-bottom:1px solid #20384c;font-size:12px}.detail-table th{color:#8ba1b5}.detail-status{display:inline-block;padding:6px 10px;border-radius:18px;background:#302b20;color:#f3d58c;font-size:11px}.detail-status.good{background:#12372f;color:#6ee7d0}.detail-status.bad{background:#3b2528;color:#ffb8a8}.detail-form{display:grid;gap:12px;max-width:700px}.detail-form input,.detail-form select,.detail-form textarea{width:100%;background:#071725;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:10px;font:inherit}.detail-form label{display:grid;gap:6px;color:#cce0ef;font-size:12px}.detail-note{color:#8ba1b5;font-size:12px;line-height:1.9}.detail-flash{padding:12px 14px;border-radius:10px;background:#12372f;color:#b8f5df;font-size:12px}.detail-edit{border:1px solid #29465e;border-radius:10px;padding:14px}.detail-edit summary{cursor:pointer;color:#9ee8db;font-size:13px;font-weight:700}.detail-edit-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:15px;margin-top:14px}.detail-edit-grid .full{grid-column:1/-1}.detail-edit-grid .shift-field{display:flex;flex-direction:column;gap:7px;min-width:0;color:#cce0ef;font-size:11px}.detail-edit-grid .shift-field input,.detail-edit-grid .shift-field select,.detail-edit-grid .shift-field textarea{width:100%;min-width:0;background:#071725;color:#e9f3fb;border:1px solid #29465e;border-radius:10px;padding:11px 12px;font:inherit;outline:none}.detail-edit-grid .shift-field input:focus,.detail-edit-grid .shift-field select:focus,.detail-edit-grid .shift-field textarea:focus{border-color:#6ee7d0;box-shadow:0 0 0 3px rgba(110,231,208,.08)}.detail-edit-grid .shift-field small{font-size:10px;color:#71879d;line-height:1.7}.detail-edit-grid .detail-date-wrap{display:grid;gap:6px}.detail-edit-grid .detail-date-hint{font-size:10px;color:#71879d}.detail-material-line{display:grid;grid-template-columns:minmax(0,2fr) minmax(90px,1fr) minmax(90px,1fr);gap:8px;margin:8px 0}.detail-checks{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}.detail-check{display:flex!important;align-items:center;gap:8px!important;border:1px solid #20384c;border-radius:8px;padding:9px}.detail-check input{width:auto!important}.detail-edit-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:14px}@media(max-width:900px){.detail-edit-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:650px){.detail-edit-grid,.detail-checks{grid-template-columns:1fr}.detail-material-line{grid-template-columns:1fr 1fr}.detail-material-line select{grid-column:1/-1}}
@media(max-width:650px){.detail-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.detail-head h1{font-size:21px}}@media(max-width:420px){.detail-grid{grid-template-columns:1fr}}
@media(min-width:651px){.receive-inventory-submit{width:auto;min-width:0;min-height:0;padding:8px 14px;font-size:12px;line-height:1.5;}}
</style>
<div class="detail-page">
<div class="detail-head"><div><h1>جزئیات گزارش تولید #{{ $row->id }}</h1><p>اطلاعات ثبت‌شده، مواد مصرفی، ضایعات و وضعیت تحویل به انبار.</p></div><a class="detail-btn" href="{{ route('production.shift-reports.index') }}">← بازگشت به گزارش‌ها</a></div>
@if(session('success'))<div class="detail-flash">{{ session('success') }}</div>@endif
@if($errors->any())<div class="detail-flash" style="background:#3b2528;color:#ffb8a8">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
@php($labels=['pending'=>'در انتظار تأیید','approved'=>((bool)($row->goods_sellable ?? false) ? 'تأییدشده' : 'تأییدشده؛ نیازمند تعیین مسیر انتقال'),'rejected'=>'ردشده','received'=>'تحویل کامل به انبار'])
<div class="panel" style="padding:16px"><div class="detail-grid">
<div class="detail-item"><span>تاریخ تولید</span><strong class="shift-date-display" data-iso-date="{{ $row->work_date }}">{{ $row->work_date }}</strong></div>
<div class="detail-item"><span>قسمت تولید</span><strong>{{ $row->section_name ?: '—' }}</strong></div><div class="detail-item"><span>کارگر</span><strong>{{ $row->worker_name ?: '—' }}</strong></div>
<div class="detail-item"><span>محصول / قطعه</span><strong>{{ $row->goods_name }} @if($row->goods_code)({{ $row->goods_code }})@endif</strong></div>
<div class="detail-item"><span>تعداد تولیدشده</span><strong>{{ \App\Support\PersianQuantityFormatter::format($row->produced_quantity) }}</strong></div><div class="detail-item"><span>ضایعات محصول</span><strong>{{ \App\Support\PersianQuantityFormatter::format($row->scrap_quantity ?? 0) }}</strong></div>
<div class="detail-item"><span>تولید سالم قابل تحویل</span><strong>{{ \App\Support\PersianQuantityFormatter::format(max(0,(float)$row->produced_quantity-(float)($row->scrap_quantity ?? 0))) }}</strong></div><div class="detail-item"><span>ساعات کارکرد</span><strong>{{ $row->worked_hours !== null ? \App\Support\PersianQuantityFormatter::format($row->worked_hours) : '—' }}</strong></div>
<div class="detail-item"><span>اضافه‌کاری / تعطیل‌کاری</span><strong>{{ \App\Support\PersianQuantityFormatter::format($row->overtime_hours ?? 0) }} / {{ \App\Support\PersianQuantityFormatter::format($row->holiday_hours ?? 0) }}</strong></div>
<div class="detail-item"><span>ثبت‌کننده</span><strong>{{ $row->reporter_name }}</strong></div><div class="detail-item"><span>بررسی‌کننده</span><strong>{{ $row->reviewer_name ?: '—' }}</strong></div>
<div class="detail-item"><span>وضعیت</span><strong><span class="detail-status {{ in_array($row->status,['approved','received'],true) ? 'good' : ($row->status==='rejected' ? 'bad' : '') }}">{{ $labels[$row->status] ?? $row->status }}</span></strong></div>
@if($row->warehouse_quantity !== null)<div class="detail-item"><span>تحویل‌شده به انبار</span><strong>{{ \App\Support\PersianQuantityFormatter::format($row->warehouse_quantity) }} — {{ $row->warehouse_name ?: '—' }}</strong></div>@endif
@if($row->review_notes)<div class="detail-item"><span>یادداشت بررسی</span><strong>{{ $row->review_notes }}</strong></div>@endif
@if($row->notes)<div class="detail-item"><span>توضیحات ثبت‌کننده</span><strong>{{ $row->notes }}</strong></div>@endif
</div></div>
@if($canEdit)
<div class="panel" style="padding:16px">
<details class="detail-edit">
<summary>✎ ویرایش اطلاعات این گزارش</summary>
<p class="detail-note">مشخصات، مقادیر تولید، ساعات، مراحل و مواد مصرفی را اصلاح کنید. پس از ذخیره، گزارش دوباره در وضعیت «در انتظار تأیید» قرار می‌گیرد و تغییرات در سوابق ثبت می‌شوند. اگر مراحل را تغییر نمی‌دهید، تیک‌ها را دست‌کاری نکنید. گزارش تأییدشده نیز تا پیش از هرگونه تحویل به انبار قابل اصلاح است؛ پس از ورود حتی بخشی از مقدار به انبار، ویرایش بسته می‌شود تا موجودی انبار دچار مغایرت نشود.</p>
<form id="report-edit-form" class="detail-form" style="max-width:none" method="POST" action="{{ route('production.shift-reports.update',$row->id) }}">@csrf @method('PUT')
<div class="detail-edit-grid">
<label class="shift-field">تاریخ تولید (شمسی)<span class="detail-date-wrap"><input type="text" id="edit-jalali-date" readonly inputmode="none" autocomplete="off" placeholder="انتخاب تاریخ شمسی" value="" required><input type="hidden" id="edit-gregorian-date" name="work_date" value="{{ old('work_date', $row->work_date) }}"><small class="detail-date-hint">تاریخ را از تقویم شمسی انتخاب کنید.</small></span></label>
<label class="shift-field">قسمت تولید<select name="production_section_id" required>@foreach($sections as $section)<option value="{{ $section->id }}" @selected((int)old('production_section_id',$row->production_section_id)===(int)$section->id)>{{ $section->name }}</option>@endforeach</select></label>
<label class="shift-field">کارگر<select name="personnel_id" required>@foreach($personnel as $worker)<option value="{{ $worker->id }}" @selected((int)old('personnel_id',$row->personnel_id)===(int)$worker->id)>{{ $worker->name }}</option>@endforeach</select></label>
<label class="shift-field">محصول / قطعه<select name="goods_id" required>@foreach($goods as $good)<option value="{{ $good->id }}" @selected((int)old('goods_id',$row->goods_id)===(int)$good->id)>{{ $good->name }}{{ $good->code ? ' ('.$good->code.')' : '' }}</option>@endforeach</select></label>
<label class="shift-field">تعداد تولیدشده<input name="produced_quantity" type="number" min="0.0001" step="0.0001" value="{{ old('produced_quantity', rtrim(rtrim(number_format((float)$row->produced_quantity, 4, '.', ''), '0'), '.')) }}" required></label>
<label class="shift-field">ضایعات محصول<input name="scrap_quantity" type="number" min="0" step="0.0001" value="{{ old('scrap_quantity', rtrim(rtrim(number_format((float)($row->scrap_quantity ?? 0), 4, '.', ''), '0'), '.')) }}" required></label>
<label class="shift-field">ساعات کارکرد<input name="worked_hours" type="number" min="0.01" max="24" step="0.01" value="{{ old('worked_hours', rtrim(rtrim(number_format((float)($row->worked_hours ?? 8), 4, '.', ''), '0'), '.')) }}" required></label>
<label class="shift-field">اضافه‌کاری (ساعت)<input name="overtime_hours" type="number" min="0" max="24" step="0.01" value="{{ old('overtime_hours', rtrim(rtrim(number_format((float)($row->overtime_hours ?? 0), 4, '.', ''), '0'), '.')) }}"></label>
<label class="shift-field">تعطیل‌کاری (ساعت)<input name="holiday_hours" type="number" min="0" max="24" step="0.01" value="{{ old('holiday_hours', rtrim(rtrim(number_format((float)($row->holiday_hours ?? 0), 4, '.', ''), '0'), '.')) }}"></label>
<label class="shift-field full">توضیحات ثبت‌کننده<textarea name="notes" rows="3" maxlength="2000">{{ old('notes',$row->notes) }}</textarea></label>
</div>
<div style="margin-top:16px"><strong style="font-size:12px">مراحل انجام‌شده</strong><div class="detail-checks" style="margin-top:9px">
@foreach($stages as $stage)<label class="detail-check"><input type="checkbox" name="stages[{{ $loop->index }}][production_stage_id]" value="{{ $stage->id }}" @checked($reportedStages->contains('id',$stage->id))><span>{{ $stage->code ? $stage->code.' — ' : '' }}{{ $stage->name }}</span></label>@endforeach
</div></div>
<div style="margin-top:16px"><strong style="font-size:12px">مواد اولیه مصرف‌شده</strong>
<div id="detail-material-lines">
@if($materialUsage->isNotEmpty())
@foreach($materialUsage as $material)
<div class="detail-material-line">
<select name="materials[{{ $loop->index }}][goods_id]"><option value="">انتخاب ماده اولیه</option>@foreach($materials as $option)<option value="{{ $option->id }}" @selected((int)$material->goods_id===(int)$option->id)>{{ $option->name }}{{ $option->code ? ' ('.$option->code.')' : '' }}</option>@endforeach</select>
<input name="materials[{{ $loop->index }}][quantity]" type="number" min="0.0001" step="0.0001" placeholder="مقدار مصرف" value="{{ rtrim(rtrim(number_format((float)$material->quantity, 4, '.', ''), '0'), '.') }}">
<input name="materials[{{ $loop->index }}][scrap_quantity]" type="number" min="0" step="0.0001" placeholder="ضایعات ماده" value="{{ rtrim(rtrim(number_format((float)($material->scrap_quantity ?? 0), 4, '.', ''), '0'), '.') }}">
</div>
@endforeach
@else
<div class="detail-material-line"><select name="materials[0][goods_id]"><option value="">انتخاب ماده اولیه</option>@foreach($materials as $option)<option value="{{ $option->id }}">{{ $option->name }}{{ $option->code ? ' ('.$option->code.')' : '' }}</option>@endforeach</select><input name="materials[0][quantity]" type="number" min="0.0001" step="0.0001" placeholder="مقدار مصرف"><input name="materials[0][scrap_quantity]" type="number" min="0" step="0.0001" placeholder="ضایعات ماده" value="0"></div>
@endif
</div>
<template id="detail-material-template"><div class="detail-material-line"><select name="materials[__INDEX__][goods_id]"><option value="">انتخاب ماده اولیه</option>@foreach($materials as $option)<option value="{{ $option->id }}">{{ $option->name }}{{ $option->code ? ' ('.$option->code.')' : '' }}</option>@endforeach</select><input name="materials[__INDEX__][quantity]" type="number" min="0.0001" step="0.0001" placeholder="مقدار مصرف"><input name="materials[__INDEX__][scrap_quantity]" type="number" min="0" step="0.0001" placeholder="ضایعات ماده" value="0"></div></template>
<button class="detail-btn" type="button" id="detail-add-material" style="margin-top:8px;cursor:pointer">+ افزودن ماده اولیه</button>
<p class="detail-note">برای حذف یک ماده از گزارش، گزینهٔ ماده و مقدار مصرف آن ردیف را خالی بگذارید.</p>
</div>
<div class="detail-edit-actions"><button class="btn primary" type="submit">ذخیره اصلاحات و ارسال برای تأیید</button></div>
</form>
</details>
</div>
@endif
<div class="panel" style="padding:16px"><h2 style="font-size:15px;margin:0 0 12px">مراحل ثبت‌شده</h2>
@if($reportedStages->isEmpty())<p class="detail-note">برای این گزارش مرحله‌ای ثبت نشده است.</p>@else<div style="display:flex;gap:8px;flex-wrap:wrap">@foreach($reportedStages as $stage)<span class="detail-status good">{{ $stage->code ? $stage->code.' — ' : '' }}{{ $stage->name }}</span>@endforeach</div>@endif
</div>
<div class="panel" style="padding:16px"><h2 style="font-size:15px;margin:0 0 12px">مواد اولیه مصرف‌شده</h2>
@if($materialUsage->isEmpty())<p class="detail-note">برای این گزارش ماده اولیه‌ای ثبت نشده است.</p>@else<div style="overflow:auto"><table class="detail-table"><thead><tr><th>ماده اولیه</th><th>مقدار مصرف</th><th>ضایعات ماده</th></tr></thead><tbody>@foreach($materialUsage as $material)<tr><td>{{ $material->name }} @if($material->code)({{ $material->code }})@endif</td><td>{{ \App\Support\PersianQuantityFormatter::format($material->quantity) }}</td><td>{{ \App\Support\PersianQuantityFormatter::format($material->scrap_quantity ?? 0) }}</td></tr>@endforeach</tbody></table></div>@endif
</div>
@if($row->status==='approved' && (bool)($row->goods_sellable ?? false) && max(0,(float)$row->produced_quantity-(float)($row->scrap_quantity ?? 0)-(float)($row->warehouse_quantity ?? 0)) > 0)
<div class="panel" style="padding:16px"><h2 style="font-size:15px;margin:0 0 12px">ارسال درخواست تحویل محصول به انبار</h2>
<p class="detail-note">تأیید گزارش به معنی تحویل کالا نیست. مقدار سالم آماده ارسال: <strong>{{ \\App\\Support\\PersianQuantityFormatter::format(max(0,(float)$row->produced_quantity-(float)($row->scrap_quantity ?? 0)-(float)($row->warehouse_quantity ?? 0))) }}</strong>.</p>
@if(empty($row->warehouse_requested_at))
    @if($canReview)
        <form method="POST" action="{{ route('production.shift-reports.request-warehouse', $row->id) }}" onsubmit="return confirm('درخواست تحویل این محصول را جداگانه برای مسئولان انبار ارسال می‌کنید؟');">
            @csrf
            <button class="btn primary" type="submit">ارسال درخواست به انبار</button>
        </form>
    @else
        <p class="detail-note">ارسال درخواست به انبار باید توسط مسئول مجاز تولید انجام شود.</p>
    @endif
@else
    <p class="detail-note">درخواست تحویل قبلاً ثبت شده و برای مسئولان انبار ارسال شده است.</p>
    <a class="detail-btn" href="{{ route('production.outputs.index') }}">مشاهده درخواست در خروجی تولید</a>
@endif
</div>
@endif
@if($row->status==='pending' && $canReview)
<div class="panel" style="padding:16px"><h2 style="font-size:15px;margin:0 0 12px">بررسی سرپرست</h2><p class="detail-note">پس از بررسی مقدار تولید، ضایعات و مواد مصرفی، گزارش را تأیید یا رد کنید. تأیید گزارش فقط وضعیت آن را تغییر می‌دهد؛ گزینه «تحویل محصول به انبار» بعد از تأیید و در بخش جداگانه نمایش داده می‌شود.</p>
<form class="detail-form" method="POST" action="{{ route('production.shift-reports.review',$row->id) }}">@csrf
<label>یادداشت بررسی (برای رد گزارش الزامی است)<textarea id="review-notes" name="review_notes" rows="3" maxlength="1000" placeholder="یادداشت یا دلیل رد گزارش"></textarea></label>
<div style="display:flex;gap:8px;flex-wrap:wrap"><button class="btn primary" type="submit" name="approve" value="1">تأیید گزارش</button><button class="btn" type="submit" name="approve" value="0" formnovalidate onclick="return confirm('گزارش رد شود؟');">رد گزارش</button></div></form></div>
@endif
</div>
@push('scripts')<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script><script src="https://cdn.jsdelivr.net/npm/persian-date@1.1.0/dist/persian-date.min.js"></script><script src="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/js/persian-datepicker.min.js"></script><script>
const editDatePicker=document.querySelector('#edit-jalali-date');
const editIsoDate=document.querySelector('#edit-gregorian-date');
const editDateForm=document.querySelector('#report-edit-form');
if(editDatePicker && editIsoDate && window.jQuery && jQuery.fn.persianDatepicker && typeof window.persianDate==='function'){
  const isoMatch=(editIsoDate.value||'').match(/^(\d{4})-(\d{2})-(\d{2})$/);
  if(isoMatch){
    const gregorianDate=new Date(Number(isoMatch[1]),Number(isoMatch[2])-1,Number(isoMatch[3]));
    editDatePicker.value=new window.persianDate(gregorianDate.getTime()).toCalendar('persian').format('YYYY/MM/DD');
  } else {
    editDatePicker.value='';
  }
  jQuery(editDatePicker).persianDatepicker({
    calendarType:'persian', initialValue:Boolean(isoMatch), initialValueType:'persian',
    format:'YYYY/MM/DD',
    onSelect:unixDate=>{
      const date=new window.persianDate(unixDate).toCalendar('gregorian');
      const value=date.format('YYYY-MM-DD');
      editIsoDate.value=/^\d{4}-\d{2}-\d{2}$/.test(value) ? value : '';
    },
    calendar:{persian:{locale:'fa'}},
    toolbox:{calendarSwitch:{enabled:false},todayButton:{enabled:true,text:{fa:'امروز'}}}
  });
}
if(editDateForm){
  editDateForm.addEventListener('submit',event=>{
    if(!editIsoDate || !/^\d{4}-\d{2}-\d{2}$/.test(editIsoDate.value||'')){
      event.preventDefault();
      alert('لطفاً تاریخ تولید را از تقویم شمسی انتخاب کنید.');
      editDatePicker?.focus();
    }
  });
}
document.querySelectorAll('#report-edit-form input[type="number"]').forEach(field=>{
  if(field.value && field.value.includes('.')) field.value=field.value.replace(/(\.\d*?[1-9])0+$|\.0+$/,'$1');
});
document.querySelectorAll('.shift-date-display[data-iso-date]').forEach(el=>{const raw=(el.dataset.isoDate||'').slice(0,10),m=raw.match(/^(\d{4})-(\d{2})-(\d{2})$/);if(!m)return;const date=new Date(Number(m[1]),Number(m[2])-1,Number(m[3]));el.textContent=new Intl.DateTimeFormat('fa-IR-u-ca-persian',{year:'numeric',month:'2-digit',day:'2-digit'}).format(date);});
const reviewForm=document.querySelector('form[action*="/review"]');
if(reviewForm){
  reviewForm.addEventListener('submit',event=>{
    const submitter=event.submitter;
    if(submitter && submitter.value==='0'){
      const note=document.getElementById('review-notes');
      if(!note || !note.value.trim()){
        event.preventDefault();
        alert('برای رد گزارش، لطفاً دلیل رد را وارد کنید.');
        note?.focus();
      }
    }
  });
}
const addMaterial=document.getElementById('detail-add-material');
if(addMaterial){
  addMaterial.addEventListener('click',()=>{
    const container=document.getElementById('detail-material-lines');
    const template=document.getElementById('detail-material-template');
    const index=container.querySelectorAll('.detail-material-line').length;
    container.insertAdjacentHTML('beforeend',template.innerHTML.replaceAll('__INDEX__',String(index)));
  });
}
</script>@endpush
@endsection
