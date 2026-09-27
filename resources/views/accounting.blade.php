<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>حسابداری صنعتی | کنترل هزینه، تولید و بهای تمام‌شده</title>
    <style>
        :root{font-family:"Vazirmatn",Tahoma,sans-serif;color:#eef4fb;background:#07101b}
        *{box-sizing:border-box}html{scroll-behavior:smooth}body{margin:0;min-width:320px;background:#07101b;color:#eef4fb}
        a{color:inherit}.page{min-height:100vh;overflow:hidden;background:radial-gradient(circle at 8% 4%,rgba(76,201,240,.13),transparent 27%),radial-gradient(circle at 92% 18%,rgba(77,208,178,.10),transparent 25%),#07101b}
        .container{width:min(1180px,92%);margin:auto}.nav{height:82px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid rgba(160,180,200,.12)}
        .brand{display:flex;align-items:center;gap:12px;text-decoration:none}.logo{width:47px;height:47px;border-radius:15px;display:grid;place-items:center;font-size:21px;font-weight:900;color:#06131c;background:linear-gradient(135deg,#72f2d2,#70b7ff);box-shadow:0 15px 35px rgba(70,220,190,.16)}
        .brand strong{display:block;font-size:16px}.brand span{display:block;margin-top:4px;color:#7f94a9;font-size:10px}.nav-link{display:flex;align-items:center;gap:22px;color:#91a5ba;font-size:12px}.nav-link a{text-decoration:none}.nav-login{padding:11px 17px;border:1px solid #29435c;border-radius:12px;color:#e4edf6;background:#0c1a2a}
        .hero{position:relative;display:grid;grid-template-columns:1.04fr .96fr;gap:68px;align-items:center;padding:85px 0 92px}.hero:before{content:"";position:absolute;width:520px;height:520px;left:-250px;top:40px;border:1px solid rgba(100,210,190,.08);border-radius:50%}
        .eyebrow{display:inline-flex;align-items:center;gap:9px;color:#6de5cb;font-size:11px;font-weight:900;letter-spacing:1.2px}.eyebrow i{width:7px;height:7px;border-radius:50%;background:#6de5cb;box-shadow:0 0 0 6px rgba(109,229,203,.08)}
        h1{font-size:clamp(42px,5.3vw,68px);line-height:1.12;letter-spacing:-2px;margin:18px 0 22px;max-width:720px}h1 em{font-style:normal;color:#6de5cb}
        .hero-copy{color:#9bafc3;font-size:16px;line-height:2.15;max-width:650px;margin:0}.actions{display:flex;gap:11px;flex-wrap:wrap;margin-top:31px}.btn{display:inline-flex;align-items:center;justify-content:center;gap:9px;padding:14px 20px;border-radius:13px;text-decoration:none;font-size:13px;font-weight:900}.primary{background:#6de5cb;color:#06141d;box-shadow:0 16px 34px rgba(61,210,180,.14)}.secondary{background:#0b1929;border:1px solid #29445e;color:#dbe8f4}
        .trust{display:flex;gap:10px;flex-wrap:wrap;margin-top:25px}.trust span{padding:8px 11px;border:1px solid #1c344b;border-radius:10px;color:#8298ae;background:rgba(10,26,42,.65);font-size:10px}
        .preview{position:relative}.glow{position:absolute;inset:7% -10%;background:radial-gradient(circle,rgba(69,155,235,.20),transparent 62%);filter:blur(25px)}
        .dashboard{position:relative;border:1px solid #2a4660;border-radius:28px;padding:14px;background:rgba(8,21,35,.94);box-shadow:0 35px 100px rgba(0,0,0,.38);transform:rotate(-1.1deg)}
        .dash-top{height:43px;display:flex;align-items:center;justify-content:space-between;padding:0 9px;color:#8095aa;font-size:9px}.traffic{display:flex;gap:5px}.traffic i{width:7px;height:7px;border-radius:50%;background:#30465c}
        .dash-body{display:grid;grid-template-columns:65px 1fr;gap:11px}.side{border-radius:16px;background:#0a1a2b;padding:9px;display:grid;align-content:start;gap:8px}.side i{height:30px;border-radius:9px;background:#11283c}.side i:first-child{background:linear-gradient(90deg,#218d7d,#164457)}
        .screen{min-height:360px;border-radius:17px;background:#0a1828;padding:16px}.screen-head{display:flex;justify-content:space-between;align-items:center}.screen-title{font-weight:900;font-size:13px}.screen-date{font-size:9px;color:#70869c}
        .stats{display:grid;grid-template-columns:repeat(3,1fr);gap:9px;margin-top:13px}.stat{padding:13px;border:1px solid #1d344b;border-radius:12px;background:#0d2033}.stat small{display:block;color:#71879d;font-size:8px}.stat b{display:block;margin-top:8px;font-size:16px}
        .chart{height:112px;margin-top:10px;border:1px solid #1d344b;border-radius:12px;background:linear-gradient(180deg,#0d2033,#0a1827);display:flex;align-items:end;gap:7px;padding:15px}.bar{flex:1;border-radius:5px 5px 2px 2px;background:linear-gradient(180deg,#55d9c8,#266878)}.bar:nth-child(1){height:38%}.bar:nth-child(2){height:54%}.bar:nth-child(3){height:45%}.bar:nth-child(4){height:72%}.bar:nth-child(5){height:61%}.bar:nth-child(6){height:86%}.rows{display:grid;gap:7px;margin-top:10px}.dash-row{height:31px;border-radius:8px;background:#0d2033;border:1px solid #182f45}
        .section{padding:18px 0 88px}.section-head{display:flex;align-items:end;justify-content:space-between;gap:20px;margin-bottom:25px}.section-kicker{color:#6de5cb;font-size:10px;font-weight:900;letter-spacing:1px}.section h2{font-size:30px;letter-spacing:-.6px;margin:8px 0 0}.section-head p{max-width:470px;color:#72889e;font-size:12px;line-height:2;margin:0}
        .modules{display:grid;grid-template-columns:repeat(3,1fr);gap:14px}.module{position:relative;min-height:200px;padding:23px;border:1px solid #1d3850;border-radius:20px;background:linear-gradient(145deg,rgba(14,33,52,.94),rgba(8,21,35,.92));overflow:hidden;transition:transform .2s,border-color .2s}.module:hover{transform:translateY(-4px);border-color:#35617a}.module:after{content:"";position:absolute;width:130px;height:130px;left:-55px;bottom:-75px;border-radius:50%;background:rgba(109,229,203,.06)}
        .module-icon{width:40px;height:40px;border-radius:12px;display:grid;place-items:center;background:#10283d;color:#6de5cb;font-size:16px}.module h3{font-size:16px;margin:18px 0 8px}.module p{margin:0;color:#73899f;font-size:11px;line-height:1.95}.module small{display:inline-block;margin-top:16px;color:#9aafc2;font-size:9px}
        .flow-wrap{border:1px solid #1c374e;border-radius:22px;padding:18px;background:rgba(9,23,38,.7)}.flow{display:grid;grid-template-columns:repeat(9,1fr);align-items:center;gap:8px}.flow-item{padding:17px 8px;text-align:center;border:1px solid #1c3449;border-radius:14px;background:#0a1828}.flow-item strong{display:block;font-size:10px}.flow-item span{display:block;margin-top:6px;color:#687f96;font-size:8px}.arrow{display:flex;align-items:center;justify-content:center;color:#4b718b}
        .numbers{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-top:22px}.number{padding:20px;border:1px solid #1c354c;border-radius:16px;background:#0b1b2c}.number b{display:block;font-size:24px}.number span{display:block;margin-top:5px;color:#71879d;font-size:10px}
        .cta{margin:0 0 45px;padding:30px;border:1px solid #24445b;border-radius:23px;background:linear-gradient(100deg,rgba(22,62,76,.76),rgba(11,28,45,.94));display:flex;align-items:center;justify-content:space-between;gap:25px}.cta h2{font-size:23px;margin:0 0 8px}.cta p{margin:0;color:#8096aa;font-size:11px}.footer{border-top:1px solid rgba(148,163,184,.1);padding:22px 0 35px;color:#60788f;font-size:9px;display:flex;justify-content:space-between}
        @media(max-width:900px){.hero{grid-template-columns:1fr;gap:48px;padding-top:55px}.preview{max-width:700px;margin:auto}.modules{grid-template-columns:1fr 1fr}.flow{grid-template-columns:1fr 1fr 1fr}.numbers{grid-template-columns:1fr 1fr}.section-head{display:block}.section-head p{margin-top:12px}.cta{display:block}.cta .btn{margin-top:18px}}
        @media(max-width:560px){.nav-link a:not(.nav-login){display:none}.hero{padding:45px 0 60px}h1{font-size:39px}.dash-body{grid-template-columns:45px 1fr}.stats{grid-template-columns:1fr}.modules,.flow,.numbers{grid-template-columns:1fr}.flow-item{display:flex;align-items:center;justify-content:space-between;text-align:right}.footer{display:block}.footer span{display:block;margin-top:8px}}
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
                    <span class="eyebrow"><i></i> INDUSTRIAL ACCOUNTING PLATFORM</span>
                    <h1>کنترل هزینه، از <em>اولین ورود کالا</em> تا محصول نهایی.</h1>
                    <p class="hero-copy">یک پلتفرم یکپارچه برای خرید، انبار، مواد اولیه، کارگاه، تولید، دستمزد، سربار و بهای تمام‌شده؛ با ساختاری که برای عملیات واقعی کسب‌وکارهای تولیدی طراحی شده است.</p>
                    <div class="actions"><a class="btn primary" href="/login">ورود به حسابداری ←</a><a class="btn secondary" href="#modules">آشنایی با امکانات</a></div>
                    <div class="trust"><span>چند شرکت و کارگاه</span><span>کنترل موجودی</span><span>ردیابی هزینه تولید</span></div>
                </div>
                <div class="preview"><div class="glow"></div><div class="dashboard"><div class="dash-top"><div class="traffic"><i></i><i></i><i></i></div><span>پنل مدیریت · حسابداری صنعتی</span></div><div class="dash-body"><aside class="side"><i></i><i></i><i></i><i></i><i></i></aside><div class="screen"><div class="screen-head"><span class="screen-title">نمای کلی عملیات</span><span class="screen-date">دوره جاری</span></div><div class="stats"><div class="stat"><small>موجودی انبار</small><b>۲۴۸</b></div><div class="stat"><small>سفارش خرید</small><b>۳۶</b></div><div class="stat"><small>تولید جاری</small><b>۱۲</b></div></div><div class="chart"><i class="bar"></i><i class="bar"></i><i class="bar"></i><i class="bar"></i><i class="bar"></i><i class="bar"></i></div><div class="rows"><i class="dash-row"></i><i class="dash-row"></i><i class="dash-row"></i></div></div></div></div></div>
            </section>

            <section class="section" id="modules">
                <div class="section-head"><div><span class="section-kicker">CORE MODULES</span><h2>یک سیستم، برای تمام زنجیره تولید</h2></div><p>اطلاعات از خرید و انبار جدا نمی‌ماند؛ مسیر آن تا مصرف، تولید و محاسبه بهای تمام‌شده قابل دنبال‌کردن است.</p></div>
                <div class="modules">
                    <article class="module"><div class="module-icon">▦</div><h3>خرید و تأمین</h3><p>مدیریت تأمین‌کنندگان، سفارش خرید، اقلام سفارش و دریافت کالا در یک جریان منظم.</p><small>خرید · تأمین‌کننده · رسید کالا</small></article>
                    <article class="module"><div class="module-icon">▤</div><h3>انبار و مواد</h3><p>کنترل موجودی، ورود و خروج، گردش مواد اولیه و ارزش‌گذاری موجودی با روش قابل تنظیم.</p><small>انبار · مواد اولیه · گردش کالا</small></article>
                    <article class="module"><div class="module-icon">⚙</div><h3>تولید و کارگاه</h3><p>ساختار کارگاه، بخش‌ها، فعالیت‌ها، سفارش تولید و فرمول ساخت برای عملیات واقعی.</p><small>کارگاه · فعالیت · تولید</small></article>
                    <article class="module"><div class="module-icon">◈</div><h3>بهای تمام‌شده</h3><p>ترکیب مواد، دستمزد و سربار برای رسیدن به بهای تمام‌شده محصول تولیدی.</p><small>مواد · دستمزد · سربار</small></article>
                    <article class="module"><div class="module-icon">◌</div><h3>گزارش و تحلیل</h3><p>نمایش هزینه، موجودی، تولید و عملکرد دوره برای تصمیم‌گیری مدیریتی.</p><small>گزارش تولید · هزینه · موجودی</small></article>
                    <article class="module"><div class="module-icon">⌘</div><h3>شرکت و دسترسی</h3><p>شرکت، دوره مالی، نقش‌ها و دسترسی کاربران در ساختاری مستقل و قابل کنترل.</p><small>شرکت · نقش · دسترسی</small></article>
                </div>
            </section>

            <section class="section" id="flow">
                <div class="section-head"><div><span class="section-kicker">PRODUCTION FLOW</span><h2>ردیابی مسیر هزینه در تولید</h2></div></div>
                <div class="flow-wrap"><div class="flow"><div class="flow-item"><strong>سفارش خرید</strong><span>تأمین</span></div><div class="arrow">←</div><div class="flow-item"><strong>ورود مواد</strong><span>انبار</span></div><div class="arrow">←</div><div class="flow-item"><strong>مصرف تولید</strong><span>کارگاه</span></div><div class="arrow">←</div><div class="flow-item"><strong>تولید محصول</strong><span>محصول نهایی</span></div><div class="arrow">←</div><div class="flow-item"><strong>بهای تمام‌شده</strong><span>تحلیل</span></div></div></div>
                <div class="numbers"><div class="number"><b>۰۱</b><span>خرید و دریافت</span></div><div class="number"><b>۰۲</b><span>کنترل و مصرف مواد</span></div><div class="number"><b>۰۳</b><span>دستمزد و سربار</span></div><div class="number"><b>۰۴</b><span>محاسبه هزینه محصول</span></div></div>
            </section>

            <section class="cta"><div><h2>اطلاعات را ثبت کنید؛ هزینه را شفاف ببینید.</h2><p>ساختار حسابداری مستقل و آماده توسعه برای عملیات واقعی تولید.</p></div><a class="btn primary" href="/login">ورود به سیستم ←</a></section>
        </main>

        <footer class="footer"><span>حسابداری صنعتی · مدیریت تولید و بهای تمام‌شده</span><span>Industrial Accounting Platform</span></footer>
    </div>
</div>
</body>
</html>