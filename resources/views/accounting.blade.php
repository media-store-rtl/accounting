<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <title>حسابداری صنعتی | کنترل تولید و بهای تمام‌شده</title>
    <style>
        :root{font-family:"Vazirmatn","Segoe UI",Tahoma,sans-serif;color:#edf5ff;background:#050b14;font-synthesis:none;text-rendering:optimizeLegibility;-webkit-font-smoothing:antialiased}
        *{box-sizing:border-box}
        html{scroll-behavior:smooth}
        body{margin:0;min-width:320px;background:#050b14;color:#edf5ff;font-family:"Vazirmatn","Segoe UI",Tahoma,sans-serif;font-weight:400}
        a{color:inherit}
        .page{min-height:100vh;overflow:hidden;background:radial-gradient(circle at 78% 5%,rgba(56,189,248,.14),transparent 24%),radial-gradient(circle at 8% 42%,rgba(45,212,191,.09),transparent 25%),#050b14}
        .container{width:min(1200px,92%);margin:auto}
        .nav{height:86px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid rgba(148,163,184,.11)}
        .brand{display:flex;align-items:center;gap:12px;text-decoration:none}
        .logo{width:48px;height:48px;border-radius:16px;display:grid;place-items:center;font-size:21px;font-weight:900;color:#041019;background:linear-gradient(135deg,#6ee7d0,#67b7ff);box-shadow:0 18px 45px rgba(68,210,188,.18)}
        .brand strong{display:block;font-size:16px;font-weight:800;letter-spacing:-.25px}.brand span{display:block;margin-top:4px;color:#748ba2;font-size:10px;font-weight:400}
        .nav-link{display:flex;align-items:center;gap:24px;color:#8298ad;font-size:12px;font-weight:500}.nav-link a{text-decoration:none}.nav-link a:not(.nav-login):hover{color:#dcecff}
        .nav-login{padding:11px 18px;border:1px solid #29445e;border-radius:12px;color:#eaf3fc;background:#0a1726}
        .hero{position:relative;display:grid;grid-template-columns:1.02fr .98fr;gap:72px;align-items:center;padding:82px 0 104px}
        .hero-copy-wrap{position:relative;z-index:2}
        .eyebrow{display:inline-flex;align-items:center;gap:10px;color:#6ee7d0;font-size:10px;font-weight:900;letter-spacing:1.4px}.eyebrow i{width:7px;height:7px;border-radius:50%;background:#6ee7d0;box-shadow:0 0 0 7px rgba(110,231,208,.08)}
        h1{font-size:clamp(44px,6vw,76px);line-height:1.08;letter-spacing:-1.8px;font-weight:800;margin:19px 0 23px;max-width:720px}h1 em{font-style:normal;color:#6ee7d0}
        .hero-copy{color:#91a6ba;font-size:16px;line-height:2.2;font-weight:400;max-width:660px;margin:0}
        .actions{display:flex;gap:11px;flex-wrap:wrap;margin-top:32px}.btn{display:inline-flex;align-items:center;justify-content:center;gap:9px;padding:14px 21px;border-radius:13px;text-decoration:none;font-size:12px;font-weight:700;transition:.2s}.btn:hover{transform:translateY(-2px)}
        .primary{background:#6ee7d0;color:#04121a;box-shadow:0 18px 40px rgba(61,210,180,.16)}.secondary{background:#091625;border:1px solid #29445e;color:#dbe8f4}
        .trust{display:flex;gap:9px;flex-wrap:wrap;margin-top:24px}.trust span{padding:8px 11px;border:1px solid #1b3147;border-radius:10px;color:#748ba1;background:rgba(8,22,36,.7);font-size:9px}
        .hero-orb{position:absolute;width:480px;height:480px;right:-270px;top:-90px;border:1px solid rgba(103,214,194,.08);border-radius:50%;box-shadow:inset 0 0 80px rgba(76,201,240,.025)}
        .preview{position:relative}.glow{position:absolute;inset:4% -13%;background:radial-gradient(circle,rgba(52,150,232,.25),transparent 63%);filter:blur(30px)}
        .dashboard{position:relative;border:1px solid #28445d;border-radius:28px;padding:14px;background:linear-gradient(145deg,rgba(10,25,41,.98),rgba(5,14,24,.97));box-shadow:0 45px 110px rgba(0,0,0,.45);transform:rotate(-1.2deg)}
        .dash-top{height:45px;display:flex;align-items:center;justify-content:space-between;padding:0 9px;color:#71889e;font-size:9px}.traffic{display:flex;gap:5px}.traffic i{width:7px;height:7px;border-radius:50%;background:#30465c}
        .dash-body{display:grid;grid-template-columns:62px 1fr;gap:11px}.side{border-radius:16px;background:#071726;padding:9px;display:grid;align-content:start;gap:8px}.side i{height:30px;border-radius:9px;background:#102538}.side i:first-child{background:linear-gradient(90deg,#218f80,#123e50)}
        .screen{min-height:370px;border-radius:17px;background:#071522;padding:16px}.screen-head{display:flex;justify-content:space-between;align-items:center}.screen-title{font-weight:900;font-size:13px}.screen-date{font-size:9px;color:#667e95}
        .stats{display:grid;grid-template-columns:repeat(3,1fr);gap:9px;margin-top:14px}.stat{padding:13px;border:1px solid #1a344b;border-radius:12px;background:#0a1d30}.stat small{display:block;color:#667e95;font-size:8px}.stat b{display:block;margin-top:8px;font-size:17px}
        .chart{height:118px;margin-top:10px;border:1px solid #1a344b;border-radius:12px;background:linear-gradient(180deg,#0b2034,#081725);display:flex;align-items:end;gap:7px;padding:15px}.bar{flex:1;border-radius:5px 5px 2px 2px;background:linear-gradient(180deg,#59ddca,#236678)}.bar:nth-child(1){height:34%}.bar:nth-child(2){height:51%}.bar:nth-child(3){height:43%}.bar:nth-child(4){height:68%}.bar:nth-child(5){height:59%}.bar:nth-child(6){height:88%}
        .rows{display:grid;gap:7px;margin-top:10px}.dash-row{height:31px;border-radius:8px;background:#0a1d30;border:1px solid #173047}
        .section{padding:12px 0 96px}.section-head{display:flex;align-items:end;justify-content:space-between;gap:24px;margin-bottom:26px}.section-kicker{color:#6ee7d0;font-size:9px;font-weight:900;letter-spacing:1.5px}.section h2{font-size:31px;letter-spacing:-.7px;font-weight:800;margin:9px 0 0}.section-head p{max-width:500px;color:#70869c;font-size:12px;line-height:2;margin:0}
        .modules{display:grid;grid-template-columns:repeat(3,1fr);gap:15px}.module{position:relative;min-height:208px;padding:24px;border:1px solid #1a344b;border-radius:21px;background:linear-gradient(145deg,rgba(11,29,47,.95),rgba(6,17,29,.96));overflow:hidden;transition:.2s}.module:hover{transform:translateY(-5px);border-color:#315a73;box-shadow:0 20px 50px rgba(0,0,0,.22)}.module:after{content:"";position:absolute;width:150px;height:150px;left:-70px;bottom:-85px;border-radius:50%;background:rgba(110,231,208,.055)}
        .module-icon{width:42px;height:42px;border-radius:13px;display:grid;place-items:center;background:#0e283d;color:#6ee7d0;font-size:17px}.module h3{font-size:16px;margin:18px 0 8px;font-weight:700;letter-spacing:-.2px}.module p{margin:0;color:#71879d;font-size:11px;line-height:2}.module small{display:inline-block;margin-top:16px;color:#9aafc2;font-size:9px}
        .flow-wrap{border:1px solid #1b374e;border-radius:23px;padding:19px;background:rgba(8,21,35,.65);box-shadow:inset 0 1px rgba(255,255,255,.02)}.flow{display:grid;grid-template-columns:repeat(9,1fr);align-items:center;gap:8px}.flow-item{padding:18px 8px;text-align:center;border:1px solid #1a3449;border-radius:15px;background:#081725}.flow-item strong{display:block;font-size:10px}.flow-item span{display:block;margin-top:6px;color:#627b92;font-size:8px}.arrow{display:flex;align-items:center;justify-content:center;color:#426983}
        .numbers{display:grid;grid-template-columns:repeat(4,1fr);gap:13px;margin-top:23px}.number{padding:21px;border:1px solid #1a344b;border-radius:17px;background:#091a2b}.number b{display:block;font-size:25px;color:#dceaf5}.number span{display:block;margin-top:5px;color:#70869d;font-size:10px}
        .cta{margin:0 0 48px;padding:32px;border:1px solid #23465e;border-radius:24px;background:radial-gradient(circle at 15% 50%,rgba(64,193,176,.12),transparent 35%),linear-gradient(100deg,rgba(13,42,57,.92),rgba(8,22,36,.97));display:flex;align-items:center;justify-content:space-between;gap:25px}.cta h2{font-size:24px;margin:0 0 8px;font-weight:800;letter-spacing:-.5px}.cta p{margin:0;color:#7d93a8;font-size:11px}
        .footer{border-top:1px solid rgba(148,163,184,.1);padding:23px 0 35px;color:#5f768c;font-size:9px;display:flex;justify-content:space-between}
        @media(max-width:900px){.hero{grid-template-columns:1fr;gap:52px;padding-top:58px}.preview{max-width:720px;margin:auto}.modules{grid-template-columns:1fr 1fr}.flow{grid-template-columns:1fr 1fr 1fr}.numbers{grid-template-columns:1fr 1fr}.section-head{display:block}.section-head p{margin-top:13px}.cta{display:block}.cta .btn{margin-top:19px}}
        @media(max-width:560px){.nav-link a:not(.nav-login){display:none}.hero{padding:45px 0 65px}h1{font-size:40px}.dash-body{grid-template-columns:46px 1fr}.stats{grid-template-columns:1fr}.modules,.flow,.numbers{grid-template-columns:1fr}.flow-item{display:flex;align-items:center;justify-content:space-between;text-align:right}.footer{display:block}.footer span{display:block;margin-top:8px}}
    </style>
</head>
<body>
<div class="page">
    <div class="container">
        <nav class="nav">
            <a class="brand" href="/"><div class="logo">ح</div><div><strong>حسابداری صنعتی</strong><span>مدیریت تولید و بهای تمام‌شده</span></div></a>
            <div class="nav-link"><a href="#modules">امکانات</a><a href="#flow">فرآیند</a><a class="nav-login" href="/login">ورود به سیستم</a></div>
        </nav>
        <main>
            <section class="hero">
                <div class="hero-orb"></div>
                <div class="hero-copy-wrap">
                    <span class="eyebrow"><i></i> طراحی توسط سام کردستانی زاده</span>
                    <h1>هزینه را از <em>ورود کالا</em> تا محصول نهایی دنبال کنید.</h1>
                    <p class="hero-copy">حسابداری صنعتی برای کسب‌وکارهای تولیدی؛ خرید، انبار، مواد اولیه، کارگاه، دستمزد، سربار و بهای تمام‌شده در یک جریان یکپارچه و قابل ردیابی.</p>
                    <div class="actions"><a class="btn primary" href="/login">ورود به حسابداری ←</a><a class="btn secondary" href="#modules">مشاهده امکانات</a></div>
                    <div class="trust"><span>کنترل موجودی</span><span>ردیابی هزینه تولید</span><span>چند شرکت و کارگاه</span></div>
                </div>
                <div class="preview"><div class="glow"></div><div class="dashboard"><div class="dash-top"><div class="traffic"><i></i><i></i><i></i></div><span>پنل مدیریت · حسابداری صنعتی</span></div><div class="dash-body"><aside class="side"><i></i><i></i><i></i><i></i><i></i></aside><div class="screen"><div class="screen-head"><span class="screen-title">نمای کلی عملیات</span><span class="screen-date">دوره جاری</span></div><div class="stats"><div class="stat"><small>موجودی انبار</small><b>۲۴۸</b></div><div class="stat"><small>سفارش خرید</small><b>۳۶</b></div><div class="stat"><small>تولید جاری</small><b>۱۲</b></div></div><div class="chart"><i class="bar"></i><i class="bar"></i><i class="bar"></i><i class="bar"></i><i class="bar"></i><i class="bar"></i></div><div class="rows"><i class="dash-row"></i><i class="dash-row"></i><i class="dash-row"></i></div></div></div></div></div>
            </section>
            <section class="section" id="modules">
                <div class="section-head"><div><span class="section-kicker">CORE MODULES</span><h2>تمام زنجیره تولید، در یک سیستم</h2></div><p>از تأمین و دریافت مواد تا مصرف، تولید و محاسبه بهای تمام‌شده؛ هر مرحله جای خودش را دارد و اطلاعات قابل ردیابی می‌ماند.</p></div>
                <div class="modules">
                    <article class="module"><div class="module-icon">▦</div><h3>خرید و تأمین</h3><p>تأمین‌کنندگان، درخواست و سفارش خرید، اقلام سفارش و دریافت کالا در یک جریان منظم.</p><small>خرید · تأمین‌کننده · رسید کالا</small></article>
                    <article class="module"><div class="module-icon">▤</div><h3>انبار و مواد</h3><p>کنترل موجودی، ورود و خروج، گردش مواد اولیه و ارزش‌گذاری موجودی با روش قابل تنظیم.</p><small>انبار · مواد اولیه · گردش کالا</small></article>
                    <article class="module"><div class="module-icon">⚙</div><h3>تولید و کارگاه</h3><p>کارگاه، بخش‌ها، فعالیت‌ها، سفارش تولید و فرمول ساخت برای عملیات واقعی تولید.</p><small>کارگاه · فعالیت · تولید</small></article>
                    <article class="module"><div class="module-icon">◈</div><h3>بهای تمام‌شده</h3><p>ترکیب مواد، دستمزد و سربار برای محاسبه بهای تمام‌شده محصول تولیدی.</p><small>مواد · دستمزد · سربار</small></article>
                    <article class="module"><div class="module-icon">◌</div><h3>گزارش و تحلیل</h3><p>نمایش هزینه، موجودی و تولید برای بررسی عملکرد دوره و تصمیم‌گیری مدیریتی.</p><small>گزارش تولید · هزینه · موجودی</small></article>
                    <article class="module"><div class="module-icon">⌘</div><h3>شرکت و دسترسی</h3><p>شرکت، دوره مالی، نقش‌ها و دسترسی کاربران در ساختاری مستقل و قابل کنترل.</p><small>شرکت · نقش · دسترسی</small></article>
                </div>
            </section>
            <section class="section" id="flow">
                <div class="section-head"><div><span class="section-kicker">PRODUCTION FLOW</span><h2>مسیر هزینه را قدم‌به‌قدم ببینید</h2></div></div>
                <div class="flow-wrap"><div class="flow"><div class="flow-item"><strong>سفارش خرید</strong><span>تأمین</span></div><div class="arrow">←</div><div class="flow-item"><strong>ورود مواد</strong><span>انبار</span></div><div class="arrow">←</div><div class="flow-item"><strong>مصرف تولید</strong><span>کارگاه</span></div><div class="arrow">←</div><div class="flow-item"><strong>تولید محصول</strong><span>محصول نهایی</span></div><div class="arrow">←</div><div class="flow-item"><strong>بهای تمام‌شده</strong><span>تحلیل</span></div></div></div>
                <div class="numbers"><div class="number"><b>۰۱</b><span>خرید و دریافت</span></div><div class="number"><b>۰۲</b><span>کنترل و مصرف مواد</span></div><div class="number"><b>۰۳</b><span>دستمزد و سربار</span></div><div class="number"><b>۰۴</b><span>محاسبه هزینه محصول</span></div></div>
            </section>
            <section class="cta"><div><h2>از خرید تا بهای تمام‌شده، همه‌چیز قابل ردیابی است.</h2><p>یک زیرساخت مستقل و آماده توسعه برای عملیات واقعی تولید.</p></div><a class="btn primary" href="/login">ورود به سیستم ←</a></section>
        </main>
        <footer class="footer"><span>حسابداری صنعتی · مدیریت تولید و بهای تمام‌شده</span><span>طراحی توسط سام کردستانی زاده</span></footer>
    </div>
</div>
</body>
</html>