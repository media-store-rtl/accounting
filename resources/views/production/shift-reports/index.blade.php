@extends('layouts.dashboard-shell')

@section('title', 'ثبت تولید شیفت')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/css/persian-datepicker.min.css">
<style>
.shift-page{display:grid;gap:18px}
.shift-intro{display:flex;align-items:flex-start;justify-content:space-between;gap:18px;flex-wrap:wrap}
.shift-intro .eyebrow{display:inline-flex;align-items:center;gap:7px;color:#6ee7d0;font-size:10px;margin-bottom:7px}
.shift-intro h1{margin:0;font-size:25px}
.shift-intro p{margin:7px 0 0;color:#8ba1b5;font-size:12px;line-height:1.9;max-width:720px}
.shift-guide{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin-top:16px}
.shift-guide-item{display:flex;align-items:flex-start;gap:10px;padding:13px;border:1px solid #1b354b;border-radius:12px;background:#0d2235}
.shift-guide-num{display:grid;place-items:center;flex:0 0 27px;height:27px;border-radius:9px;background:#12372f;color:#6ee7d0;font-size:11px;font-weight:800}
.shift-guide-item strong{display:block;font-size:11px;margin:1px 0 4px}
.shift-guide-item span{display:block;color:#8ba1b5;font-size:10px;line-height:1.8}
.shift-panel-head{display:flex;justify-content:space-between;align-items:flex-start;gap:12px;margin-bottom:17px}
.shift-panel-head h2{font-size:15px;margin:0 0 5px}
.shift-panel-head p{font-size:11px;line-height:1.9;color:#8ba1b5;margin:0}
.shift-tag{white-space:nowrap;border:1px solid #29465e;background:#0d2235;color:#9eb2c4;border-radius:20px;padding:5px 9px;font-size:10px}
.shift-form{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:15px}
.shift-field{display:flex;flex-direction:column;gap:7px;min-width:0;color:#cce0ef;font-size:11px}
.shift-field.full{grid-column:1/-1}.shift-material-lines{display:grid;gap:9px}.shift-material-line{display:grid;grid-template-columns:minmax(0,2fr) minmax(100px,1fr) minmax(100px,1fr) auto;gap:8px;align-items:center}.shift-material-remove{border:1px solid #63343b;background:#3b2528;color:#ffb8a8;border-radius:8px;padding:9px 11px;cursor:pointer}.shift-add-material{border:1px solid #29465e;background:#12314a;color:#dcebf6;border-radius:9px;padding:9px 12px;cursor:pointer;font:inherit}.shift-material-help{font-size:10px;color:#71879d;line-height:1.8}
.shift-field input,.shift-field select,.shift-field textarea,.shift-action input,.shift-action select{width:100%;min-width:0;background:#071725;color:#e9f3fb;border:1px solid #29465e;border-radius:10px;padding:11px 12px;font:inherit;outline:none}
.shift-field input:focus,.shift-field select:focus,.shift-field textarea:focus,.shift-action input:focus,.shift-action select:focus{border-color:#6ee7d0;box-shadow:0 0 0 3px rgba(110,231,208,.08)}
.shift-field input::placeholder,.shift-field textarea::placeholder,.shift-action input::placeholder{color:#587087}
.shift-date-wrap{display:grid;gap:6px}.shift-date-hint{font-size:10px;color:#71879d}
.shift-field small{font-size:10px;color:#71879d;line-height:1.7}
.shift-form-footer{grid-column:1/-1;display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;padding-top:5px}
.shift-form-footer p{font-size:10px;color:#71879d;line-height:1.8;margin:0}
.shift-table-title{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:14px}
.shift-table-title h2{font-size:15px;margin:0}
.shift-table-title p{font-size:10px;color:#71879d;margin:5px 0 0}
.shift-table td{vertical-align:middle}
.shift-primary-cell{font-weight:600;color:#dcebf6}
.shift-secondary{display:block;color:#71879d;font-size:10px;margin-top:5px;white-space:normal;min-width:90px;line-height:1.7}
.shift-number{font-variant-numeric:tabular-nums}
.shift-action{display:grid;gap:7px;min-width:165px}
.shift-action form{display:grid;gap:7px}
.shift-action .btn{justify-content:center;cursor:pointer}
.shift-note{font-size:10px;line-height:1.8;color:#8ba1b5;white-space:normal;min-width:130px;max-width:220px}
.shift-empty{padding:30px 14px!important;text-align:center!important;white-space:normal;color:#8198ac}
.shift-flash{padding:12px 15px;border-radius:12px;font-size:12px;line-height:1.8}
.shift-flash.success{border:1px solid #246451;background:#0c282b;color:#8ce8ce}
.shift-flash.error{border:1px solid #714448;background:#302126;color:#ffb8a8}
@media(max-width:900px){.shift-form{grid-template-columns:repeat(2,minmax(0,1fr))}.shift-guide{grid-template-columns:1fr}}
@media(max-width:560px){.shift-intro h1{font-size:22px}.shift-form{grid-template-columns:1fr}.shift-field.full,.shift-form-footer{grid-column:auto}.shift-panel-head{flex-direction:column}.shift-tag{white-space:normal}.shift-material-line{grid-template-columns:minmax(0,1fr) minmax(0,1fr)}.shift-material-line select{grid-column:1/-1}.shift-form-footer .btn{width:100%;justify-content:center}}
</style>
@endpush

@section('content')
<div class="shift-page">
  <div class="shift-intro">
    <div>
      <span class="eyebrow">⚒ ثبت عملکرد واقعی تولید</span>
      <h1>ثبت تولید شیفت</h1>
      <p>مقدار تولید هر قسمت را به تفکیک محصول، کارگر و شیفت ثبت کنید. گزارش پس از ثبت برای بررسی سرپرست ارسال می‌شود.</p>
    </div>
    <span class="shift-tag">فرایند ساده ثبت تولید</span>
  </div>

  @if(session('success'))<div class="shift-flash success">{{ session('success') }}</div>@endif
  @if($errors->any())<div class="shift-flash error"><strong>لطفاً موارد زیر را بررسی کنید:</strong>@foreach($errors->all() as $error)<div>• {{ $error }}</div>@endforeach</div>@endif

  <div class="panel">
    <div class="shift-panel-head">
      <div>
        <h2>راهنمای ثبت گزارش</h2>
        <p>ابتدا مشخصات تولید را وارد کنید؛ ساعات کارکرد و اضافه‌کاری جداگانه ثبت می‌شوند. تاریخ را به شمسی وارد کنید.</p>
      </div>
    </div>
    <div class="shift-guide">
      <div class="shift-guide-item"><span class="shift-guide-num">۱</span><div><strong>مشخصات تولید</strong><span>قسمت تولید، تاریخ شمسی، کارگر و محصول را انتخاب کنید.</span></div></div>
      <div class="shift-guide-item"><span class="shift-guide-num">۲</span><div><strong>زمان کار</strong><span>ساعات کارکرد عادی و اضافه‌کاری را جداگانه وارد کنید.</span></div></div>
      <div class="shift-guide-item"><span class="shift-guide-num">۳</span><div><strong>تولید و تحویل</strong><span>تعداد تولید و ضایعات را ثبت کنید؛ پس از تأیید سرپرست، محصول سالم قابل تحویل به انبار است.</span></div></div>
    </div>
  </div>

  <div class="panel">
    <div class="shift-panel-head">
      <div>
        <h2>فرم ثبت تولید</h2>
        <p>فیلدهای ستاره‌دار الزامی‌اند. ثبت گزارش به‌تنهایی موجودی انبار را تغییر نمی‌دهد.</p>
      </div>
      <span class="shift-tag">گزارش جدید</span>
    </div>
    <form method="POST" action="{{ route('production.shift-reports.store') }}" class="shift-form">
      @csrf
      <label class="shift-field">قسمت تولید *
        <select name="production_section_id" required>
          <option value="">انتخاب قسمت تولید</option>
          @foreach($sections as $s)<option value="{{ $s->id }}" @selected((string)old('production_section_id')===(string)$s->id)>{{ $s->name ?: 'قسمت '.$s->id }}</option>@endforeach
        </select>
      </label>
      <label class="shift-field">تاریخ تولید (شمسی) *
        <span class="shift-date-wrap"><input type="text" id="shift-jalali-date" inputmode="none" autocomplete="off" placeholder="انتخاب تاریخ شمسی" value="{{ old('work_date', now()->toDateString()) }}" aria-label="تاریخ تولید به شمسی" required><input type="hidden" id="shift-gregorian-date" name="work_date" value="{{ old('work_date', now()->toDateString()) }}"><small class="shift-date-hint">تاریخ را به شکل سال/ماه/روز وارد کنید.</small></span>
      </label>
      <label class="shift-field">ساعات کارکرد *
        <input type="number" name="worked_hours" min="0.01" max="24" step="0.25" value="{{ old('worked_hours', 8) }}" placeholder="مثلاً ۸" required>
        <small>ساعات کار عادی در این روز</small>
      </label>
      <label class="shift-field">اضافه‌کاری (ساعت)
        <input type="number" name="overtime_hours" min="0" max="24" step="0.25" value="{{ old('overtime_hours', 0) }}" placeholder="مثلاً ۲">
        <small>در صورت نداشتن اضافه‌کاری، صفر وارد کنید.</small>
      </label>
      <label class="shift-field">تعطیل‌کاری (ساعت)
        <input type="number" name="holiday_hours" min="0" max="24" step="0.25" value="{{ old('holiday_hours', 0) }}" placeholder="مثلاً ۸">
        <small>جدا ثبت می‌شود تا نرخ پرداخت تعطیل‌کاری قابل محاسبه باشد.</small>
      </label>
      <label class="shift-field">کارگر *
        <select name="personnel_id" required>
          <option value="">انتخاب کارگر</option>
          @foreach($personnel as $p)<option value="{{ $p->id }}" @selected((string)old('personnel_id')===(string)$p->id)>{{ $p->code ? $p->code.' — ' : '' }}{{ $p->name }}</option>@endforeach
        </select>
      </label>
      <label class="shift-field">محصول / قطعه *
        <select name="goods_id" required>
          <option value="">انتخاب محصول</option>
          @foreach($goods as $g)<option value="{{ $g->id }}" @selected((string)old('goods_id')===(string)$g->id)>{{ $g->code ? $g->code.' — ' : '' }}{{ $g->name }}</option>@endforeach
        </select>
      </label>
      <div class="shift-field full">
        <span>مواد اولیه مصرف‌شده</span>
        <div id="shift-material-lines" class="shift-material-lines">
          @forelse(old('materials', [['goods_id' => '', 'quantity' => '']]) as $i => $line)
            <div class="shift-material-line">
              <select name="materials[{{ $i }}][goods_id]" aria-label="ماده اولیه">
                <option value="">انتخاب ماده اولیه</option>
                @foreach($materials as $m)<option value="{{ $m->id }}" @selected((string)($line['goods_id'] ?? '')===(string)$m->id)>{{ $m->code ? $m->code.' — ' : '' }}{{ $m->name }}</option>@endforeach
              </select>
              <input type="number" name="materials[{{ $i }}][quantity]" min="0.0001" step="0.0001" value="{{ $line['quantity'] ?? '' }}" placeholder="مقدار مصرف" aria-label="مقدار مصرف ماده اولیه">
              <input type="number" name="materials[{{ $i }}][scrap_quantity]" min="0" step="0.0001" value="{{ $line['scrap_quantity'] ?? '0' }}" placeholder="ضایعات ماده" aria-label="ضایعات ماده اولیه">
              <button type="button" class="shift-material-remove" onclick="removeShiftMaterial(this)" aria-label="حذف ماده اولیه">حذف</button>
            </div>
          @empty
            <div class="shift-material-line">
              <select name="materials[0][goods_id]" aria-label="ماده اولیه"><option value="">انتخاب ماده اولیه</option>@foreach($materials as $m)<option value="{{ $m->id }}">{{ $m->code ? $m->code.' — ' : '' }}{{ $m->name }}</option>@endforeach</select>
              <input type="number" name="materials[0][quantity]" min="0.0001" step="0.0001" placeholder="مقدار مصرف" aria-label="مقدار مصرف ماده اولیه">
              <input type="number" name="materials[0][scrap_quantity]" min="0" step="0.0001" value="0" placeholder="ضایعات ماده" aria-label="ضایعات ماده اولیه">
              <button type="button" class="shift-material-remove" onclick="removeShiftMaterial(this)">حذف</button>
            </div>
          @endforelse
        </div>
        <div><button type="button" class="shift-add-material" onclick="addShiftMaterial()">+ افزودن ماده اولیه</button></div>
        <small class="shift-material-help">برای هر ماده اولیه، مقدار مصرف و ضایعات همان ماده را وارد کنید. ضایعات ماده نمی‌تواند از مقدار مصرف آن بیشتر باشد. اگر مواد اولیه‌ای ثبت نمی‌کنید، ردیف را خالی بگذارید.</small>
      </div>
      <label class="shift-field">تعداد تولیدشده *
        <input type="number" min="0.0001" step="0.0001" name="produced_quantity" value="{{ old('produced_quantity') }}" placeholder="مثلاً 250" required>
        <small>کل تعداد تولیدشده در این شیفت</small>
      </label>
      <label class="shift-field">مقدار ضایعات
        <input type="number" min="0" step="0.0001" name="scrap_quantity" value="{{ old('scrap_quantity', '0') }}" placeholder="اگر ضایعات ندارید صفر بماند">
        <small>نمی‌تواند بیشتر از کل تولید باشد.</small>
      </label>
      <label class="shift-field full">توضیحات
        <textarea name="notes" rows="3" maxlength="2000" placeholder="توضیحات یا نکات مربوط به این شیفت (اختیاری)">{{ old('notes') }}</textarea>
      </label>
      <div class="shift-form-footer">
        <p>پس از ثبت، وضعیت گزارش «در انتظار تأیید» خواهد بود.</p>
        <button class="btn primary" type="submit">ثبت گزارش تولید</button>
      </div>
    </form>
  </div>

  <div class="panel">
    <div class="shift-table-title">
      <div><h2>گزارش‌های ثبت‌شده</h2><p>سوابق تولید، وضعیت بررسی و تحویل به انبار</p></div>
      <span class="shift-tag">آخرین گزارش‌ها</span>
    </div>
    <div class="table-wrap">
      <table class="shift-table">
        <thead><tr><th>تاریخ / شیفت</th><th>قسمت</th><th>کارگر</th><th>محصول</th><th>تولید</th><th>ضایعات</th><th>ثبت‌کننده</th><th>وضعیت</th><th>اقدام</th></tr></thead>
        <tbody>
        @forelse($rows as $r)
          <tr>
            <td><span class="shift-primary-cell shift-date-display" data-iso-date="{{ $r->work_date }}">{{ $r->work_date }}</span><span class="shift-secondary">کارکرد: {{ $r->worked_hours ?? '—' }} ساعت</span><span class="shift-secondary">اضافه‌کاری: {{ $r->overtime_hours ?? 0 }} ساعت</span><span class="shift-secondary">تعطیل‌کاری: {{ $r->holiday_hours ?? 0 }} ساعت</span></td>
            <td>{{ $r->section_name ?: '—' }}</td>
            <td>{{ $r->worker_name ?: '—' }}</td>
            <td><span class="shift-primary-cell">{{ $r->goods_name }}</span>@if($r->goods_code)<span class="shift-secondary">{{ $r->goods_code }}</span>@endif
              @foreach(($materialUsage[$r->id] ?? collect()) as $material)<span class="shift-secondary">ماده: {{ $material->name }} — مصرف: {{ $material->quantity }} — ضایعات ماده: {{ $material->scrap_quantity ?? 0 }}</span>@endforeach
            </td>
            <td class="shift-number">{{ $r->produced_quantity }}</td>
            <td class="shift-number">{{ $r->scrap_quantity }}</td>
            <td>{{ $r->reporter_name }}</td>
            <td>
              @php($labels=['pending'=>'در انتظار تأیید','approved'=>'تأییدشده؛ آماده تحویل','rejected'=>'ردشده','received'=>'تحویل کامل به انبار'])
              <span class="status {{ in_array($r->status,['approved','received'],true) ? 'active' : 'inactive' }}">{{ $labels[$r->status] ?? $r->status }}</span>
              @if($r->review_notes)<div class="shift-note" style="margin-top:7px">یادداشت: {{ $r->review_notes }}</div>@endif
              @if((float)($r->warehouse_quantity ?? 0)>0)<div class="shift-note" style="margin-top:7px">تحویل‌شده: {{ $r->warehouse_quantity }}<br>انبار: {{ $r->warehouse_name ?: '—' }}</div>@endif
            </td>
            <td>
              <div class="shift-action">
                @if($r->status==='pending' && $canReview)
                  <form method="POST" action="{{ route('production.shift-reports.review',$r->id) }}">@csrf
                    <input type="hidden" name="approve" value="1"><input name="review_notes" placeholder="یادداشت سرپرست (اختیاری)">
                    <button class="btn primary" type="submit">تأیید گزارش</button>
                  </form>
                  <form method="POST" action="{{ route('production.shift-reports.review',$r->id) }}">@csrf
                    <input type="hidden" name="approve" value="0"><input name="review_notes" placeholder="دلیل رد گزارش" required>
                    <button class="btn" type="submit">رد گزارش</button>
                  </form>
                @elseif($r->status==='approved' && $canReceive)
                  <form method="POST" action="{{ route('production.shift-reports.receive',$r->id) }}">@csrf
                    <select name="warehouse_location_id" required><option value="">انتخاب انبار</option>@foreach($warehouses as $w)<option value="{{ $w->id }}" @selected((int)($r->warehouse_location_id ?? 0)===(int)$w->id)>{{ $w->name }}</option>@endforeach</select>
                    <input name="warehouse_quantity" type="number" min="0.0001" max="{{ max(0,(float)$r->produced_quantity-(float)$r->scrap_quantity-(float)($r->warehouse_quantity ?? 0)) }}" step="0.0001" placeholder="مقدار تحویل این‌بار" required>
                    <button class="btn primary" type="submit">ثبت ورود به انبار</button>
                  </form>
                @else
                  <span class="shift-secondary">اقدامی لازم نیست</span>
                @endif
              </div>
            </td>
          </tr>
        @empty
          <tr><td colspan="9" class="shift-empty">هنوز گزارشی ثبت نشده است. از فرم بالا برای ثبت اولین شیفت استفاده کنید.</td></tr>
        @endforelse
        </tbody>
      </table>
    </div>
    @if(method_exists($rows,'links'))<div class="pagination">{{ $rows->links() }}</div>@endif
  </div>
</div>
@push('scripts')
<script>
let shiftMaterialIndex = document.querySelectorAll('#shift-material-lines .shift-material-line').length;
function addShiftMaterial() {
  const lines = document.getElementById('shift-material-lines');
  if (!lines) return;
  const source = lines.querySelector('.shift-material-line');
  if (!source) return;
  const row = source.cloneNode(true);
  row.querySelectorAll('select,input').forEach((field) => {
    field.name = field.name.replace(/materials\[\d+\]/, 'materials[' + shiftMaterialIndex + ']');
    if (field.tagName === 'SELECT') field.selectedIndex = 0;
    else field.value = '';
  });
  lines.appendChild(row);
  shiftMaterialIndex++;
}
function removeShiftMaterial(button) {
  const lines = document.getElementById('shift-material-lines');
  const row = button.closest('.shift-material-line');
  if (!lines || !row) return;
  if (lines.querySelectorAll('.shift-material-line').length === 1) {
    row.querySelectorAll('select,input').forEach(field => field.value = '');
    return;
  }
  row.remove();
}
</script>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/persian-date@1.1.0/dist/persian-date.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/js/persian-datepicker.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const picker = document.querySelector('#shift-jalali-date');
  const isoField = document.querySelector('#shift-gregorian-date');
  if (picker && isoField && window.jQuery && jQuery.fn.persianDatepicker) {
    jQuery(picker).persianDatepicker({
      calendarType: 'persian',
      initialValue: Boolean(picker.value),
      initialValueType: 'gregorian',
      format: 'YYYY/MM/DD',
      altField: '#shift-gregorian-date',
      altFormat: 'YYYY-MM-DD',
      altFieldFormatter: function (unixDate) {
        const date = new Date(unixDate);
        return date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0') + '-' + String(date.getDate()).padStart(2, '0');
      },
      calendar: { persian: { locale: 'fa' } },
      toolbox: { calendarSwitch: { enabled: false }, todayButton: { enabled: true, text: { fa: 'امروز' } } }
    });
  }
  document.querySelectorAll('.shift-date-display[data-iso-date]').forEach(el => {
    const raw = (el.dataset.isoDate || '').slice(0, 10);
    const match = raw.match(/^(\d{4})-(\d{2})-(\d{2})$/);
    if (!match) return;
    const date = new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]));
    el.textContent = new Intl.DateTimeFormat('fa-IR-u-ca-persian', {year:'numeric',month:'2-digit',day:'2-digit'}).format(date);
  });
});
</script>
@endpush
@endsection
