<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ $siteContent['hero_copy'] }}">
    <meta name="theme-color" content="#142d46">
    <meta property="og:type" content="website">
    <meta property="og:locale" content="fa_IR">
    <meta property="og:title" content="خدمات مشاوره حسابداری مالی و مالیاتی فرنام">
    <meta property="og:description" content="مشاوره تخصصی حسابداری، مالی، مالیاتی و حسابداری صنعتی برای کسب‌وکارها.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <title>خدمات مشاوره حسابداری مالی و مالیاتی فرنام</title>
    <style>
        :root {
            color-scheme: light;
            font-family: "Vazirmatn", Tahoma, sans-serif;
            color: #203247;
            background: #f6f7f8;
            font-synthesis: none;
            text-rendering: optimizeLegibility;
            -webkit-font-smoothing: antialiased;
            --navy: #142d46;
            --navy-deep: #0d2136;
            --gold: #b99a5b;
            --gold-light: #e8dcc0;
            --ink: #203247;
            --muted: #718092;
            --line: #e6e9ed;
            --surface: #ffffff;
            --soft: #f2f4f6;
        }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; scroll-padding-top: 92px; }
        body { margin: 0; min-width: 320px; background: #f6f7f8; color: var(--ink); }
        body, button, a { font-family: "Vazirmatn", Tahoma, sans-serif; }
        a { color: inherit; }
        button, a { -webkit-tap-highlight-color: transparent; }
        .site { overflow: hidden; }
        .container { width: min(1160px, calc(100% - 44px)); margin-inline: auto; }
        .topbar { position: relative; z-index: 5; background: rgba(255,255,255,.94); border-bottom: 1px solid rgba(20,45,70,.08); }
        .nav { min-height: 86px; display: flex; align-items: center; justify-content: space-between; gap: 24px; }
        .brand { display: inline-flex; align-items: center; gap: 12px; text-decoration: none; flex: 0 0 auto; }
        .brand-mark { width: 48px; height: 48px; display: grid; place-items: center; border-radius: 15px; color: var(--gold); background: var(--navy); box-shadow: 0 9px 22px rgba(20,45,70,.13); }
        .brand-mark svg { width: 29px; height: 29px; }
        .brand-name { display: block; color: var(--navy); font-size: 23px; font-weight: 900; line-height: 1.2; letter-spacing: -.7px; }
        .brand-caption { display: block; margin-top: 5px; color: #7c8793; font-size: 10px; font-weight: 500; }
        .nav-links { display: flex; align-items: center; gap: clamp(15px, 2.2vw, 29px); color: #667587; font-size: 12px; font-weight: 600; }
        .nav-links a { text-decoration: none; transition: color .2s; }
        .nav-links a:hover { color: var(--gold); }
        .nav-actions { display: flex; align-items: center; gap: 10px; }
        .login-link { color: var(--navy); text-decoration: none; font-size: 11px; font-weight: 700; padding: 10px 12px; }
        .button { min-height: 46px; display: inline-flex; align-items: center; justify-content: center; gap: 9px; padding: 11px 20px; border: 1px solid transparent; border-radius: 12px; text-decoration: none; font-size: 12px; font-weight: 800; transition: transform .2s, box-shadow .2s, background .2s; cursor: pointer; }
        .button:hover { transform: translateY(-2px); }
        .button-gold { color: #fff; background: var(--gold); box-shadow: 0 10px 24px rgba(185,154,91,.2); }
        .button-gold:hover { background: #a88749; box-shadow: 0 13px 28px rgba(185,154,91,.27); }
        .button-outline { color: #fff; border-color: rgba(255,255,255,.3); background: rgba(255,255,255,.04); }
        .button-outline:hover { background: rgba(255,255,255,.1); }
        .hero { position: relative; isolation: isolate; padding: 78px 0 84px; color: #fff; background: var(--navy); }
        .hero::before, .hero::after { content: ""; position: absolute; z-index: -1; border: 1px solid rgba(232,220,192,.12); border-radius: 50%; pointer-events: none; }
        .hero::before { width: 620px; height: 620px; top: -365px; left: -130px; }
        .hero::after { width: 430px; height: 430px; bottom: -310px; right: 33%; }
        .hero-grid { display: grid; grid-template-columns: 1.05fr .95fr; align-items: center; gap: 68px; }
        .eyebrow { display: inline-flex; align-items: center; gap: 10px; color: #e2c994; font-size: 11px; font-weight: 800; letter-spacing: .2px; }
        .eyebrow::before { content: ""; width: 24px; height: 1px; background: var(--gold); }
        .hero h1 { max-width: 650px; margin: 20px 0 19px; color: #fff; font-size: clamp(35px, 4.7vw, 59px); font-weight: 900; line-height: 1.55; letter-spacing: -1.5px; }
        .hero h1 span { color: #e2c994; }
        .hero-copy { max-width: 600px; margin: 0; color: #c3cfda; font-size: 14px; line-height: 2.35; }
        .hero-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 12px; margin-top: 29px; }
        .hero-note { display: flex; flex-wrap: wrap; gap: 18px; margin-top: 29px; color: #aebdca; font-size: 10px; }
        .hero-note span { display: inline-flex; align-items: center; gap: 7px; }
        .hero-note i { width: 6px; height: 6px; border-radius: 50%; background: var(--gold); }
        .visual { position: relative; min-height: 390px; display: grid; place-items: center; }
        .visual-glow { position: absolute; inset: 8% 4%; border-radius: 50%; background: radial-gradient(circle, rgba(185,154,91,.2), transparent 68%); filter: blur(18px); }
        .ledger { position: relative; width: min(100%, 450px); padding: 24px; border: 1px solid rgba(232,220,192,.22); border-radius: 25px; background: linear-gradient(145deg, rgba(255,255,255,.12), rgba(255,255,255,.035)); box-shadow: 0 35px 90px rgba(0,0,0,.24); transform: rotate(-2deg); backdrop-filter: blur(8px); }
        .ledger-top { display: flex; align-items: center; justify-content: space-between; padding-bottom: 18px; border-bottom: 1px solid rgba(255,255,255,.12); }
        .ledger-title { color: #fff; font-size: 12px; font-weight: 800; }
        .ledger-label { color: #cdb985; font-size: 9px; }
        .ledger-main { display: grid; grid-template-columns: 1fr 105px; align-items: end; gap: 15px; padding: 24px 0 18px; }
        .ledger-kicker { color: #aebdca; font-size: 9px; }
        .ledger-value { margin-top: 7px; color: #fff; font-size: 28px; font-weight: 800; letter-spacing: 1px; }
        .ledger-sub { margin-top: 5px; color: #cdb985; font-size: 9px; }
        .seal { width: 88px; height: 88px; display: grid; place-items: center; border: 1px solid rgba(205,185,133,.65); border-radius: 50%; color: #e2c994; background: rgba(185,154,91,.08); }
        .seal svg { width: 49px; height: 49px; }
        .ledger-lines { display: grid; gap: 10px; }
        .ledger-line { height: 9px; border-radius: 20px; background: rgba(255,255,255,.1); }
        .ledger-line:nth-child(1) { width: 100%; }
        .ledger-line:nth-child(2) { width: 84%; }
        .ledger-line:nth-child(3) { width: 92%; }
        .ledger-bottom { display: flex; align-items: end; gap: 9px; height: 96px; margin-top: 24px; padding: 12px 14px 0; border-radius: 14px; background: rgba(5,19,32,.25); }
        .ledger-bar { flex: 1; min-width: 8px; border-radius: 6px 6px 0 0; background: linear-gradient(180deg, #ddc58f, rgba(185,154,91,.18)); }
        .ledger-bar:nth-child(1) { height: 35%; }.ledger-bar:nth-child(2) { height: 56%; }.ledger-bar:nth-child(3) { height: 45%; }.ledger-bar:nth-child(4) { height: 78%; }.ledger-bar:nth-child(5) { height: 62%; }.ledger-bar:nth-child(6) { height: 91%; }.ledger-bar:nth-child(7) { height: 73%; }
        .floating-note { position: absolute; left: -8px; bottom: 25px; display: flex; align-items: center; gap: 10px; padding: 13px 16px; border: 1px solid rgba(20,45,70,.08); border-radius: 14px; color: var(--navy); background: #fff; box-shadow: 0 16px 40px rgba(0,0,0,.18); transform: rotate(2deg); }
        .floating-note strong { display: block; font-size: 10px; }.floating-note small { display: block; margin-top: 4px; color: var(--muted); font-size: 8px; }
        .floating-icon { width: 31px; height: 31px; display: grid; place-items: center; border-radius: 10px; color: var(--gold); background: #f5f0e5; font-size: 16px; }
        .trust-strip { position: relative; z-index: 1; margin-top: -1px; background: #fff; border-bottom: 1px solid var(--line); }
        .trust-grid { display: grid; grid-template-columns: repeat(3, 1fr); }
        .trust-item { display: flex; align-items: center; justify-content: center; gap: 12px; min-height: 90px; padding: 15px; border-left: 1px solid var(--line); }
        .trust-item:last-child { border-left: 0; }
        .trust-icon { width: 36px; height: 36px; display: grid; place-items: center; border-radius: 12px; color: var(--gold); background: #f6f2e9; font-size: 17px; }
        .trust-item strong { display: block; color: var(--navy); font-size: 12px; }.trust-item small { display: block; margin-top: 4px; color: var(--muted); font-size: 9px; }
        .section { padding: 88px 0; }
        .section-heading { max-width: 670px; margin: 0 auto 39px; text-align: center; }
        .section-kicker { color: var(--gold); font-size: 10px; font-weight: 900; letter-spacing: 1px; }
        .section-heading h2 { margin: 11px 0 12px; color: var(--navy); font-size: clamp(25px, 3vw, 35px); font-weight: 900; line-height: 1.6; }
        .section-heading p { margin: 0; color: var(--muted); font-size: 12px; line-height: 2.2; }
        .services { background: #f6f7f8; }
        .service-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
        .service-card { min-height: 218px; padding: 25px; border: 1px solid #e8ebee; border-radius: 19px; background: #fff; box-shadow: 0 9px 28px rgba(20,45,70,.035); transition: transform .2s, border-color .2s, box-shadow .2s; }
        .service-card:hover { transform: translateY(-5px); border-color: #d8c69f; box-shadow: 0 18px 36px rgba(20,45,70,.08); }
        .service-icon { width: 46px; height: 46px; display: grid; place-items: center; border-radius: 14px; color: var(--gold); background: #f6f2e9; }
        .service-icon svg { width: 23px; height: 23px; }
        .service-card h3 { margin: 18px 0 8px; color: var(--navy); font-size: 15px; font-weight: 800; }
        .service-card p { margin: 0; color: var(--muted); font-size: 11px; line-height: 2.1; }
        .about { background: #fff; }
        .about-grid { display: grid; grid-template-columns: .9fr 1.1fr; align-items: center; gap: 70px; }
        .about-art { position: relative; min-height: 330px; display: grid; place-items: center; border-radius: 25px; background: var(--navy); overflow: hidden; }
        .about-art::before, .about-art::after { content: ""; position: absolute; border: 1px solid rgba(232,220,192,.2); border-radius: 50%; }
        .about-art::before { width: 330px; height: 330px; }.about-art::after { width: 240px; height: 240px; }
        .about-monogram { position: relative; z-index: 1; color: #e2c994; font-size: 100px; font-weight: 900; }
        .about-copy .section-kicker { display: inline-block; }
        .about-copy h2 { margin: 12px 0 16px; color: var(--navy); font-size: clamp(25px, 3vw, 34px); line-height: 1.65; font-weight: 900; }
        .about-copy p { margin: 0; color: var(--muted); font-size: 12px; line-height: 2.35; }
        .value-list { display: grid; grid-template-columns: 1fr 1fr; gap: 13px; margin-top: 24px; }
        .value-item { display: flex; align-items: center; gap: 9px; color: var(--navy); font-size: 11px; font-weight: 700; }
        .value-item::before { content: ""; width: 7px; height: 7px; flex: 0 0 7px; border-radius: 50%; background: var(--gold); }
        .process { background: #f6f7f8; }
        .process-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; }
        .process-card { position: relative; padding: 24px 20px; border: 1px solid #e7eaed; border-radius: 17px; background: #fff; }
        .process-number { color: #d7c49a; font-size: 27px; font-weight: 900; }
        .process-card h3 { margin: 11px 0 7px; color: var(--navy); font-size: 13px; font-weight: 800; }
        .process-card p { margin: 0; color: var(--muted); font-size: 10px; line-height: 2; }
        .audience { background: #fff; }
        .audience-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
        .audience-item { padding: 20px 14px; border: 1px solid var(--line); border-radius: 15px; color: var(--navy); background: #fff; text-align: center; font-size: 11px; font-weight: 700; }
        .cta-section { padding: 0 0 82px; background: #fff; }
        .cta-panel { display: flex; align-items: center; justify-content: space-between; gap: 25px; padding: 34px 38px; border-radius: 22px; color: #fff; background: var(--navy); }
        .cta-panel h2 { margin: 0 0 8px; font-size: 22px; font-weight: 900; }.cta-panel p { margin: 0; color: #c0ccd7; font-size: 11px; line-height: 2; }
        .contact { padding: 72px 0; color: #d5dee6; background: var(--navy-deep); }
        .contact-grid { display: grid; grid-template-columns: 1.2fr .8fr; gap: 60px; align-items: center; }
        .contact h2 { margin: 10px 0 12px; color: #fff; font-size: 27px; font-weight: 900; }
        .contact p { max-width: 650px; margin: 0; color: #b5c2ce; font-size: 11px; line-height: 2.3; }
        .contact-card { padding: 23px; border: 1px solid rgba(232,220,192,.22); border-radius: 17px; background: rgba(255,255,255,.04); }
        .contact-card strong { display: block; color: #e2c994; font-size: 13px; }.contact-card span { display: block; margin-top: 8px; color: #b5c2ce; font-size: 10px; line-height: 2; }.contact-phone { display: inline-flex; align-items: center; gap: 9px; margin-top: 15px; color: #fff; font-size: 20px; font-weight: 800; letter-spacing: .5px; text-decoration: none; direction: ltr; }.contact-phone:hover { color: #e2c994; }.contact-phone-label { display: block; margin-top: 5px; color: #b5c2ce; font-size: 10px; }
        .footer { padding: 22px 0; color: #9baab8; background: #091a2b; font-size: 9px; }
        .footer-inner { display: flex; justify-content: space-between; align-items: center; gap: 20px; }
        .footer strong { color: #e2c994; font-size: 12px; }
        .footer a { color: #c5d0d9; text-decoration: none; }
        @media (max-width: 980px) {
            .nav { min-height: 76px; }.nav-links { gap: 14px; }.hero-grid { gap: 28px; }.visual { min-height: 340px; }
            .about-grid { gap: 35px; }.process-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 760px) {
            .container { width: min(100% - 32px, 560px); }
            .nav { flex-wrap: wrap; padding: 13px 0; gap: 12px; }.nav-links { order: 3; width: 100%; justify-content: space-between; padding-top: 9px; border-top: 1px solid var(--line); font-size: 10px; }
            .nav-actions { margin-right: auto; }.login-link { display: none; }.nav-actions .button { min-height: 39px; padding: 8px 12px; font-size: 10px; }
            .hero { padding: 55px 0 60px; }.hero-grid { grid-template-columns: 1fr; gap: 26px; }.hero h1 { font-size: clamp(32px, 8vw, 43px); }
            .hero-copy { font-size: 12px; }.visual { min-height: 315px; }.ledger { width: min(100%, 430px); }.floating-note { left: 0; bottom: 0; }
            .trust-grid { grid-template-columns: 1fr; }.trust-item { justify-content: flex-start; min-height: 68px; border-left: 0; border-bottom: 1px solid var(--line); }.trust-item:last-child { border-bottom: 0; }
            .section { padding: 65px 0; }.service-grid { grid-template-columns: 1fr 1fr; gap: 11px; }.service-card { min-height: 205px; padding: 18px; }.service-card h3 { font-size: 13px; }
            .about-grid { grid-template-columns: 1fr; }.about-art { min-height: 240px; order: 2; }.about-monogram { font-size: 80px; }
            .audience-grid { grid-template-columns: 1fr 1fr; }.contact-grid { grid-template-columns: 1fr; gap: 24px; }.cta-panel { align-items: flex-start; flex-direction: column; padding: 27px 23px; }
        }
        @media (max-width: 430px) {
            .service-grid, .process-grid { grid-template-columns: 1fr; }.service-card { min-height: 0; }
            .hero-note { gap: 10px 15px; }.ledger { padding: 18px; }.ledger-main { grid-template-columns: 1fr 75px; }.seal { width: 68px; height: 68px; }
            .footer-inner { align-items: flex-start; flex-direction: column; gap: 9px; }.value-list { grid-template-columns: 1fr; }
        }
        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
            *, *::before, *::after { transition-duration: .01ms !important; animation-duration: .01ms !important; animation-iteration-count: 1 !important; }
        }
    </style>
</head>
<body>
@php
$siteContent = array_merge([
'hero_eyebrow' => 'همراه مطمئن تصمیم‌های مالی کسب‌وکار',
'hero_title' => 'شفافیت مالی، آغاز تصمیم‌های مطمئن است.',
'hero_copy' => 'در فرنام، حسابداری را فقط ثبت اعداد نمی‌دانیم؛ اطلاعات مالی باید به شناخت بهتر کسب‌وکار، کنترل هزینه‌ها و تصمیم‌گیری آگاهانه کمک کند. در مسیر حسابداری، امور مالی و مالیاتی همراه شما هستیم.',
'services_title' => 'خدمات مالی، متناسب با نیاز کسب‌وکار شما',
'services_intro' => 'از ساماندهی اطلاعات حسابداری تا تحلیل مالی و مشاوره مالیاتی؛ خدمات فرنام بر شناخت مسئله و ارائه راهکار متناسب با شرایط هر مجموعه متمرکز است.',
'about_title' => 'فرنام؛ همراه شما برای مدیریت آگاهانه امور مالی',
'about_copy' => 'هر کسب‌وکار، شرایط و نیازهای مالی ویژه خود را دارد. رویکرد فرنام بر شناخت دقیق این نیازها، نظم‌بخشی به اطلاعات، تحلیل مسئله و ارائه راهکارهای کاربردی در حوزه حسابداری، مالی و مالیاتی استوار است. هدف، فراهم‌کردن دیدی روشن‌تر برای مدیران و کمک به تصمیم‌گیری مسئولانه‌تر است.',
'contact_title' => 'برای شروع یک گفت‌وگوی حرفه‌ای آماده‌ایم.',
'contact_copy' => 'برای دریافت مشاوره در زمینه حسابداری، امور مالی و مالیاتی با فرنام تماس بگیرید. کارشناسان ما آماده شنیدن نیازهای کسب‌وکار شما و گفت‌وگو درباره مسیر مناسب همکاری هستند.',
'phone' => '09130162404',
'services' => [
['title' => 'خدمات حسابداری', 'description' => 'ساماندهی فرآیندهای حسابداری، ثبت و طبقه‌بندی اطلاعات و بهبود کیفیت گزارش‌های مالی.'],
['title' => 'مشاوره مالی', 'description' => 'تحلیل وضعیت مالی، بررسی هزینه‌ها و پشتیبانی اطلاعاتی برای تصمیم‌های مدیریتی.'],
['title' => 'مشاوره مالیاتی', 'description' => 'بررسی تکالیف و پرونده‌های مالیاتی، شناخت ریسک‌ها و همراهی در چارچوب قوانین و مقررات.'],
['title' => 'حسابداری صنعتی و بهای تمام‌شده', 'description' => 'تحلیل مواد، دستمزد و سربار، هزینه‌یابی و شناخت عوامل مؤثر بر بهای تمام‌شده محصولات.'],
['title' => 'گزارشگری مدیریتی', 'description' => 'طراحی و تحلیل گزارش‌هایی که تصویر روشن‌تری از عملکرد و وضعیت مالی مجموعه ارائه می‌کنند.'],
['title' => 'استقرار و بهبود سیستم مالی', 'description' => 'بررسی ساختارها و گردش کار مالی و پیشنهاد مسیرهای بهبود متناسب با اندازه و فعالیت شرکت.'],
],
], $siteContent ?? []);
@endphp
@php($displayPhone = strtr($siteContent['phone'], '0123456789', '۰۱۲۳۴۵۶۷۸۹'))
<div class="site">
    <header class="topbar">
        <div class="container nav">
            <a class="brand" href="/" aria-label="فرنام، صفحه اصلی">
                <span class="brand-mark" aria-hidden="true">
                    <svg viewBox="0 0 32 32" fill="none">
                        <path d="M6 25V7h20M6 15h14M6 25h20" stroke="currentColor" stroke-width="2.1" stroke-linecap="round"/>
                        <path d="M20 7v18M15 15v10" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" opacity=".72"/>
                    </svg>
                </span>
                <span><span class="brand-name">فرنام</span><span class="brand-caption">مشاوره حسابداری، مالی و مالیاتی</span></span>
            </a>
            <nav class="nav-links" aria-label="منوی اصلی">
                <a href="#home">صفحه اصلی</a>
                <a href="#services">خدمات</a>
                <a href="#about">درباره فرنام</a>
                <a href="#process">فرآیند همکاری</a>
                <a href="#contact">تماس با ما</a>
            </nav>
            <div class="nav-actions">
                <a class="login-link" href="/login">ورود کاربران</a>
                <a class="button button-gold" href="#contact">درخواست مشاوره <span aria-hidden="true">←</span></a>
            </div>
        </div>
    </header>

    <main>
        <section class="hero" id="home">
            <div class="container hero-grid">
                <div>
                    <span class="eyebrow">{{ $siteContent['hero_eyebrow'] }}</span>
                    <h1>{{ $siteContent['hero_title'] }}</h1>
                    <p class="hero-copy">{{ $siteContent['hero_copy'] }}</p>
                    <div class="hero-actions">
                        <a class="button button-gold" href="#contact">درخواست جلسه مشاوره <span aria-hidden="true">←</span></a>
                        <a class="button button-outline" href="#services">آشنایی با خدمات</a>
                    </div>
                    <div class="hero-note">
                        <span><i></i> نگاه تحلیلی و مدیریتی</span>
                        <span><i></i> راهکار متناسب با کسب‌وکار</span>
                        <span><i></i> شفافیت در ارائه خدمات</span>
                    </div>
                </div>
                <div class="visual" aria-hidden="true">
                    <div class="visual-glow"></div>
                    <div class="ledger">
                        <div class="ledger-top"><span class="ledger-title">دیدی روشن‌تر از وضعیت مالی</span><span class="ledger-label">FARNAM / FINANCIAL ADVISORY</span></div>
                        <div class="ledger-main">
                            <div><div class="ledger-kicker">تحلیل و نظم مالی</div><div class="ledger-value">FARNAM</div><div class="ledger-sub">ACCOUNTING · FINANCE · TAX</div></div>
                            <div class="seal"><svg viewBox="0 0 48 48" fill="none"><path d="M24 5v34M13 13h22M17 13l-7 13h14l-7-13ZM31 13l-7 13h14l-7-13ZM16 40h16" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
                        </div>
                        <div class="ledger-lines"><i class="ledger-line"></i><i class="ledger-line"></i><i class="ledger-line"></i></div>
                        <div class="ledger-bottom"><i class="ledger-bar"></i><i class="ledger-bar"></i><i class="ledger-bar"></i><i class="ledger-bar"></i><i class="ledger-bar"></i><i class="ledger-bar"></i><i class="ledger-bar"></i></div>
                    </div>
                    <div class="floating-note"><span class="floating-icon">✓</span><span><strong>نظم و شفافیت مالی</strong><small>پایه‌ای برای تصمیم‌گیری بهتر</small></span></div>
                </div>
            </div>
        </section>

        <section class="trust-strip" aria-label="ارزش‌های کاری فرنام">
            <div class="container trust-grid">
                <div class="trust-item"><span class="trust-icon">◎</span><span><strong>دقت و انضباط</strong><small>توجه به جزئیات و کیفیت اطلاعات</small></span></div>
                <div class="trust-item"><span class="trust-icon">◇</span><span><strong>شفافیت و پاسخ‌گویی</strong><small>ارتباط روشن در مسیر همکاری</small></span></div>
                <div class="trust-item"><span class="trust-icon">↗</span><span><strong>نگاه رو به رشد</strong><small>توجه به نیازهای واقعی کسب‌وکار</small></span></div>
            </div>
        </section>

        <section class="section services" id="services">
            <div class="container">
                <div class="section-heading">
                    <span class="section-kicker">FARNAM SERVICES</span>
                    <h2>{{ $siteContent['services_title'] }}</h2>
                    <p>{{ $siteContent['services_intro'] }}</p>
                </div>
                <div class="service-grid">
                    @foreach ($siteContent['services'] as $service)
                        <article class="service-card">
                            <span class="service-icon" aria-hidden="true">✦</span>
                            <h3>{{ $service['title'] }}</h3>
                            <p>{{ $service['description'] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="section about" id="about">
            <div class="container about-grid">
                <div class="about-art" aria-hidden="true"><span class="about-monogram">ف</span></div>
                <div class="about-copy">
                    <span class="section-kicker">ABOUT FARNAM</span>
                    <h2>{{ $siteContent['about_title'] }}</h2>
                    <p>{{ $siteContent['about_copy'] }}</p>
                    <div class="value-list">
                        <span class="value-item">شناخت نیاز هر مجموعه</span>
                        <span class="value-item">توجه به جزئیات مالی</span>
                        <span class="value-item">راهکارهای قابل اجرا</span>
                        <span class="value-item">ارتباط حرفه‌ای و شفاف</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="section process" id="process">
            <div class="container">
                <div class="section-heading">
                    <span class="section-kicker">HOW WE WORK</span>
                    <h2>مسیر همکاری با فرنام</h2>
                    <p>فرآیند همکاری با شناخت نیاز آغاز می‌شود و با ارائه راهکار متناسب با توافق و دامنه خدمات ادامه پیدا می‌کند.</p>
                </div>
                <div class="process-grid">
                    <article class="process-card"><span class="process-number">۰۱</span><h3>گفت‌وگوی اولیه</h3><p>آشنایی با فعالیت مجموعه و شنیدن مسئله‌ها و انتظارات شما.</p></article>
                    <article class="process-card"><span class="process-number">۰۲</span><h3>بررسی و شناخت</h3><p>بررسی وضعیت موجود و مشخص‌کردن اولویت‌ها و نیازهای تخصصی.</p></article>
                    <article class="process-card"><span class="process-number">۰۳</span><h3>پیشنهاد راهکار</h3><p>تدوین پیشنهاد همکاری، محدوده خدمات و مسیر اجرایی متناسب.</p></article>
                    <article class="process-card"><span class="process-number">۰۴</span><h3>همراهی تخصصی</h3><p>ارائه خدمات و پیگیری موضوعات در چارچوب توافق‌شده.</p></article>
                </div>
            </div>
        </section>

        <section class="section audience">
            <div class="container">
                <div class="section-heading">
                    <span class="section-kicker">WHO WE SUPPORT</span>
                    <h2>در کنار کسب‌وکارهای گوناگون</h2>
                    <p>نوع خدمات و دامنه همکاری بر اساس ساختار، اندازه و نیاز هر مجموعه تعیین می‌شود.</p>
                </div>
                <div class="audience-grid">
                    <div class="audience-item">شرکت‌های تولیدی</div>
                    <div class="audience-item">شرکت‌های بازرگانی</div>
                    <div class="audience-item">کسب‌وکارهای خدماتی</div>
                    <div class="audience-item">شرکت‌های در حال رشد</div>
                </div>
            </div>
        </section>

        <section class="cta-section">
            <div class="container">
                <div class="cta-panel">
                    <div><h2>برای تصمیم‌های مالی، با دیدی روشن‌تر پیش بروید.</h2><p>نیاز کسب‌وکار خود را مطرح کنید تا درباره مسیر مناسب همکاری گفت‌وگو کنیم.</p></div>
                    <a class="button button-gold" href="#contact">شروع گفت‌وگو با فرنام <span aria-hidden="true">←</span></a>
                </div>
            </div>
        </section>

        <section class="contact" id="contact">
            <div class="container contact-grid">
                <div>
                    <span class="section-kicker">CONTACT FARNAM</span>
                    <h2>{{ $siteContent['contact_title'] }}</h2>
                    <p>{{ $siteContent['contact_copy'] }}</p>
                </div>
                <div class="contact-card">
                    <strong>خدمات مشاوره حسابداری مالی و مالیاتی فرنام</strong>
                    <span>حسابداری · مشاوره مالی · مشاوره مالیاتی · حسابداری صنعتی</span>
                    <a class="contact-phone" href="tel:{{ preg_replace('/[^0-9+]/', '', $siteContent['phone']) }}" aria-label="تماس تلفنی با فرنام: {{ $displayPhone }}">{{ $displayPhone }}</a>
                    <span class="contact-phone-label">برای تماس مستقیم، شماره را لمس کنید.</span>
                </div>
            </div>
        </section>
    </main>

    <footer class="footer">
        <div class="container footer-inner">
            <span><strong>فرنام</strong> · خدمات مشاوره حسابداری مالی و مالیاتی</span>
            <span>تمامی حقوق این وب‌سایت محفوظ است.</span>
            <a href="#home">بازگشت به بالا ↑</a>
        </div>
    </footer>
</div>
</body>
</html>
