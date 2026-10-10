@extends('layouts.dashboard-shell')

@section('title', 'اطلاعات مجموعه | حسابداری صنعتی')

@section('content')
<div class="page-heading">
    <div>
        <h1>اطلاعات مجموعه</h1>
        <p>اطلاعات پایه، هویتی و تماس مجموعه را مدیریت کنید.</p>
    </div>
    <a class="btn" href="{{ route('dashboard') }}">بازگشت به داشبورد</a>
</div>

<style>
.company-form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:15px}
.company-form-section{grid-column:1/-1;color:#6ee7d0;font-size:12px;font-weight:800;margin-top:9px;padding-bottom:8px;border-bottom:1px solid #1b354b}
.company-form-field{min-width:0}
.company-form-field.full{grid-column:1/-1}
.company-form-field label{display:block;color:#b9cbd9;font-size:11px;margin-bottom:7px}
.company-form-field input,.company-form-field select,.company-form-field textarea{width:100%;background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:10px;padding:11px 13px;font-family:inherit;font-size:12px;outline:none}
.company-form-field textarea{min-height:95px;resize:vertical}
.company-form-field input:focus,.company-form-field select:focus,.company-form-field textarea:focus{border-color:#6ee7d0}
.company-form-error{color:#ff9d9d;font-size:10px;margin-top:6px}
.company-form-success{background:#0d2c2b;border:1px solid #1e625a;color:#8ff0dd;border-radius:10px;padding:12px;font-size:11px;margin-bottom:18px}
.company-form-actions{display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-top:22px}
.company-form-save{border:0;border-radius:10px;padding:11px 18px;background:#6ee7d0;color:#06151d;font-family:inherit;font-size:11px;font-weight:800;cursor:pointer}
.company-form-cancel{color:#8ba1b5;text-decoration:none;font-size:11px}
@media(max-width:650px){.company-form-grid{grid-template-columns:1fr}.company-form-section,.company-form-field.full{grid-column:auto}}
</style>

@if(session('success'))
    <div class="company-form-success">{{ session('success') }}</div>
@endif

@if($errors->any())
    <div class="company-form-success" style="background:#3b2528;border-color:#6b343b;color:#ffb8b8">
        لطفاً خطاهای فرم را بررسی کنید.
    </div>
@endif

<div class="panel">
    <form method="POST" action="{{ route('company.update') }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="company-form-grid">
            <div class="company-form-section">اطلاعات پایه</div>
            <div class="company-form-field">
                <label for="name">نام مجموعه *</label>
                <input id="name" name="name" value="{{ old('name', $company->name) }}" required maxlength="255">
                @error('name')<div class="company-form-error">{{ $message }}</div>@enderror
            </div>
            <div class="company-form-field">
                <label for="trade_name">نام تجاری</label>
                <input id="trade_name" name="trade_name" value="{{ old('trade_name', $company->trade_name) }}" maxlength="255">
                @error('trade_name')<div class="company-form-error">{{ $message }}</div>@enderror
            </div>
            <div class="company-form-field">
                <label for="code">کد مجموعه *</label>
                <input id="code" name="code" value="{{ old('code', $company->code) }}" required maxlength="50">
                @error('code')<div class="company-form-error">{{ $message }}</div>@enderror
            </div>
            <div class="company-form-field">
                <label for="type">نوع مجموعه</label>
                <select id="type" name="type">
                    <option value="">انتخاب کنید</option>
                    @foreach(['شرکت'=>'شرکت','فروشگاه'=>'فروشگاه','کارگاه'=>'کارگاه','مؤسسه'=>'مؤسسه','مجموعه'=>'مجموعه','سایر'=>'سایر'] as $value=>$label)
                        <option value="{{ $value }}" @selected(old('type', $company->type) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('type')<div class="company-form-error">{{ $message }}</div>@enderror
            </div>

            <div class="company-form-section">اطلاعات ثبتی</div>
            <div class="company-form-field">
                <label for="national_id">شناسه ملی</label>
                <input id="national_id" name="national_id" value="{{ old('national_id', $company->national_id) }}" maxlength="50">
                @error('national_id')<div class="company-form-error">{{ $message }}</div>@enderror
            </div>
            <div class="company-form-field">
                <label for="registration_number">شماره ثبت</label>
                <input id="registration_number" name="registration_number" value="{{ old('registration_number', $company->registration_number) }}" maxlength="50">
                @error('registration_number')<div class="company-form-error">{{ $message }}</div>@enderror
            </div>
            <div class="company-form-field">
                <label for="economic_code">کد اقتصادی</label>
                <input id="economic_code" name="economic_code" value="{{ old('economic_code', $company->economic_code) }}" maxlength="50">
                @error('economic_code')<div class="company-form-error">{{ $message }}</div>@enderror
            </div>

            <div class="company-form-section">اطلاعات تماس</div>
            <div class="company-form-field">
                <label for="phone">تلفن</label>
                <input id="phone" name="phone" value="{{ old('phone', $company->phone) }}" maxlength="50">
                @error('phone')<div class="company-form-error">{{ $message }}</div>@enderror
            </div>
            <div class="company-form-field">
                <label for="mobile">موبایل</label>
                <input id="mobile" name="mobile" value="{{ old('mobile', $company->mobile) }}" maxlength="50">
                @error('mobile')<div class="company-form-error">{{ $message }}</div>@enderror
            </div>
            <div class="company-form-field">
                <label for="email">ایمیل</label>
                <input id="email" name="email" type="email" value="{{ old('email', $company->email) }}" maxlength="255">
                @error('email')<div class="company-form-error">{{ $message }}</div>@enderror
            </div>
            <div class="company-form-field">
                <label for="website">وب‌سایت</label>
                <input id="website" name="website" type="url" value="{{ old('website', $company->website) }}" maxlength="255">
                @error('website')<div class="company-form-error">{{ $message }}</div>@enderror
            </div>

            <div class="company-form-section">نشانی</div>
            <div class="company-form-field">
                <label for="province">استان</label>
                <input id="province" name="province" value="{{ old('province', $company->province) }}" maxlength="100">
                @error('province')<div class="company-form-error">{{ $message }}</div>@enderror
            </div>
            <div class="company-form-field">
                <label for="city">شهر</label>
                <input id="city" name="city" value="{{ old('city', $company->city) }}" maxlength="100">
                @error('city')<div class="company-form-error">{{ $message }}</div>@enderror
            </div>
            <div class="company-form-field">
                <label for="postal_code">کدپستی</label>
                <input id="postal_code" name="postal_code" value="{{ old('postal_code', $company->postal_code) }}" maxlength="30">
                @error('postal_code')<div class="company-form-error">{{ $message }}</div>@enderror
            </div>
            <div class="company-form-field full">
                <label for="address">آدرس</label>
                <textarea id="address" name="address">{{ old('address', $company->address) }}</textarea>
                @error('address')<div class="company-form-error">{{ $message }}</div>@enderror
            </div>

            <div class="company-form-section">لوگو</div>
            <div class="company-form-field full">
                <label for="logo">فایل لوگو (اختیاری، حداکثر ۲ مگابایت)</label>
                <input id="logo" name="logo" type="file" accept="image/*">
                @error('logo')<div class="company-form-error">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="company-form-actions">
            <button class="company-form-save" type="submit">ذخیره تغییرات</button>
            <a class="company-form-cancel" href="{{ route('dashboard') }}">انصراف</a>
        </div>
    </form>
</div>
@endsection
