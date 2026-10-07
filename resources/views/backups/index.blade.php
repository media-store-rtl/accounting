<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>پشتیبان‌گیری و بازیابی | حسابداری صنعتی</title>
<style>
body{font-family:Tahoma,sans-serif;background:#07111f;color:#e9f3fb;margin:0;padding:28px}.wrap{max-width:1100px;margin:auto}.card{background:#0a1b2b;border:1px solid #1b354b;border-radius:16px;padding:20px;margin-bottom:18px}h1,h2{margin-top:0}button{background:#173b54;color:#e9f3fb;border:1px solid #2b5873;border-radius:9px;padding:9px 14px;cursor:pointer}button.danger{background:#5a2730}input{margin:8px 0;padding:9px;background:#071725;color:#fff;border:1px solid #29465e;border-radius:8px;width:100%}.row{display:grid;grid-template-columns:1fr 1fr;gap:18px}.muted{color:#8aa0b3;font-size:13px}.msg{margin-top:12px;padding:10px;border-radius:8px;background:#10283c;white-space:pre-wrap}.item{display:flex;justify-content:space-between;gap:12px;align-items:center;padding:12px 0;border-bottom:1px solid #173047}.item:last-child{border-bottom:0}.actions{display:flex;gap:7px;flex-wrap:wrap}@media(max-width:760px){.row{grid-template-columns:1fr}.item{display:block}.actions{margin-top:8px}}
</style>
</head>
<body><div class="wrap">
<div class="card"><h1>پشتیبان‌گیری و بازیابی</h1><p class="muted">ایجاد، دریافت، بارگذاری و بازیابی چهار عملیات جداگانه هستند. بازیابی فقط با مجوز مربوط و تأیید صریح انجام می‌شود.</p></div>
<div class="row">
<div class="card"><h2>ایجاد Backup</h2><p class="muted">از داده‌های فعلی سیستم یک فایل پشتیبان معتبر ایجاد می‌کند.</p><button id="create">ایجاد Backup</button><div id="create-msg" class="msg" hidden></div></div>
<div class="card"><h2>بارگذاری Backup</h2><form id="upload"><input type="file" name="backup" accept=".json,application/json" required><button>بارگذاری و اعتبارسنجی</button></form><div id="upload-msg" class="msg" hidden></div></div>
</div>
<div class="card"><h2>Backupهای موجود</h2><div id="list" class="muted">در حال دریافت...</div></div>
</div>
<script>
const csrf='{{ csrf_token() }}';
const jsonOpts={headers:{'Accept':'application/json','X-CSRF-TOKEN':csrf}};
function msg(id,text){const e=document.getElementById(id);e.hidden=false;e.textContent=text}
async function call(url,opts={}){const r=await fetch(url,{...opts,...jsonOpts});let d={};try{d=await r.json()}catch{}if(!r.ok)throw new Error(d.message||d.error||'عملیات ناموفق بود.');return d}
async function refresh(){try{const d=await call('{{ route('backups.index') }}');const el=document.getElementById('list');if(!d.backups.length){el.textContent='Backupای ثبت نشده است.';return}el.innerHTML=d.backups.map(b=>'<div class="item"><div><strong>'+escapeHtml(b.original_name)+'</strong><div class="muted">'+escapeHtml(b.type)+' · '+escapeHtml(b.status)+' · '+escapeHtml(String(b.size))+' bytes</div></div><div class="actions"><a href="/backups/'+encodeURIComponent(b.id)+'/download"><button type="button">دریافت</button></a>'+ (b.status==='ready'?'<button type="button" class="danger" onclick="restoreBackup(\''+b.id+'\')">بازیابی</button>':'')+'</div></div>').join('')}catch(e){document.getElementById('list').textContent=e.message}}
function escapeHtml(v){return String(v).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]))}
document.getElementById('create').onclick=async()=>{try{const d=await call('{{ route('backups.create') }}',{method:'POST'});msg('create-msg','Backup با موفقیت ایجاد شد: '+d.backup.original_name);refresh()}catch(e){msg('create-msg',e.message)}}
document.getElementById('upload').onsubmit=async e=>{e.preventDefault();try{const fd=new FormData(e.target);const d=await call('{{ route('backups.upload') }}',{method:'POST',body:fd});msg('upload-msg','فایل معتبر است و ذخیره شد: '+d.backup.original_name);e.target.reset();refresh()}catch(e){msg('upload-msg',e.message)}}
async function restoreBackup(id){const confirmation=prompt('بازیابی می‌تواند داده‌های فعلی را با Backup جایگزین کند. برای ادامه دقیقاً RESTORE را وارد کنید.');if(confirmation!=='RESTORE'){return}try{await call('/backups/'+encodeURIComponent(id)+'/restore',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({confirmation})});alert('بازیابی با موفقیت انجام شد.');refresh()}catch(e){alert(e.message)}}
refresh();
</script></body></html>