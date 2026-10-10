@extends('layouts.dashboard-shell')

@section('title', 'ثبت اجرای عملیات تولید')

@section('content')
<div class="page-heading"><div><h1>ثبت اجرای عملیات تولید</h1><p>ثبت مقادیر ورودی، خروجی و ضایعات برای عملیات انتخاب‌شده و ارسال جهت تأیید</p></div><a class="btn" href="{{ route('production.execution.index') }}">بازگشت به فهرست</a></div>
@if(session('success'))<div style="padding:12px 15px;margin-bottom:16px;border:1px solid #246451;border-radius:12px;background:#0c282b;color:#8ce8ce;font-size:12px">{{ session('success') }}</div>@endif
@if($errors->any())<div style="padding:12px 15px;margin-bottom:16px;border:1px solid #714448;border-radius:12px;background:#302126;color:#ffb8a8;font-size:12px">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
<form method="POST" action="{{ route('production.execution.store') }}" class="panel">
@csrf
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,240px),1fr));gap:16px">
<label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">عملیات تولید
<select name="production_operation_run_id" class="form-select" required style="background:#081522;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit">
<option value="">انتخاب عملیات</option>
@foreach($runs as $r)<option value="{{$r->id}}" @selected((string)old('production_operation_run_id')===(string)$r->id)>{{$r->number}} — {{$r->name}}</option>@endforeach
</select></label>
<label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">زمان پایان
<input name="completed_at" type="datetime-local" value="{{ old('completed_at') }}" style="background:#081522;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit;color-scheme:dark"></label>
</div>

<div style="margin-top:24px">
<h2 style="font-size:14px;margin:0 0 12px">مواد ورودی</h2>
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,220px),1fr));gap:14px">
<label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">کالای ورودی
<select name="inputs[0][goods_id]" class="form-select" style="background:#081522;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit"><option value="">انتخاب کالا (اختیاری)</option>@foreach($goods as $g)<option value="{{$g->id}}" @selected((string)old('inputs.0.goods_id')===(string)$g->id)>{{$g->code}} — {{$g->name}}</option>@endforeach</select></label>
<label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">مقدار ورودی
<input name="inputs[0][quantity]" type="number" step="0.0001" min="0.0001" value="{{ old('inputs.0.quantity') }}" style="background:#081522;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit"></label>
</div>
</div>

<div style="margin-top:24px">
<h2 style="font-size:14px;margin:0 0 12px">محصولات خروجی</h2>
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,220px),1fr));gap:14px">
<label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">کالای خروجی
<select name="outputs[0][goods_id]" class="form-select" style="background:#081522;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit"><option value="">انتخاب کالا (اختیاری)</option>@foreach($goods as $g)<option value="{{$g->id}}" @selected((string)old('outputs.0.goods_id')===(string)$g->id)>{{$g->code}} — {{$g->name}}</option>@endforeach</select></label>
<label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">مقدار خروجی
<input name="outputs[0][quantity]" type="number" step="0.0001" min="0.0001" value="{{ old('outputs.0.quantity') }}" style="background:#081522;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit"></label>
</div>
</div>

<div style="margin-top:24px">
<h2 style="font-size:14px;margin:0 0 12px">ضایعات</h2>
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,220px),1fr));gap:14px">
<label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">کالای ضایعات
<select name="scraps[0][goods_id]" class="form-select" style="background:#081522;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit"><option value="">انتخاب کالا (اختیاری)</option>@foreach($goods as $g)<option value="{{$g->id}}" @selected((string)old('scraps.0.goods_id')===(string)$g->id)>{{$g->code}} — {{$g->name}}</option>@endforeach</select></label>
<label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">مقدار ضایعات
<input name="scraps[0][quantity]" type="number" step="0.0001" min="0.0001" value="{{ old('scraps.0.quantity') }}" style="background:#081522;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit"></label>
<label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">علت ضایعات
<input name="scraps[0][reason]" value="{{ old('scraps.0.reason') }}" style="background:#081522;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit"></label>
</div>
</div>

<div style="margin-top:18px">
<label style="display:flex;flex-direction:column;gap:7px;color:#9eb2c4;font-size:11px">توضیحات
<textarea name="notes" rows="3" style="background:#081522;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;padding:11px;font:inherit;resize:vertical">{{ old('notes') }}</textarea></label>
</div>
<div style="margin-top:20px;display:flex;gap:10px;flex-wrap:wrap"><button class="btn primary" type="submit">ثبت برای تأیید</button><a class="btn" href="{{ route('production.execution.index') }}">انصراف</a></div>
</form>
@endsection