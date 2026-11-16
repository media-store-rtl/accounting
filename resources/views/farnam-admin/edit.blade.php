<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>مدیریت وب‌سایت فرنام</title>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *{box-sizing:border-box}body{margin:0;background:#f3f5f7;color:#203247;font-family:Vazirmatn,Tahoma,sans-serif}
        .wrap{width:min(980px,calc(100% - 32px));margin:36px auto}.head,.panel{background:#fff;border:1px solid #e5e9ed;border-radius:18px;padding:24px;margin-bottom:18px}
        h1{margin:0 0 8px;font-size:24px;color:#142d46}h2{font-size:17px;margin:0 0 18px;color:#142d46}p,.hint{color:#718092;font-size:13px;line-height:2}
        .field{margin:0 0 17px}label{display:block;margin-bottom:7px;font-size:13px;font-weight:700}
        input,textarea{width:100%;padding:12px;border:1px solid #dce2e8;border-radius:10px;font:inherit;font-size:13px;background:#fff;color:#203247}
        textarea{min-height:100px;resize:vertical}.service{padding:17px;border:1px solid #e5e9ed;border-radius:13px;margin:12px 0;background:#fafbfc}
        .service-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px}.remove{border:0;background:#fff0f0;color:#a33;padding:7px 12px;border-radius:8px;cursor:pointer}
        .add,.save{border:0;border-radius:10px;padding:12px 19px;font:inherit;font-weight:700;cursor:pointer}.add{background:#f1eee7;color:#705b30}.save{background:#b99a5b;color:white}
        .actions{display:flex;gap:12px;align-items:center;flex-wrap:wrap}.actions a{color:#526579;text-decoration:none;font-size:13px}
        .notice{padding:12px 15px;border-radius:10px;background:#edf8f1;color:#24643d;margin-bottom:16px}.errors{background:#fff0f0;color:#933;padding:12px 18px;border-radius:10px;margin-bottom:16px}
        @media(max-width:600px){.wrap{margin:18px auto}.head,.panel{padding:17px}}
    </style>
</head>
<body>
<div class="wrap">
    <header class="head">
        <h1>مدیریت محتوای وب‌سایت فرنام</h1>
        <p>متن‌های صفحه اصلی و فهرست خدمات را ویرایش کنید. تغییرات پس از ذخیره در وب‌سایت عمومی نمایش داده می‌شوند.</p>
        <div class="actions"><a href="{{ route('farnam.admin.leads.index') }}">پیگیری درخواست‌های مشتریان</a><a href="{{ route('home') }}" target="_blank" rel="noopener">مشاهده وب‌سایت ↗</a><a href="{{ route('dashboard') }}">بازگشت به داشبورد</a></div>
    </header>

    @if (session('status'))<div class="notice">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="errors"><strong>لطفاً موارد زیر را بررسی کنید:</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <form method="POST" action="{{ route('farnam.admin.update') }}">
        @csrf
        @method('PUT')
        <section class="panel">
            <h2>بخش معرفی اصلی</h2>
            <div class="field"><label for="hero_eyebrow">متن کوتاه بالای عنوان</label><input id="hero_eyebrow" name="hero_eyebrow" value="{{ old('hero_eyebrow', $content['hero_eyebrow']) }}" required maxlength="160"></div>
            <div class="field"><label for="hero_title">عنوان اصلی صفحه</label><textarea id="hero_title" name="hero_title" required maxlength="220">{{ old('hero_title', $content['hero_title']) }}</textarea></div>
            <div class="field"><label for="hero_copy">توضیح معرفی</label><textarea id="hero_copy" name="hero_copy" required maxlength="1200">{{ old('hero_copy', $content['hero_copy']) }}</textarea></div>
        </section>
        <section class="panel">
            <h2>خدمات</h2>
            <div class="field"><label for="services_title">عنوان بخش خدمات</label><input id="services_title" name="services_title" value="{{ old('services_title', $content['services_title']) }}" required maxlength="180"></div>
            <div class="field"><label for="services_intro">توضیح بخش خدمات</label><textarea id="services_intro" name="services_intro" required maxlength="1200">{{ old('services_intro', $content['services_intro']) }}</textarea></div>
            <div id="services-list">
                @foreach (old('services', $content['services']) as $index => $service)
                    <div class="service">
                        <div class="service-head"><strong>خدمت <span class="number">{{ $index + 1 }}</span></strong><button type="button" class="remove">حذف</button></div>
                        <div class="field"><label>عنوان خدمت</label><input name="services[{{ $index }}][title]" value="{{ $service['title'] }}" required maxlength="120"></div>
                        <div class="field"><label>توضیحات خدمت</label><textarea name="services[{{ $index }}][description]" required maxlength="500">{{ $service['description'] }}</textarea></div>
                    </div>
                @endforeach
            </div>
            <button type="button" class="add" id="add-service">+ افزودن خدمت</button>
            <p class="hint">حداکثر ۱۲ خدمت قابل ثبت است. برای حذف هر خدمت از دکمه حذف همان کارت استفاده کنید.</p>
        </section>
        <section class="panel">
            <h2>درباره فرنام</h2>
            <div class="field"><label for="about_title">عنوان</label><input id="about_title" name="about_title" value="{{ old('about_title', $content['about_title']) }}" required maxlength="180"></div>
            <div class="field"><label for="about_copy">متن معرفی</label><textarea id="about_copy" name="about_copy" required maxlength="1800">{{ old('about_copy', $content['about_copy']) }}</textarea></div>
        </section>
        <section class="panel">
            <h2>راه‌های ارتباطی</h2>
            <div class="field"><label for="contact_title">عنوان بخش تماس</label><input id="contact_title" name="contact_title" value="{{ old('contact_title', $content['contact_title']) }}" required maxlength="180"></div>
            <div class="field"><label for="contact_copy">متن بخش تماس</label><textarea id="contact_copy" name="contact_copy" required maxlength="1200">{{ old('contact_copy', $content['contact_copy']) }}</textarea></div>
            <div class="field"><label for="phone">شماره تماس (با ارقام انگلیسی)</label><input id="phone" name="phone" value="{{ old('phone', $content['phone']) }}" required maxlength="24" pattern="[0-9+()\- ]+"></div>
        </section>
        <div class="actions"><button class="save" type="submit">ذخیره و انتشار تغییرات</button><a href="{{ route('home') }}" target="_blank" rel="noopener">پیش‌نمایش صفحه</a></div>
    </form>
</div>
<script>
(() => {
    const list = document.getElementById('services-list');
    const add = document.getElementById('add-service');
    const renumber = () => {
        [...list.querySelectorAll('.service')].forEach((card, index) => {
            card.querySelector('.number').textContent = index + 1;
            card.querySelectorAll('input,textarea').forEach(field => {
                field.name = field.name.replace(/services\[\d+\]/, 'services[' + index + ']');
            });
        });
        add.disabled = list.querySelectorAll('.service').length >= 12;
    };
    list.addEventListener('click', event => {
        if (event.target.matches('.remove')) {
            event.target.closest('.service').remove();
            renumber();
        }
    });
    add.addEventListener('click', () => {
        const index = list.querySelectorAll('.service').length;
        const card = document.createElement('div');
        card.className = 'service';
        const head = document.createElement('div');
        head.className = 'service-head';
        head.innerHTML = '<strong>خدمت <span class="number"></span></strong><button type="button" class="remove">حذف</button>';
        const titleWrap = document.createElement('div');
        titleWrap.className = 'field';
        const titleLabel = document.createElement('label');
        titleLabel.textContent = 'عنوان خدمت';
        const title = document.createElement('input');
        title.name = 'services[' + index + '][title]';
        title.required = true;
        title.maxLength = 120;
        titleWrap.append(titleLabel, title);
        const descWrap = document.createElement('div');
        descWrap.className = 'field';
        const descLabel = document.createElement('label');
        descLabel.textContent = 'توضیحات خدمت';
        const desc = document.createElement('textarea');
        desc.name = 'services[' + index + '][description]';
        desc.required = true;
        desc.maxLength = 500;
        descWrap.append(descLabel, desc);
        card.append(head, titleWrap, descWrap);
        list.append(card);
        renumber();
        title.focus();
    });
    renumber();
})();
</script>
</body>
</html>
