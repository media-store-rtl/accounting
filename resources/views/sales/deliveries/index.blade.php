@extends('layouts.dashboard-shell')

@section('title', 'تحویل و ارسال')

@section('content')
<div class="page-heading"><div><h1>تحویل و ارسال</h1><p>مدیریت خروج سفارش از انبار و ثبت رسید تحویل به مشتری</p></div><a class="btn" href="{{ route('sales.orders.index') }}">مشاهده سفارش‌های فروش</a></div>
<div class="panel"><div class="table-wrap"><table><thead><tr><th>سفارش</th><th>مشتری</th><th>زمان تحویل</th><th>وضعیت</th><th>راننده / خودرو</th><th>عملیات</th></tr></thead><tbody>
@forelse($deliveries as $d)
<tr>
<td>{{ $d->order_number }}</td><td>{{ $d->customer_name }}</td><td>{{ $d->scheduled_at }}</td>
<td>@if($d->status === 'pending')<span class="status inactive">در انتظار خروج</span>@elseif($d->status === 'issued')<span class="status active">خارج‌شده از انبار</span>@elseif(in_array($d->status, ['completed','delivered']))<span class="status active">تحویل‌شده</span>@else<span class="status">{{ $d->status }}</span>@endif</td>
<td>@if($d->vehicle_plate){{ $d->vehicle_plate }}<br><small>{{ $d->driver_name }}</small>@else<span style="color:#71879d">—</span>@endif</td>
<td>
@if($d->status === 'pending')<button class="btn primary" type="button" onclick="issue({{ $d->id }})">خروج از انبار</button>
@elseif($d->status === 'issued')<button class="btn primary" type="button" onclick="openHandover({{ $d->id }}, @js($d->recipient_name))">ثبت تحویل و امضا</button>
@elseif($d->recipient_signature)<button class="btn" type="button" onclick="showReceipt(@js($d->received_by), @js($d->driver_name), @js($d->driver_phone), @js($d->vehicle_plate), @js($d->recipient_signature), @js($d->handed_over_at))">مشاهده رسید</button>
@else<span style="color:#71879d">—</span>@endif
</td>
</tr>
@empty
<tr><td colspan="6" style="text-align:center;color:#71879d;padding:28px">درخواستی برای تحویل ثبت نشده است.</td></tr>
@endforelse
</tbody></table></div></div>

<div id="handoverModal" style="display:none;position:fixed;inset:0;z-index:1000;background:#0008;overflow:auto;padding:20px">
  <div class="panel" style="max-width:680px;margin:30px auto;padding:24px">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px"><h2 style="margin:0">فرم ثبت تحویل سفارش</h2><button class="btn" type="button" onclick="closeHandover()">بستن</button></div>
    <p style="color:#71879d">مشخصات راننده و خودرو را وارد کنید و امضای تحویل‌گیرنده را ثبت کنید.</p>
    <form id="handoverForm" onsubmit="submitHandover(event)">
      <input type="hidden" id="handoverDeliveryId">
      <div class="form-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px">
        <label>نام تحویل‌گیرنده *
          <input id="receivedBy" required maxlength="255" autocomplete="name" style="width:100%">
        </label>
        <label>نام راننده *
          <input id="driverName" required maxlength="255" style="width:100%">
        </label>
        <label>شماره پلاک خودرو *
          <input id="vehiclePlate" required maxlength="30" placeholder="مثلاً ۱۲ ب ۳۴۵ ایران ۶۷" style="width:100%">
        </label>
        <label>شماره تماس راننده
          <input id="driverPhone" maxlength="30" inputmode="tel" style="width:100%">
        </label>
      </div>
      <label style="display:block;margin-top:14px">توضیحات (اختیاری)
        <textarea id="handoverNote" rows="2" maxlength="2000" style="width:100%"></textarea>
      </label>
      <div style="margin-top:16px">
        <label style="display:block;margin-bottom:8px">امضای تحویل‌گیرنده * <small>(با ماوس یا لمس روی صفحه امضا کنید)</small></label>
        <canvas id="signatureCanvas" width="620" height="180" aria-label="محل امضای تحویل‌گیرنده" style="display:block;width:100%;max-width:620px;height:180px;border:1px solid #cbd5e1;border-radius:8px;background:#fff;touch-action:none"></canvas>
        <div style="display:flex;gap:8px;margin-top:8px"><button type="button" class="btn" onclick="clearSignature()">پاک‌کردن امضا</button><span id="signatureHint" style="align-self:center;color:#71879d;font-size:13px">امضا الزامی است.</span></div>
      </div>
      <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:20px"><button class="btn" type="button" onclick="closeHandover()">انصراف</button><button id="saveHandover" class="btn primary" type="submit">ثبت نهایی تحویل</button></div>
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

<script>
const token='{{ csrf_token() }}';
async function post(url,data={}){const r=await fetch(url,{method:'POST',headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':token},body:JSON.stringify(data)});const j=await r.json();if(!r.ok){const first=j.errors?Object.values(j.errors).flat()[0]:null;throw Error(first||j.message||'خطا در ثبت اطلاعات')}return j}
async function issue(id){if(!confirm('خروج این سفارش از انبار ثبت شود؟'))return;try{await post('/api/delivery-requests/'+id+'/issue');location.reload()}catch(e){alert(e.message)}}
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
function openHandover(id,recipient){document.getElementById('handoverDeliveryId').value=id;document.getElementById('receivedBy').value=recipient||'';document.getElementById('driverName').value='';document.getElementById('driverPhone').value='';document.getElementById('vehiclePlate').value='';document.getElementById('handoverNote').value='';clearSignature();modal.style.display='block'}
function closeHandover(){modal.style.display='none'}
async function submitHandover(e){e.preventDefault();if(!hasSignature){alert('لطفاً امضای تحویل‌گیرنده را ثبت کنید.');return}const button=document.getElementById('saveHandover');button.disabled=true;button.textContent='در حال ثبت…';try{await post('/api/delivery-requests/'+document.getElementById('handoverDeliveryId').value+'/handover',{received_by:document.getElementById('receivedBy').value.trim(),driver_name:document.getElementById('driverName').value.trim(),driver_phone:document.getElementById('driverPhone').value.trim()||null,vehicle_plate:document.getElementById('vehiclePlate').value.trim(),handover_note:document.getElementById('handoverNote').value.trim()||null,recipient_signature:canvas.toDataURL('image/png')});location.reload()}catch(e){alert(e.message);button.disabled=false;button.textContent='ثبت نهایی تحویل'}}
function showReceipt(recipient,driver,phone,plate,signature,at){const details=document.getElementById('receiptDetails');details.replaceChildren();for(const [label,value] of [['تحویل‌گیرنده',recipient],['راننده',driver],['شماره تماس راننده',phone],['پلاک خودرو',plate],['زمان تحویل',at]]){if(!value)continue;const row=document.createElement('div');const strong=document.createElement('strong');strong.textContent=label+' : ';row.append(strong,document.createTextNode(value));details.append(row)}document.getElementById('receiptSignature').src=signature;document.getElementById('receiptModal').style.display='block'}
</script>
@endsection
