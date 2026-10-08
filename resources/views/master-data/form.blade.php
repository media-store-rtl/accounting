@extends('layouts.app')
@section('content')
@php
$labels=['code'=>'کد','name'=>'نام','parent_id'=>'دسته والد','symbol'=>'نماد','unit_type'=>'نوع واحد','national_id'=>'شناسه ملی','phone'=>'تلفن','email'=>'ایمیل','address'=>'آدرس','category_id'=>'دسته‌بندی','purchasable'=>'قابل خرید','producible'=>'قابل تولید','sellable'=>'قابل فروش','location_id'=>'محل / انبار','supervisor_personnel_id'=>'سرپرست','type'=>'نوع','production_route_id'=>'مسیر تولید','production_section_id'=>'قسمت تولید','production_stage_id'=>'مرحله تولید','sequence'=>'ترتیب','standard_duration_minutes'=>'مدت استاندارد (دقیقه)','setup_duration_minutes'=>'زمان آماده‌سازی (دقیقه)','status'=>'وضعیت','number'=>'شماره','goods_id'=>'کالا','fiscal_year_id'=>'سال مالی','planned_quantity'=>'مقدار برنامه‌ریزی‌شده','planned_start_at'=>'شروع برنامه‌ریزی‌شده','planned_end_at'=>'پایان برنامه‌ریزی‌شده','description'=>'توضیحات','notes'=>'یادداشت'];
@endphp
<div class="container py-3" dir="rtl">
<div class="d-flex justify-content-between align-items-center mb-4"><div><h1 class="h3 mb-1">{{ $definition['title'] }}</h1><div class="text-muted small">{{ ($editing ?? false) ? 'ویرایش اطلاعات ثبت‌شده' : 'ثبت اطلاعات جدید' }}</div></div><a href="{{route('master.index',$module)}}" class="btn btn-outline-secondary">بازگشت</a></div>
@if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $e)<div>{{$e}}</div>@endforeach</div>@endif
<form method="post" action="{{ ($editing ?? false) ? route('master.update',[$module,$row->id]) : route('master.store',$module) }}" class="card shadow-sm border-0 p-4">
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