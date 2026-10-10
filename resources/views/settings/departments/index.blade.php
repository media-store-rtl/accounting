@extends('layouts.dashboard-shell')
@section('title', 'واحدهای سازمانی')
@section('content')
<div class="page-heading"><div><h1>واحدهای سازمانی</h1><p>تعریف واحدهای این شرکت و انتساب کاربران؛ مستقل از نقش‌ها و مجوزهای دسترسی</p></div></div>
@if(session('success'))<div class="panel" style="margin-bottom:16px;border-color:#245447;color:#6ee7d0">{{ session('success') }}</div>@endif
@if($errors->any())<div class="panel" style="margin-bottom:16px;border-color:#63343b;color:#ffb8a8">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
<div class="actions"><a class="btn" href="{{ route('users.index') }}">بازگشت به کاربران</a><a class="btn" href="{{ route('settings.notification-recipients.index') }}">تنظیم گیرندگان اعلان</a></div>
<div class="panel" style="margin-bottom:18px">
<h2 style="font-size:15px;margin-top:0">تعریف واحد جدید</h2>
<form method="POST" action="{{ route('settings.departments.store') }}" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;align-items:end">
@csrf
<label>کد واحد<input name="code" required maxlength="50" value="{{ old('code') }}" style="display:block;width:100%;margin-top:6px;padding:10px;background:#081522;border:1px solid #29465e;color:white;border-radius:8px"></label>
<label>نام واحد<input name="name" required maxlength="150" value="{{ old('name') }}" style="display:block;width:100%;margin-top:6px;padding:10px;background:#081522;border:1px solid #29465e;color:white;border-radius:8px"></label>
<label>توضیحات<input name="description" maxlength="2000" value="{{ old('description') }}" style="display:block;width:100%;margin-top:6px;padding:10px;background:#081522;border:1px solid #29465e;color:white;border-radius:8px"></label>
<button class="btn primary" type="submit">ایجاد واحد</button>
</form></div>
@forelse($departments as $department)
<div class="panel" style="margin-bottom:16px">
<div style="display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap"><h2 style="font-size:15px;margin:0">{{ $department->name }} <span style="color:#71879d;font-size:11px">({{ $department->code }})</span></h2><span class="status {{ $department->is_active ? 'active' : 'inactive' }}">{{ $department->is_active ? 'فعال' : 'غیرفعال' }}</span></div>
<form method="POST" action="{{ route('settings.departments.update', $department->id) }}" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;align-items:end;margin-top:14px">
@csrf @method('PUT')
<label>کد<input name="code" required maxlength="50" value="{{ $department->code }}" style="display:block;width:100%;margin-top:6px;padding:10px;background:#081522;border:1px solid #29465e;color:white;border-radius:8px"></label>
<label>نام<input name="name" required maxlength="150" value="{{ $department->name }}" style="display:block;width:100%;margin-top:6px;padding:10px;background:#081522;border:1px solid #29465e;color:white;border-radius:8px"></label>
<label>توضیحات<input name="description" maxlength="2000" value="{{ $department->description }}" style="display:block;width:100%;margin-top:6px;padding:10px;background:#081522;border:1px solid #29465e;color:white;border-radius:8px"></label>
<label style="display:flex;gap:8px;align-items:center"><input type="checkbox" name="is_active" value="1" @checked($department->is_active)> واحد فعال باشد</label>
<button class="btn" type="submit">ذخیره مشخصات</button>
</form>
<form method="POST" action="{{ route('settings.departments.members', $department->id) }}" style="margin-top:18px;border-top:1px solid #1b354b;padding-top:14px">
@csrf <strong style="display:block;margin-bottom:10px;font-size:12px">اعضای واحد</strong>
@if(!$department->is_active)
<p style="font-size:11px;color:#ffb8a8">این واحد غیرفعال است؛ انتساب جدید امکان‌پذیر نیست. انتساب‌های قبلی حفظ شده‌اند.</p>
@else
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:8px">
@forelse($users as $member)
<label style="display:flex;align-items:center;gap:8px;padding:8px;border:1px solid #1b354b;border-radius:8px;font-size:11px"><input type="checkbox" name="users[]" value="{{ $member->id }}" @checked(in_array((int)$member->id, $membersByDepartment->get($department->id, []), true))><span>{{ $member->name }} <small style="display:block;color:#71879d">{{ $member->email }}</small></span></label>
@empty <p style="color:#71879d;font-size:11px">کاربر فعال عضو این شرکت نیست.</p>@endforelse
</div><button class="btn primary" type="submit" style="margin-top:12px">ذخیره اعضای واحد</button>
@endif
</form></div>
@empty
<div class="panel" style="text-align:center;color:#71879d">هنوز واحد سازمانی تعریف نشده است. ابتدا واحدهایی مثل فروش، تولید، انبار یا مالی را ایجاد کنید.</div>
@endforelse
@endsection