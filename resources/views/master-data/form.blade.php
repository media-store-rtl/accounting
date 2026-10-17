@extends(in_array(($module ?? null), ['goods', 'goods-categories', 'units', 'locations', 'productions', 'suppliers', 'production-routes', 'production-sections', 'production-stages', 'production-operations'], true) ? 'layouts.dashboard-shell' : 'layouts.app')
@if(in_array(($module ?? null), ['goods', 'goods-categories', 'units', 'locations', 'productions', 'suppliers', 'production-routes', 'production-sections', 'production-stages', 'production-operations'], true))
@section('title', (($editing ?? false) ? 'ویرایش ' : 'ثبت ') . ($definition['title'] ?? 'اطلاعات پایه'))
@endif
@section('content')
@php
$labels=['code'=>'کد','name'=>'نام','parent_id'=>'دسته والد','symbol'=>'نماد','unit_type'=>'نوع واحد','national_id'=>'شناسه ملی','phone'=>'تلفن','email'=>'ایمیل','address'=>'آدرس','category_id'=>'دسته‌بندی','unit_id'=>'واحد اندازه‌گیری','purchasable'=>'قابل خرید','producible'=>'قابل تولید','sellable'=>'قابل فروش','location_id'=>'محل / انبار','supervisor_personnel_id'=>'سرپرست','type'=>'نوع','production_route_id'=>'مسیر تولید','production_section_id'=>'قسمت تولید','production_stage_id'=>'مرحله تولید','sequence'=>'ترتیب','standard_duration_minutes'=>'مدت استاندارد (دقیقه)','setup_duration_minutes'=>'زمان آماده‌سازی (دقیقه)','status'=>'وضعیت','number'=>'شماره','goods_id'=>'کالا','fiscal_year_id'=>'سال مالی','planned_quantity'=>'مقدار برنامه‌ریزی‌شده','planned_start_at'=>'شروع برنامه‌ریزی‌شده','planned_end_at'=>'پایان برنامه‌ریزی‌شده','description'=>'توضیحات','notes'=>'یادداشت'];
@endphp
@if(in_array(($module ?? null), ['goods', 'goods-categories', 'units', 'locations', 'productions', 'suppliers', 'production-routes', 'production-sections', 'production-stages', 'production-operations'], true))
<style>
.master-form-page{max-width:1100px;margin:0 auto}
.master-form-page .master-heading{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:20px}
.master-form-page h1{font-size:24px;margin:0 0 6px}
.master-form-page .text-muted{color:#71879d;font-size:11px}
.master-form-page .small{font-size:11px}
.master-form-page .mb-1{margin-bottom:4px}
.master-form-page .mb-4{margin-bottom:20px}
.master-form-page .master-card{background:#0a1b2b;border:1px solid #1b354b;border-radius:16px;padding:22px}
.master-form-page .row{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px 24px;margin:0}
.master-form-page .col-md-6{min-width:0;padding:0}
.master-form-page .form-field{display:flex;flex-direction:column;gap:7px}
.master-form-page .field-help{color:#8da5ba;font-size:11px;line-height:1.8;margin:0}
.master-form-page .form-actions{display:flex;align-items:center;gap:10px;flex-wrap:wrap;border-top:1px solid #1b354b;padding-top:18px}
.master-form-page .form-actions .btn{min-width:130px;justify-content:center}
.master-form-page .section-form .form-section-title{font-size:14px;margin:0 0 4px;color:#e9f3fb}
.master-form-page .section-form .form-section-intro{font-size:12px;color:#8da5ba;margin:0 0 18px;line-height:1.9}
.master-form-page .section-form .form-label{margin-bottom:0}
.master-form-page .section-form .form-control,.master-form-page .section-form .form-select{min-height:46px}
.master-form-page .form-label{display:block;color:#c5d5e3;font-size:11px;font-weight:600;margin-bottom:8px}
.master-form-page .form-control,.master-form-page .form-select{width:100%;background:#081522;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px 12px;font:inherit;font-size:12px;min-height:42px}
.master-form-page textarea.form-control{resize:vertical}
.master-form-page .form-check{display:flex;align-items:center;gap:10px;color:#c5d5e3;font-size:11px}
.master-form-page .form-check-input{width:18px;height:18px;accent-color:#6ee7d0}
.master-form-page .pt-2{padding-top:8px}
.master-form-page .mt-4{margin-top:22px}
.master-form-page .alert{border-radius:10px;padding:12px;margin-bottom:16px;font-size:11px}
.master-form-page .alert-danger{background:#3b2528;border:1px solid #63343b;color:#ffb8a8}
.master-form-page .text-danger{color:#ffb8a8}
.master-form-page .px-4{padding-left:22px;padding-right:22px}
.master-form-page .btn{cursor:pointer}
@media(max-width:620px){.master-form-page .row{grid-template-columns:1fr;gap:16px}.master-form-page .master-heading{align-items:flex-start;flex-direction:column}.master-form-page .master-card{padding:15px}.master-form-page h1{font-size:21px}.master-form-page .form-actions .btn{width:100%}}
</style>
@endif
<div class="{{ in_array(($module ?? null), ['goods', 'goods-categories', 'units', 'locations', 'productions', 'suppliers', 'production-routes', 'production-sections', 'production-stages', 'production-operations'], true) ? 'master-form-page' : 'container py-3' }}" dir="rtl">
<div class="{{ in_array(($module ?? null), ['goods', 'goods-categories', 'units', 'locations', 'productions', 'suppliers', 'production-routes', 'production-sections', 'production-stages', 'production-operations'], true) ? 'master-heading' : 'd-flex justify-content-between align-items-center mb-4' }}"><div><h1 class="{{ in_array(($module ?? null), ['goods', 'goods-categories', 'units', 'locations', 'productions', 'suppliers', 'production-routes', 'production-sections', 'production-stages', 'production-operations'], true) ? '' : 'h3 mb-1' }}">{{ in_array(($module ?? null), ['goods', 'goods-categories', 'units', 'locations', 'productions', 'suppliers', 'production-routes', 'production-sections', 'production-stages', 'production-operations'], true) ? ((($editing ?? false) ? 'ویرایش اطلاعات ' : 'ثبت اطلاعات ') . ($definition['title'] ?? '')) : $definition['title'] }}</h1><div class="text-muted small">{{ ($editing ?? false) ? 'ویرایش اطلاعات ثبت‌شده' : 'ثبت اطلاعات جدید' }}</div></div><a href="{{route('master.index',$module)}}" class="btn btn-outline-secondary">بازگشت به فهرست {{ $definition['title'] }}</a></div>
@if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $e)<div>{{$e}}</div>@endforeach</div>@endif
@if(($module ?? null) === 'productions')
<div class="master-card" style="margin-bottom:18px;line-height:2">
<h2 style="font-size:15px;margin:0 0 8px">راهنمای ثبت دستور تولید</h2>
<p>این فرم برای برنامه‌ریزی تولید یک کالا است. پیش از ثبت، کالا باید در بخش کالاها قابل تولید باشد و مسیر تولید و مراحل آن تعریف شده باشند.</p>
<ul>
<li><b>شماره:</b> شناسه یکتای دستور برای پیگیری است؛ از الگوی ثابت مجموعه استفاده کنید، مانند PR-1405-001.</li>
<li><b>کالا:</b> محصولی که قرار است ساخته شود. فقط کالاهای فعال و قابل تولید نمایش داده می‌شوند.</li>
<li><b>مسیر تولید:</b> مسیر تعریف‌شده ساخت محصول، شامل مراحل و عملیات آن.</li>
<li><b>سال مالی:</b> سال مالی مربوط به این دستور را انتخاب کنید.</li>
<li><b>مقدار برنامه‌ریزی‌شده:</b> مقدار هدف تولید بر اساس واحد تعریف‌شده برای کالا.</li>
<li><b>شروع برنامه‌ریزی‌شده:</b> زمان پیش‌بینی‌شده شروع کار.</li>
<li><b>پایان برنامه‌ریزی‌شده:</b> زمان پیش‌بینی‌شده پایان کار؛ با توجه به مقدار و مراحل تولید تعیین شود.</li>
</ul>
<p>زمان‌های بالا برنامه‌ریزی هستند، نه زمان واقعی اجرا. ثبت دستور به‌تنهایی تولید را شروع نمی‌کند.</p>
</div>
@endif

@if(($module ?? null) === 'production-sections')
<div class="master-card" style="margin-bottom:18px;line-height:2">
<h2 style="font-size:15px;margin:0 0 8px">راهنمای ثبت قسمت تولید</h2>
<p>قسمت تولید، محل یا واحدی است که یک بخش از فرایند ساخت در آن انجام می‌شود؛ مانند کارگاه برش، سالن مونتاژ یا واحد بسته‌بندی. بعداً می‌توانید این قسمت را به مرحله مرتبط در مسیر تولید وصل کنید.</p>
<ul>
<li><b>محل / انبار:</b> محل فیزیکی یا سازمانی مربوط به این قسمت را انتخاب کنید. اگر فهرست محل‌ها خالی است، ابتدا از بخش «انبارها و محل‌ها» محل مناسب را تعریف کنید.</li>
<li><b>سرپرست:</b> در صورت مشخص بودن مسئول قسمت، پرسنل سرپرست را انتخاب کنید. اگر هنوز تعیین نشده، می‌توانید این مورد را خالی بگذارید.</li>
</ul>
<p><b>ترتیب پیشنهادی:</b> ابتدا محل را تعریف کنید؛ سپس قسمت تولید را ثبت کنید؛ بعد در «مراحل تولید» آن را به مرحله مناسب وصل کنید و مرحله را به مسیر مربوط اختصاص دهید.</p>
<p style="color:#ffd7a1"><b>توجه:</b> ثبت قسمت تولید به‌تنهایی مرحله فعال ایجاد نمی‌کند. برای شروع دستور تولید، مسیر انتخاب‌شده باید دست‌کم یک مرحله فعال داشته باشد.</p>
</div>
@endif

@if(($module ?? null) === 'production-stages')
<div class="master-card" style="margin-bottom:18px;line-height:2">
<h2 style="font-size:15px;margin:0 0 8px">راهنمای ثبت مرحله تولید</h2>
<p>مرحله تولید، یکی از گام‌های مسیر ساخت یک کالا است؛ مانند برش، دوخت، مونتاژ یا بسته‌بندی. برای اینکه دستور تولید بتواند شروع شود، باید حداقل یک مرحله فعال به همان مسیر تولید متصل باشد.</p>
<ul>
<li><b>کد:</b> شناسه کوتاه و یکتای مرحله در همان مسیر وارد کنید؛ مانند CUT-01 یا PACK-01. کد تکراری در یک مسیر قابل ثبت نیست.</li>
<li><b>نام:</b> نام قابل فهم مرحله را بنویسید؛ مانند «برش پارچه» یا «بسته‌بندی نهایی».</li>
<li><b>مسیر تولید:</b> مسیر مربوط به همین محصول را انتخاب کنید. مرحله باید به همان مسیری متصل باشد که در دستور تولید انتخاب می‌شود.</li>
<li><b>قسمت تولید:</b> محل یا واحد مسئول انجام این مرحله را انتخاب کنید. اگر فهرست خالی است، ابتدا در اطلاعات پایه، قسمت تولید و محل مربوط به آن را تعریف کنید.</li>
<li><b>ترتیب:</b> عددی برای ترتیب اجرای مرحله وارد کنید؛ عدد کمتر معمولاً به معنی اجرای زودتر است. برای نمونه: برش 1، دوخت 2، بسته‌بندی 3.</li>
<li><b>وضعیت:</b> «فعال» را انتخاب کنید تا مرحله در شروع دستور تولید لحاظ شود. مرحله غیرفعال به اجرای تولید اضافه نمی‌شود.</li>
</ul>
<p><b>ترتیب کار پیشنهادی:</b> ابتدا مسیر تولید را تعریف و فعال کنید؛ سپس مراحل آن را در این صفحه ثبت و فعال کنید؛ بعد در بخش «عملیات تولید» عملیات هر مرحله را بسازید. در پایان، در دستور تولید همان مسیر را انتخاب و شروع کنید.</p>
<p style="color:#ffd7a1"><b>توجه:</b> اگر با وجود ثبت مرحله هنوز پیام «مسیر تولید مرحله فعالی ندارد» نمایش داده می‌شود، بررسی کنید مرحله به مسیر درست وصل باشد و وضعیت آن «فعال» باشد. این راهنما به‌تنهایی اطلاعات پایگاه داده را تغییر نمی‌دهد.</p>
</div>
@endif

@if(($module ?? null) === 'production-routes')
<div class="master-card" style="margin-bottom:18px;line-height:2">
<h2 style="font-size:15px;margin:0 0 8px">راهنمای تعریف مسیر تولید</h2>
<p>مسیر تولید نقشه کلی ساخت یک کالا است. در این صفحه خود مسیر را تعریف می‌کنید؛ مراحل و عملیات آن در بخش‌های جداگانه ساخته می‌شوند.</p>
<ul>
<li><b>کد:</b> شناسه قابل پیگیری مسیر را وارد کنید و از کد تکراری پرهیز کنید.</li>
<li><b>نام:</b> نامی روشن برای مسیر انتخاب کنید؛ مانند «مسیر تولید محصول الف».</li>
<li><b>کالا:</b> مشخص کنید این مسیر برای ساخت کدام کالا است.</li>
<li><b>توضیحات:</b> نکته‌های خاص یا کاربرد مسیر را در صورت نیاز بنویسید.</li>
<li><b>وضعیت:</b> از فهرست زیر، «فعال» را انتخاب کنید تا مسیر برای استفاده در دستور تولید قابل انتخاب باشد. نیازی به تایپ کلمه انگلیسی نیست. مراحل و عملیات موردنیاز را نیز جداگانه فعال کنید.</li>
</ul>
<p>پس از ثبت مسیر، در بخش «مراحل تولید» مرحله‌ها و ترتیب اجرای آن‌ها را تعریف کنید؛ سپس در بخش «عملیات تولید» عملیات هر مرحله را مشخص کنید. این صفحه برای ثبت نتیجه اجرای واقعی عملیات نیست.</p>
</div>
@endif

@if(($module ?? null) === 'production-operations')
<div class="master-card" style="margin-bottom:18px;line-height:2">
<h2 style="font-size:15px;margin:0 0 8px">راهنمای ثبت عملیات تولید</h2>
<p>عملیات تولید، یک فعالیت مشخص درون یک مرحله تولید است؛ مانند برش، دوخت، مونتاژ، کنترل کیفیت یا بسته‌بندی. در این صفحه تعریف می‌کنید هر عملیات در کدام مرحله انجام شود و چه زمان استانداردی برای آن در نظر گرفته شده است.</p>
<ul>
<li><b>کد:</b> یک شناسه کوتاه و قابل پیگیری برای عملیات وارد کنید؛ بهتر است در همان مرحله تکراری نباشد، مانند CUT-01 یا QC-01.</li>
<li><b>نام:</b> نام روشن فعالیت را بنویسید؛ مانند «برش قطعات»، «دوخت نهایی» یا «کنترل کیفیت».</li>
<li><b>مرحله تولید:</b> مرحله‌ای را انتخاب کنید که این عملیات متعلق به آن است. عملیات بدون اتصال به مرحله، در ترتیب واقعی مسیر تولید جای مشخصی ندارد.</li>
<li><b>ترتیب:</b> ترتیب اجرای عملیات در همان مرحله را مشخص کنید؛ برای عملیات متعدد از اعداد 1، 2، 3 و مانند آن استفاده کنید.</li>
<li><b>مدت استاندارد:</b> زمان معمول موردنیاز برای انجام عملیات را بر حسب دقیقه وارد کنید. این مقدار مبنای برنامه‌ریزی زمانی عملیات است.</li>
<li><b>زمان آماده‌سازی:</b> زمان لازم برای آماده‌کردن دستگاه، مواد یا محیط کار پیش از اجرای عملیات را بر حسب دقیقه وارد کنید؛ اگر آماده‌سازی جداگانه‌ای ندارید، صفر مناسب است.</li>
<li><b>وضعیت:</b> «فعال» را انتخاب کنید تا عملیات در مسیر تولید قابل استفاده باشد. عملیات غیرفعال برای اجرای فعال مسیر در نظر گرفته نمی‌شود.</li>
</ul>
<p><b>ترتیب کار پیشنهادی:</b> ابتدا مسیر تولید و مراحل آن را تعریف و فعال کنید؛ سپس برای هر مرحله عملیات موردنیاز را ثبت کنید و ترتیب، مدت استاندارد و زمان آماده‌سازی را مشخص کنید؛ در پایان دستور تولید را با همان مسیر ایجاد و شروع کنید.</p>
<p style="color:#ffd7a1"><b>توجه:</b> این صفحه برای تعریف ساختار عملیات است، نه ثبت نتیجه اجرای واقعی آن. ثبت مقدار ورودی، خروجی، ضایعات و زمان پایان اجرای واقعی از بخش «اجرای عملیات تولید» انجام می‌شود.</p>
</div>
@endif

@if(($module ?? null) === 'productions' && ($row->status ?? null) === 'paused')
<div class="alert alert-warning">این دستور متوقف موقت است؛ فقط تاریخ و ساعت برنامه‌ریزی‌شده قابل ویرایش است. کالا، مسیر و مقدار تولید قفل هستند.</div>
@endif
<form method="post" action="{{ ($editing ?? false) ? route('master.update',[$module,$row->id]) : route('master.store',$module) }}" class="{{ in_array(($module ?? null), ['goods', 'goods-categories', 'units', 'locations', 'productions', 'suppliers', 'production-routes', 'production-sections', 'production-stages', 'production-operations'], true) ? 'master-card' : 'card shadow-sm border-0 p-4' }} {{ ($module ?? null) === 'production-sections' ? 'section-form' : '' }}">
@csrf @if($editing ?? false) @method('PUT') @endif
@if(($module ?? null) === 'production-sections')
    <h2 class="form-section-title">مشخصات قسمت تولید</h2>
    <p class="form-section-intro">محل انجام فعالیت و مسئول این قسمت را مشخص کنید. موارد ستاره‌دار الزامی هستند.</p>
@endif
<div class="row">
@foreach($definition['fields'] as $f)
@php $value=old($f,$row->$f ?? ''); @endphp
<div class="col-md-6 form-field">
<label class="form-label fw-semibold" for="field-{{$f}}">{{ $labels[$f] ?? $f }}@if(($module ?? null) === 'production-sections' && $f === 'location_id') <span class="text-danger">*</span>@endif</label>
@if(($module ?? null) === 'productions' && ($row->status ?? null) === 'paused' && !in_array($f, ['planned_start_at', 'planned_end_at'], true))
<div class="form-control" style="display:flex;align-items:center;min-height:42px;opacity:.75" aria-readonly="true">{{ $value !== '' && $value !== null ? $value : '—' }}</div>
@else
@if($f==='category_id')
<select name="{{$f}}" class="form-select" required><option value="">انتخاب کنید</option>@foreach(($options['categories']??[]) as $o)<option value="{{$o->id}}" @selected((string)$value===(string)$o->id)>{{$o->name}}</option>@endforeach</select>
@elseif($f==='unit_id')
<select id="field-{{$f}}" name="{{$f}}" class="form-select" required><option value="">انتخاب واحد اندازه‌گیری</option>@foreach(($options['units']??[]) as $o)<option value="{{$o->id}}" @selected((string)$value===(string)$o->id)>{{$o->name}}@if($o->symbol) ({{$o->symbol}})@endif</option>@endforeach</select>
<p class="field-help">واحدی را انتخاب کنید که مقدارهای پایه این کالا با آن ثبت می‌شوند. برای کالاهای قدیمی که واحد ندارند، هنگام ویرایش واحد پایه را مشخص کنید.</p>
@elseif($f==='parent_id')
<select name="{{$f}}" class="form-select"><option value="">بدون والد</option>@foreach(($options['parents']??[]) as $o)<option value="{{$o->id}}" @selected((string)$value===(string)$o->id)>{{$o->name}}</option>@endforeach</select>
@elseif($f==='goods_id')
<select name="{{$f}}" class="form-select" required><option value="">انتخاب کنید</option>@foreach(($options['goods']??[]) as $o)<option value="{{$o->id}}" @selected((string)$value===(string)$o->id)>{{$o->code}} — {{$o->name}}</option>@endforeach</select>
@elseif($f==='production_route_id')
<select name="{{$f}}" class="form-select" required>@foreach(($options['routes']??[]) as $o)<option value="{{$o->id}}" @selected((string)$value===(string)$o->id)>{{$o->name}}</option>@endforeach</select>
@elseif($f==='production_section_id')
<select name="{{$f}}" class="form-select" required><option value="">انتخاب کنید</option>@foreach(($options['sections']??[]) as $o)<option value="{{$o->id}}" @selected((string)$value===(string)$o->id)>{{$o->name}}</option>@endforeach</select>
@elseif($f==='production_stage_id')
<select name="{{$f}}" class="form-select" required>@foreach(($options['stages']??[]) as $o)<option value="{{$o->id}}" @selected((string)$value===(string)$o->id)>{{$o->name}}</option>@endforeach</select>
@elseif($f==='fiscal_year_id')
<select name="{{$f}}" class="form-select" required>@foreach(($options['fiscalYears']??[]) as $o)<option value="{{$o->id}}" @selected((string)$value===(string)$o->id)>{{$o->name}}</option>@endforeach</select>
@elseif($f==='location_id')
<select id="field-{{$f}}" name="{{$f}}" class="form-select" required><option value="">انتخاب محل / انبار</option>@foreach(($options['locations']??[]) as $o)<option value="{{$o->id}}" @selected((string)$value===(string)$o->id)>{{$o->name}}</option>@endforeach</select>
@if(($module ?? null) === 'production-sections')<p class="field-help">محل فیزیکی یا سازمانی این قسمت را انتخاب کنید. اگر فهرست خالی است، ابتدا محل را در بخش «انبارها و محل‌ها» ثبت کنید.</p>@endif
@elseif($f==='supervisor_personnel_id')
<select id="field-{{$f}}" name="{{$f}}" class="form-select"><option value="">بدون سرپرست</option>@foreach(($options['personnel']??[]) as $o)<option value="{{$o->id}}" @selected((string)$value===(string)$o->id)>{{$o->name}}</option>@endforeach</select>
@if(($module ?? null) === 'production-sections')<p class="field-help">در صورت مشخص بودن مسئول این قسمت، سرپرست را انتخاب کنید؛ این فیلد اختیاری است.</p>@endif
@elseif($f==='type' && ($module ?? null) === 'locations')
<select id="field-{{$f}}" name="{{$f}}" class="form-select" required>
<option value="">انتخاب نوع محل</option>
<option value="warehouse" @selected((string)$value==='warehouse')>انبار</option>
<option value="انبار" @selected((string)$value==='انبار')>انبار (نوع فارسی قدیمی)</option>
<option value="محل تولید" @selected((string)$value==='محل تولید')>محل تولید</option>
<option value="سالن تولید" @selected((string)$value==='سالن تولید')>سالن تولید</option>
<option value="محصول" @selected((string)$value==='محصول')>محصول</option>
<option value="مواد" @selected((string)$value==='مواد')>مواد</option>
<option value="مواد اولیه" @selected((string)$value==='مواد اولیه')>مواد اولیه</option>
</select>
<p class="field-help">برای نمایش محل در فهرست تحویل مواد و رسید خرید، نوع «انبار» را انتخاب کنید. این تغییر فقط نوعی را که ثبت می‌کنید تعیین می‌کند و اطلاعات قبلی را خودکار تغییر نمی‌دهد.</p>
@elseif($f==='status')
<select name="{{$f}}" class="form-select" required><option value="active" @selected((string)$value==='active')>فعال</option><option value="inactive" @selected((string)$value==='inactive')>غیرفعال</option></select>
@elseif(in_array($f,['purchasable','producible','sellable']))
<div class="form-check form-switch pt-2"><input type="checkbox" name="{{$f}}" value="1" class="form-check-input" @checked((bool)$value)><label class="form-check-label">فعال</label></div>
@elseif(in_array($f,['sequence','standard_duration_minutes','setup_duration_minutes']))
<input type="number" name="{{$f}}" value="{{$value}}" min="0" class="form-control">
@elseif(str_contains($f,'quantity'))
<input type="number" step="0.0001" min="0.0001" name="{{$f}}" value="{{$value}}" class="form-control" required>
@elseif(str_contains($f,'_at') && ($module ?? null) === 'productions')
<input type="text" id="field-{{$f}}-picker" class="form-control production-jalali-picker" placeholder="تاریخ و ساعت شمسی را انتخاب کنید" autocomplete="off" readonly>
<input type="hidden" id="field-{{$f}}" name="{{$f}}" value="{{$value ? \Illuminate\Support\Carbon::parse($value)->format('Y-m-d H:i') : ''}}">
<p class="field-help">تاریخ با تقویم شمسی انتخاب می‌شود؛ زمان را هم مشخص کنید.</p>
@elseif(str_contains($f,'_at'))
<input type="datetime-local" name="{{$f}}" value="{{$value ? \Illuminate\Support\Carbon::parse($value)->format('Y-m-d\\TH:i') : ''}}" class="form-control">
@elseif(in_array($f,['description','notes','address']))
<textarea name="{{$f}}" class="form-control" rows="3">{{$value}}</textarea>
@else
<input name="{{$f}}" value="{{$value}}" class="form-control" @if(in_array($f,['code','name','number'])) required @endif>
@endif
@endif
@error($f)<div class="text-danger small mt-1">{{$message}}</div>@enderror
</div>
@endforeach
</div>
<div class="mt-4 form-actions"><button type="submit" class="btn btn-primary px-4">{{ ($editing ?? false) ? 'ذخیره تغییرات' : 'ثبت قسمت تولید' }}</button><a href="{{ route('master.index', $module) }}" class="btn btn-outline-secondary">انصراف و بازگشت</a></div>
</form></div>

@if(($module ?? null) === 'productions')
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/persian-date@1.1.0/dist/persian-date.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/js/persian-datepicker.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/persian-datepicker@1.2.0/dist/css/persian-datepicker.min.css">
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.production-jalali-picker').forEach(function (picker) {
        const hidden = document.getElementById(picker.id.replace('-picker', ''));
        if (!hidden || !window.jQuery || !jQuery.fn.persianDatepicker) return;
        const oldValue = hidden.value;
        if (oldValue) picker.value = oldValue;
        jQuery(picker).persianDatepicker({
            calendarType: 'persian',
            format: 'YYYY/MM/DD HH:mm',
            initialValue: Boolean(oldValue),
            initialValueType: 'gregorian',
            altField: '#' + hidden.id,
            altFormat: 'YYYY-MM-DD HH:mm',
            altFieldFormatter: function (unixDate) {
                const date = new Date(unixDate);
                return date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0') + '-' + String(date.getDate()).padStart(2, '0') + ' ' + String(date.getHours()).padStart(2, '0') + ':' + String(date.getMinutes()).padStart(2, '0');
            },
            timePicker: { enabled: true, meridiem: { enabled: false } },
            calendar: { persian: { locale: 'fa' } },
            toolbox: { calendarSwitch: { enabled: false }, todayButton: { enabled: true, text: { fa: 'امروز' } } }
        });
    });
});
</script>
@endif

@endsection