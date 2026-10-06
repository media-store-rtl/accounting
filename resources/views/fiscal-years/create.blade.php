<!doctype html>
<html lang="fa" dir="rtl">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>ایجاد سال مالی</title>
<style>body{font-family:Tahoma,sans-serif;background:#07111f;color:#e9f3fb;margin:0}.wrap{max-width:700px;margin:45px auto;padding:0 18px}.panel{background:#0a1b2b;border:1px solid #1b354b;border-radius:18px;padding:25px}.head{display:flex;justify-content:space-between;align-items:center}.head a{color:#6ee7d0;text-decoration:none}.field{margin-top:16px}.field label{display:block;color:#91a7ba;font-size:12px;margin-bottom:7px}.field input{width:100%;box-sizing:border-box;background:#0d2235;border:1px solid #29455d;color:#e9f3fb;border-radius:9px;padding:11px;font:inherit}.error{color:#ff9b9b;font-size:11px;margin-top:5px}.btn{margin-top:20px;background:#6ee7d0;color:#06202a;border:0;border-radius:9px;padding:11px 18px;cursor:pointer;font-weight:700}.note{color:#71879d;font-size:10px;margin-top:8px}</style>
</head><body><div class="wrap"><div class="head"><h1>ایجاد سال مالی</h1><a href="{{ route('fiscal-years.index') }}">← بازگشت</a></div><div class="panel">
<form method="POST" action="{{ route('fiscal-years.store') }}">@csrf
<div class="field"><label for="name">نام سال مالی *</label><input id="name" name="name" value="{{ old('name') }}" required>@error('name')<div class="error">{{ $message }}</div>@enderror</div>
<div class="field"><label for="starts_at">تاریخ شروع *</label><input id="starts_at" type="date" name="starts_at" value="{{ old('starts_at',$startsAt) }}" required>@error('starts_at')<div class="error">{{ $message }}</div>@enderror</div>
<div class="field"><label for="ends_at">تاریخ پایان *</label><input id="ends_at" type="date" name="ends_at" value="{{ old('ends_at') }}" required>@error('ends_at')<div class="error">{{ $message }}</div>@enderror</div>
<div class="note">واحد پول در سال مالی ثبت نمی‌شود.</div>
<button class="btn" type="submit">ثبت سال مالی</button>
</form></div></div></body></html>
