<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>{{ isset($fiscalYear)?'ویرایش سال مالی':'تعریف سال مالی' }}</title>
<style>
body{font-family:Vazirmatn,Tahoma,sans-serif;background:#07111f;color:#e9f3fb;padding:30px;margin:0}.panel{background:#0a1b2b;border:1px solid #1b354b;border-radius:16px;padding:24px;max-width:720px;margin:auto}label{display:block;margin:14px 0 6px;color:#9bb0c1;font-size:13px}input{width:100%;padding:11px;background:#081522;border:1px solid #29465e;color:#fff;border-radius:8px;box-sizing:border-box}.btn{display:inline-block;padding:10px 15px;background:#123b4d;color:#6ee7d0;border:0;border-radius:8px;text-decoration:none;cursor:pointer;margin-top:18px}.muted{color:#7890a5;font-size:12px}.error{color:#ffb4b4;margin-top:15px;line-height:2}
</style>
</head>
<body>
<div class="panel">
<a class="muted" href="{{ route('fiscal-years.index') }}">← بازگشت</a>
<h1>{{ isset($fiscalYear)?'ویرایش سال مالی':'تعریف سال مالی جدید' }}</h1>
<p class="muted">شرکت: {{ $company->name }}</p>
<form method="POST" action="{{ isset($fiscalYear)?route('fiscal-years.update',$fiscalYear):route('fiscal-years.store') }}">
@csrf @if(isset($fiscalYear))@method('PUT')@endif
<label for="name">نام سال مالی</label>
<input id="name" name="name" value="{{ old('name',$fiscalYear->name??'') }}" placeholder="مثلاً سال مالی ۱۴۰۵" required maxlength="100">
<label for="starts_at">تاریخ شروع</label>
<input id="starts_at" type="date" name="starts_at" value="{{ old('starts_at',isset($fiscalYear)?$fiscalYear->starts_at?->format('Y-m-d'):now()->format('Y-m-d')) }}" required>
<label for="ends_at">تاریخ پایان</label>
<input id="ends_at" type="date" name="ends_at" value="{{ old('ends_at',isset($fiscalYear)?$fiscalYear->ends_at?->format('Y-m-d'):'') }}" required>
@if($errors->any())<div class="error">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
<button class="btn" type="submit">{{ isset($fiscalYear)?'ذخیره تغییرات':'تعریف سال مالی' }}</button>
</form>
</div>
</body>
</html>
