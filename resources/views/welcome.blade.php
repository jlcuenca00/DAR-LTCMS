<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="DAR-LTCMS public information for land transfer clearance requirements, processing, monitoring, and DAR Negros Oriental contact channels.">
    <title>DAR-LTCMS | DAR Negros Oriental</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:opsz,wght@17..18,400..700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --green-950: #082d1b;
            --green-900: #0b3b23;
            --green-800: #0d502c;
            --green-700: #166b3a;
            --green-100: #dff1e5;
            --green-50: #f2f8f4;
            --ink: #18231d;
            --muted: #5f6d64;
            --border: #d7e1da;
            --page: #f7f9f7;
            --focus: #f5c842;
        }

        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; scroll-padding-top: 86px; }
        body { margin: 0; font-family: 'Google Sans', Arial, sans-serif; color: var(--ink); background: var(--page); line-height: 1.55; }
        a { color: inherit; }
        img { max-width: 100%; }
        [hidden] { display: none !important; }
        .container { width: min(1160px, calc(100% - 2rem)); margin: 0 auto; }

        .skip-link { position: fixed; left: 1rem; top: 1rem; z-index: 100; transform: translateY(-170%); padding: .7rem 1rem; background: #fff; color: var(--green-950); border: 2px solid var(--focus); border-radius: 8px; font-weight: 700; text-decoration: none; }
        .skip-link:focus { transform: translateY(0); }
        :where(a, summary, button):focus-visible { outline: 3px solid var(--focus); outline-offset: 3px; border-radius: 6px; }

        .sticky-shell { position: sticky; top: 0; z-index: 50; }
        .development-notice { background: #fff6cf; color: #4a3b00; border-bottom: 1px solid #eadb8e; }
        .development-notice-inner { min-height: 44px; display: grid; grid-template-columns: 1fr auto; align-items: center; gap: 1rem; }
        .development-message { display: flex; align-items: center; gap: .65rem; min-width: 0; }
        .development-icon { width: 19px; height: 19px; flex: 0 0 auto; color: #9a6a00; }
        .development-notice p { margin: 0; font-size: .88rem; font-weight: 600; }
        .development-dismiss { width: 44px; height: 44px; display: inline-flex; align-items: center; justify-content: center; padding: 0; border: 0; border-radius: 8px; background: transparent; color: #4a3b00; cursor: pointer; font-size: 1.35rem; line-height: 1; }
        .development-dismiss:hover { background: rgba(154,106,0,.09); }

        .site-header { position: relative; background: rgba(8,45,27,.97); border-bottom: 1px solid rgba(255,255,255,.12); color: #fff; }
        .header-inner { min-height: 72px; display: flex; align-items: center; justify-content: space-between; gap: 1.25rem; }
        .brand { min-height: 48px; display: inline-flex; align-items: center; gap: .75rem; text-decoration: none; }
        .brand img { width: 44px; height: 44px; object-fit: contain; }
        .brand-name { display: block; font-weight: 700; line-height: 1.1; }
        .brand-office { display: block; margin-top: .15rem; color: #c7d8cd; font-size: .72rem; }
        .nav { display: flex; align-items: center; gap: .18rem; }
        .nav a { min-height: 44px; display: inline-flex; align-items: center; padding: 0 .68rem; border-radius: 8px; color: #dce9e0; text-decoration: none; font-size: .86rem; font-weight: 600; }
        .nav a:hover { background: rgba(255,255,255,.08); color: #fff; }
        .nav .sign-in { margin-left: .4rem; padding-inline: .95rem; background: #fff; color: #102117; }
        .nav .sign-in:hover { background: #eef5f0; color: #102117; }

        .hero { position: relative; overflow: hidden; color: #fff; background: var(--green-950); }
        .hero::before { content: ''; position: absolute; inset: 0; opacity: .11; pointer-events: none; background-image: linear-gradient(rgba(255,255,255,.18) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.18) 1px, transparent 1px); background-size: 64px 64px; mask-image: linear-gradient(to bottom right, black, transparent 78%); }
        .hero-grid { position: relative; min-height: 610px; display: grid; grid-template-columns: 1.35fr .65fr; align-items: center; gap: 4.5rem; padding: 5rem 0; }
        .eyebrow { margin: 0 0 .9rem; color: #b8d9c3; font-size: .8rem; font-weight: 700; letter-spacing: .09em; text-transform: uppercase; }
        h1 { margin: 0; max-width: 820px; font-size: clamp(3.1rem, 6vw, 5.25rem); line-height: .98; letter-spacing: -.05em; }
        .hero-copy { max-width: 650px; margin: 1.35rem 0 0; color: #d4e3d9; font-size: 1.08rem; }
        .hero-actions { display: flex; flex-wrap: wrap; gap: .75rem; margin-top: 1.8rem; }
        .button { min-height: 48px; display: inline-flex; align-items: center; justify-content: center; padding: .75rem 1rem; border-radius: 8px; text-decoration: none; font-weight: 700; transition: transform .15s ease, background .15s ease; }
        .button:hover { transform: translateY(-2px); }
        .button-light { background: #fff; color: #153020; }
        .button-outline { border: 1px solid rgba(255,255,255,.42); color: #fff; background: rgba(255,255,255,.04); }
        .button-outline:hover { background: rgba(255,255,255,.09); }

        .hero-panel { overflow: hidden; border: 1px solid rgba(255,255,255,.17); border-radius: 12px; background: rgba(255,255,255,.055); box-shadow: 0 28px 70px rgba(0,0,0,.18); }
        .hero-panel-head { display: flex; align-items: center; gap: .8rem; padding: 1.15rem 1.25rem; border-bottom: 1px solid rgba(255,255,255,.12); }
        .hero-panel-head img { width: 50px; height: 50px; object-fit: contain; }
        .panel-title { font-weight: 700; }
        .panel-sub { margin-top: .12rem; color: #b9cec0; font-size: .77rem; }
        .panel-body { padding: .4rem 1.25rem 1rem; }
        .panel-row { padding: .9rem 0; border-bottom: 1px solid rgba(255,255,255,.1); }
        .panel-row:last-child { border-bottom: 0; }
        .panel-label { color: #9fc5ab; font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .07em; }
        .panel-value { margin-top: .18rem; color: #fff; font-size: .9rem; }

        .section { padding: 5.5rem 0; }
        .section-white { background: #fff; }
        .section-dark { background: #0d3421; color: #fff; }
        .section-grid { display: grid; grid-template-columns: 280px minmax(0, 1fr); gap: 4.5rem; }
        .section-kicker { margin: 0 0 .5rem; color: var(--green-700); font-size: .8rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
        .section-dark .section-kicker { color: #a9d2b6; }
        .section-title { margin: 0; font-size: clamp(2rem, 4vw, 3.15rem); line-height: 1.08; letter-spacing: -.035em; }
        .section-intro { margin: .75rem 0 0; color: var(--muted); max-width: 700px; }
        .section-dark .section-intro { color: #c8d8ce; }
        .side-note { position: sticky; top: 105px; align-self: start; }

        .feature-line { display: grid; grid-template-columns: repeat(4, 1fr); border: 1px solid var(--border); border-radius: 12px; overflow: hidden; background: var(--border); gap: 1px; }
        .feature { min-height: 145px; padding: 1.25rem; background: #fff; }
        .feature-number { color: var(--green-700); font-size: .76rem; font-weight: 700; }
        .feature h3 { margin: .65rem 0 0; font-size: 1rem; }
        .feature p { margin: .35rem 0 0; color: var(--muted); font-size: .84rem; }

        .requirement-groups { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .requirement-card { overflow: hidden; border: 1px solid var(--border); border-radius: 12px; background: #fff; }
        .requirement-head { padding: 1.15rem 1.3rem; border-bottom: 1px solid var(--border); }
        .requirement-head h3 { margin: 0; font-size: 1.07rem; }
        .requirement-list { list-style: none; margin: 0; padding: .35rem 1.3rem .9rem; }
        .requirement-list li { position: relative; padding: .68rem 0 .68rem 1.45rem; border-bottom: 1px solid #edf1ee; font-size: .9rem; }
        .requirement-list li:last-child { border-bottom: 0; }
        .requirement-list li::before { content: '✓'; position: absolute; left: 0; color: var(--green-700); font-weight: 700; }
        .additional { grid-column: 1 / -1; display: grid; grid-template-columns: 210px 1fr; gap: 1.25rem; align-items: start; padding: 1.15rem 1.3rem; border: 1px solid #bfd4c6; border-radius: 12px; background: var(--green-50); }
        .additional strong { color: var(--green-900); }
        .additional span { color: #46564d; font-size: .88rem; }
        .requirement-note { margin: .8rem 0 0; color: var(--muted); font-size: .85rem; }

        .timeline { position: relative; }
        .timeline::before { content: ''; position: absolute; left: 20px; top: 22px; bottom: 22px; width: 2px; background: #cfe0d4; }
        .timeline-step { position: relative; display: grid; grid-template-columns: 42px 1fr; gap: 1rem; padding: .35rem 0 1.3rem; }
        .timeline-step:last-child { padding-bottom: 0; }
        .timeline-number { position: relative; z-index: 1; width: 42px; height: 42px; display: flex; align-items: center; justify-content: center; border-radius: 50%; background: var(--green-800); color: #fff; border: 5px solid #fff; box-shadow: 0 0 0 1px #bcd3c3; font-weight: 700; }
        .timeline-copy { padding-top: .35rem; }
        .timeline-copy h3 { margin: 0; font-size: 1rem; }
        .timeline-copy p { margin: .18rem 0 0; color: var(--muted); font-size: .87rem; }

        .role-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-top: 1.8rem; }
        .role { padding: 1.25rem 0 0; border-top: 2px solid rgba(255,255,255,.22); }
        .role h3 { margin: 0; font-size: 1.03rem; }
        .role p { margin: .45rem 0 0; color: #c8d8ce; font-size: .87rem; }
        .no-apply { margin: 1.5rem 0 0; color: #f6e8ad; font-size: .88rem; }

        .faq-list { border-top: 1px solid var(--border); }
        details { border-bottom: 1px solid var(--border); background: #fff; }
        summary { min-height: 60px; display: flex; align-items: center; position: relative; padding: 1rem 3rem 1rem 0; cursor: pointer; list-style: none; font-weight: 700; }
        summary::-webkit-details-marker { display: none; }
        summary::after { content: '+'; position: absolute; right: .35rem; color: var(--green-700); font-size: 1.45rem; font-weight: 400; }
        details[open] summary::after { content: '−'; }
        .faq-answer { max-width: 820px; padding: 0 3rem 1.15rem 0; color: var(--muted); font-size: .92rem; }
        .faq-answer p { margin: 0; }

        .office-grid { display: grid; grid-template-columns: 1.1fr .9fr; gap: 1rem; }
        .office-card { padding: 1.35rem; border: 1px solid var(--border); border-radius: 12px; background: #fff; }
        .office-card h3 { margin: 0; font-size: 1.05rem; }
        .contact-list { list-style: none; margin: 1rem 0 0; padding: 0; border-top: 1px solid var(--border); }
        .contact-list li { display: grid; grid-template-columns: 115px 1fr; gap: 1rem; padding: .72rem 0; border-bottom: 1px solid #edf1ee; }
        .contact-label { color: var(--muted); font-size: .8rem; font-weight: 700; }
        .contact-value { overflow-wrap: anywhere; }
        .contact-value a { min-height: 44px; display: inline-flex; align-items: center; color: var(--green-800); font-weight: 700; text-decoration: none; }
        .contact-value a:hover { text-decoration: underline; }
        .office-links { display: grid; gap: .7rem; margin-top: 1rem; }
        .office-link { min-height: 54px; display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: .8rem 1rem; border: 1px solid var(--border); border-radius: 10px; color: var(--green-900); font-weight: 700; text-decoration: none; }
        .office-link:hover { background: var(--green-50); border-color: #a8c0b0; }

        footer { background: #071f14; color: #dce9e1; }
        .footer-inner { min-height: 135px; display: flex; align-items: center; justify-content: space-between; gap: 2rem; padding: 2rem 0; }
        .footer-brand { display: flex; align-items: center; gap: .8rem; }
        .footer-brand img { width: 50px; height: 50px; object-fit: contain; }
        .footer-title { font-weight: 700; }
        .footer-sub { color: #9db7a7; font-size: .79rem; }
        .footer-links { display: flex; flex-wrap: wrap; gap: .2rem; }
        .footer-links a { min-height: 44px; display: inline-flex; align-items: center; padding: 0 .55rem; border-radius: 7px; color: #bcd0c3; text-decoration: none; font-size: .82rem; }
        .footer-links a:hover { color: #fff; background: rgba(255,255,255,.06); }

        @media (max-width: 920px) {
            .header-inner { align-items: flex-start; flex-direction: column; gap: .35rem; padding: .65rem 0; }
            .nav { width: 100%; justify-content: flex-start; overflow-x: auto; padding-bottom: .15rem; }
            .nav .sign-in { margin-left: 0; }
            .hero-grid { grid-template-columns: 1fr; gap: 2rem; min-height: auto; }
            .hero-panel { max-width: 600px; }
            .section-grid { grid-template-columns: 1fr; gap: 2rem; }
            .side-note { position: static; }
            .feature-line { grid-template-columns: 1fr 1fr; }
            .office-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 620px) {
            html { scroll-padding-top: 130px; }
            .container { width: min(100% - 1.25rem, 1160px); }
            .brand-office { display: none; }
            .nav a { padding: 0 .55rem; font-size: .8rem; }
            .hero-grid { padding: 4rem 0; }
            h1 { font-size: clamp(2.75rem, 13vw, 4rem); }
            .hero-actions .button { width: 100%; }
            .section { padding: 4.25rem 0; }
            .feature-line, .requirement-groups, .role-grid { grid-template-columns: 1fr; }
            .additional { grid-column: auto; grid-template-columns: 1fr; }
            .contact-list li { grid-template-columns: 1fr; gap: .15rem; }
            .footer-inner { align-items: flex-start; flex-direction: column; }
            .development-notice p { font-size: .82rem; }
        }

        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
            *, *::before, *::after { transition-duration: .01ms !important; animation-duration: .01ms !important; animation-iteration-count: 1 !important; }
        }
    </style>
</head>
<body>
    <a class="skip-link" href="#main-content">Skip to main content</a>

    <div class="sticky-shell">
        <div class="development-notice" id="development-notice" role="status">
            <div class="container development-notice-inner">
                <div class="development-message">
                    <svg class="development-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"></circle>
                        <path d="M12 7.5V13" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path>
                        <circle cx="12" cy="16.5" r="1" fill="currentColor"></circle>
                    </svg>
                    <p>This site is still undergoing development. Please bear with us.</p>
                </div>
                <button type="button" class="development-dismiss" id="dismiss-development-notice" aria-label="Dismiss development notice">&times;</button>
            </div>
        </div>

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
                    <a href="#about">About</a>
                    <a href="#requirements">Requirements</a>
                    <a href="#process">Process</a>
                    <a href="#faq">FAQ</a>
                    <a href="#office">Office</a>
                    <a href="{{ route('login') }}" class="sign-in">Sign In</a>
                </nav>
            </div>
        </header>
    </div>

    <main id="main-content">
        <section class="hero" aria-labelledby="hero-title">
            <div class="container hero-grid">
                <div>
                    <p class="eyebrow">Department of Agrarian Reform · Negros Oriental</p>
                    <h1 id="hero-title">Land Transfer Clearance and Monitoring System</h1>
                    <p class="hero-copy">Requirements, application monitoring, and authorized DAR access in one place.</p>
                    <div class="hero-actions">
                        <a href="{{ route('login') }}" class="button button-light">Sign In</a>
                        <a href="#requirements" class="button button-outline">View Requirements</a>
                    </div>
                </div>

                <aside class="hero-panel" aria-label="Service overview">
                    <div class="hero-panel-head">
                        <img src="{{ asset('images/dar-logo.svg') }}" alt="">
                        <div>
                            <div class="panel-title">DAR-LTCMS</div>
                            <div class="panel-sub">Public service information</div>
                        </div>
                    </div>
                    <div class="panel-body">
                        <div class="panel-row"><div class="panel-label">Service</div><div class="panel-value">Land Transfer Clearance</div></div>
                        <div class="panel-row"><div class="panel-label">Office</div><div class="panel-value">DAR Negros Oriental</div></div>
                        <div class="panel-row"><div class="panel-label">Application</div><div class="panel-value">Encoded by authorized DAR staff</div></div>
                        <div class="panel-row"><div class="panel-label">Access</div><div class="panel-value">Staff · Landowner · Geodetic</div></div>
                    </div>
                </aside>
            </div>
        </section>

        <section class="section section-white" id="about" aria-labelledby="about-title">
            <div class="container section-grid">
                <div class="side-note">
                    <p class="section-kicker">About</p>
                    <h2 class="section-title" id="about-title">Built for clearance processing.</h2>
                </div>
                <div class="feature-line">
                    <article class="feature"><span class="feature-number">01</span><h3>Applications</h3><p>Encode and process clearance records.</p></article>
                    <article class="feature"><span class="feature-number">02</span><h3>Records</h3><p>Manage linked landowner and parcel information.</p></article>
                    <article class="feature"><span class="feature-number">03</span><h3>Monitoring</h3><p>Track application progress and decisions.</p></article>
                    <article class="feature"><span class="feature-number">04</span><h3>Reports</h3><p>Support monitoring and audit review.</p></article>
                </div>
            </div>
        </section>

        <section class="section" id="requirements" aria-labelledby="requirements-title">
            <div class="container section-grid">
                <div class="side-note">
                    <p class="section-kicker">Requirements</p>
                    <h2 class="section-title" id="requirements-title">Prepare before visiting DAR.</h2>
                </div>
                <div>
                    <div class="requirement-groups">
                        <article class="requirement-card">
                            <div class="requirement-head"><h3>Transferor requirements</h3></div>
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
                            <div class="requirement-head"><h3>Transferee requirements</h3></div>
                            <ul class="requirement-list">
                                <li>Affidavit of Transferee</li>
                                <li>Municipal Assessor's Certificate of Aggregate Landholding</li>
                                <li>Provincial Assessor's Certificate of Aggregate Landholding</li>
                                <li>MARPO Certification (LTC Form No. 2)</li>
                            </ul>
                        </article>
                        <div class="additional">
                            <strong>Additional / case-dependent</strong>
                            <span>Death Certificate · City Assessor's Certificate · Recent Tax Declaration · Other documents requested after DAR review</span>
                        </div>
                    </div>
                    <p class="requirement-note">DAR personnel determine the official requirements applicable to each transaction.</p>
                </div>
            </div>
        </section>

        <section class="section section-white" id="process" aria-labelledby="process-title">
            <div class="container section-grid">
                <div class="side-note">
                    <p class="section-kicker">Process</p>
                    <h2 class="section-title" id="process-title">How it works.</h2>
                </div>
                <div class="timeline">
                    <div class="timeline-step"><div class="timeline-number">1</div><div class="timeline-copy"><h3>Prepare requirements</h3><p>Gather the applicable documents.</p></div></div>
                    <div class="timeline-step"><div class="timeline-number">2</div><div class="timeline-copy"><h3>Coordinate with DAR</h3><p>Present the transaction and requirements.</p></div></div>
                    <div class="timeline-step"><div class="timeline-number">3</div><div class="timeline-copy"><h3>Staff encoding</h3><p>DAR staff create the application record.</p></div></div>
                    <div class="timeline-step"><div class="timeline-number">4</div><div class="timeline-copy"><h3>Review</h3><p>Requirements and records are checked.</p></div></div>
                    <div class="timeline-step"><div class="timeline-number">5</div><div class="timeline-copy"><h3>Monitoring</h3><p>Application progress is recorded.</p></div></div>
                    <div class="timeline-step"><div class="timeline-number">6</div><div class="timeline-copy"><h3>Decision / output</h3><p>The final clearance result is recorded.</p></div></div>
                </div>
            </div>
        </section>

        <section class="section section-dark" id="access" aria-labelledby="access-title">
            <div class="container">
                <p class="section-kicker">Authorized access</p>
                <h2 class="section-title" id="access-title">Who can sign in?</h2>
                <div class="role-grid">
                    <article class="role"><h3>DAR Staff</h3><p>Process applications and manage authorized records.</p></article>
                    <article class="role"><h3>Landowners</h3><p>View their own linked records and application status.</p></article>
                    <article class="role"><h3>Geodetic Personnel</h3><p>Limited read-only parcel and map review.</p></article>
                </div>
                <p class="no-apply"><strong>No online self-application.</strong> Applications are encoded by authorized DAR staff.</p>
            </div>
        </section>

        <section class="section section-white" id="faq" aria-labelledby="faq-title">
            <div class="container section-grid">
                <div class="side-note">
                    <p class="section-kicker">Frequently asked questions</p>
                    <h2 class="section-title" id="faq-title">Quick answers.</h2>
                </div>
                <div class="faq-list">
                    <details><summary>Can I submit an application online?</summary><div class="faq-answer"><p>No. Authorized DAR staff encode clearance applications.</p></div></details>
                    <details><summary>Can I see my application status?</summary><div class="faq-answer"><p>Yes, when your landowner account is linked to your record.</p></div></details>
                    <details><summary>Does an approved clearance mean ownership already transferred?</summary><div class="faq-answer"><p>No. The clearance result does not itself change land ownership or registry records.</p></div></details>
                    <details><summary>What should I bring to DAR?</summary><div class="faq-answer"><p>Start with the requirements above. DAR personnel determine what applies to your transaction.</p></div></details>
                    <details><summary>Can DAR-LTCMS make the final legal determination?</summary><div class="faq-answer"><p>No. Official determinations remain with authorized personnel and the applicable government procedures.</p></div></details>
                </div>
            </div>
        </section>

        <section class="section" id="office" aria-labelledby="office-title">
            <div class="container section-grid">
                <div class="side-note">
                    <p class="section-kicker">Office</p>
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
                        <div class="office-links">
                            <a class="office-link" href="https://www.facebook.com/DARLegalNegor" target="_blank" rel="noopener noreferrer"><span>DAR Legal Negros Oriental</span><span aria-hidden="true">↗</span></a>
                            <a class="office-link" href="https://www.dar.gov.ph/home" target="_blank" rel="noopener noreferrer"><span>Official DAR Website</span><span aria-hidden="true">↗</span></a>
                        </div>
                    </article>
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
    </footer>

    <script>
        (() => {
            const notice = document.getElementById('development-notice');
            const dismissButton = document.getElementById('dismiss-development-notice');
            const storageKey = 'darltcms-development-notice-dismissed';

            try {
                if (sessionStorage.getItem(storageKey) === '1') {
                    notice.hidden = true;
                }
            } catch (error) {
                // The notice remains visible when session storage is unavailable.
            }

            dismissButton?.addEventListener('click', () => {
                notice.hidden = true;

                try {
                    sessionStorage.setItem(storageKey, '1');
                } catch (error) {
                    // Dismissal still works for the current page when storage is unavailable.
                }
            });
        })();
    </script>
</body>
</html>
