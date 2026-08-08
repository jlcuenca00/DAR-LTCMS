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
            --green-950: #062719;
            --green-900: #0a3923;
            --green-800: #0f5330;
            --green-700: #166b3a;
            --green-100: #dcefe3;
            --green-50: #f0f7f2;
            --ink: #162019;
            --muted: #5d6961;
            --border: #d9e2dc;
            --surface: #ffffff;
            --page: #f5f7f5;
            --focus: #f6c945;
            --radius: 18px;
        }

        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; scroll-padding-top: 96px; }
        body {
            margin: 0;
            font-family: 'Google Sans', Arial, sans-serif;
            color: var(--ink);
            background: var(--page);
            line-height: 1.6;
        }
        img { max-width: 100%; }
        a { color: inherit; }
        button, input, summary { font: inherit; }
        .container { width: min(1180px, calc(100% - 2rem)); margin: 0 auto; }

        .skip-link {
            position: fixed;
            left: 1rem;
            top: 1rem;
            z-index: 100;
            transform: translateY(-160%);
            padding: .7rem 1rem;
            background: #fff;
            color: var(--green-950);
            border: 2px solid var(--focus);
            border-radius: 10px;
            font-weight: 700;
            text-decoration: none;
        }
        .skip-link:focus { transform: translateY(0); }

        :where(a, summary):focus-visible {
            outline: 3px solid var(--focus);
            outline-offset: 3px;
            border-radius: 6px;
        }

        .site-header {
            position: sticky;
            top: 0;
            z-index: 40;
            background: rgba(6, 39, 25, .97);
            border-bottom: 1px solid rgba(255,255,255,.12);
            color: #fff;
        }
        .header-inner {
            min-height: 76px;
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
        .brand img { width: 44px; height: 44px; object-fit: contain; }
        .brand-name { display: block; font-weight: 700; line-height: 1.1; }
        .brand-office { display: block; margin-top: .18rem; color: #bfd0c5; font-size: .72rem; }
        .nav {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: .25rem;
        }
        .nav a {
            min-height: 44px;
            display: inline-flex;
            align-items: center;
            padding: 0 .72rem;
            color: #d9e7de;
            text-decoration: none;
            border-radius: 10px;
            font-size: .88rem;
            font-weight: 600;
        }
        .nav a:hover { background: rgba(255,255,255,.08); color: #fff; }
        .nav .sign-in { margin-left: .4rem; background: #fff; color: #10291b; }
        .nav .sign-in:hover { background: #edf5ef; color: #10291b; }

        .hero {
            position: relative;
            overflow: hidden;
            color: #fff;
            background: var(--green-950);
        }
        .hero::before {
            content: "";
            position: absolute;
            inset: 0;
            pointer-events: none;
            background:
                radial-gradient(circle at 82% 18%, rgba(67, 146, 91, .24), transparent 30%),
                radial-gradient(circle at 18% 90%, rgba(255,255,255,.08), transparent 28%);
        }
        .hero-grid {
            position: relative;
            display: grid;
            grid-template-columns: minmax(0, 1.3fr) minmax(330px, .7fr);
            align-items: center;
            gap: 4.5rem;
            min-height: 620px;
            padding: 5.25rem 0;
        }
        .hero-badge {
            width: fit-content;
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            margin-bottom: 1.1rem;
            padding: .42rem .72rem;
            border: 1px solid rgba(255,255,255,.2);
            border-radius: 999px;
            background: rgba(255,255,255,.06);
            color: #cce0d2;
            font-size: .8rem;
            font-weight: 700;
        }
        .hero-badge::before { content: ""; width: 8px; height: 8px; border-radius: 50%; background: #69c383; }
        h1 {
            max-width: 820px;
            margin: 0;
            font-size: clamp(3rem, 6vw, 5.15rem);
            line-height: 1;
            letter-spacing: -.055em;
        }
        .hero-copy {
            max-width: 720px;
            margin: 1.5rem 0 0;
            color: #d2e0d6;
            font-size: clamp(1.02rem, 1.6vw, 1.2rem);
        }
        .hero-actions { display: flex; flex-wrap: wrap; gap: .8rem; margin-top: 2rem; }
        .button {
            min-height: 48px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: .78rem 1.1rem;
            border-radius: 11px;
            text-decoration: none;
            font-weight: 700;
        }
        .button-primary { background: #fff; color: var(--green-950); }
        .button-secondary { border: 1px solid rgba(255,255,255,.32); color: #fff; background: rgba(255,255,255,.05); }
        .button-secondary:hover { background: rgba(255,255,255,.1); }

        .service-card {
            overflow: hidden;
            background: rgba(255,255,255,.07);
            border: 1px solid rgba(255,255,255,.18);
            border-radius: 22px;
            box-shadow: 0 30px 70px rgba(0,0,0,.2);
        }
        .service-card-top {
            display: flex;
            align-items: center;
            gap: .9rem;
            padding: 1.35rem;
            border-bottom: 1px solid rgba(255,255,255,.12);
        }
        .service-card-top img { width: 54px; height: 54px; object-fit: contain; }
        .service-card-title { font-weight: 700; font-size: 1.05rem; }
        .service-card-sub { margin-top: .15rem; color: #b6cabd; font-size: .8rem; }
        .service-list { list-style: none; margin: 0; padding: .45rem 1.35rem 1rem; }
        .service-list li { padding: .9rem 0; border-bottom: 1px solid rgba(255,255,255,.1); }
        .service-list li:last-child { border-bottom: 0; }
        .service-label { display: block; color: #9fc5ab; font-size: .73rem; font-weight: 700; text-transform: uppercase; letter-spacing: .07em; }
        .service-value { display: block; margin-top: .2rem; color: #fff; font-size: .92rem; }

        .quick-strip {
            position: relative;
            z-index: 2;
            margin-top: -46px;
        }
        .quick-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
        }
        .quick-card {
            min-height: 155px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 1.25rem;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: 0 14px 34px rgba(21, 43, 29, .08);
            text-decoration: none;
        }
        .quick-card:hover { border-color: #a9c3b1; transform: translateY(-2px); }
        .quick-label { color: var(--green-700); font-size: .78rem; font-weight: 700; text-transform: uppercase; letter-spacing: .07em; }
        .quick-title { margin-top: .35rem; font-size: 1.15rem; font-weight: 700; }
        .quick-arrow { color: var(--green-800); font-size: 1.2rem; }

        .section { padding: 6rem 0; }
        .section-white { background: #fff; }
        .section-dark { background: #0b3120; color: #fff; }
        .section-header { max-width: 760px; margin-bottom: 2.2rem; }
        .kicker { margin: 0 0 .55rem; color: var(--green-700); font-size: .82rem; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; }
        .section-dark .kicker { color: #abd0b7; }
        .section-title { margin: 0; font-size: clamp(2rem, 4vw, 3.35rem); line-height: 1.08; letter-spacing: -.035em; }
        .section-copy { margin: .9rem 0 0; color: var(--muted); font-size: 1rem; }
        .section-dark .section-copy { color: #c6d8cd; }

        .about-grid {
            display: grid;
            grid-template-columns: 1.1fr .9fr;
            gap: 1rem;
        }
        .about-main, .about-side {
            padding: 1.5rem;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            background: #fff;
        }
        .about-main { min-height: 250px; display: flex; flex-direction: column; justify-content: space-between; }
        .about-main p { margin: 0; max-width: 760px; color: var(--muted); font-size: 1.06rem; }
        .about-points { display: grid; grid-template-columns: repeat(2, 1fr); gap: .75rem; margin-top: 1.4rem; }
        .about-point { padding: .9rem; border-radius: 13px; background: var(--green-50); }
        .about-point strong { display: block; color: var(--green-900); }
        .about-point span { display: block; margin-top: .25rem; color: var(--muted); font-size: .86rem; }
        .about-side { background: var(--green-900); border-color: var(--green-900); color: #fff; }
        .about-side h3 { margin: 0; font-size: 1.25rem; }
        .about-side p { margin: .75rem 0 0; color: #c7d8ce; }
        .about-side a { min-height: 44px; display: inline-flex; align-items: center; margin-top: 1.35rem; color: #fff; font-weight: 700; }

        .requirement-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; }
        .requirement-card {
            overflow: hidden;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: var(--radius);
        }
        .requirement-head { padding: 1.25rem; border-bottom: 1px solid var(--border); }
        .requirement-head h3 { margin: 0; font-size: 1.08rem; }
        .tag { display: inline-flex; margin-top: .55rem; padding: .25rem .5rem; border-radius: 999px; background: var(--green-100); color: var(--green-900); font-size: .72rem; font-weight: 700; }
        .requirement-list { list-style: none; margin: 0; padding: .4rem 1.25rem 1rem; }
        .requirement-list li { position: relative; padding: .75rem 0 .75rem 1.55rem; border-bottom: 1px solid #edf1ee; font-size: .92rem; }
        .requirement-list li:last-child { border-bottom: 0; }
        .requirement-list li::before { content: "✓"; position: absolute; left: 0; top: .76rem; color: var(--green-700); font-weight: 700; }
        .requirements-note { margin: 1rem 0 0; color: var(--muted); font-size: .9rem; }

        .process-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; }
        .process-card { padding: 1.25rem; border: 1px solid var(--border); border-radius: var(--radius); background: #fff; }
        .step-number { width: 38px; height: 38px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; background: var(--green-800); color: #fff; font-weight: 700; }
        .process-card h3 { margin: .9rem 0 0; font-size: 1.03rem; }
        .process-card p { margin: .45rem 0 0; color: var(--muted); font-size: .9rem; }
        .process-note { margin-top: 1rem; padding: 1rem 1.1rem; border-radius: 14px; background: var(--green-50); color: #405147; }

        .role-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; }
        .role-card { padding: 1.35rem; border: 1px solid rgba(255,255,255,.16); border-radius: var(--radius); background: rgba(255,255,255,.05); }
        .role-card h3 { margin: 0; }
        .role-card p { margin: .55rem 0 0; color: #c8d9ce; font-size: .92rem; }
        .no-apply { margin-top: 1rem; padding: 1rem 1.1rem; border: 1px solid rgba(246,201,69,.38); border-radius: 14px; background: rgba(246,201,69,.08); color: #f8ebbd; }

        .faq-grid { display: grid; grid-template-columns: .72fr 1.28fr; gap: 3rem; align-items: start; }
        .faq-list { border-top: 1px solid var(--border); }
        details { border-bottom: 1px solid var(--border); background: #fff; }
        summary { min-height: 60px; display: flex; align-items: center; cursor: pointer; list-style: none; position: relative; padding: 1rem 3rem 1rem 0; font-weight: 700; }
        summary::-webkit-details-marker { display: none; }
        summary::after { content: "+"; position: absolute; right: .4rem; font-size: 1.5rem; color: var(--green-700); font-weight: 400; }
        details[open] summary::after { content: "−"; }
        .faq-answer { max-width: 850px; padding: 0 3rem 1.2rem 0; color: var(--muted); }
        .faq-answer p { margin: 0; }

        .office-grid { display: grid; grid-template-columns: 1.15fr .85fr; gap: 1rem; }
        .office-card { padding: 1.45rem; border: 1px solid var(--border); border-radius: var(--radius); background: #fff; }
        .office-card h3 { margin: 0; }
        .office-card > p { margin: .4rem 0 0; color: var(--muted); }
        .contact-list { list-style: none; margin: 1.15rem 0 0; padding: 0; border-top: 1px solid var(--border); }
        .contact-list li { display: grid; grid-template-columns: 120px 1fr; gap: 1rem; padding: .8rem 0; border-bottom: 1px solid #edf1ee; }
        .contact-label { color: var(--muted); font-size: .82rem; font-weight: 700; }
        .contact-value { overflow-wrap: anywhere; }
        .contact-value a { min-height: 44px; display: inline-flex; align-items: center; color: var(--green-800); font-weight: 700; text-decoration: none; }
        .contact-value a:hover { text-decoration: underline; }
        .office-links { display: grid; gap: .75rem; margin-top: 1.1rem; }
        .office-link { min-height: 54px; display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: .8rem 1rem; border: 1px solid var(--border); border-radius: 12px; text-decoration: none; color: var(--green-900); font-weight: 700; }
        .office-link:hover { background: var(--green-50); border-color: #a8c0b0; }

        footer { background: #061f14; color: #dce9e1; }
        .footer-top { display: grid; grid-template-columns: 1fr auto; align-items: center; gap: 2rem; padding: 2.5rem 0 1.75rem; }
        .footer-brand { display: flex; align-items: center; gap: .8rem; }
        .footer-brand img { width: 48px; height: 48px; object-fit: contain; }
        .footer-title { font-weight: 700; }
        .footer-sub { color: #9eb5a6; font-size: .8rem; }
        .footer-links { display: flex; flex-wrap: wrap; gap: .25rem; }
        .footer-links a { min-height: 44px; display: inline-flex; align-items: center; padding: 0 .6rem; color: #bdd0c3; text-decoration: none; border-radius: 8px; font-size: .84rem; }
        .footer-links a:hover { color: #fff; background: rgba(255,255,255,.06); }
        .footer-bottom { padding: 1rem 0 1.4rem; border-top: 1px solid rgba(255,255,255,.09); color: #8fa99a; font-size: .78rem; }

        @media (max-width: 980px) {
            .header-inner { align-items: flex-start; flex-direction: column; gap: .45rem; padding: .7rem 0; }
            .nav { width: 100%; justify-content: flex-start; overflow-x: auto; padding-bottom: .2rem; scrollbar-width: thin; }
            .nav .sign-in { margin-left: 0; }
            .hero-grid { grid-template-columns: 1fr; gap: 2rem; min-height: auto; padding: 4.5rem 0 5.5rem; }
            .service-card { max-width: 640px; }
            .quick-grid, .requirement-grid, .process-grid, .role-grid { grid-template-columns: 1fr 1fr; }
            .about-grid, .faq-grid, .office-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 640px) {
            html { scroll-padding-top: 126px; }
            .container { width: min(100% - 1.25rem, 1180px); }
            .brand-office { display: none; }
            .nav a { padding: 0 .6rem; font-size: .82rem; }
            .hero-grid { padding-top: 3.6rem; }
            h1 { font-size: clamp(2.65rem, 13vw, 4rem); }
            .hero-actions .button { width: 100%; }
            .quick-grid, .requirement-grid, .process-grid, .role-grid, .about-points { grid-template-columns: 1fr; }
            .section { padding: 4.5rem 0; }
            .contact-list li { grid-template-columns: 1fr; gap: .2rem; }
            .footer-top { grid-template-columns: 1fr; }
        }
        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
            *, *::before, *::after { transition-duration: .01ms !important; animation-duration: .01ms !important; animation-iteration-count: 1 !important; }
        }
    </style>
</head>
<body>
    <a class="skip-link" href="#main-content">Skip to main content</a>

    <header class="site-header">
        <div class="container header-inner">
            <a href="{{ route('home') }}" class="brand" aria-label="DAR-LTCMS home">
                <img src="{{ asset('images/dar-logo.svg') }}" alt="Department of Agrarian Reform logo">
                <span>
                    <span class="brand-name">DAR-LTCMS</span>
                    <span class="brand-office">DAR Negros Oriental Provincial Office</span>
                </span>
            </a>

            <nav class="nav" aria-label="Public information">
                <a href="#requirements">Requirements</a>
                <a href="#process">Process</a>
                <a href="#access">Access</a>
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
                    <div class="hero-badge">DAR Negros Oriental public service information</div>
                    <h1 id="hero-title">Land transfer clearance, without the guesswork.</h1>
                    <p class="hero-copy">
                        Find the documents to prepare, understand the clearance process, and access DAR-LTCMS if you already have an authorized account.
                    </p>
                    <div class="hero-actions">
                        <a href="#requirements" class="button button-primary">View Requirements</a>
                        <a href="{{ route('login') }}" class="button button-secondary">Sign In to DAR-LTCMS</a>
                    </div>
                </div>

                <aside class="service-card" aria-label="DAR-LTCMS service summary">
                    <div class="service-card-top">
                        <img src="{{ asset('images/dar-logo.svg') }}" alt="">
                        <div>
                            <div class="service-card-title">DAR-LTCMS</div>
                            <div class="service-card-sub">Land Transfer Clearance and Monitoring System</div>
                        </div>
                    </div>
                    <ul class="service-list">
                        <li>
                            <span class="service-label">Office</span>
                            <span class="service-value">DAR Negros Oriental Provincial Office</span>
                        </li>
                        <li>
                            <span class="service-label">Applications</span>
                            <span class="service-value">Encoded and processed by authorized DAR staff</span>
                        </li>
                        <li>
                            <span class="service-label">Landowner access</span>
                            <span class="service-value">View linked records, status, decision information, and available output</span>
                        </li>
                    </ul>
                </aside>
            </div>
        </section>

        <section class="quick-strip" aria-label="Quick links">
            <div class="container quick-grid">
                <a class="quick-card" href="#requirements">
                    <div>
                        <span class="quick-label">Prepare</span>
                        <div class="quick-title">What documents should I bring?</div>
                    </div>
                    <span class="quick-arrow" aria-hidden="true">→</span>
                </a>
                <a class="quick-card" href="#process">
                    <div>
                        <span class="quick-label">Understand</span>
                        <div class="quick-title">How does clearance processing work?</div>
                    </div>
                    <span class="quick-arrow" aria-hidden="true">→</span>
                </a>
                <a class="quick-card" href="#office">
                    <div>
                        <span class="quick-label">Contact</span>
                        <div class="quick-title">How do I reach DAR Legal Negros Oriental?</div>
                    </div>
                    <span class="quick-arrow" aria-hidden="true">→</span>
                </a>
            </div>
        </section>

        <section class="section section-white" id="about" aria-labelledby="about-title">
            <div class="container">
                <div class="section-header">
                    <p class="kicker">About the service</p>
                    <h2 class="section-title" id="about-title">One place to understand the clearance process.</h2>
                    <p class="section-copy">DAR-LTCMS supports the administrative processing, record management, document review, status monitoring, clearance generation, reporting, and audit trail needs of the DAR Negros Oriental Provincial Office.</p>
                </div>

                <div class="about-grid">
                    <article class="about-main">
                        <p>For visitors, this page acts as a public information desk. For authorized users, DAR-LTCMS provides role-based access to the records and actions appropriate to their work or linked landowner account.</p>
                        <div class="about-points">
                            <div class="about-point"><strong>Clear requirements</strong><span>See the core documents to prepare before coordinating with DAR.</span></div>
                            <div class="about-point"><strong>Clear process</strong><span>Know what happens from preparation through the clearance decision or output.</span></div>
                            <div class="about-point"><strong>Role-based access</strong><span>Staff, landowners, and geodetic personnel see only the functions allowed for their role.</span></div>
                            <div class="about-point"><strong>Traceable records</strong><span>Authorized actions and final decisions are preserved for accountability and monitoring.</span></div>
                        </div>
                    </article>
                    <aside class="about-side">
                        <h3>Already have an account?</h3>
                        <p>Use the secure sign-in page to access the DAR-LTCMS functions available to your assigned role.</p>
                        <a href="{{ route('login') }}">Go to Sign In →</a>
                    </aside>
                </div>
            </div>
        </section>

        <section class="section" id="requirements" aria-labelledby="requirements-title">
            <div class="container">
                <div class="section-header">
                    <p class="kicker">Clearance requirements</p>
                    <h2 class="section-title" id="requirements-title">Prepare before visiting DAR.</h2>
                    <p class="section-copy">This public preparation guide is curated from the current DAR-LTCMS clearance configuration. DAR personnel remain responsible for determining the official and applicable requirements for each transaction.</p>
                </div>

                <div class="requirement-grid">
                    <article class="requirement-card">
                        <div class="requirement-head">
                            <h3>Transferor requirements</h3>
                            <span class="tag">Core preparation</span>
                        </div>
                        <ul class="requirement-list">
                            <li>Official Receipt for LTC fee payment</li>
                            <li>Electronic Copy of Title</li>
                            <li>Deed or Document to be Registered</li>
                            <li>Affidavit of Transferor</li>
                            <li>Municipal Assessor's Certificate of Aggregate Landholding</li>
                            <li>Provincial Assessor's Certificate of Aggregate Landholding</li>
                        </ul>
                    </article>

                    <article class="requirement-card">
                        <div class="requirement-head">
                            <h3>Transferee requirements</h3>
                            <span class="tag">Core preparation</span>
                        </div>
                        <ul class="requirement-list">
                            <li>Affidavit of Transferee</li>
                            <li>Municipal Assessor's Certificate of Aggregate Landholding</li>
                            <li>Provincial Assessor's Certificate of Aggregate Landholding</li>
                            <li>MARPO Certification (LTC Form No. 2)</li>
                        </ul>
                    </article>

                    <article class="requirement-card">
                        <div class="requirement-head">
                            <h3>Additional documents</h3>
                            <span class="tag">When applicable</span>
                        </div>
                        <ul class="requirement-list">
                            <li>Death Certificate, when applicable</li>
                            <li>City Assessor's Certificate of Aggregate Landholding, depending on jurisdiction</li>
                            <li>Recent Tax Declaration, when available</li>
                            <li>Other documents requested after official DAR review</li>
                        </ul>
                    </article>
                </div>

                <p class="requirements-note">Additional documents may be required depending on the circumstances of the transaction.</p>
            </div>
        </section>

        <section class="section section-white" id="process" aria-labelledby="process-title">
            <div class="container">
                <div class="section-header">
                    <p class="kicker">How processing works</p>
                    <h2 class="section-title" id="process-title">Six steps from preparation to clearance output.</h2>
                    <p class="section-copy">Landowners do not create applications themselves. Authorized DAR staff encode and process applications in DAR-LTCMS.</p>
                </div>

                <div class="process-grid">
                    <article class="process-card"><span class="step-number">1</span><h3>Prepare requirements</h3><p>Gather the core and applicable supporting documents.</p></article>
                    <article class="process-card"><span class="step-number">2</span><h3>Coordinate with DAR</h3><p>Present the transaction information and documents through the appropriate office process.</p></article>
                    <article class="process-card"><span class="step-number">3</span><h3>Staff encodes the application</h3><p>Authorized DAR personnel create and maintain the application record.</p></article>
                    <article class="process-card"><span class="step-number">4</span><h3>Requirements are reviewed</h3><p>Documents, parcel information, landholding records, and applicable references are reviewed.</p></article>
                    <article class="process-card"><span class="step-number">5</span><h3>Progress is monitored</h3><p>DAR-LTCMS records the administrative status through the authorized review workflow.</p></article>
                    <article class="process-card"><span class="step-number">6</span><h3>Decision or output</h3><p>The final clearance result is recorded and the appropriate output may be generated.</p></article>
                </div>

                <div class="process-note">Any legal conveyance, registration, or ownership-record change required after the clearance process remains subject to the separate procedures of the appropriate offices.</div>
            </div>
        </section>

        <section class="section section-dark" id="access" aria-labelledby="access-title">
            <div class="container">
                <div class="section-header">
                    <p class="kicker">Authorized access</p>
                    <h2 class="section-title" id="access-title">Different roles, different access.</h2>
                    <p class="section-copy">Accounts are created and managed by authorized DAR personnel.</p>
                </div>

                <div class="role-grid">
                    <article class="role-card"><h3>DAR Staff</h3><p>Encode and process applications, manage authorized records and documents, record workflow actions, and prepare monitoring outputs and reports.</p></article>
                    <article class="role-card"><h3>Landowners</h3><p>View only their own linked parcel records, application status, final decision information, and available clearance output.</p></article>
                    <article class="role-card"><h3>Geodetic Personnel</h3><p>Use limited read-only access for parcel, reference, and map-based review.</p></article>
                </div>
                <div class="no-apply"><strong>No online self-application.</strong> Clearance applications are encoded by authorized DAR staff.</div>
            </div>
        </section>

        <section class="section section-white" id="faq" aria-labelledby="faq-title">
            <div class="container faq-grid">
                <div class="section-header">
                    <p class="kicker">Frequently asked questions</p>
                    <h2 class="section-title" id="faq-title">Quick answers before you proceed.</h2>
                </div>

                <div class="faq-list">
                    <details>
                        <summary>Can I submit a land transfer clearance application online?</summary>
                        <div class="faq-answer"><p>No. Authorized DAR staff encode and process clearance applications. The public landing page is for information and preparation guidance.</p></div>
                    </details>
                    <details>
                        <summary>Can I see my application status?</summary>
                        <div class="faq-answer"><p>Yes, when your landowner account is properly linked to your record. Landowner accounts cannot view records belonging to other landowners.</p></div>
                    </details>
                    <details>
                        <summary>Does an approved or released clearance mean ownership has already transferred?</summary>
                        <div class="faq-answer"><p>No. The clearance decision or generated output does not itself change land ownership or registry ownership records.</p></div>
                    </details>
                    <details>
                        <summary>What should I bring to DAR?</summary>
                        <div class="faq-answer"><p>Start with the transferor and transferee requirements listed on this page, plus any additional documents that apply to your transaction. DAR personnel determine the official applicable requirements.</p></div>
                    </details>
                    <details>
                        <summary>Can the system decide whether my transaction is legally valid?</summary>
                        <div class="faq-answer"><p>No. DAR-LTCMS supports administrative processing, records management, monitoring, validation assistance, and clearance generation. Official legal and administrative determinations remain with authorized personnel and the applicable government procedures.</p></div>
                    </details>
                </div>
            </div>
        </section>

        <section class="section" id="office" aria-labelledby="office-title">
            <div class="container">
                <div class="section-header">
                    <p class="kicker">Office information</p>
                    <h2 class="section-title" id="office-title">DAR Negros Oriental Legal Assistance Division</h2>
                </div>

                <div class="office-grid">
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
                        <p>Use these public channels for DAR information and office updates.</p>
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
        <div class="container footer-top">
            <div class="footer-brand">
                <img src="{{ asset('images/dar-logo.svg') }}" alt="Department of Agrarian Reform logo">
                <div>
                    <div class="footer-title">Department of Agrarian Reform</div>
                    <div class="footer-sub">Negros Oriental Provincial Office · DAR-LTCMS</div>
                </div>
            </div>
            <nav class="footer-links" aria-label="Footer navigation">
                <a href="#requirements">Requirements</a>
                <a href="#process">Process</a>
                <a href="#faq">FAQ</a>
                <a href="#office">Office</a>
                <a href="{{ route('login') }}">Sign In</a>
            </nav>
        </div>
        <div class="container footer-bottom">© {{ now()->year }} Department of Agrarian Reform. DAR-LTCMS supports administrative land transfer clearance processing and monitoring for the DAR Negros Oriental Provincial Office.</div>
    </footer>
</body>
</html>
