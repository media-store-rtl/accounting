<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>اطلاعات مجموعه | حسابداری صنعتی</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
:root{font-family:"Vazirmatn","Segoe UI",Tahoma,sans-serif;background:#07111f;color:#e9f3fb}*{box-sizing:border-box}body{margin:0;background:#07111f}.wrap{max-width:760px;margin:0 auto;padding:34px 20px}.panel{background:#0a1b2b;border:1px solid #1b354b;border-radius:18px;padding:24px}.back{display:inline-block;color:#6ee7d0;text-decoration:none;font-size:10px;margin-bottom:18px}.eyebrow{color:#6ee7d0;font-size:9px;font-weight:800;letter-spacing:1px}.title{font-size:24px;margin:7px 0 4px}.hint{color:#71879d;font-size:10px;line-height:2;margin:0 0 24px}.field{margin-bottom:17px}.field label{display:block;color:#b9cbd9;font-size:10px;margin-bottom:7px}.field input{width:100%;background:#0d2235;color:#e9f3fb;border:1px solid #29465e;border-radius:10px;padding:12px 13px;font-family:inherit;font-size:11px;outline:none}.field input:focus{border-color:#6ee7d0}.error{color:#ff9d9d;font-size:9px;margin-top:6px}.actions{display:flex;gap:10px;align-items:center;margin-top:22px}.save{border:0;border-radius:10px;padding:11px 18px;background:#6ee7d0;color:#06151d;font-family:inherit;font-size:10px;font-weight:800;cursor:pointer}.cancel{color:#8ba1b5;text-decoration:none;font-size:10px}.success{background:#0d2c2b;border:1px solid #1e625a;color:#8ff0dd;border-radius:10px;padding:11px;font-size:10px;margin-bottom:18px}
</style>
</head>
<body>
<div class="wrap">
<a class="back" href="{{ route('dashboard') }}">← بازگشت به داشبورد</a>
<div class="panel">
<div class="eyebrow">COMPANY SETTINGS</div>
<h1 class="title">اطلاعات مجموعه</h1>
<p class="hint">نام مجموعه و کد آن را از این بخش می‌توانی ثبت یا ویرایش کنی.</p>

@if(session('success'))
<div class="success">{{ session('success') }}</div>
@endif

<form method="POST" action="{{ route('company.update') }}">
@csrf
@method('PUT')

<div class="field">
<label for="name">نام مجموعه <span style="color:#6ee7d0">*</span></label>
<input id="name" name="name" type="text" value="{{ old('name', $company->name) }}" required maxlength="255" autofocus>
@error('name')<div class="error">{{ $message }}</div>@enderror
</div>

<div class="field">
<label for="code">کد مجموعه <span style="color:#6ee7d0">*</span></label>
<input id="code" name="code" type="text" value="{{ old('code', $company->code) }}" required maxlength="50">
@error('code')<div class="error">{{ $message }}</div>@enderror
</div>

<div class="actions">
<button class="save" type="submit">ذخیره تغییرات</button>
<a class="cancel" href="{{ route('dashboard') }}">انصراف</a>
</div>
</form>
</div>
</div>
</body>
</html>
