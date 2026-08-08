<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="DAR-LTCMS is the Land Transfer Clearance and Monitoring System of the Department of Agrarian Reform Negros Oriental Provincial Office.">
    <title>DAR-LTCMS | Land Transfer Clearance and Monitoring System</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:opsz,wght@17..18,400..700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --dar-green: #166b3a;
            --dar-green-dark: #0d4f2b;
            --dar-green-soft: #edf7f0;
            --text: #17201b;
            --muted: #5c6961;
            --border: #dbe4de;
            --page: #f7faf8;
        }

        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            margin: 0;
            font-family: 'Google Sans', Arial, sans-serif;
            color: var(--text);
            background: var(--page);
            line-height: 1.6;
        }
        a { color: inherit; }
        .container { width: min(1120px, calc(100% - 2rem)); margin: 0 auto; }

        .site-header {
            background: #fff;
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 20;
        }
        .header-inner {
            min-height: 76px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }
        .brand {
            display: flex;
            align-items: center;
            gap: .85rem;
            text-decoration: none;
        }
        .brand img { width: 54px; height: 54px; object-fit: contain; }
        .brand-name { font-size: 1.05rem; font-weight: 700; color: var(--dar-green-dark); }
        .brand-office { display: block; font-size: .77rem; font-weight: 400; color: var(--muted); }
        .header-login {
            text-decoration: none;
            background: var(--dar-green);
            color: #fff;
            border-radius: .45rem;
            padding: .7rem 1rem;
            font-weight: 700;
            white-space: nowrap;
        }
        .header-login:hover { background: var(--dar-green-dark); }

        .hero {
            background: #fff;
            border-bottom: 1px solid var(--border);
        }
        .hero-grid {
            min-height: 510px;
            display: grid;
            grid-template-columns: 1.35fr .65fr;
            align-items: center;
            gap: 4rem;
            padding: 4.5rem 0;
        }
        .eyebrow {
            margin: 0 0 .8rem;
            color: var(--dar-green);
            font-size: .86rem;
            font-weight: 700;
            letter-spacing: .08em;
            text-transform: uppercase;
        }
        h1 {
            margin: 0;
            max-width: 780px;
            font-size: clamp(2.35rem, 5vw, 4.2rem);
            line-height: 1.04;
            letter-spacing: -.035em;
            color: #132219;
        }
        .hero-copy {
            max-width: 720px;
            margin: 1.35rem 0 0;
            color: var(--muted);
            font-size: 1.08rem;
        }
        .hero-actions { display: flex; gap: .8rem; flex-wrap: wrap; margin-top: 1.8rem; }
        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 46px;
            padding: .72rem 1rem;
            border-radius: .45rem;
            text-decoration: none;
            font-weight: 700;
        }
        .button-primary { background: var(--dar-green); color: #fff; }
        .button-primary:hover { background: var(--dar-green-dark); }
        .button-secondary { border: 1px solid #b9c9bf; background: #fff; color: var(--dar-green-dark); }
        .hero-mark {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
        }
        .hero-mark img { width: min(230px, 100%); max-height: 230px; object-fit: contain; }

        .section { padding: 4.5rem 0; }
        .section-title { margin: 0; font-size: 1.9rem; line-height: 1.2; }
        .section-intro { margin: .65rem 0 0; color: var(--muted); max-width: 720px; }
        .cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-top: 2rem;
        }
        .card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: .65rem;
            padding: 1.35rem;
        }
        .card-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background: var(--dar-green-soft);
            color: var(--dar-green-dark);
            font-weight: 700;
            margin-bottom: 1rem;
        }
        .card h3 { margin: 0; font-size: 1.02rem; }
        .card p { margin: .55rem 0 0; color: var(--muted); font-size: .92rem; }

        .notice-wrap { padding: 0 0 4.5rem; }
        .notice {
            background: #fff;
            border: 1px solid var(--border);
            border-left: 5px solid var(--dar-green);
            border-radius: .55rem;
            padding: 1.25rem 1.35rem;
        }
        .notice strong { color: var(--dar-green-dark); }
        .notice p { margin: .35rem 0 0; color: var(--muted); }

        footer { background: #123d28; color: #eaf3ed; }
        .footer-inner {
            min-height: 150px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.5rem;
            padding: 2rem 0;
        }
        .footer-brand { display: flex; align-items: center; gap: .85rem; }
        .footer-brand img { width: 52px; height: 52px; object-fit: contain; }
        .footer-title { font-weight: 700; }
        .footer-sub { font-size: .82rem; color: #bdd0c4; }
        .footer-copy { text-align: right; font-size: .82rem; color: #bdd0c4; }

        @media (max-width: 900px) {
            .hero-grid { grid-template-columns: 1fr; gap: 1rem; min-height: auto; }
            .hero-mark { order: -1; justify-content: flex-start; padding: 0; }
            .hero-mark img { width: 120px; height: 120px; }
            .cards { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 620px) {
            .header-inner { min-height: 68px; }
            .brand-office { display: none; }
            .brand img { width: 44px; height: 44px; }
            .header-login { padding: .62rem .8rem; font-size: .88rem; }
            .hero-grid { padding: 3.2rem 0; }
            .cards { grid-template-columns: 1fr; }
            .footer-inner { align-items: flex-start; flex-direction: column; }
            .footer-copy { text-align: left; }
        }
    </style>
</head>
<body>
    <header class="site-header">
        <div class="container header-inner">
            <a href="{{ route('home') }}" class="brand" aria-label="DAR-LTCMS home">
                <img src="{{ asset('images/dar-logo.svg') }}" alt="Department of Agrarian Reform logo">
                <span>
                    <span class="brand-name">DAR-LTCMS</span>
                    <span class="brand-office">DAR Negros Oriental Provincial Office</span>
                </span>
            </a>
            <a href="{{ route('login') }}" class="header-login">Authorized User Login</a>
        </div>
    </header>

    <main>
        <section class="hero">
            <div class="container hero-grid">
                <div>
                    <p class="eyebrow">Department of Agrarian Reform · Negros Oriental</p>
                    <h1>Land Transfer Clearance and Monitoring System</h1>
                    <p class="hero-copy">
                        DAR-LTCMS supports the administrative processing, record management, clearance generation, and monitoring of land transfer clearance applications handled by the DAR Negros Oriental Provincial Office.
                    </p>
                    <div class="hero-actions">
                        <a href="{{ route('login') }}" class="button button-primary">Proceed to Login</a>
                        <a href="#system-support" class="button button-secondary">View System Functions</a>
                    </div>
                </div>
                <div class="hero-mark" aria-hidden="true">
                    <img src="{{ asset('images/dar-logo.svg') }}" alt="">
                </div>
            </div>
        </section>

        <section class="section" id="system-support">
            <div class="container">
                <h2 class="section-title">What the system supports</h2>
                <p class="section-intro">
                    The platform provides authorized users with a centralized web-based workspace for clearance processing and related records and monitoring activities.
                </p>

                <div class="cards">
                    <article class="card">
                        <span class="card-number">1</span>
                        <h3>Clearance Application Processing</h3>
                        <p>Encode, review, process, and record land transfer clearance applications through the approved administrative workflow.</p>
                    </article>
                    <article class="card">
                        <span class="card-number">2</span>
                        <h3>Parcel &amp; Landholding Records</h3>
                        <p>Maintain authorized landowner, parcel, landholding, and reference records used during clearance processing.</p>
                    </article>
                    <article class="card">
                        <span class="card-number">3</span>
                        <h3>Application Status Monitoring</h3>
                        <p>Track application progress, final decisions, clearance outputs, and status information appropriate to each user role.</p>
                    </article>
                    <article class="card">
                        <span class="card-number">4</span>
                        <h3>Monitoring &amp; Reports</h3>
                        <p>Support administrative monitoring, reporting, traceability, and audit review for authorized DAR personnel.</p>
                    </article>
                </div>
            </div>
        </section>

        <section class="notice-wrap">
            <div class="container">
                <div class="notice">
                    <strong>Authorized access only.</strong>
                    <p>
                        Accounts are created and managed by authorized DAR personnel. The system assists clearance processing and monitoring; any actual land ownership transfer or registry alteration remains subject to separate legal and administrative procedures.
                    </p>
                </div>
            </div>
        </section>
    </main>

    <footer>
        <div class="container footer-inner">
            <div class="footer-brand">
                <img src="{{ asset('images/dar-logo.svg') }}" alt="Department of Agrarian Reform logo">
                <div>
                    <div class="footer-title">Department of Agrarian Reform</div>
                    <div class="footer-sub">Negros Oriental Provincial Office</div>
                </div>
            </div>
            <div class="footer-copy">
                DAR-LTCMS<br>
                © {{ now()->year }} Department of Agrarian Reform
            </div>
        </div>
    </footer>
</body>
</html>
