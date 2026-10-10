@extends('layouts.dashboard-shell')

@section('title', isset($user) ? 'ویرایش کاربر' : 'ایجاد کاربر')

@section('content')
@php
    // Capture the edited account before the shared dashboard layout uses $user for the signed-in account.
    $editingUser = $user ?? null;
@endphp

<div class="page-heading">
    <div>
        <h1>{{ $editingUser ? 'ویرایش کاربر' : 'ایجاد کاربر' }}</h1>
        <p>{{ $editingUser ? 'اطلاعات و نقش‌های کاربر را ویرایش کنید.' : 'حساب کاربری جدیدی برای مجموعه ایجاد کنید.' }}</p>
    </div>
    <a class="btn" href="{{ route('users.index') }}">بازگشت به کاربران</a>
</div>

@if ($errors->any())
    <div role="alert" class="panel" style="margin-bottom:16px;border-color:#63343b;color:#ffb8a8">
        @foreach ($errors->all() as $error)
            <p style="margin:0 0 6px">{{ $error }}</p>
        @endforeach
    </div>
@endif

@push('styles')
<style>
.user-form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
.user-form-grid label{display:block;color:#9eb2c4;font-size:11px}
.user-form-grid input,.user-form-grid select{display:block;width:100%;margin-top:7px;padding:11px 12px;background:#081522;border:1px solid #29465e;color:#e9f3fb;border-radius:9px;font:inherit;font-size:12px;min-width:0}
.user-form-grid input:focus,.user-form-grid select:focus{outline:2px solid #6ee7d0;outline-offset:1px}
.user-form-actions{grid-column:1/-1;display:flex;gap:10px;flex-wrap:wrap;margin-top:4px}
@media(max-width:560px){.user-form-grid{grid-template-columns:minmax(0,1fr)}}
</style>
@endpush

<div class="panel">
    <form method="POST" action="{{ $editingUser ? route('users.update', $editingUser) : route('users.store') }}" class="user-form-grid">
        @csrf
        @if($editingUser)
            @method('PUT')
        @endif

        @foreach(['code'=>'کد پرسنلی','first_name'=>'نام','last_name'=>'نام خانوادگی','national_id'=>'کد ملی','job_title'=>'عنوان شغلی','phone'=>'تلفن','mobile'=>'موبایل','email'=>'ایمیل','username'=>'نام کاربری'] as $field=>$label)
            <label>
                {{ $label }}
                <input
                    name="{{ $field }}"
                    value="{{ old($field, $editingUser?->personnel?->$field ?? '') }}"
                    @if(in_array($field, ['code','first_name','last_name','email','username'], true)) required @endif
                    @if($field === 'email') type="email" @else type="text" @endif
                    autocomplete="{{ $field === 'email' ? 'email' : ($field === 'username' ? 'username' : 'off') }}"
                >
            </label>
        @endforeach

        @if(!$editingUser)
            <label>رمز عبور
                <input type="password" name="password" required autocomplete="new-password">
            </label>
            <label>تکرار رمز عبور
                <input type="password" name="password_confirmation" required autocomplete="new-password">
            </label>
        @else
            <label>رمز جدید
                <input type="password" name="password" autocomplete="new-password">
            </label>
            <label>تکرار رمز جدید
                <input type="password" name="password_confirmation" autocomplete="new-password">
            </label>
        @endif

        <label>نقش
            <select name="role_id">
                <option value="">بدون نقش</option>
                @foreach($roles as $role)
                    <option value="{{ $role->id }}" @selected($editingUser && $editingUser->roles->contains($role->id))>{{ $role->name }}</option>
                @endforeach
            </select>
        </label>

        <div class="user-form-actions">
            <button class="btn primary" type="submit">ذخیره</button>
            <a class="btn" href="{{ route('users.index') }}">انصراف</a>
        </div>
    </form>
</div>
@endsection
