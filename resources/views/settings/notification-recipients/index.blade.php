@extends('layouts.dashboard-shell')
@section('title', 'گیرندگان اعلان')
@section('content')
<div class="page-heading"><div><h1>تنظیم گیرندگان اعلان</h1><p>برای هر رویداد، واحدها و/یا کاربران مشخص را انتخاب کنید. انتخاب گیرنده مجوز عملیاتی ایجاد نمی‌کند.</p></div></div>
@if(session('success'))<div class="panel" style="margin-bottom:16px;border-color:#245447;color:#6ee7d0">{{ session('success') }}</div>@endif
@if($errors->any())<div class="panel" style="margin-bottom:16px;border-color:#63343b;color:#ffb8a8">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
<div class="actions"><a class="btn" href="{{ route('settings.departments.index') }}">مدیریت واحدهای سازمانی</a><a class="btn" href="{{ route('users.index') }}">بازگشت به کاربران</a></div>
@if($departments->isEmpty())<div class="panel" style="margin-bottom:16px;color:#ffcf86">هنوز واحد فعالی تعریف نشده است. می‌توانید فعلاً کاربران مشخص را گیرنده کنید یا ابتدا واحدها را تعریف کنید.</div>@endif
@foreach($rules as $rule)
<div class="panel" style="margin-bottom:16px"><form method="POST" action="{{ route('settings.notification-recipients.update', $rule->id) }}">
@csrf @method('PUT')
<div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;flex-wrap:wrap;margin-bottom:14px"><div><h2 style="font-size:14px;margin:0 0 5px">{{ $rule->name }}</h2><small style="color:#71879d">{{ $rule->event_key }}</small></div><label style="display:flex;align-items:center;gap:7px;font-size:11px"><input type="checkbox" name="is_active" value="1" @checked($rule->is_active)> ارسال این نوع اعلان فعال باشد</label></div>
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:18px">
<div><strong style="font-size:12px;display:block;margin-bottom:8px">ارسال به واحدها</strong>
@forelse($departments as $department)<label style="display:flex;gap:8px;align-items:center;margin:8px 0;font-size:11px"><input type="checkbox" name="departments[]" value="{{ $department->id }}" @checked(in_array((int)$department->id, $rule->department_ids, true))>{{ $department->name }}</label>
@empty<small style="color:#71879d">واحد فعالی برای انتخاب وجود ندارد.</small>@endforelse</div>
<div><strong style="font-size:12px;display:block;margin-bottom:8px">ارسال به کاربران مشخص</strong>
@forelse($users as $member)<label style="display:flex;gap:8px;align-items:center;margin:8px 0;font-size:11px"><input type="checkbox" name="users[]" value="{{ $member->id }}" @checked(in_array((int)$member->id, $rule->user_ids, true))><span>{{ $member->name }} <small style="color:#71879d">{{ $member->email }}</small></span></label>
@empty<small style="color:#71879d">کاربر فعال عضو این شرکت وجود ندارد.</small>@endforelse</div></div>
<div style="border-top:1px solid #1b354b;margin-top:14px;padding-top:12px"><label style="display:flex;gap:8px;align-items:center;font-size:11px"><input type="checkbox" name="exclude_actor" value="1" @checked($rule->exclude_actor)> ثبت‌کننده رویداد به‌صورت پیش‌فرض اعلان خودش را دریافت نکند</label>
@if(!$rule->is_configured)<p style="font-size:10px;color:#ffcf86;margin:8px 0 0">هنوز ذخیره نشده: تا زمان ذخیره این قانون، ارسال قبلی مبتنی بر مجوز برای سازگاری حفظ می‌شود.</p>
@elseif($rule->is_active && count($rule->department_ids) === 0 && count($rule->user_ids) === 0)<p style="font-size:10px;color:#ffcf86;margin:8px 0 0">گیرنده‌ای انتخاب نشده؛ با ذخیره این وضعیت، اعلان برای این رویداد ارسال نخواهد شد و در گزارش ثبت می‌شود.</p>@endif</div>
<button class="btn primary" type="submit" style="margin-top:14px">ذخیره گیرندگان این رویداد</button>
</form></div>
@endforeach
@endsection