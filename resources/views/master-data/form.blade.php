@extends(in_array(($module ?? null), ['goods', 'goods-categories', 'locations', 'productions', 'suppliers', 'production-routes'], true) ? 'layouts.dashboard-shell' : 'layouts.app')
@if(in_array(($module ?? null), ['goods', 'goods-categories', 'locations', 'productions', 'suppliers', 'production-routes'], true))
@section('title', (($editing ?? false) ? 'ویرایش ' : 'ثبت ') . ($definition['title'] ?? 'اطلاعات پایه'))
@endif
@section('content')
@php
$labels=['code'=>'کد','name'=>'نام','parent_id'=>'دسته والد','symbol'=>'نماد','unit_type'=>'نوع واحد','national_id'=>'شناسه ملی','phone'=>'تلفن','email'=>'ایمیل','address'=>'آدرس','category_id'=>'دسته‌بندی','purchasable'=>'قابل خرید','producible'=>'قابل تولید','sellable'=>'قابل فروش','location_id'=>'محل / انبار','supervisor_personnel_id'=>'سرپرست','type'=>'نوع','production_route_id'=>'مسیر تولید','production_section_id'=>'قسمت تولید','production_stage_id'=>'مرحله تولید','sequence'=>'ترتیب','standard_duration_minutes'=>'مدت استاندارد (دقیقه)','setup_duration_minutes'=>'زمان آماده‌سازی (دقیقه)','status'=>'وضعیت','number'=>'شماره','goods_id'=>'کالا','fiscal_year_id'=>'سال مالی','planned_quantity'=>'مقدار برنامه‌ریزی‌شده','planned_start_at'=>'شروع برنامه‌ریزی‌شده','planned_end_at'=>'پایان برنامه‌ریزی‌شده','description'=>'توضیحات','notes'=>'یادداشت'];
@endphp
@if(in_array(($module ?? null), ['goods', 'goods-categories', 'locations', 'productions', 'suppliers', 'production-routes'], true))
<style>
.master-form-page{max-width:1100px;margin:0 auto}
.master-form-page .master-heading{display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:20px}
.master-form-page h1{font-size:24px;margin:0 0 6px}
.master-form-page .text-muted{color:#71879d;font-size:11px}
.master-form-page .small{font-size:11px}
.master-form-page .mb-1{margin-bottom:4px}
.master-form-page .mb-4{margin-bottom:20px}
.master-form-page .master-card{background:#0a1b2b;border:1px solid #1b354b;border-radius:16px;padding:22px}
.master-form-page .row{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}
.master-form-page .col-md-6{min-width:0}
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
@media(max-width:620px){.master-form-page .row{grid-template-columns:1fr;gap:14px}.master-form-page .master-heading{align-items:flex-start;flex-direction:column}.master-form-page .master-card{padding:15px}.master-form-page h1{font-size:21px}}
</style>
@endif
<div class="{{ in_array(($module ?? null), ['goods', 'goods-categories', 'locations', 'productions', 'suppliers', 'production-routes'], true) ? 'master-form-page' : 'container py-3' }}" dir="rtl">
<div class="{{ in_array(($module ?? null), ['goods', 'goods-categories', 'locations', 'productions', 'suppliers', 'production-routes'], true) ? 'master-heading' : 'd-flex justify-content-between align-items-center mb-4' }}"><div><h1 class="{{ in_array(($module ?? null), ['goods', 'goods-categories', 'locations', 'productions', 'suppliers', 'production-routes'], true) ? '' : 'h3 mb-1' }}">{{ in_array(($module ?? null), ['goods', 'goods-categories', 'locations', 'productions', 'suppliers', 'production-routes'], true) ? ((($editing ?? false) ? 'ویرایش اطلاعات ' : 'ثبت اطلاعات ') . ($definition['title'] ?? '')) : $definition['title'] }}</h1><div class="text-muted small">{{ ($editing ?? false) ? 'ویرایش اطلاعات ثبت‌شده' : 'ثبت اطلاعات جدید' }}</div></div><a href="{{route('master.index',$module)}}" class="btn btn-outline-secondary">بازگشت به فهرست {{ $definition['title'] }}</a></div>
@if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $e)<div>{{$e}}</div>@endforeach</div>@endif
<form method="post" action="{{ ($editing ?? false) ? route('master.update',[$module,$row->id]) : route('master.store',$module) }}" class="{{ in_array(($module ?? null), ['goods', 'goods-categories', 'locations', 'productions', 'suppliers', 'production-routes'], true) ? 'master-card' : 'card shadow-sm border-0 p-4' }}">
@csrf @if($editing ?? false) @method('PUT') @endif
<div class="row g-3">
@foreach($definition['fields'] as $f)
@php $value=old($f,$row->$f ?? ''); @endphp
<div class="col-md-6">
<label class="form-label fw-semibold">{{ $labels[$f] ?? $f }}</label>
@if($f==='category_id')
<select name="{{$f}}" class="form-select" required><option value="">انتخاب کنید</option>@foreach(($options['categories']??[]) as $o)<option value="{{$o->id}}" @selected((string)$value===(string)$o->id)>{{$o->name}}</option>@endforeach</select>
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
<select name="{{$f}}" class="form-select" required>@foreach(($options['locations']??[]) as $o)<option value="{{$o->id}}" @selected((string)$value===(string)$o->id)>{{$o->name}}</option>@endforeach</select>
@elseif($f==='supervisor_personnel_id')
<select name="{{$f}}" class="form-select"><option value="">بدون سرپرست</option>@foreach(($options['personnel']??[]) as $o)<option value="{{$o->id}}" @selected((string)$value===(string)$o->id)>{{$o->name}}</option>@endforeach</select>
@elseif(in_array($f,['purchasable','producible','sellable']))
<div class="form-check form-switch pt-2"><input type="checkbox" name="{{$f}}" value="1" class="form-check-input" @checked((bool)$value)><label class="form-check-label">فعال</label></div>
@elseif(in_array($f,['sequence','standard_duration_minutes','setup_duration_minutes']))
<input type="number" name="{{$f}}" value="{{$value}}" min="0" class="form-control">
@elseif(str_contains($f,'quantity'))
<input type="number" step="0.0001" min="0.0001" name="{{$f}}" value="{{$value}}" class="form-control" required>
@elseif(str_contains($f,'_at'))
<input type="datetime-local" name="{{$f}}" value="{{$value ? \Illuminate\Support\Carbon::parse($value)->format('Y-m-d\\TH:i') : ''}}" class="form-control">
@elseif(in_array($f,['description','notes','address']))
<textarea name="{{$f}}" class="form-control" rows="3">{{$value}}</textarea>
@else
<input name="{{$f}}" value="{{$value}}" class="form-control" @if(in_array($f,['code','name','number'])) required @endif>
@endif
@error($f)<div class="text-danger small mt-1">{{$message}}</div>@enderror
</div>
@endforeach
</div>
<div class="mt-4"><button class="btn btn-primary px-4">{{ ($editing ?? false) ? 'ذخیره تغییرات' : 'ثبت اطلاعات' }}</button></div>
</form></div>
@endsection