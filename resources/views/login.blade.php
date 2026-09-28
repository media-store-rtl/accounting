<!doctype html>
<html lang="fa" dir="rtl">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>ورود | حسابداری صنعتی</title>
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
body{margin:0;min-height:100vh;font-family:Vazirmatn,Segoe UI,Tahoma,sans-serif;background:#050b14;color:#edf6ff;display:grid;place-items:center}.box{width:min(430px,88%);background:#0b1b2c;border:1px solid #29445f;border-radius:26px;padding:34px;box-shadow:0 25px 80px #0008}h1{margin:0 0 8px;font-size:25px}.muted{color:#8fa6bd;line-height:1.9;margin-bottom:25px;font-size:12px}label{display:block;margin:16px 0 7px;color:#b9cadd;font-size:11px}input{width:100%;padding:13px;border-radius:12px;border:1px solid #2d4963;background:#081725;color:white;box-sizing:border-box;font:inherit}input:focus{outline:none;border-color:#55cdb6}button{width:100%;margin-top:24px;padding:14px;border:0;border-radius:13px;background:#6ee7d0;color:#06131d;font-weight:800;font-family:inherit;cursor:pointer}.remember{display:flex;align-items:center;gap:8px;margin-top:14px;color:#8197aa;font-size:10px}.remember input{width:auto}.error{margin-top:14px;padding:10px 12px;border-radius:10px;background:#3a1720;color:#ffb5c1;font-size:10px}.back{display:block;text-align:center;margin-top:18px;color:#9fc1df;text-decoration:none;font-size:10px}
</style></head><body><main class="box"><h1>ورود به حسابداری</h1><div class="muted">مدیریت شرکت، تولید، انبار و بهای تمام‌شده</div>
@if($errors->any())<div class="error">{{ $errors->first() }}</div>@endif
<form method="POST" action="{{ route('login.store') }}">@csrf
<label>ایمیل</label><input name="email" type="email" value="{{ old('email') }}" placeholder="name@example.com" required autofocus>
<label>رمز عبور</label><input name="password" type="password" placeholder="••••••••" required>
<label class="remember"><input name="remember" type="checkbox" value="1"> مرا به خاطر بسپار</label>
<button type="submit">ورود به سیستم</button></form><a class="back" href="/">بازگشت به صفحه اصلی</a></main></body></html>