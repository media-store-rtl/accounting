<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>ورود به حسابداری</title>
<style>
*{box-sizing:border-box}
body{margin:0;min-height:100vh;display:grid;place-items:center;background:#07111f;color:#e9f3fb;font-family:Tahoma,Arial,sans-serif}
.box{width:min(460px,92%);padding:32px;background:#0a1b2b;border:1px solid #1b354b;border-radius:20px}
h1{font-size:23px;margin:0 0 8px}
.muted{color:#8ba1b5;font-size:12px;line-height:2;margin-bottom:24px}
.sso{display:block;text-align:center;padding:13px 18px;border-radius:12px;background:#6ee7d0;color:#06131d;text-decoration:none;font-weight:bold;font-size:13px;margin-bottom:22px}
.divider{display:flex;align-items:center;gap:10px;color:#71879a;font-size:11px;margin:18px 0}
.divider:before,.divider:after{content:"";height:1px;background:#1b354b;flex:1}
label{display:block;font-size:12px;margin:14px 0 7px}
input{width:100%;padding:12px;border:1px solid #28455b;border-radius:10px;background:#071522;color:#fff;outline:none}
button{width:100%;border:0;padding:13px;border-radius:12px;background:#2d7ff9;color:#fff;font-weight:bold;cursor:pointer;margin-top:20px}
.errors{background:#3a1620;border:1px solid #6f2838;border-radius:10px;padding:10px 14px;font-size:12px;line-height:2;margin-bottom:15px}
.remember{display:flex;align-items:center;gap:8px;font-size:11px;color:#9db0c0;margin-top:12px}
.remember input{width:auto}
</style>
</head>
<body>
<main class="box">
<h1>ورود به حسابداری</h1>
<div class="muted">روش ورود خود را انتخاب کنید.</div>

<a class="sso" href="{{ route('sso.start') }}">ورود با حساب Web2022</a>

<div class="divider">یا ورود مستقیم</div>

@if ($errors->any())
<div class="errors">
    @foreach ($errors->all() as $error)
        <div>{{ $error }}</div>
    @endforeach
</div>
@endif

<form method="POST" action="{{ route('login.submit') }}">
    @csrf

    <label for="email">ایمیل</label>
    <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email">

    <label for="password">رمز عبور</label>
    <input id="password" name="password" type="password" required autocomplete="current-password">

    <label class="remember">
        <input type="checkbox" name="remember" value="1">
        مرا به خاطر بسپار
    </label>

    <button type="submit">ورود</button>
</form>
</main>
</body>
</html>
