@extends('layouts.dashboard-shell')

@section('title', 'تحویل و ارسال')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/css/persian-datepicker.min.css">
<style>
.delivery-request-fields{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px;margin-top:16px}
.delivery-request-fields label{display:flex;flex-direction:column;align-items:stretch;gap:6px;min-width:0;font-size:13px!important;line-height:1.7;color:inherit}
.delivery-request-fields input{box-sizing:border-box;width:100%;min-width:0;min-height:42px;margin-top:0!important;padding:9px 11px;border:1px solid #52677b;border-radius:8px;background:rgba(255,255,255,.04);color:inherit;font:inherit}
.delivery-request-fields input:focus{outline:2px solid #60a5fa;outline-offset:1px;border-color:#60a5fa}
.delivery-request-form>.btn{margin-top:18px!important}

/* Handover modal: clear hierarchy, RTL layout, and touch-friendly controls */
.handover-modal{display:none;position:fixed;inset:0;z-index:1000;background:rgba(8,18,32,.68);backdrop-filter:blur(3px);overflow-y:auto;padding:24px;align-items:center;justify-content:center;direction:rtl}
.handover-dialog{box-sizing:border-box;width:min(100%,720px);margin:auto;padding:0!important;overflow:hidden;border:1px solid rgba(148,163,184,.28);border-radius:18px;box-shadow:0 24px 80px rgba(0,0,0,.3)}
.handover-header{display:flex;align-items:flex-start;justify-content:space-between;gap:16px;padding:22px 24px;border-bottom:1px solid rgba(148,163,184,.22)}
.handover-title-wrap{display:flex;align-items:flex-start;gap:12px;min-width:0}
.handover-title-icon{display:grid;place-items:center;flex:0 0 42px;width:42px;height:42px;border-radius:12px;background:rgba(59,130,246,.12);color:#60a5fa;font-size:21px}
.handover-header h2{margin:0;font-size:20px;line-height:1.6}
.handover-subtitle{margin:4px 0 0;color:#8da2b7;font-size:13px;line-height:1.9}
.handover-close{flex:0 0 auto;min-width:38px;min-height:38px;padding:0!important;font-size:22px;line-height:1}
.handover-body{padding:22px 24px 8px}
.handover-section-title{margin:0 0 14px;font-size:14px;font-weight:700}
.handover-fields{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
.handover-field{display:flex;flex-direction:column;gap:7px;min-width:0;font-size:13px;font-weight:600;line-height:1.8}
.handover-field input,.handover-field textarea{box-sizing:border-box;width:100%;min-width:0;min-height:44px;margin:0!important;padding:10px 12px;border:1px solid #52677b;border-radius:10px;background:rgba(255,255,255,.035);color:inherit;font:inherit;font-weight:400;transition:border-color .15s,box-shadow .15s}
.handover-field textarea{min-height:88px;resize:vertical}
.handover-field input::placeholder,.handover-field textarea::placeholder{color:#8295a8;font-weight:400}
.handover-field input:focus,.handover-field textarea:focus{outline:none;border-color:#60a5fa;box-shadow:0 0 0 3px rgba(96,165,250,.16)}
.handover-required{color:#f87171;margin-right:3px}
.handover-optional{font-size:11px;font-weight:400;color:#8da2b7;margin-right:4px}
.handover-note-field{margin-top:18px}
.handover-signature{margin-top:22px}
.handover-signature-heading{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:9px}
.handover-signature-heading label{font-size:13px;font-weight:700}
.handover-signature-help{font-size:12px;color:#8da2b7;font-weight:400}
.handover-signature-pad{padding:10px;border:1px solid rgba(148,163,184,.35);border-radius:12px;background:rgba(148,163,184,.06)}
#signatureCanvas{display:block;width:100%;height:170px;border:1px dashed #9aaabd;border-radius:8px;background:#fff;touch-action:none;cursor:crosshair}
.handover-signature-tools{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-top:10px}
#signatureHint{font-size:12px;color:#8da2b7}
.handover-footer{display:flex;justify-content:flex-start;align-items:center;gap:10px;padding:18px 24px 22px;margin-top:12px;border-top:1px solid rgba(148,163,184,.18)}
.handover-footer .btn{min-height:42px;padding:9px 18px}
.handover-footer .btn.primary{min-width:175px}
@media(max-width:600px){
 .handover-modal{padding:10px;align-items:flex-start}
 .handover-dialog{border-radius:14px;margin:auto}
 .handover-header{padding:17px 16px}
 .handover-header h2{font-size:17px}
 .handover-title-icon{flex-basis:36px;width:36px;height:36px}
 .handover-body{padding:18px 16px 4px}
 .handover-fields{grid-template-columns:minmax(0,1fr);gap:13px}
 .handover-signature-heading{align-items:flex-start;flex-direction:column;gap:3px}
 #signatureCanvas{height:150px}
 .handover-footer{padding:16px;margin-top:10px}
 .handover-footer .btn{flex:1;padding:9px 10px}
 .handover-footer .btn.primary{min-width:0}
}
@media(max-width:900px){.delivery-request-fields{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:560px){.delivery-request-fields{grid-template-columns:minmax(0,1fr);gap:12px}}
@media(max-width:650px){.datepicker-container{position:fixed!important;top:50%!important;left:50%!important;right:auto!important;bottom:auto!important;transform:translate(-50%,-50%)!important;z-index:10000!important;max-width:calc(100vw - 20px)!important}.datepicker-plot-area{max-width:calc(100vw - 20px)!important;box-sizing:border-box!important}}
</style>
@endpush

@section('content')
<div class="page-heading"><div><h1>تحویل و ارسال</h1><p>ثبت درخواست تحویل، مدیریت خروج سفارش از انبار و ثبت رسید تحویل به مشتری</p></div></div>

@if($canCreateDeliveryRequest)
<div class="panel" style="margin-bottom:16px">
  <h2 style="font-size:14px;margin:0 0 14px">سفارش‌های آماده ثبت درخواست تحویل</h2>
  @forelse($eligibleOrders as $order)
    <form class="delivery-request-form" data-order-id="{{ $order->id }}" style="padding:14px 0;border-top:1px solid #29465e">
      <strong>سفارش {{ $order->number }}</strong> — {{ $order->customer_name }}
      <div class="delivery-request-fields">
        <label style="font-size:11px">تاریخ برنامه‌ریزی‌شده تحویل
          <input id="scheduled_date_picker_{{ $order->id }}" type="text" inputmode="none" autocomplete="off" placeholder="انتخاب تاریخ شمسی" required style="display:block;width:100%;margin-top:5px">
          <input id="scheduled_date_iso_{{ $order->id }}" type="hidden">
        </label>
        <label style="font-size:11px">ساعت برنامه‌ریزی‌شده تحویل
          <input name="scheduled_time" type="time" required style="display:block;width:100%;margin-top:5px">
        </label>
        <label style="font-size:11px">شخص تحویل‌گیرنده
          <input name="recipient_name" required maxlength="255" placeholder="نام تحویل‌گیرنده" style="display:block;width:100%;margin-top:5px">
        </label>
        <label style="font-size:11px">کد شخص تحویل‌گیرنده
          <input name="recipient_code" maxlength="100" placeholder="کد شخص" style="display:block;width:100%;margin-top:5px">
        </label>
        <label style="font-size:11px">نام راننده
          <input name="driver_name" maxlength="255" placeholder="نام راننده" style="display:block;width:100%;margin-top:5px">
        </label>
        <label style="font-size:11px">شماره بارنامه
          <input name="waybill_number" maxlength="100" placeholder="شماره بارنامه" style="display:block;width:100%;margin-top:5px">
        </label>
        <label style="font-size:11px">شماره وسیله نقلیه
          <input name="vehicle_number" maxlength="100" placeholder="شماره وسیله" style="display:block;width:100%;margin-top:5px">
        </label>
        <label style="font-size:11px">نوع وسیله نقلیه
          <input name="vehicle_type" maxlength="100" placeholder="مثلاً وانت، کامیون" style="display:block;width:100%;margin-top:5px">
        </label>
      </div>
      <button class="btn primary" type="submit" style="margin-top:12px">ثبت درخواست تحویل</button>
    </form>
  @empty
    <p style="color:#71879d;margin:0">سفارش آماده‌ای که هنوز درخواست تحویل نداشته باشد، وجود ندارد.</p>
  @endforelse
</div>
@endif

<div class="panel"><h2 style="font-size:14px;margin:0 0 14px">درخواست‌های ثبت‌شده</h2><div class="table-wrap"><table><thead><tr><th>سفارش</th><th>مشتری</th><th>تاریخ درخواستی تحویل</th><th>وضعیت</th><th>عملیات</th></tr></thead><tbody>
@forelse($deliveries as $d)
<tr>
<td>{{ $d->order_number }}</td><td>{{ $d->customer_name }}</td><td><time class="jalali-delivery-date" data-iso-date="{{ $d->requested_delivery_at ? substr((string) $d->requested_delivery_at, 0, 10) : '' }}">{{ $d->requested_delivery_at ? substr((string) $d->requested_delivery_at, 0, 10) : '—' }}</time></td>
<td>@if($d->status === 'awaiting_sales_approval')<span class="status inactive">در انتظار مجوز خروج فروش</span>@elseif($d->status === 'pending')<span class="status inactive">در انتظار خروج انبار</span>@elseif($d->status === 'issued')<span class="status active">خارج‌شده از انبار</span>@elseif(in_array($d->status, ['completed','delivered']))<span class="status active">تحویل‌شده</span>@else<span class="status">{{ $d->status }}</span>@endif</td>
<td>
@if($d->status === 'pending' && $canIssueDelivery)<button class="btn primary" type="button" data-recipient="{{ $d->recipient_name ?? $d->received_by ?? '' }}" data-driver="{{ $d->driver_name ?? $d->physical_driver_name ?? '' }}" data-plate="{{ $d->vehicle_plate ?? $d->physical_vehicle_plate ?? '' }}" data-phone="{{ $d->driver_phone ?? $d->physical_driver_phone ?? '' }}" data-note="{{ $d->handover_note ?? $d->physical_handover_note ?? '' }}" onclick="openPrepare({{ $d->id }}, this)">تکمیل مشخصات و خروج</button>
@elseif($d->status === 'issued')<button class="btn primary" type="button" data-recipient="{{ $d->recipient_name ?? $d->received_by ?? '' }}" data-driver="{{ $d->driver_name ?? $d->physical_driver_name ?? '' }}" data-plate="{{ $d->vehicle_plate ?? $d->physical_vehicle_plate ?? '' }}" data-phone="{{ $d->driver_phone ?? $d->physical_driver_phone ?? '' }}" data-note="{{ $d->handover_note ?? $d->physical_handover_note ?? '' }}" onclick="openHandover({{ $d->id }}, this)">ثبت امضای تحویل</button>
@elseif($d->received_by)<button class="btn" type="button" onclick="showReceipt({{ $d->id }})">مشاهده رسید</button>
@else<span style="color:#71879d">—</span>@endif
</td>
</tr>
@empty
<tr><td colspan="5" style="text-align:center;color:#71879d;padding:28px">درخواستی برای تحویل ثبت نشده است.</td></tr>
@endforelse
</tbody></table></div></div>

<div id="prepareModal" class="handover-modal" role="dialog" aria-modal="true" aria-labelledby="prepareTitle">
  <div class="panel handover-dialog">
    <header class="handover-header"><div class="handover-title-wrap"><span class="handover-title-icon" aria-hidden="true">▤</span><div>
      <h2 id="prepareTitle">تکمیل مشخصات پیش از خروج</h2>
      <p class="handover-subtitle">ابتدا نام تحویل‌گیرنده را ثبت کنید. سپس خروج انبار انجام می‌شود؛ امضا هنگام تحویل نهایی ثبت خواهد شد.</p>
    </div></div><button class="btn handover-close" type="button" aria-label="بستن فرم" onclick="closePrepare()">×</button></header>
    <form id="prepareForm" onsubmit="submitPrepare(event)">
      <div class="handover-body"><input type="hidden" id="prepareDeliveryId"><h3 class="handover-section-title">مشخصات تحویل و حمل‌ونقل</h3>
        <div class="handover-fields">
          <label class="handover-field" for="prepareRecipient">نام تحویل‌گیرنده <span class="handover-required">*</span><input id="prepareRecipient" required maxlength="255" autocomplete="name" placeholder="نام و نام خانوادگی تحویل‌گیرنده"></label>
          <label class="handover-field" for="prepareDriver">نام راننده <span class="handover-optional">(اختیاری)</span><input id="prepareDriver" maxlength="255" placeholder="نام و نام خانوادگی راننده"></label>
          <label class="handover-field" for="preparePlate">شماره پلاک خودرو <span class="handover-optional">(اختیاری)</span><input id="preparePlate" maxlength="30" placeholder="مثلاً ۱۲ ب ۳۴۵ ایران ۶۷"></label>
          <label class="handover-field" for="preparePhone">شماره تماس راننده <span class="handover-optional">(اختیاری)</span><input id="preparePhone" maxlength="30" inputmode="tel" autocomplete="tel" placeholder="شماره تماس راننده"></label>
        </div>
        <label class="handover-field handover-note-field" for="prepareNote">توضیحات <span class="handover-optional">(اختیاری)</span><textarea id="prepareNote" rows="3" maxlength="2000" placeholder="توضیحات مربوط به ارسال یا تحویل"></textarea></label>
      </div>
      <footer class="handover-footer"><button class="btn" type="button" onclick="closePrepare()">انصراف</button><button id="savePrepare" class="btn primary" type="submit">ثبت مشخصات و اجازه خروج</button></footer>
    </form>
  </div>
</div>

<div id="handoverModal" class="handover-modal" role="dialog" aria-modal="true" aria-labelledby="handoverTitle">
  <div class="panel handover-dialog">
    <header class="handover-header">
      <div class="handover-title-wrap">
        <span class="handover-title-icon" aria-hidden="true">✍</span>
        <div>
          <h2 id="handoverTitle">ثبت نهایی تحویل سفارش</h2>
          <p class="handover-subtitle">اطلاعات تحویل را تکمیل کنید و امضای تحویل‌گیرنده را ثبت کنید.</p>
        </div>
      </div>
      <button class="btn handover-close" type="button" aria-label="بستن فرم" onclick="closeHandover()">×</button>
    </header>
    <form id="handoverForm" onsubmit="submitHandover(event)">
      <div class="handover-body">
        <input type="hidden" id="handoverDeliveryId">
        <h3 class="handover-section-title">مشخصات تحویل و حمل‌ونقل</h3>
        <div class="handover-fields">
          <label class="handover-field" for="receivedBy">نام تحویل‌گیرنده <span class="handover-required">*</span>
            <input id="receivedBy" required maxlength="255" autocomplete="name" placeholder="نام و نام خانوادگی تحویل‌گیرنده">
          </label>
          <label class="handover-field" for="driverName">نام راننده <span class="handover-optional">(اختیاری)</span>
            <input id="driverName" maxlength="255" autocomplete="name" placeholder="نام و نام خانوادگی راننده">
          </label>
          <label class="handover-field" for="vehiclePlate">شماره پلاک خودرو <span class="handover-optional">(اختیاری)</span>
            <input id="vehiclePlate" maxlength="30" placeholder="مثلاً ۱۲ ب ۳۴۵ ایران ۶۷" autocomplete="off">
          </label>
          <label class="handover-field" for="driverPhone">شماره تماس راننده <span class="handover-optional">(اختیاری)</span>
            <input id="driverPhone" maxlength="30" inputmode="tel" autocomplete="tel" placeholder="مثلاً ۰۹۱۲۱۲۳۴۵۶۷">
          </label>
        </div>
        <label class="handover-field handover-note-field" for="handoverNote">توضیحات <span class="handover-optional">(اختیاری)</span>
          <textarea id="handoverNote" rows="3" maxlength="2000" placeholder="در صورت نیاز، توضیحات مربوط به تحویل را بنویسید"></textarea>
        </label>
        <section class="handover-signature" aria-labelledby="signatureLabel">
          <div class="handover-signature-heading">
            <label id="signatureLabel">امضای تحویل‌گیرنده <span class="handover-required">*</span></label>
            <span class="handover-signature-help">با ماوس یا لمس صفحه، داخل کادر امضا کنید.</span>
          </div>
          <div class="handover-signature-pad">
            <canvas id="signatureCanvas" width="620" height="180" aria-label="محل امضای تحویل‌گیرنده"></canvas>
            <div class="handover-signature-tools">
              <button type="button" class="btn" onclick="clearSignature()">پاک‌کردن امضا</button>
              <span id="signatureHint" role="status" aria-live="polite">امضا الزامی است.</span>
            </div>
          </div>
        </section>
      </div>
      <footer class="handover-footer">
        <button class="btn" type="button" onclick="closeHandover()">انصراف</button>
        <button id="saveHandover" class="btn primary" type="submit">ثبت نهایی تحویل</button>
      </footer>
    </form>
  </div>
</div>

<div id="receiptModal" style="display:none;position:fixed;inset:0;z-index:1001;background:#0008;overflow:auto;padding:20px">
  <div class="panel" style="max-width:600px;margin:30px auto;padding:24px">
    <div style="display:flex;justify-content:space-between;align-items:center"><h2 style="margin:0">رسید تحویل</h2><button class="btn" type="button" onclick="document.getElementById('receiptModal').style.display='none'">بستن</button></div>
    <div id="receiptDetails" style="line-height:2;margin-top:12px"></div>
    <img id="receiptSignature" alt="امضای ثبت‌شده تحویل‌گیرنده" style="display:block;max-width:100%;max-height:180px;margin-top:12px;border:1px solid #cbd5e1;border-radius:8px;background:#fff">
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/persian-date@1.1.0/dist/persian-date.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/js/persian-datepicker.min.js"></script>
<script type="module">
import { setupProductionReportDatePicker } from '/js/production-report-date-filters.js';
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.delivery-request-form').forEach(form => {
    const id = form.dataset.orderId;
    setupProductionReportDatePicker('#scheduled_date_picker_' + id, '#scheduled_date_iso_' + id);
  });
});
</script>
<script>
document.querySelectorAll('.jalali-delivery-date[data-iso-date]').forEach(cell=>{
  const iso=cell.dataset.isoDate;
  if(!/^\d{4}-\d{2}-\d{2}$/.test(iso))return;
  const [year,month,day]=iso.split('-').map(Number);
  const localDate=new Date(year,month-1,day,12);
  cell.textContent=new Intl.DateTimeFormat('fa-IR-u-ca-persian',{year:'numeric',month:'2-digit',day:'2-digit'}).format(localDate);
});
</script>
<script>
const token='{{ csrf_token() }}';
document.querySelectorAll('.delivery-request-form').forEach(form=>form.addEventListener('submit',async event=>{
  event.preventDefault();
  const submit=form.querySelector('button[type="submit"]');
  const data=new FormData(form);
  const date=form.querySelector('[id^="scheduled_date_iso_"]').value,time=data.get('scheduled_time');
  if(!date||!time){alert('تاریخ و ساعت تحویل را وارد کنید.');return}
  submit.disabled=true;submit.textContent='در حال ثبت…';
  try{
    await post('/api/orders/'+form.dataset.orderId+'/delivery-request',{
      scheduled_at:date+'T'+time,
      recipient_name:String(data.get('recipient_name')||'').trim(),
      recipient_code:String(data.get('recipient_code')||'').trim()||null,
      driver_name:String(data.get('driver_name')||'').trim()||null,
      waybill_number:String(data.get('waybill_number')||'').trim()||null,
      vehicle_number:String(data.get('vehicle_number')||'').trim()||null,
      vehicle_type:String(data.get('vehicle_type')||'').trim()||null,
      vehicle:String(data.get('vehicle_number')||'').trim()||null
    });
    location.reload();
  }catch(e){alert(e.message);submit.disabled=false;submit.textContent='ثبت درخواست تحویل'}
}));
async function post(url,data={}){const r=await fetch(url,{method:'POST',headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':token},body:JSON.stringify(data)});const j=await r.json();if(!r.ok){const first=j.errors?Object.values(j.errors).flat()[0]:null;throw Error(first||j.message||'خطا در ثبت اطلاعات')}return j}
const prepareModal=document.getElementById('prepareModal');
function openPrepare(id,button){document.getElementById('prepareDeliveryId').value=id;document.getElementById('prepareRecipient').value=button.dataset.recipient||'';document.getElementById('prepareDriver').value=button.dataset.driver||'';document.getElementById('preparePlate').value=button.dataset.plate||'';document.getElementById('preparePhone').value=button.dataset.phone||'';document.getElementById('prepareNote').value=button.dataset.note||'';prepareModal.style.display='flex';document.body.style.overflow='hidden';document.getElementById('prepareRecipient').focus()}
function closePrepare(){prepareModal.style.display='none';document.body.style.overflow=''}
prepareModal.addEventListener('click',e=>{if(e.target===prepareModal)closePrepare()});
async function submitPrepare(e){e.preventDefault();const button=document.getElementById('savePrepare');button.disabled=true;button.textContent='در حال ثبت…';const id=document.getElementById('prepareDeliveryId').value;try{await post('/api/delivery-requests/'+id+'/prepare',{received_by:document.getElementById('prepareRecipient').value.trim(),driver_name:document.getElementById('prepareDriver').value.trim()||null,vehicle_plate:document.getElementById('preparePlate').value.trim()||null,driver_phone:document.getElementById('preparePhone').value.trim()||null,handover_note:document.getElementById('prepareNote').value.trim()||null});await post('/api/delivery-requests/'+id+'/issue');location.reload()}catch(e){alert(e.message);button.disabled=false;button.textContent='ثبت مشخصات و اجازه خروج'}}
const modal=document.getElementById('handoverModal');
const canvas=document.getElementById('signatureCanvas');
const ctx=canvas.getContext('2d');
let drawing=false,hasSignature=false;
ctx.strokeStyle='#111827';ctx.lineWidth=2.5;ctx.lineCap='round';ctx.lineJoin='round';
function point(e){const r=canvas.getBoundingClientRect();return {x:(e.clientX-r.left)*canvas.width/r.width,y:(e.clientY-r.top)*canvas.height/r.height}}
canvas.addEventListener('pointerdown',e=>{drawing=true;hasSignature=true;canvas.setPointerCapture(e.pointerId);const p=point(e);ctx.beginPath();ctx.moveTo(p.x,p.y);ctx.lineTo(p.x+0.1,p.y+0.1);ctx.stroke();document.getElementById('signatureHint').textContent='امضا ثبت شده است.'});
canvas.addEventListener('pointermove',e=>{if(!drawing)return;const p=point(e);ctx.lineTo(p.x,p.y);ctx.stroke()});
for(const name of ['pointerup','pointercancel','lostpointercapture'])canvas.addEventListener(name,()=>{drawing=false});
function clearSignature(){ctx.clearRect(0,0,canvas.width,canvas.height);hasSignature=false;document.getElementById('signatureHint').textContent='امضا الزامی است.'}
function openHandover(id,button){document.getElementById('handoverDeliveryId').value=id;document.getElementById('receivedBy').value=button.dataset.recipient||'';document.getElementById('driverName').value=button.dataset.driver||'';document.getElementById('driverPhone').value=button.dataset.phone||'';document.getElementById('vehiclePlate').value=button.dataset.plate||'';document.getElementById('handoverNote').value=button.dataset.note||'';clearSignature();modal.style.display='flex';document.body.style.overflow='hidden';document.getElementById('receivedBy').focus()}
function closeHandover(){modal.style.display='none';document.body.style.overflow=''}
modal.addEventListener('click',e=>{if(e.target===modal)closeHandover()});
document.addEventListener('keydown',e=>{if(e.key==='Escape'&&modal.style.display==='flex')closeHandover()});
async function submitHandover(e){e.preventDefault();if(!hasSignature){alert('لطفاً امضای تحویل‌گیرنده را ثبت کنید.');return}const button=document.getElementById('saveHandover');button.disabled=true;button.textContent='در حال ثبت…';try{await post('/api/delivery-requests/'+document.getElementById('handoverDeliveryId').value+'/handover',{received_by:document.getElementById('receivedBy').value.trim(),driver_name:document.getElementById('driverName').value.trim(),driver_phone:document.getElementById('driverPhone').value.trim()||null,vehicle_plate:document.getElementById('vehiclePlate').value.trim(),handover_note:document.getElementById('handoverNote').value.trim()||null,recipient_signature:canvas.toDataURL('image/png')});location.reload()}catch(e){alert(e.message);button.disabled=false;button.textContent='ثبت نهایی تحویل'}}
async function showReceipt(id){try{const response=await fetch('/api/delivery-requests/'+id+'/receipt',{headers:{'Accept':'application/json'}});const payload=await response.json();if(!response.ok)throw Error(payload.message||'دریافت رسید ناموفق بود');const r=payload.data;const details=document.getElementById('receiptDetails');details.replaceChildren();for(const [label,value] of [['تحویل‌گیرنده',r.received_by],['راننده',r.driver_name],['شماره تماس راننده',r.driver_phone],['پلاک خودرو',r.vehicle_plate],['زمان تحویل',r.handed_over_at]]){if(!value)continue;const row=document.createElement('div');const strong=document.createElement('strong');strong.textContent=label+' : ';row.append(strong,document.createTextNode(value));details.append(row)}document.getElementById('receiptSignature').src=r.recipient_signature;document.getElementById('receiptModal').style.display='block'}catch(e){alert(e.message)}}
</script>
@endsection
