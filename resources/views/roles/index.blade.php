@extends('layouts.dashboard-shell')

@section('title', 'نقش‌ها و دسترسی‌ها')

@section('content')
<div class="page-heading"><div><h1>نقش‌ها و دسترسی‌ها</h1><p>تعریف نقش‌ها و تعیین مجوزهای هر نقش</p></div></div>
@if(session('success'))
  <div class="panel" style="margin-bottom:16px;border-color:#245447;color:#6ee7d0">{{ session('success') }}</div>
@endif
<style>
.role-card{background:#0a1b2b;border:1px solid #1b354b;border-radius:16px;padding:18px;margin-bottom:14px}
.role-card input:not([type=checkbox]){width:100%;min-width:0;padding:10px;background:#081522;border:1px solid #29465e;color:#e9f3fb;border-radius:8px;font:inherit}
.role-fields{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px;margin:12px 0}
.permission-list{display:flex;flex-wrap:wrap;gap:8px;margin:12px 0}
.permission-list label{display:flex;align-items:center;gap:6px;background:#10283c;border:1px solid #1b354b;padding:7px 9px;border-radius:8px;font-size:10px}
.role-title{font-size:14px;margin-bottom:6px}.muted-role{color:#71879d;font-size:10px}
@media(max-width:650px){.role-fields{grid-template-columns:1fr}}
</style>
<div class="role-card">
  <h2 class="role-title">ایجاد نقش جدید</h2>
  <form method="POST" action="{{ route('roles.store') }}">
    @csrf
    <div class="role-fields">
      <input name="name" placeholder="نام نقش" required>
      <input name="slug" placeholder="شناسه (slug)" required>
      <input name="description" placeholder="توضیح نقش">
    </div>
    <div class="permission-list">
      @foreach($permissions as $p)<label><input type="checkbox" name="permissions[]" value="{{ $p->id }}"> {{ $p->name }}</label>@endforeach
    </div>
    <button class="btn primary" type="submit">ایجاد نقش</button>
  </form>
</div>
@forelse($roles as $role)
  <div class="role-card">
    <div class="role-title">{{ $role->name }}</div>
    <div class="muted-role">شناسه: {{ $role->slug }}</div>
    @if(!$role->is_system)
      <form method="POST" action="{{ route('roles.update',$role) }}">
        @csrf
        @method('PUT')
        <div class="role-fields">
          <input name="name" value="{{ $role->name }}" placeholder="نام نقش" required>
          <input name="slug" value="{{ $role->slug }}" placeholder="شناسه (slug)" required>
        </div>
        <div class="permission-list">
          @foreach($permissions as $p)<label><input type="checkbox" name="permissions[]" value="{{ $p->id }}" @checked($role->permissions->contains($p->id))> {{ $p->name }}</label>@endforeach
        </div>
        <div class="actions" style="margin:0">
          <button class="btn primary" type="submit">ذخیره تغییرات</button>
          <button class="btn" type="submit" form="delete-role-{{ $role->id }}" style="background:#3b2528;border-color:#63343b;color:#ffb8a8" onclick="return confirm('از حذف این نقش مطمئن هستید؟')">حذف نقش</button>
        </div>
      </form>
      <form id="delete-role-{{ $role->id }}" method="POST" action="{{ route('roles.destroy',$role) }}" style="display:none">@csrf @method('DELETE')</form>
    @else
      <div class="muted-role" style="margin-top:10px">مجوزها: {{ $role->permissions->pluck('name')->join('، ') ?: 'بدون مجوز' }}</div>
      <span class="status active" style="margin-top:10px">نقش سیستمی</span>
    @endif
  </div>
@empty
  <div class="panel" style="color:#71879d;text-align:center">نقشی برای نمایش وجود ندارد.</div>
@endforelse
@endsection
