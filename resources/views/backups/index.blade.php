<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>پشتیبان‌گیری | حسابداری صنعتی</title>
<style>
body{margin:0;background:#07111f;color:#e9f3fb;font-family:Tahoma,sans-serif}.wrap{max-width:1100px;margin:40px auto;padding:0 20px}.card{background:#0a1b2b;border:1px solid #1b354b;border-radius:16px;padding:22px;margin-bottom:18px}h1{margin:0 0 8px}.muted{color:#8ba1b5;font-size:13px}.row{display:flex;gap:12px;align-items:center;flex-wrap:wrap}button{border:0;border-radius:10px;padding:11px 16px;cursor:pointer;background:#6ee7d0;color:#04121a;font-weight:700}input{padding:10px;border-radius:9px;border:1px solid #29445e;background:#081522;color:#fff}a{color:#6ee7d0;text-decoration:none}.danger{background:#efb0b0}.status{margin-top:12px;white-space:pre-wrap}.item{padding:12px 0;border-bottom:1px solid #173047;display:flex;justify-content:space-between;gap:15px;align-items:center}.item:last-child{border-bottom:0}
</style></head>
<body><div class="wrap">
<div class="card"><h1>پشتیبان‌گیری و بازیابی</h1><div class="muted">پشتیبان این صفحه فقط اطلاعات شرکت انتخاب‌شده را پوشش می‌دهد و به کاربران، نقش‌ها و مجوزهای امنیتی دست نمی‌زند.</div></div>
<div class="card"><h2>ایجاد Backup</h2><p class="muted">ابتدا فایل پشتیبان ساخته می‌شود؛ سپس از فهرست زیر قابل دریافت است.</p><button id="create">ایجاد Backup</button><div id="createStatus" class="status"></div></div>
<div class="card"><h2>بارگذاری Backup</h2><form id="uploadForm" class="row"><input id="backupFile" type="file" accept=".json,application/json" required><button>اعتبارسنجی و بارگذاری</button></form><div id="uploadStatus" class="status"></div></div>
<div class="card"><h2>Backupهای موجود</h2><div id="list">در حال بارگذاری…</div></div>
<div class="card"><a href="{{ route('imports.excel.page') }}">ورود اطلاعات از Excel →</a></div>
</div>
<script>
const csrf=document.querySelector('meta[name="csrf-token"]')?.content||'{{ csrf_token() }}';
async function json(url,options={}){options.headers={...(options.headers||{}),'X-CSRF-TOKEN':csrf,'Accept':'application/json'};const r=await fetch(url,options);const d=await r.json().catch(()=>({message:'پاسخ نامعتبر از سرور'}));if(!r.ok)throw new Error(d.message||'عملیات ناموفق بود');return d}
function show(id,msg){document.getElementById(id).textContent=msg}
async function load(){const d=await json('{{ route('backups.index') }}');const el=document.getElementById('list');if(!d.backups.length){el.textContent='Backupای ثبت نشده است.';return}el.innerHTML=d.backups.map(b=>{const restore=b.status==='ready'?'<button class="danger" data-restore="'+b.id+'">Restore</button>':'';return '<div class="item"><div><b>'+escapeHtml(b.original_name)+'</b><div class="muted">'+escapeHtml(b.type)+' · '+escapeHtml(String(b.size))+' bytes · '+escapeHtml(b.status)+'</div></div><div class="row"><a href="{{ url('/backups') }}/'+b.id+'/download">دریافت</a>'+restore+'</div></div>'}).join('');document.querySelectorAll('[data-restore]').forEach(btn=>btn.onclick=async()=>{const confirmation=prompt('برای بازیابی، دقیقاً RESTORE را وارد کنید:');if(confirmation===null)return;try{await json('{{ url('/backups') }}/'+btn.dataset.restore+'/restore',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({confirmation})});alert('Restore با موفقیت انجام شد.');location.reload()}catch(e){alert(e.message)}})}
document.getElementById('create').onclick=async()=>{show('createStatus','در حال ایجاد…');try{await json('{{ route('backups.create') }}',{method:'POST'});show('createStatus','Backup ایجاد شد.');load()}catch(e){show('createStatus',e.message)}};
document.getElementById('uploadForm').onsubmit=async e=>{e.preventDefault();show('uploadStatus','در حال بررسی…');const fd=new FormData();fd.append('backup',document.getElementById('backupFile').files[0]);try{await json('{{ route('backups.upload') }}',{method:'POST',body:fd});show('uploadStatus','Backup معتبر بارگذاری شد.');e.target.reset();load()}catch(e){show('uploadStatus',e.message)}};
function escapeHtml(s){return s.replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]))}
load().catch(e=>show('list',e.message));
</script></body></html>