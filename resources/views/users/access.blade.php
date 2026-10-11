@extends('layouts.dashboard-shell')

@section('title', 'دسترسی کاربر')

@section('content')
@php
    // Preserve the target user because the shared dashboard layout uses $user
    // for the currently authenticated user.
    $targetUser = $user;
@endphp

@push('styles')
<style>
.access-panel{max-width:900px}
.access-form select{display:block;width:100%;margin-top:12px;padding:12px;background:#081522;border:1px solid #29465e;color:#e9f3fb;border-radius:8px;font:inherit}
.access-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:16px}
</style>
@endpush

<div class="page-heading">
    <div>
        <h1>دسترسی کاربر</h1>
        <p>نقش و مجوزهای کاربر {{ $targetUser->name }} را مدیریت کنید.</p>
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

@if (session('success'))
    <div class="panel" role="status" style="margin-bottom:16px;border-color:#245b50;color:#6ee7d0">
        {{ session('success') }}
    </div>
@endif

<div class="panel access-panel">
    <form class="access-form" method="POST" action="{{ route('users.access.update', $targetUser) }}">
        @csrf
        @method('PUT')

        <label for="role_id">نقش کاربر</label>
        <select id="role_id" name="role_id">
            <option value="">بدون نقش</option>
            @foreach($roles as $role)
                <option value="{{ $role->id }}" @selected((string) old('role_id', $targetUser->roles->first()?->id ?? '') === (string) $role->id)>
                    {{ $role->name }} — {{ $role->permissions->pluck('name')->join('، ') }}
                </option>
            @endforeach
        </select>

        <div class="access-actions">
            <button class="btn primary" type="submit">ذخیره دسترسی</button>
            <a class="btn" href="{{ route('users.index') }}">انصراف</a>
        </div>
    </form>
</div>
@endsection
