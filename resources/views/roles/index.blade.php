@extends('layouts.dashboard-shell')

@section('title', 'نقش‌ها و دسترسی‌ها')

@section('content')
<div class="page-heading">
    <div>
        <h1>نقش‌ها و دسترسی‌ها</h1>
        <p>نقش‌ها را جداگانه مدیریت کنید و مجوزهای هر نقش را در صفحهٔ خودش ببینید.</p>
    </div>
    <a class="btn primary" href="{{ route('roles.create') }}">ایجاد نقش جدید</a>
</div>

@if(session('success'))
    <div class="panel" style="margin-bottom:16px;border-color:#245447;color:#6ee7d0">{{ session('success') }}</div>
@endif

<style>
.role-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
.role-card{display:flex;flex-direction:column;gap:14px;background:#0a1b2b;border:1px solid #1b354b;border-radius:16px;padding:18px;min-width:0}
.role-card-top{display:flex;align-items:flex-start;gap:12px}
.role-icon{display:flex;align-items:center;justify-content:center;flex:0 0 42px;height:42px;border-radius:12px;background:#10283c;color:#8ce8ce;font-size:20px}
.role-name{font-size:14px;font-weight:700;color:#e9f3fb}
.role-slug{font-size:11px;color:#8198ac;margin-top:5px;overflow-wrap:anywhere}
.role-description{font-size:12px;color:#a9bac9;line-height:1.9}
.role-stats{display:flex;flex-wrap:wrap;gap:8px}
.role-stat{padding:6px 9px;border-radius:8px;background:#10283c;color:#b9cbd9;font-size:11px}
.role-card-footer{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-top:auto;padding-top:12px;border-top:1px solid #1b354b}
.role-system{font-size:10px;color:#d7c28a}
@media(max-width:700px){.role-grid{grid-template-columns:1fr}.page-heading{gap:12px;flex-wrap:wrap}}
</style>

@if($roles->isNotEmpty())
    <div class="role-grid">
        @foreach($roles as $role)
            <article class="role-card">
                <div class="role-card-top">
                    <div class="role-icon" aria-hidden="true">♙</div>
                    <div style="min-width:0;flex:1">
                        <div class="role-name">{{ $role->name }}</div>
                        <div class="role-slug">شناسه: {{ $role->slug }}</div>
                    </div>
                    @if($role->is_system)
                        <span class="role-system">نقش سیستمی</span>
                    @endif
                </div>

                @if($role->description)
                    <div class="role-description">{{ $role->description }}</div>
                @else
                    <div class="role-description">برای این نقش توضیحی ثبت نشده است.</div>
                @endif

                <div class="role-stats">
                    <span class="role-stat">{{ $role->permissions->count() }} مجوز</span>
                    <span class="role-stat">{{ $role->users_count }} کاربر</span>
                </div>

                <div class="role-card-footer">
                    <span class="role-description">{{ $role->is_system ? 'مدیریت محدود' : 'قابل مدیریت' }}</span>
                    <a class="btn" href="{{ route('roles.show', $role) }}">مشاهده و مدیریت <span aria-hidden="true">←</span></a>
                </div>
            </article>
        @endforeach
    </div>
@else
    <div class="panel" style="text-align:center;padding:36px 18px">
        <h2 style="font-size:15px;margin-bottom:8px">هنوز نقشی تعریف نشده است</h2>
        <p style="color:#8198ac;font-size:12px;margin-bottom:16px">برای شروع، اولین نقش را ایجاد کنید.</p>
        <a class="btn primary" href="{{ route('roles.create') }}">ایجاد نقش جدید</a>
    </div>
@endif
@endsection
