<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>کالای ساخته‌شده | حسابداری صنعتی</title>
<style>body{font-family:Tahoma,sans-serif;background:#f5f7fa;color:#172033;margin:0}.wrap{max-width:1100px;margin:40px auto;padding:0 18px}.card{background:#fff;border:1px solid #dfe5ec;border-radius:14px;padding:22px;margin-bottom:18px}.grid{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}label{display:block;font-size:13px;margin-bottom:6px}input,select,textarea,button{width:100%;padding:10px;border:1px solid #cfd7e2;border-radius:8px;font:inherit;box-sizing:border-box}button{cursor:pointer;background:#173b5c;color:#fff;border:0}.actions{display:flex;gap:8px}.actions button{width:auto;padding:8px 14px}.danger{background:#9b2c2c}.muted{color:#697386;font-size:12px}.ok{color:#176b43}.pending{color:#9a6700}table{width:100%;border-collapse:collapse}th,td{text-align:right;padding:10px;border-bottom:1px solid #edf0f4;font-size:12px}.empty{text-align:center;padding:25px;color:#7b8493}@media(max-width:800px){.grid{grid-template-columns:1fr}.card{overflow:auto}}</style>
</head>
<body><div class="wrap">
<div class="card"><h1>ورود کالای ساخته‌شده</h1><p class="muted">فقط تولیدهای تکمیل‌شده قابل ثبت هستند. ثبت خروجی موجودی را تغییر نمی‌دهد؛ موجودی پس از تأیید دریافت انبار افزایش می‌یابد.</p>
<form id="output-form"><div class="grid">
<div><label>تولید</label><select name="production_id" required><option value="">انتخاب کنید</option>@foreach($productions as $p)<option value="{{ $p->id }}">{{ $p->number }} — برنامه {{ $p->planned_quantity }}</option>@endforeach</select></div>
<div><label>سفارش مرتبط (اختیاری)</label><select name="order_id"><option value="">بدون سفارش</option>@foreach($orders as $o)<option value="{{ $o->id }}">{{ $o->number }}</option>@endforeach</select></div>
<div><label>انبار کالای ساخته‌شده</label><select name="warehouse_location_id" required><option value="">انتخاب کنید</option>@foreach($warehouses as $w)<option value="{{ $w->id }}">{{ $w->name }}</option>@endforeach</select></div>
<div><label>مقدار</label><input name="quantity" type="number" min="0.0001" step="0.0001" required></div>
<div><label>تاریخ/زمان تولید</label><input name="produced_at" type="datetime-local" required></div>
<div><label>توضیحات</label><textarea name="notes" rows="2"></textarea></div>
</div><div style="margin-top:14px"><button type="submit">ثبت خروجی در انتظار تأیید</button></div></form><div id="message" class="muted" style="margin-top:12px"></div></div>
<div class="card"><h2>خروجی‌ها و دریافت انبار</h2><table><thead><tr><th>تولید</th><th>سفارش</th><th>کالا</th><th>مقدار</th><th>انبار</th><th>وضعیت</th><th>عملیات</th></tr></thead><tbody>
@forelse($outputs as $output)<tr><td>{{ $output->production_number }}</td><td>{{ $output->order_number ?? '—' }}</td><td>{{ $output->goods_name }}</td><td>{{ $output->quantity }}</td><td>{{ $output->warehouse_name }}</td><td class="{{ $output->status === 'confirmed' ? 'ok' : ($output->status === 'pending' ? 'pending' : '') }}">{{ $output->status }}</td><td>@if($output->status === 'pending')<div class="actions"><button onclick="confirmOutput({{ $output->id }})">تأیید دریافت</button><button class="danger" onclick="rejectOutput({{ $output->id }})">رد</button></div>@else—@endif</td></tr>@empty<tr><td colspan="7" class="empty">هنوز خروجی ثبت نشده است.</td></tr>@endforelse
</tbody></table></div></div>
<script>
const csrf='{{ csrf_token() }}';
async function request(url,method='POST',body={}){const r=await fetch(url,{method,headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf,'Accept':'application/json'},body:JSON.stringify(body)});const data=await r.json().catch(()=>({}));if(!r.ok)throw new Error(data.message||Object.values(data.errors||{}).flat().join(' ')||'خطا در عملیات');return data;}
document.getElementById('output-form').addEventListener('submit',async e=>{e.preventDefault();const f=new FormData(e.target);try{await request('/api/production/outputs','POST',Object.fromEntries(f.entries()));location.reload()}catch(err){document.getElementById('message').textContent=err.message}});
async function confirmOutput(id){try{await request('/api/production/outputs/'+id+'/confirm');location.reload()}catch(e){alert(e.message)}}
async function rejectOutput(id){const reason=prompt('علت رد (اختیاری):')??'';try{await request('/api/production/outputs/'+id+'/reject','POST',{reason});location.reload()}catch(e){alert(e.message)}}
</script></body></html>