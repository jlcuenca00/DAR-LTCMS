<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="DAR-LTCMS public information for land transfer clearance requirements, processing, application monitoring, and DAR Negros Oriental contact channels.">
    <title>DAR-LTCMS | DAR Negros Oriental</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:opsz,wght@17..18,400..700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --dar-green: #166b3a;
            --dar-green-dark: #073b23;
            --dar-green-deep: #052d1b;
            --dar-green-soft: #edf6f0;
            --dar-yellow: #f2c94c;
            --ink: #172019;
            --muted: #5e6a62;
            --border: #dce4de;
            --surface: #ffffff;
            --page: #f6f8f6;
            --focus: #ffd45c;
            --radius: 16px;
        }

        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; scroll-padding-top: 118px; }
        body {
            margin: 0;
            font-family: 'Google Sans', Arial, sans-serif;
            color: var(--ink);
            background: var(--page);
            line-height: 1.6;
        }
        img { max-width: 100%; }
        a { color: inherit; }
        .container { width: min(1180px, calc(100% - 2rem)); margin: 0 auto; }

        .skip-link {
            position: fixed;
            z-index: 100;
            top: 1rem;
            left: 1rem;
            transform: translateY(-180%);
            padding: .7rem 1rem;
            border: 2px solid var(--focus);
            border-radius: 8px;
            background: #fff;
            color: #10251a;
            font-weight: 700;
            text-decoration: none;
        }
        .skip-link:focus { transform: translateY(0); }

        :where(a, summary):focus-visible {
            outline: 3px solid var(--focus);
            outline-offset: 3px;
            border-radius: 6px;
        }

        .utility-bar {
            background: var(--dar-green-deep);
            color: #cfe1d5;
            font-size: .76rem;
        }
        .utility-inner {
            min-height: 34px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }
        .utility-links { display: flex; align-items: center; gap: 1rem; }
        .utility-links a {
            min-height: 34px;
            display: inline-flex;
            align-items: center;
            text-decoration: none;
        }
        .utility-links a:hover { color: #fff; text-decoration: underline; }

        .site-header {
            position: sticky;
            top: 0;
            z-index: 40;
            background: rgba(255,255,255,.98);
            border-bottom: 1px solid var(--border);
            box-shadow: 0 3px 16px rgba(12, 42, 25, .04);
        }
        .header-inner {
            min-height: 78px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.5rem;
        }
        .brand {
            min-height: 48px;
            display: inline-flex;
            align-items: center;
            gap: .8rem;
            text-decoration: none;
            flex-shrink: 0;
        }
        .brand img { width: 48px; height: 48px; object-fit: contain; }
        .brand-name { display: block; color: var(--dar-green-dark); font-size: 1.06rem; font-weight: 700; line-height: 1.1; }
        .brand-office { display: block; margin-top: .16rem; color: var(--muted); font-size: .73rem; }
        .nav { display: flex; align-items: center; gap: .1rem; }
        .nav a {
            min-height: 44px;
            display: inline-flex;
            align-items: center;
            padding: 0 .72rem;
            border-radius: 8px;
            color: #334239;
            text-decoration: none;
            font-size: .87rem;
            font-weight: 600;
        }
        .nav a:hover { background: var(--dar-green-soft); color: var(--dar-green-dark); }
        .nav .sign-in {
            margin-left: .55rem;
            padding-inline: 1rem;
            background: var(--dar-green);
            color: #fff;
        }
        .nav .sign-in:hover { background: var(--dar-green-dark); color: #fff; }

        .hero {
            position: relative;
            overflow: hidden;
            color: #fff;
            background: linear-gradient(115deg, #052d1b 0%, #0a4728 58%, #12643a 100%);
        }
        .hero::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image: linear-gradient(rgba(5,45,27,.88), rgba(5,45,27,.88)), url("{{ asset('images/login-bg.png') }}");
            background-size: cover;
            background-position: center;
            opacity: .48;
            pointer-events: none;
        }
        .hero::after {
            content: "";
            position: absolute;
            width: 440px;
            height: 440px;
            right: -120px;
            top: -140px;
            border-radius: 50%;
            background: rgba(242,201,76,.12);
            filter: blur(4px);
            pointer-events: none;
        }
        .hero-grid {
            position: relative;
            z-index: 1;
            min-height: 590px;
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) minmax(330px, .8fr);
            align-items: center;
            gap: 4.5rem;
            padding: 5rem 0;
        }
        .eyebrow {
            margin: 0 0 .85rem;
            color: #c5e0ce;
            font-size: .8rem;
            font-weight: 700;
            letter-spacing: .09em;
            text-transform: uppercase;
        }
        h1 {
            max-width: 810px;
            margin: 0;
            font-size: clamp(3.15rem, 6.4vw, 5.35rem);
            line-height: .99;
            letter-spacing: -.052em;
        }
        .hero-copy {
            max-width: 710px;
            margin: 1.5rem 0 0;
            color: #dce9e0;
            font-size: clamp(1.02rem, 1.55vw, 1.18rem);
        }
        .hero-actions { display: flex; flex-wrap: wrap; gap: .8rem; margin-top: 2rem; }
        .button {
            min-height: 48px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: .78rem 1.1rem;
            border-radius: 9px;
            font-weight: 700;
            text-decoration: none;
            transition: transform .15s ease, background .15s ease, border-color .15s ease;
        }
        .button:hover { transform: translateY(-2px); }
        .button-primary { background: #fff; color: var(--dar-green-dark); }
        .button-secondary { border: 1px solid rgba(255,255,255,.44); background: rgba(255,255,255,.05); color: #fff; }
        .button-secondary:hover { background: rgba(255,255,255,.11); border-color: #fff; }

        .hero-visual {
            position: relative;
            min-height: 390px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .hero-visual::before {
            content: "";
            position: absolute;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.1);
        }
        .workflow-panel {
            position: relative;
            z-index: 2;
            width: min(390px, 100%);
            padding: 1.25rem;
            border: 1px solid rgba(255,255,255,.18);
            border-radius: 18px;
            background: rgba(4, 34, 20, .74);
            box-shadow: 0 28px 65px rgba(0,0,0,.2);
            backdrop-filter: blur(8px);
        }
        .workflow-head { display: flex; align-items: center; gap: .8rem; padding-bottom: 1rem; border-bottom: 1px solid rgba(255,255,255,.12); }
        .workflow-head img { width: 52px; height: 52px; object-fit: contain; }
        .workflow-title { font-weight: 700; }
        .workflow-sub { margin-top: .1rem; color: #adc9b7; font-size: .78rem; }
        .workflow-list { list-style: none; margin: 1rem 0 0; padding: 0; }
        .workflow-list li { position: relative; display: grid; grid-template-columns: 30px 1fr; gap: .7rem; padding: .55rem 0; color: #e8f0eb; font-size: .9rem; }
        .workflow-list li:not(:last-child)::after { content: ""; position: absolute; left: 14px; top: 31px; width: 1px; height: 20px; background: rgba(255,255,255,.22); }
        .workflow-dot { width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; background: rgba(255,255,255,.1); border: 1px solid rgba(255,255,255,.2); font-size: .74rem; font-weight: 700; }
        .workflow-foot { margin-top: 1rem; padding: .8rem .9rem; border-radius: 10px; background: rgba(242,201,76,.1); color: #f4e6aa; font-size: .82rem; }

        .section { padding: 6rem 0; }
        .section-white { background: #fff; }
        .section-green { background: #0a3a23; color: #fff; }
        .section-heading { max-width: 760px; margin-bottom: 2.5rem; }
        .kicker { margin: 0 0 .55rem; color: var(--dar-green); font-size: .8rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
        .section-green .kicker { color: #a9d3b6; }
        .section-title { margin: 0; font-size: clamp(2.1rem, 4vw, 3.25rem); line-height: 1.08; letter-spacing: -.035em; }
        .section-copy { margin: .85rem 0 0; color: var(--muted); font-size: 1.02rem; }
        .section-green .section-copy { color: #c9d9cf; }

        .about-layout { display: grid; grid-template-columns: 1.1fr .9fr; gap: 3.2rem; align-items: start; }
        .about-lead { font-size: 1.14rem; color: #334139; margin: 0; }
        .about-divider { width: 58px; height: 3px; margin: 1.5rem 0; background: var(--dar-yellow); border-radius: 999px; }
        .about-list { display: grid; gap: .95rem; }
        .about-item { display: grid; grid-template-columns: 34px 1fr; gap: .8rem; align-items: start; }
        .about-icon { width: 34px; height: 34px; display: inline-flex; align-items: center; justify-content: center; border-radius: 9px; background: var(--dar-green-soft); color: var(--dar-green); font-weight: 700; }
        .about-item strong { display: block; }
        .about-item span { display: block; margin-top: .15rem; color: var(--muted); font-size: .9rem; }

        .requirements-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .requirements-column { background: #fff; border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; }
        .requirements-column header { padding: 1.3rem 1.4rem; border-bottom: 1px solid var(--border); background: #fbfcfb; }
        .requirements-column h3 { margin: 0; font-size: 1.12rem; }
        .requirements-column p { margin: .35rem 0 0; color: var(--muted); font-size: .85rem; }
        .requirement-list { list-style: none; margin: 0; padding: .45rem 1.4rem 1rem; }
        .requirement-list li { position: relative; padding: .8rem 0 .8rem 1.55rem; border-bottom: 1px solid #edf1ee; }
        .requirement-list li:last-child { border-bottom: 0; }
        .requirement-list li::before { content: "✓"; position: absolute; left: 0; color: var(--dar-green); font-weight: 700; }
        .additional-band { grid-column: 1 / -1; display: grid; grid-template-columns: 220px 1fr; gap: 1.6rem; padding: 1.3rem 1.4rem; border: 1px solid #bfd4c6; border-radius: var(--radius); background: var(--dar-green-soft); }
        .additional-band h3 { margin: 0; color: var(--dar-green-dark); font-size: 1.05rem; }
        .additional-band ul { margin: 0; padding-left: 1.2rem; color: #425249; display: grid; gap: .35rem; }
        .requirements-note { margin: 1rem 0 0; color: var(--muted); font-size: .88rem; }

        .process-track { position: relative; display: grid; grid-template-columns: repeat(6, 1fr); gap: .7rem; }
        .process-track::before { content: ""; position: absolute; left: 7%; right: 7%; top: 23px; height: 2px; background: #c7d9cc; }
        .process-step { position: relative; z-index: 1; text-align: center; }
        .process-number { width: 46px; height: 46px; margin: 0 auto .8rem; display: flex; align-items: center; justify-content: center; border-radius: 50%; background: var(--dar-green); border: 5px solid #fff; box-shadow: 0 0 0 1px #b6cbbb; color: #fff; font-weight: 700; }
        .process-step h3 { margin: 0; font-size: .96rem; }
        .process-step p { margin: .35rem 0 0; color: var(--muted); font-size: .82rem; line-height: 1.45; }
        .process-note { margin-top: 2rem; padding-top: 1.2rem; border-top: 1px solid var(--border); color: var(--muted); font-size: .9rem; }

        .roles { display: grid; grid-template-columns: repeat(3, 1fr); gap: 2rem; }
        .role { padding-top: 1.2rem; border-top: 3px solid rgba(255,255,255,.26); }
        .role h3 { margin: 0; font-size: 1.1rem; }
        .role p { margin: .55rem 0 0; color: #c9d8cf; font-size: .9rem; }
        .no-apply { margin-top: 2rem; color: #f4e4a4; font-size: .9rem; }

        .faq-layout { display: grid; grid-template-columns: .7fr 1.3fr; gap: 4rem; align-items: start; }
        .faq-list { border-top: 1px solid var(--border); }
        details { border-bottom: 1px solid var(--border); }
        summary { min-height: 62px; position: relative; display: flex; align-items: center; padding: .95rem 3rem .95rem 0; cursor: pointer; list-style: none; font-weight: 700; }
        summary::-webkit-details-marker { display: none; }
        summary::after { content: "+"; position: absolute; right: .35rem; color: var(--dar-green); font-size: 1.45rem; font-weight: 400; }
        details[open] summary::after { content: "−"; }
        .faq-answer { max-width: 830px; padding: 0 3rem 1.25rem 0; color: var(--muted); }
        .faq-answer p { margin: 0; }

        .office-section { background: linear-gradient(120deg, #f8faf8, #edf4ef); }
        .office-layout { display: grid; grid-template-columns: 1.05fr .95fr; gap: 1rem; }
        .office-card { padding: 1.5rem; border: 1px solid var(--border); border-radius: var(--radius); background: #fff; }
        .office-card h3 { margin: 0; font-size: 1.15rem; }
        .contact-list { list-style: none; margin: 1.15rem 0 0; padding: 0; border-top: 1px solid var(--border); }
        .contact-list li { display: grid; grid-template-columns: 120px 1fr; gap: 1rem; padding: .78rem 0; border-bottom: 1px solid #edf1ee; }
        .contact-label { color: var(--muted); font-size: .82rem; font-weight: 700; }
        .contact-value { overflow-wrap: anywhere; }
        .contact-value a { min-height: 44px; display: inline-flex; align-items: center; color: var(--dar-green-dark); font-weight: 700; text-decoration: none; }
        .contact-value a:hover { text-decoration: underline; }
        .office-links { display: grid; gap: .7rem; margin-top: 1rem; }
        .office-link { min-height: 54px; display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: .8rem 1rem; border: 1px solid var(--border); border-radius: 10px; color: var(--dar-green-dark); font-weight: 700; text-decoration: none; }
        .office-link:hover { border-color: #a7c0ae; background: var(--dar-green-soft); }

        footer { background: #041d12; color: #dbe7df; }
        .footer-main { display: grid; grid-template-columns: 1fr auto; gap: 2rem; align-items: center; padding: 2.4rem 0 1.7rem; }
        .footer-brand { display: flex; align-items: center; gap: .8rem; }
        .footer-brand img { width: 50px; height: 50px; object-fit: contain; }
        .footer-brand-text strong { display: block; }
        .footer-brand-text span { color: #9db3a5; font-size: .79rem; }
        .footer-logos { display: flex; align-items: center; gap: 1rem; }
        .footer-logos img { max-width: 78px; max-height: 58px; object-fit: contain; }
        .footer-nav { display: flex; flex-wrap: wrap; gap: .2rem; }
        .footer-nav a { min-height: 44px; display: inline-flex; align-items: center; padding: 0 .6rem; border-radius: 7px; color: #bfd0c5; text-decoration: none; font-size: .82rem; }
        .footer-nav a:hover { color: #fff; background: rgba(255,255,255,.06); }
        .footer-bottom { padding: 1rem 0 1.35rem; border-top: 1px solid rgba(255,255,255,.08); color: #879f90; font-size: .76rem; }

        @media (max-width: 980px) {
            .utility-inner { align-items: flex-start; flex-direction: column; gap: 0; padding: .25rem 0; }
            .utility-links { flex-wrap: wrap; gap: .5rem; }
            .header-inner { align-items: flex-start; flex-direction: column; gap: .35rem; padding: .7rem 0; }
            .nav { width: 100%; overflow-x: auto; justify-content: flex-start; padding-bottom: .2rem; scrollbar-width: thin; }
            .nav .sign-in { margin-left: 0; }
            .hero-grid { grid-template-columns: 1fr; gap: 2rem; min-height: auto; }
            .hero-visual { justify-content: flex-start; min-height: auto; }
            .workflow-panel { max-width: 560px; }
            .about-layout, .faq-layout, .office-layout { grid-template-columns: 1fr; gap: 2.2rem; }
            .process-track { grid-template-columns: repeat(3, 1fr); row-gap: 2rem; }
            .process-track::before { display: none; }
            .roles { gap: 1rem; }
        }

        @media (max-width: 650px) {
            html { scroll-padding-top: 155px; }
            .container { width: min(100% - 1.25rem, 1180px); }
            .utility-links { font-size: .72rem; }
            .brand-office { display: none; }
            .nav a { font-size: .8rem; padding: 0 .55rem; }
            .hero-grid { padding: 4rem 0; }
            h1 { font-size: clamp(2.8rem, 13vw, 4rem); }
            .hero-actions .button { width: 100%; }
            .section { padding: 4.5rem 0; }
            .requirements-layout, .roles, .process-track { grid-template-columns: 1fr; }
            .additional-band { grid-template-columns: 1fr; }
            .process-step { display: grid; grid-template-columns: 48px 1fr; gap: .8rem; text-align: left; align-items: start; }
            .process-number { margin: 0; }
            .contact-list li { grid-template-columns: 1fr; gap: .2rem; }
            .footer-main { grid-template-columns: 1fr; }
        }

        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
            *, *::before, *::after { transition-duration: .01ms !important; animation-duration: .01ms !important; animation-iteration-count: 1 !important; }
        }
    </style>
</head>
<body>
    <a class="skip-link" href="#main-content">Skip to main content</a>

    <div class="utility-bar">
        <div class="container utility-inner">
            <span>Department of Agrarian Reform · Negros Oriental Provincial Office</span>
            <div class="utility-links">
                <a href="https://www.dar.gov.ph/home" target="_blank" rel="noopener noreferrer">Official DAR Website ↗</a>
                <a href="https://www.facebook.com/DARLegalNegor" target="_blank" rel="noopener noreferrer">DAR Legal Negros Oriental ↗</a>
            </div>
        </div>
    </div>

    <header class="site-header">
        <div class="container header-inner">
            <a href="{{ route('home') }}" class="brand" aria-label="DAR-LTCMS home">
                <img src="{{ asset('images/dar-logo.svg') }}" alt="Department of Agrarian Reform logo">
                <span>
                    <span class="brand-name">DAR-LTCMS</span>
                    <span class="brand-office">Land Transfer Clearance and Monitoring System</span>
                </span>
            </a>

            <nav class="nav" aria-label="Public information">
                <a href="#about">About</a>
                <a href="#requirements">Requirements</a>
                <a href="#process">Process</a>
                <a href="#faq">FAQ</a>
                <a href="#office">Office</a>
                <a href="{{ route('login') }}" class="sign-in">Sign In</a>
            </nav>
        </div>
    </header>

    <main id="main-content">
        <section class="hero" aria-labelledby="hero-title">
            <div class="container hero-grid">
                <div>
                    <p class="eyebrow">DAR Negros Oriental · Legal Assistance Division</p>
                    <h1 id="hero-title">Land Transfer Clearance and Monitoring System</h1>
                    <p class="hero-copy">
                        A public information and authorized-access portal for land transfer clearance requirements, office processing, application monitoring, and clearance outputs handled by the DAR Negros Oriental Provincial Office.
                    </p>
                    <div class="hero-actions">
                        <a href="{{ route('login') }}" class="button button-primary">Sign In</a>
                        <a href="#requirements" class="button button-secondary">View Clearance Requirements</a>
                    </div>
                </div>

                <div class="hero-visual" aria-label="Clearance processing overview">
                    <div class="workflow-panel">
                        <div class="workflow-head">
                            <img src="{{ asset('images/dar-logo.svg') }}" alt="">
                            <div>
                                <div class="workflow-title">Clearance processing at a glance</div>
                                <div class="workflow-sub">DAR Negros Oriental Provincial Office</div>
                            </div>
                        </div>
                        <ol class="workflow-list">
                            <li><span class="workflow-dot">1</span><span>Prepare and present the applicable requirements</span></li>
                            <li><span class="workflow-dot">2</span><span>DAR staff encode and review the application</span></li>
                            <li><span class="workflow-dot">3</span><span>Application progress is recorded and monitored</span></li>
                            <li><span class="workflow-dot">4</span><span>Final clearance decision or output is recorded</span></li>
                        </ol>
                        <div class="workflow-foot">Landowners do not create applications themselves. Applications are encoded by authorized DAR staff.</div>
                    </div>
                </div>
            </div>
        </section>

        <section class="section section-white" id="about" aria-labelledby="about-title">
            <div class="container">
                <div class="section-heading">
                    <p class="kicker">About DAR-LTCMS</p>
                    <h2 class="section-title" id="about-title">A digital front desk for land transfer clearance.</h2>
                </div>

                <div class="about-layout">
                    <div>
                        <p class="about-lead">DAR-LTCMS supports the administrative processing and monitoring of land transfer clearance applications handled within the DAR Negros Oriental Provincial Office.</p>
                        <div class="about-divider"></div>
                        <p class="section-copy">The platform gives authorized personnel one place to manage application records, supporting documents, parcel and landholding references, workflow status, monitoring outputs, and audit history. Authorized landowners and geodetic personnel receive limited role-appropriate access.</p>
                    </div>

                    <div class="about-list">
                        <div class="about-item"><span class="about-icon">01</span><div><strong>Clearance processing</strong><span>Staff encode, review, process, and record clearance applications.</span></div></div>
                        <div class="about-item"><span class="about-icon">02</span><div><strong>Records and documents</strong><span>Landowner, parcel, landholding, reference, and supporting document information are organized for authorized use.</span></div></div>
                        <div class="about-item"><span class="about-icon">03</span><div><strong>Status monitoring</strong><span>Application progress and final decisions remain visible to the appropriate authorized users.</span></div></div>
                        <div class="about-item"><span class="about-icon">04</span><div><strong>Traceability</strong><span>Important actions and final records are preserved for accountability and monitoring.</span></div></div>
                    </div>
                </div>
            </div>
        </section>

        <section class="section" id="requirements" aria-labelledby="requirements-title">
            <div class="container">
                <div class="section-heading">
                    <p class="kicker">Clearance requirements</p>
                    <h2 class="section-title" id="requirements-title">Prepare before visiting DAR.</h2>
                    <p class="section-copy">Use this public preparation guide as a starting point. DAR personnel remain responsible for determining the official requirements that apply to each transaction.</p>
                </div>

                <div class="requirements-layout">
                    <article class="requirements-column">
                        <header>
                            <h3>Transferor requirements</h3>
                            <p>Core documents commonly prepared for the transferor side.</p>
                        </header>
                        <ul class="requirement-list">
                            <li>Official Receipt for LTC fee payment</li>
                            <li>Electronic Copy of Title</li>
                            <li>Deed or Document to be Registered</li>
                            <li>Affidavit of Transferor</li>
                            <li>Municipal Assessor's Certificate of Aggregate Landholding</li>
                            <li>Provincial Assessor's Certificate of Aggregate Landholding</li>
                        </ul>
                    </article>

                    <article class="requirements-column">
                        <header>
                            <h3>Transferee requirements</h3>
                            <p>Core documents commonly prepared for the transferee side.</p>
                        </header>
                        <ul class="requirement-list">
                            <li>Affidavit of Transferee</li>
                            <li>Municipal Assessor's Certificate of Aggregate Landholding</li>
                            <li>Provincial Assessor's Certificate of Aggregate Landholding</li>
                            <li>MARPO Certification (LTC Form No. 2)</li>
                        </ul>
                    </article>

                    <aside class="additional-band">
                        <h3>Additional / case-dependent</h3>
                        <ul>
                            <li>Death Certificate, when applicable</li>
                            <li>City Assessor's Certificate of Aggregate Landholding, depending on jurisdiction</li>
                            <li>Recent Tax Declaration, when available</li>
                            <li>Other documents requested after official DAR review</li>
                        </ul>
                    </aside>
                </div>
                <p class="requirements-note">Additional documents may be required depending on the circumstances of the transaction.</p>
            </div>
        </section>

        <section class="section section-white" id="process" aria-labelledby="process-title">
            <div class="container">
                <div class="section-heading">
                    <p class="kicker">How processing works</p>
                    <h2 class="section-title" id="process-title">From preparation to clearance output.</h2>
                    <p class="section-copy">The public flow is straightforward. Authorized DAR personnel handle the application inside the system.</p>
                </div>

                <div class="process-track">
                    <article class="process-step"><div class="process-number">1</div><div><h3>Prepare</h3><p>Gather the applicable requirements.</p></div></article>
                    <article class="process-step"><div class="process-number">2</div><div><h3>Coordinate</h3><p>Visit or coordinate with DAR Negros Oriental.</p></div></article>
                    <article class="process-step"><div class="process-number">3</div><div><h3>Encode</h3><p>DAR staff create the application record.</p></div></article>
                    <article class="process-step"><div class="process-number">4</div><div><h3>Review</h3><p>Requirements and records are reviewed.</p></div></article>
                    <article class="process-step"><div class="process-number">5</div><div><h3>Monitor</h3><p>Administrative progress is recorded.</p></div></article>
                    <article class="process-step"><div class="process-number">6</div><div><h3>Decision</h3><p>Final clearance decision or output is recorded.</p></div></article>
                </div>

                <p class="process-note">Any later legal conveyance, registration, or ownership-record change required by the transaction is handled through the applicable separate legal and administrative procedures.</p>
            </div>
        </section>

        <section class="section section-green" id="access" aria-labelledby="access-title">
            <div class="container">
                <div class="section-heading">
                    <p class="kicker">Authorized access</p>
                    <h2 class="section-title" id="access-title">Who can use DAR-LTCMS?</h2>
                    <p class="section-copy">Access is based on assigned role and authorized records.</p>
                </div>

                <div class="roles">
                    <article class="role"><h3>DAR Staff</h3><p>Encode and process applications, manage authorized records and documents, record workflow actions, and prepare monitoring outputs and reports.</p></article>
                    <article class="role"><h3>Landowners</h3><p>View only their own linked parcel records, application status, final decision information, and available clearance output.</p></article>
                    <article class="role"><h3>Geodetic Personnel</h3><p>Use limited read-only access for parcel, reference, and map-based review.</p></article>
                </div>
                <p class="no-apply"><strong>No online self-application.</strong> Clearance applications are encoded by authorized DAR staff.</p>
            </div>
        </section>

        <section class="section section-white" id="faq" aria-labelledby="faq-title">
            <div class="container faq-layout">
                <div>
                    <p class="kicker">Frequently asked questions</p>
                    <h2 class="section-title" id="faq-title">Before you proceed.</h2>
                    <p class="section-copy">Quick answers to common questions about DAR-LTCMS and land transfer clearance processing.</p>
                </div>

                <div class="faq-list">
                    <details>
                        <summary>Can I submit a land transfer clearance application online?</summary>
                        <div class="faq-answer"><p>No. Authorized DAR staff encode and process clearance applications. This public page is for information and preparation guidance.</p></div>
                    </details>
                    <details>
                        <summary>Can I see my application status?</summary>
                        <div class="faq-answer"><p>Yes, when your landowner account is properly linked to your record. Landowner accounts cannot view another landowner's records.</p></div>
                    </details>
                    <details>
                        <summary>Does an approved or released clearance mean ownership has already transferred?</summary>
                        <div class="faq-answer"><p>No. A clearance decision or generated output does not itself change land ownership or registry ownership records.</p></div>
                    </details>
                    <details>
                        <summary>What should I bring to DAR?</summary>
                        <div class="faq-answer"><p>Start with the transferor and transferee requirements listed above, plus any additional documents that apply to your transaction. DAR personnel determine the official applicable requirements.</p></div>
                    </details>
                    <details>
                        <summary>Can the system decide whether my transaction is legally valid?</summary>
                        <div class="faq-answer"><p>No. DAR-LTCMS supports administrative processing, records management, monitoring, validation assistance, and clearance generation. Official determinations remain with authorized personnel and the applicable government procedures.</p></div>
                    </details>
                </div>
            </div>
        </section>

        <section class="section office-section" id="office" aria-labelledby="office-title">
            <div class="container">
                <div class="section-heading">
                    <p class="kicker">Office information</p>
                    <h2 class="section-title" id="office-title">DAR Negros Oriental Legal Assistance Division</h2>
                </div>

                <div class="office-layout">
                    <article class="office-card">
                        <h3>Contact details</h3>
                        <ul class="contact-list">
                            <li><span class="contact-label">Telephone</span><span class="contact-value"><a href="tel:5227144">522-7144</a></span></li>
                            <li><span class="contact-label">Cellphone</span><span class="contact-value"><a href="tel:+639168763071">0916-876-3071</a></span></li>
                            <li><span class="contact-label">Email</span><span class="contact-value"><a href="mailto:dar_legal_orneg@yahoo.com">dar_legal_orneg@yahoo.com</a></span></li>
                            <li><span class="contact-label">Regional Office</span><span class="contact-value"><a href="tel:+63322536498">(032) 253-6498</a></span></li>
                        </ul>
                    </article>

                    <article class="office-card">
                        <h3>Online channels</h3>
                        <div class="office-links">
                            <a class="office-link" href="https://www.facebook.com/DARLegalNegor" target="_blank" rel="noopener noreferrer"><span>DAR Legal Negros Oriental</span><span aria-hidden="true">↗</span></a>
                            <a class="office-link" href="https://www.dar.gov.ph/home" target="_blank" rel="noopener noreferrer"><span>Department of Agrarian Reform Website</span><span aria-hidden="true">↗</span></a>
                        </div>
                    </article>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <div class="container footer-main">
            <div class="footer-brand">
                <img src="{{ asset('images/dar-logo.svg') }}" alt="Department of Agrarian Reform logo">
                <div class="footer-brand-text">
                    <strong>Department of Agrarian Reform</strong>
                    <span>Negros Oriental Provincial Office · DAR-LTCMS</span>
                </div>
            </div>
            <div class="footer-logos" aria-label="Government identity marks">
                <img src="{{ asset('images/bagong-pilipinas-logo.svg') }}" alt="Bagong Pilipinas">
            </div>
        </div>
        <div class="container footer-nav" aria-label="Footer navigation">
            <a href="#about">About</a>
            <a href="#requirements">Requirements</a>
            <a href="#process">Process</a>
            <a href="#faq">FAQ</a>
            <a href="#office">Office</a>
            <a href="{{ route('login') }}">Sign In</a>
        </div>
        <div class="container footer-bottom">© {{ now()->year }} Department of Agrarian Reform. DAR-LTCMS supports administrative land transfer clearance processing and monitoring for the DAR Negros Oriental Provincial Office.</div>
    </footer>
</body>
</html>
