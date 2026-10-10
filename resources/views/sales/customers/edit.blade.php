@extends('layouts.dashboard-shell')

@section('title', 'ویرایش مشتری | حسابداری صنعتی')

@section('content')
<div class="page-heading">
    <div>
        <h1>ویرایش مشتری</h1>
        <p>اطلاعات مشتری «{{ $customer->name }}» را اصلاح کنید</p>
    </div>
    <a class="btn" href="{{ route('sales.customers.index') }}">بازگشت به فهرست مشتریان</a>
</div>

@if($errors->any())
    <div class="panel" style="margin-bottom:16px;border-color:#754047;color:#ffb8a8">
        <strong>لطفاً خطاهای زیر را بررسی کنید:</strong>
        <ul style="margin-bottom:0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="panel" style="max-width:900px">
    <form method="POST" action="{{ route('sales.customers.update', $customer->id) }}">
        @csrf
        @method('PUT')
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:16px">
            <div>
                <label for="name" style="display:block;margin-bottom:7px;font-size:12px">نام مشتری <span style="color:#ffb8a8">*</span></label>
                <input id="name" name="name" value="{{ old('name', $customer->name) }}" required autocomplete="organization" class="form-control" style="width:100%;padding:11px;background:#10283c;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;font:inherit">
                @error('name')<small style="color:#ffb8a8">{{ $message }}</small>@enderror
            </div>
            <div>
                <label for="code" style="display:block;margin-bottom:7px;font-size:12px">کد مشتری <span style="color:#ffb8a8">*</span></label>
                <input id="code" name="code" value="{{ old('code', $customer->code) }}" required autocomplete="off" class="form-control" style="width:100%;padding:11px;background:#10283c;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;font:inherit">
                @error('code')<small style="color:#ffb8a8">{{ $message }}</small>@enderror
            </div>
            <div>
                <label for="national_id" style="display:block;margin-bottom:7px;font-size:12px">شناسه ملی</label>
                <input id="national_id" name="national_id" value="{{ old('national_id', $customer->national_id) }}" autocomplete="off" class="form-control" style="width:100%;padding:11px;background:#10283c;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;font:inherit">
                @error('national_id')<small style="color:#ffb8a8">{{ $message }}</small>@enderror
            </div>
            <div>
                <label for="phone" style="display:block;margin-bottom:7px;font-size:12px">تلفن</label>
                <input id="phone" name="phone" value="{{ old('phone', $customer->phone) }}" type="tel" autocomplete="tel" class="form-control" style="width:100%;padding:11px;background:#10283c;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;font:inherit">
                @error('phone')<small style="color:#ffb8a8">{{ $message }}</small>@enderror
            </div>
            <div>
                <label for="email" style="display:block;margin-bottom:7px;font-size:12px">ایمیل</label>
                <input id="email" name="email" value="{{ old('email', $customer->email) }}" type="email" autocomplete="email" class="form-control" style="width:100%;padding:11px;background:#10283c;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;font:inherit">
                @error('email')<small style="color:#ffb8a8">{{ $message }}</small>@enderror
            </div>
            <div style="grid-column:1/-1">
                <label for="address" style="display:block;margin-bottom:7px;font-size:12px">آدرس</label>
                <textarea id="address" name="address" rows="4" autocomplete="street-address" style="width:100%;padding:11px;background:#10283c;color:#e9f3fb;border:1px solid #29465e;border-radius:9px;font:inherit;resize:vertical">{{ old('address', $customer->address) }}</textarea>
                @error('address')<small style="color:#ffb8a8">{{ $message }}</small>@enderror
            </div>
        </div>
        <div class="actions" style="margin-top:22px;margin-bottom:0">
            <button type="submit" class="btn primary">ذخیره تغییرات</button>
            <a class="btn" href="{{ route('sales.customers.index') }}">انصراف</a>
        </div>
    </form>
</div>
@endsection
