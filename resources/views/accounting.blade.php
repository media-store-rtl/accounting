<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>حسابداری صنعتی | مدیریت تولید و بهای تمام‌شده</title>
    <style>
        :root{font-family:"Vazirmatn",Tahoma,sans-serif;color:#e8eef8;background:#07111f}
        *{box-sizing:border-box}
        html{scroll-behavior:smooth}
        body{margin:0;min-width:320px;background:#07111f;color:#e8eef8}
        a{color:inherit}
        .page{min-height:100vh;overflow:hidden;background:radial-gradient(circle at 10% 0%,rgba(43,114,210,.25),transparent 30%),radial-gradient(circle at 95% 35%,rgba(31,177,151,.16),transparent 28%),#07111f}
        .container{width:min(1180px,92%);margin:auto}
        .nav{height:82px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid rgba(148,163,184,.12)}
        .brand{display:flex;align-items:center;gap:12px;text-decoration:none}.logo{width:46px;height:46px;border-radius:15px;display:grid;place-items:center;font-size:21px;font-weight:900;color:#06111d;background:linear-gradient(135deg,#5eead4,#60a5fa);box-shadow:0 12px 30px rgba(45,212,191,.18)}
        .brand strong{display:block;font-size:16px}.brand span{display:block;margin-top:3px;color:#8295ad;font-size:11px}
        .nav-link{display:flex;align-items:center;gap:18px;color:#9eb0c5;font-size:13px}.nav-link a{text-decoration:none}.nav-login{padding:10px 16px;border:1px solid #29425e;border-radius:12px;color:#dce8f5;background:#0c1b2d}
        .hero{display:grid;grid-template-columns:1.12fr .88fr;gap:70px;align-items:center;padding:82px 0 72px}
        .eyebrow{display:inline-flex;align-items:center;gap:8px;color:#5eead4;font-size:12px;font-weight:800;letter-spacing:.8px}.eyebrow i{width:7px;height:7px;border-radius:50%;background:#5eead4;box-shadow:0 0 0 6px rgba(94,234,212,.08)}
        h1{font-size:clamp(42px,5.5vw,70px);line-height:1.12;letter-spacing:-1.5px;margin:17px 0 20px;max-width:760px}h1 em{font-style:normal;color:#61d9c4}
        .hero-copy{color:#9eafc4;font-size:17px;line-height:2.15;max-width:690px;margin:0}.actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:31px}.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:14px 20px;border-radius:13px;text-decoration:none;font-size:14px;font-weight:800}.primary{background:#5eead4;color:#06131e;box-shadow:0 14px 30px rgba(45,212,191,.14)}.secondary{background:#0b192a;border:1px solid #29445f;color:#dce8f5}
        .hero-note{display:flex;gap:22px;margin-top:27px;color:#71869d;font-size:11px}.hero-note span{display:flex;align-items:center;gap:7px}.hero-note b{color:#b6c6d7;font-size:12px}
        .preview{position:relative}.glow{position:absolute;inset:10% -8%;background:radial-gradient(circle,rgba(50,133,220,.24),transparent 62%);filter:blur(20px)}.dashboard{position:relative;border:1px solid #29445f;border-radius:28px;padding:15px;background:rgba(8,22,37,.88);box-shadow:0 35px 100px rgba(0,0,0,.34);transform:rotate(-1deg)}
        .dash-top{height:44px;display:flex;align-items:center;justify-content:space-between;padding:0 8px;color:#8094aa;font-size:10px}.traffic{display:flex;gap:5px}.traffic i{width:7px;height:7px;border-radius:50%;background:#304760}.dash-body{display:grid;grid-template-columns:72px 1fr;gap:12px}.side{border-radius:16px;background:#0b1b2d;padding:10px;display:grid;align-content:start;gap:8px}.side i{height:30px;border-radius:9px;background:#12263b}.side i:first-child{background:linear-gradient(90deg,#1c8f81,#143f50)}.screen{min-height:350px;border-radius:17px;background:#0b1929;padding:16px}.screen-head{display:flex;justify-content:space-between;align-items:center}.screen-title{font-weight:800;font-size:14px}.screen-date{font-size:9px;color:#71869d}.stats{display:grid;grid-template-columns:repeat(3,1fr);gap:9px;margin-top:13px}.stat{padding:13px;border:1px solid #1d344b;border-radius:12px;background:#0d2033}.stat small{display:block;color:#71869d;font-size:8px}.stat b{display:block;margin-top:8px;font-size:16px}.chart{height:105px;margin-top:10px;border:1px solid #1d344b;border-radius:12px;background:linear-gradient(180deg,#0d2033,#0a1827);display:flex;align-items:end;gap:7px;padding:15px}.bar{flex:1;border-radius:5px 5px 2px 2px;background:linear-gradient(180deg,#4fd1c5,#246c79)}.bar:nth-child(1){height:38%}.bar:nth-child(2){height:54%}.bar:nth-child(3){height:45%}.bar:nth-child(4){height:72%}.bar:nth-child(5){height:61%}.bar:nth-child(6){height:86%}.rows{display:grid;gap:7px;margin-top:10px}.dash-row{height:31px;border-radius:8px;background:#0d2033;border:1px solid #182f45}
        .section{padding:24px 0 86px}.section-head{display:flex;align-items:end;justify-content:space-between;gap:20px;margin-bottom:24px}.section-kicker{color:#5eead4;font-size:11px;font-weight:800}.section h2{font-size:30px;margin:8px 0 0}.section-head p{max-width:460px;color:#71869d;font-size:13px;line-height:1.9;margin:0}
        .modules{display:grid;grid-template-columns:repeat(3,1fr);gap:14px}.module{position:relative;min-height:190px;padding:23px;border:1px solid #1e3850;border-radius:20px;background:linear-gradient(145deg,rgba(14,32,51,.92),rgba(8,22,37,.9));overflow:hidden}.module:after{content:"";position:absolute;width:100px;height:100px;left:-40px;bottom:-55px;border-radius:50%;background:rgba(94,234,212,.08)}.module-icon{width:39px;height:39px;border-radius:11px;display:grid;place-items:center;background:#10283d;color:#67e8d0;font-size:17px}.module h3{font-size:17px;margin:18px 0 8px}.module p{margin:0;color:#71869d;font-size:12px;line-height:1.9}.module small{display:inline-block;margin-top:16px;color:#93a7bc;font-size:10px}
        .flow{display:grid;grid-template-columns:repeat(5,1fr);gap:8px;margin-top:28px}.flow-item{padding:16px 10px;text-align:center;border:1px solid #1c344b;border-radius:14px;background:#0a1828}.flow-item strong{display:block;font-size:11px}.flow-item span{display:block;margin-top:6px;color:#667e96;font-size:9px}.arrow{display:flex;align-items:center;justify-content:center;color:#3b607e}
        .cta{margin:0 0 45px;padding:28px 30px;border:1px solid #23435a;border-radius:22px;background:linear-gradient(100deg,rgba(24,64,78,.72),rgba(13,31,49,.92));display:flex;align-items:center;justify-content:space-between;gap:25px}.cta h2{font-size:23px;margin:0 0 7px}.cta p{margin:0;color:#7f95ab;font-size:12px}.footer{border-top:1px solid rgba(148,163,184,.1);padding:22px 0 35px;color:#627991;font-size:10px;display:flex;justify-content:space-between}
        @media(max-width:900px){.hero{grid-template-columns:1fr;gap:45px;padding-top:55px}.preview{max-width:700px;margin:auto}.modules{grid-template-columns:1fr}.flow{grid-template-columns:1fr 1fr}.section-head{display:block}.section-head p{margin-top:12px}.cta{display:block}.cta .btn{margin-top:18px}}
        @media(max-width:560px){.nav-link a:not(.nav-login){display:none}.hero{padding:45px 0 55px}h1{font-size:39px}.hero-note{gap:12px;flex-wrap:wrap}.dash-body{grid-template-columns:48px 1fr}.stats{grid-template-columns:1fr}.flow{grid-template-columns:1fr}.flow-item{display:flex;align-items:center;justify-content:space-between;text-align:right}.footer{display:block}.footer span{display:block;margin-top:8px}}
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
                <div>
                    <span class="eyebrow"><i></i> ACCOUNTING · INDUSTRIAL</span>
                    <h1>حسابداری را از عددها فراتر ببرید؛ <em>هزینه را کنترل کنید.</em></h1>
                    <p class="hero-copy">یک سیستم یکپارچه برای مدیریت خرید، انبار، مواد اولیه، کارگاه، تولید، دستمزد، سربار و بهای تمام‌شده؛ ساخته‌شده برای کسب‌وکارهای تولیدی و قابل توسعه با ساختار واقعی کارخانه.</p>
                    <div class="actions"><a class="btn primary" href="/login">ورود به حسابداری ←</a><a class="btn secondary" href="#modules">مشاهده امکانات</a></div>
                    <div class="hero-note"><span>✓ <b>چند شرکت و کارگاه</b></span><span>✓ <b>کنترل موجودی و تولید</b></span><span>✓ <b>محاسبه بهای تمام‌شده</b></span></div>
                </div>
                <div class="preview"><div class="glow"></div><div class="dashboard"><div class="dash-top"><div class="traffic"><i></i><i></i><i></i></div><span>پنل مدیریت · حسابداری صنعتی</span></div><div class="dash-body"><aside class="side"><i></i><i></i><i></i><i></i><i></i></aside><div class="screen"><div class="screen-head"><span class="screen-title">نمای کلی سیستم</span><span class="screen-date">وضعیت عملیات</span></div><div class="stats"><div class="stat"><small>موجودی انبار</small><b>۲۴۸</b></div><div class="stat"><small>سفارش خرید</small><b>۳۶</b></div><div class="stat"><small>تولید جاری</small><b>۱۲</b></div></div><div class="chart"><i class="bar"></i><i class="bar"></i><i class="bar"></i><i class="bar"></i><i class="bar"></i><i class="bar"></i></div><div class="rows"><i class="dash-row"></i><i class="dash-row"></i><i class="dash-row"></i></div></div></div></div></div>
            </section>

            <section class="section" id="modules">
                <div class="section-head"><div><span class="section-kicker">CORE MODULES</span><h2>همه‌چیز برای یک جریان واقعی تولید</h2></div><p>ماژول‌ها از ابتدا طوری چیده می‌شوند که اطلاعات خرید و انبار در نهایت به تولید و بهای تمام‌شده متصل شود.</p></div>
                <div class="modules">
                    <article class="module"><div class="module-icon">▦</div><h3>خرید و تأمین</h3><p>تأمین‌کنندگان، سفارش خرید، اقلام سفارش و دریافت کالا با گردش اطلاعات منسجم.</p><small>خرید · تأمین‌کننده · رسید کالا</small></article>
                    <article class="module"><div class="module-icon">▤</div><h3>انبار و مواد</h3><p>کنترل موجودی، ورود و خروج، گردش مواد اولیه و ارزش‌گذاری موجودی در یک ساختار واحد.</p><small>انبار · مواد اولیه · گردش کالا</small></article>
                    <article class="module"><div class="module-icon">⚙</div><h3>تولید و کارگاه</h3><p>کارگاه، بخش‌ها، فعالیت‌ها، نیروی کار، فرمول ساخت و سفارش تولید قابل توسعه.</p><small>کارگاه · فعالیت · تولید</small></article>
                    <article class="module"><div class="module-icon">◈</div><h3>بهای تمام‌شده</h3><p>ترکیب مواد، دستمزد مستقیم و سربار برای محاسبه بهای واقعی محصولات تولیدی.</p><small>مواد · دستمزد · سربار</small></article>
                    <article class="module"><div class="module-icon">◌</div><h3>گزارش‌ها</h3><p>گزارش‌های مدیریتی و عملیاتی برای بررسی هزینه، موجودی، تولید و عملکرد دوره.</p><small>گزارش تولید · هزینه · موجودی</small></article>
                    <article class="module"><div class="module-icon">⌘</div><h3>ساختار سازمانی</h3><p>شرکت، کارگاه، بخش، فعالیت و نقش‌های کاربری با دسترسی‌های قابل کنترل.</p><small>شرکت · نقش · دسترسی</small></article>
                </div>
            </section>

            <section class="section" id="flow">
                <div class="section-head"><div><span class="section-kicker">PRODUCTION FLOW</span><h2>مسیر اطلاعات از خرید تا بهای تمام‌شده</h2></div></div>
                <div class="flow"><div class="flow-item"><strong>سفارش خرید</strong><span>تأمین و خرید</span></div><div class="arrow">←</div><div class="flow-item"><strong>ورود مواد</strong><span>انبار و موجودی</span></div><div class="arrow">←</div><div class="flow-item"><strong>مصرف تولید</strong><span>کارگاه و فعالیت</span></div><div class="arrow">←</div><div class="flow-item"><strong>تولید محصول</strong><span>محصول نهایی</span></div><div class="arrow">←</div><div class="flow-item"><strong>بهای تمام‌شده</strong><span>گزارش و تحلیل</span></div></div>
            </section>

            <section class="cta"><div><h2>کنترل هزینه از اولین ورود کالا شروع می‌شود.</h2><p>ساختار حسابداری مستقل، آماده توسعه برای عملیات واقعی تولید.</p></div><a class="btn primary" href="/login">ورود به سیستم ←</a></section>
        </main>

        <footer class="footer"><span>حسابداری صنعتی · مدیریت تولید و بهای تمام‌شده</span><span>Accounting · Industrial Cost Management</span></footer>
    </div>
</div>
</body>
</html>
