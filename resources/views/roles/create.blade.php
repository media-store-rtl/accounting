@extends('layouts.dashboard-shell')

@section('title', 'ایجاد نقش جدید')

@section('content')
<div class="page-heading">
    <div>
        <h1>ایجاد نقش جدید</h1>
        <p>ابتدا مشخصات نقش را وارد کنید و سپس دسترسی‌های موردنیاز را از دسته‌بندی‌ها انتخاب کنید.</p>
    </div>
    <a class="btn" href="{{ route('roles.index') }}">بازگشت به فهرست</a>
</div>

@if($errors->any())
    <div class="panel" style="margin-bottom:16px;border-color:#63343b;color:#ffb8a8">
        <strong>لطفاً موارد زیر را بررسی کنید:</strong>
        <ul style="margin:8px 18px 0 0;line-height:2">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

<style>
.role-panel{background:#0a1b2b;border:1px solid #1b354b;border-radius:16px;padding:18px;margin-bottom:16px}
.role-fields{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}
.role-field{display:flex;flex-direction:column;gap:7px;min-width:0}
.role-field label{font-size:12px;color:#a9bac9}
.role-field input,.role-field textarea{width:100%;min-width:0;padding:10px;background:#081522;border:1px solid #29465e;color:#e9f3fb;border-radius:8px;font:inherit}
.permission-group{border:1px solid #1b354b;border-radius:12px;margin-top:10px;overflow:hidden}
.permission-group summary{cursor:pointer;list-style:none;display:flex;align-items:center;justify-content:space-between;gap:12px;padding:13px;background:#10283c;color:#e9f3fb;font-size:12px}
.permission-group summary::-webkit-details-marker{display:none}
.permission-group summary:after{content:'＋';color:#8ce8ce;font-size:16px}
.permission-group[open] summary:after{content:'−'}
.permission-items{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;padding:12px}
.permission-item{display:flex;align-items:flex-start;gap:8px;padding:9px;border:1px solid #1b354b;border-radius:8px;color:#c2d1de;font-size:11px;line-height:1.8}
.permission-item input{margin-top:4px;accent-color:#4cc9a6}
.permission-count{font-size:10px;color:#8ce8ce;white-space:nowrap}
.role-actions{display:flex;justify-content:flex-end;gap:8px;flex-wrap:wrap;margin-top:16px}
@media(max-width:700px){.role-fields,.permission-items{grid-template-columns:1fr}}
</style>

<form method="POST" action="{{ route('roles.store') }}">
    @csrf
    <section class="role-panel">
        <h2 style="font-size:14px;margin-bottom:14px">مشخصات نقش</h2>
        <div class="role-fields">
            <div class="role-field">
                <label for="role-name">نام نقش</label>
                <input id="role-name" name="name" value="{{ old('name') }}" placeholder="مثلاً سرپرست تولید" required maxlength="100">
            </div>
            <div class="role-field">
                <label for="role-slug">شناسه انگلیسی</label>
                <input id="role-slug" name="slug" value="{{ old('slug') }}" placeholder="production-supervisor" required maxlength="100" pattern="[A-Za-z0-9_-]+">
            </div>
            <div class="role-field">
                <label for="role-description">توضیحات</label>
                <input id="role-description" name="description" value="{{ old('description') }}" placeholder="شرح کوتاه مسئولیت‌ها" maxlength="255">
            </div>
        </div>
    </section>

    <section class="role-panel">
        <h2 style="font-size:14px;margin-bottom:5px">دسترسی‌ها</h2>
        <p style="font-size:11px;color:#8198ac;line-height:1.9">دستهٔ موردنظر را باز کنید و فقط مجوزهای لازم را انتخاب کنید. دسته‌ها به‌صورت پیش‌فرض بسته هستند تا صفحه خلوت بماند.</p>
        @foreach($permissions as $module => $items)
            <details class="permission-group">
                <summary>
                    <span>{{ $moduleLabels[$module] ?? ucfirst(str_replace('_', ' ', $module ?: 'other')) }}</span>
                    <span class="permission-count">{{ $items->count() }} مجوز</span>
                </summary>
                <div class="permission-items">
                    @foreach($items as $permission)
                        <label class="permission-item">
                            <input type="checkbox" name="permissions[]" value="{{ $permission->id }}" @checked(in_array($permission->id, old('permissions', [])))>
                            <span>
                                <strong style="display:block;color:#e9f3fb;font-weight:600">{{ $permission->name }}</strong>
                                <span style="color:#71879d">{{ $permission->slug }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </details>
        @endforeach
        <div class="role-actions">
            <a class="btn" href="{{ route('roles.index') }}">انصراف</a>
            <button class="btn primary" type="submit">ایجاد نقش</button>
        </div>
    </section>
</form>
@endsection
