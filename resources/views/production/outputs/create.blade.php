@extends('layouts.dashboard-shell')

@section('title', 'ثبت خروجی تولید')

@section('content')
<div class="page-heading"><div><h1>ثبت خروجی تولید</h1><p>ثبت مقدار محصول نهایی از تولید تکمیل‌شده برای تأیید و دریافت انبار</p></div><a class="btn" href="{{ route('production.outputs.index') }}">بازگشت به فهرست</a></div>
@if(session('success'))<div style="padding:12px 15px;margin-bottom:16px;border:1px solid #246451;border-radius:12px;background:#0c282b;color:#8ce8ce;font-size:12px">{{ session('success') }}</div>@endif
@if($errors->any())<div style="padding:12px 15px;margin-bottom:16px;border:1px solid #714448;border-radius:12px;background:#302126;color:#ffb8a8;font-size:12px">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
<div class="panel" style="margin-bottom:18px"><p style="font-size:12px;line-height:1.9">کالای خروجی از روی تولید انتخاب‌شده تعیین می‌شود تا کالا با تولید ثبت‌شده ناسازگار نباشد. ثبت خروجی به‌تنهایی موجودی انبار را افزایش نمی‌دهد؛ پس از تأیید، دریافت انبار جداگانه ثبت می‌شود.</p>
<form method="POST" action="{{ route('production.outputs.store') }}" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,220px),1fr));gap:14px">
@csrf
<label>تولید تکمیل‌شده<select name="production_id" class="form-select" required><option value="">انتخاب تولید</option>@foreach($productions as $p)<option value="{{ $p->id }}" @selected((string)old('production_id')===(string)$p->id)>{{ $p->number }} — {{ $p->goods_code ? $p->goods_code.' / ' : '' }}{{ $p->goods_name }}</option>@endforeach</select></label>
<label>انبار مقصد<select name="warehouse_location_id" class="form-select" required><option value="">انتخاب انبار</option>@foreach($locations as $l)<option value="{{ $l->id }}" @selected((string)old('warehouse_location_id')===(string)$l->id)>{{ $l->name }}</option>@endforeach</select></label>
<label>مقدار محصول سالم<input type="number" name="quantity" step="0.0001" min="0.0001" value="{{ old('quantity') }}" class="form-input" required></label>
<label>زمان تولید<input type="datetime-local" name="produced_at" value="{{ old('produced_at', now()->format('Y-m-d\\TH:i')) }}" class="form-input" required></label>
<label style="grid-column:1/-1">توضیحات<textarea name="notes" rows="2" class="form-input">{{ old('notes') }}</textarea></label>
<div><button class="btn primary" type="submit">ثبت خروجی برای تأیید</button></div>
</form></div>
@endsection
