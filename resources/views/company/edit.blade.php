<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>اطلاعات مجموعه | حسابداری صنعتی</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
:root{font-family:"Vazirmatn","Segoe UI",Tahoma,sans-serif;background:#07111f;color:#e9f3fb}*{box-sizing:border-box}body{margin:0;background:#07111f}.wrap{max-width:980px;margin:0 auto;padding:34px 20px}.panel{background:#0a1b2b;border:1px solid #1b354b;border-radius:18px;padding:24px}.back{display:inline-block;color:#6ee7d0;text-decoration:none;font-size:10px;margin-bottom:18px}.eyebrow{color:#6ee7d0;font-size:9px;font-weight:800;letter-spacing:1px}.title{font-size:24px;margin:7px 0 4px}.hint{color:#71879d;font-size:10px;line-height:2;margin:0 0 24px}.grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}.field{margin-bottom:4px}.field.full{grid-column:1/-1}.field label{display:block;color:#b9cbd9;font-size:10px;margin-bottom:7px}.field input,.field select,.field textarea{width:100%;background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:10px;padding:11px 13px;font-family:inherit;font-size:11px;outline:none}.field textarea{min-height:90px;resize:vertical}.field input:focus,.field select:focus,.field textarea:focus{border-color:#6ee7d0}.error{color:#ff9d9d;font-size:9px;margin-top:6px}.actions{display:flex;gap:10px;align-items:center;margin-top:22px}.save{border:0;border-radius:10px;padding:11px 18px;background:#6ee7d0;color:#06151d;font-family:inherit;font-size:10px;font-weight:800;cursor:pointer}.cancel{color:#8ba1b5;text-decoration:none;font-size:10px}.success{background:#0d2c2b;border:1px solid #1e625a;color:#8ff0dd;border-radius:10px;padding:11px;font-size:10px;margin-bottom:18px}.section-title{grid-column:1/-1;color:#6ee7d0;font-size:11px;font-weight:800;margin-top:10px;padding-bottom:7px;border-bottom:1px solid #173047}@media(max-width:700px){.grid{grid-template-columns:1fr}.field.full{grid-column:auto}.section-title{grid-column:auto}}
</style>
</head>
<body>
<div class="wrap">
<a class="back" href="{{ route('dashboard') }}">← بازگشت به داشبورد</a>
<div class="panel">
<div class="eyebrow">COMPANY SETTINGS</div>
<h1 class="title">اطلاعات مجموعه</h1>
<p class="hint">اطلاعات پایه، هویتی و تماس مجموعه در این بخش نگهداری می‌شود. لوگو به‌صورت فایل در Storage ذخیره می‌شود.</p>

@if(session('success'))<div class="success">{{ session('success') }}</div>@endif

<form method="POST" action="{{ route('company.update') }}" enctype="multipart/form-data">
@csrf @method('PUT')
<div class="grid">
<div class="section-title">اطلاعات پایه</div>
<div class="field"><label for="name">نام مجموعه *</label><input id="name" name="name" value="{{ old('name',$company->name) }}" required maxlength="255">@error('name')<div class="error">{{ $message }}</div>@enderror</div>
<div class="field"><label for="trade_name">نام تجاری</label><input id="trade_name" name="trade_name" value="{{ old('trade_name',$company->trade_name) }}" maxlength="255">@error('trade_name')<div class="error">{{ $message }}</div>@enderror</div>
<div class="field"><label for="code">کد مجموعه *</label><input id="code" name="code" value="{{ old('code',$company->code) }}" required maxlength="50">@error('code')<div class="error">{{ $message }}</div>@enderror</div>
<div class="field"><label for="type">نوع مجموعه</label><select id="type" name="type"><option value="">انتخاب کنید</option>@foreach(['شرکت'=>'شرکت','فروشگاه'=>'فروشگاه','کارگاه'=>'کارگاه','مؤسسه'=>'مؤسسه','مجموعه'=>'مجموعه','سایر'=>'سایر'] as $value=>$label)<option value="{{ $value }}" @selected(old('type',$company->type)===$value)>{{ $label }}</option>@endforeach</select></div>

<div class="section-title">اطلاعات ثبتی</div>
<div class="field"><label for="national_id">شناسه ملی</label><input id="national_id" name="national_id" value="{{ old('national_id',$company->national_id) }}" maxlength="50"></div>
<div class="field"><label for="registration_number">شماره ثبت</label><input id="registration_number" name="registration_number" value="{{ old('registration_number',$company->registration_number) }}" maxlength="50"></div>
<div class="field"><label for="economic_code">کد اقتصادی</label><input id="economic_code" name="economic_code" value="{{ old('economic_code',$company->economic_code) }}" maxlength="50"></div>

<div class="section-title">اطلاعات تماس</div>
<div class="field"><label for="phone">تلفن</label><input id="phone" name="phone" value="{{ old('phone',$company->phone) }}" maxlength="50"></div>
<div class="field"><label for="mobile">موبایل</label><input id="mobile" name="mobile" value="{{ old('mobile',$company->mobile) }}" maxlength="50"></div>
<div class="field"><label for="email">ایمیل</label><input id="email" name="email" type="email" value="{{ old('email',$company->email) }}" maxlength="255">@error('email')<div class="error">{{ $message }}</div>@enderror</div>
<div class="field"><label for="website">وب‌سایت</label><input id="website" name="website" type="url" value="{{ old('website',$company->website) }}" maxlength="255">@error('website')<div class="error">{{ $message }}</div>@enderror</div>

<div class="section-title">نشانی</div>
<div class="field"><label for="province">استان</label><input id="province" name="province" value="{{ old('province',$company->province) }}" maxlength="100"></div>
<div class="field"><label for="city">شهر</label><input id="city" name="city" value="{{ old('city',$company->city) }}" maxlength="100"></div>
<div class="field"><label for="postal_code">کدپستی</label><input id="postal_code" name="postal_code" value="{{ old('postal_code',$company->postal_code) }}" maxlength="30"></div>
<div class="field full"><label for="address">آدرس</label><textarea id="address" name="address">{{ old('address',$company->address) }}</textarea></div>

<div class="section-title">لوگو</div>
<div class="field full"><label for="logo">فایل لوگو (اختیاری، حداکثر ۲ مگابایت)</label><input id="logo" name="logo" type="file" accept="image/*">@error('logo')<div class="error">{{ $message }}</div>@enderror</div>
</div>

<div class="actions"><button class="save" type="submit">ذخیره تغییرات</button><a class="cancel" href="{{ route('dashboard') }}">انصراف</a></div>
</form>
</div>
</div>
</body>
</html>
