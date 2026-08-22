<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="DAR-LTCMS is the DAR Negros Oriental Provincial Office web-based system for land transfer clearance processing, monitoring, records management, and clearance result generation.">
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
            --amber-50: #fff9e8;
            --amber-border: #eadb8e;
        }

        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; scroll-padding-top: 86px; }
        body { margin: 0; font-family: 'Google Sans', Arial, sans-serif; color: var(--ink); background: var(--page); line-height: 1.55; }
        a { color: inherit; }
        img { max-width: 100%; }
        .container { width: min(1160px, calc(100% - 2rem)); margin: 0 auto; }

        .skip-link { position: fixed; left: 1rem; top: 1rem; z-index: 100; transform: translateY(-170%); padding: .7rem 1rem; background: #fff; color: var(--green-950); border: 2px solid var(--focus); border-radius: 8px; font-weight: 700; text-decoration: none; }
        .skip-link:focus { transform: translateY(0); }
        :where(a, summary, button):focus-visible { outline: 3px solid var(--focus); outline-offset: 3px; border-radius: 6px; }

        .sticky-shell { position: sticky; top: 0; z-index: 50; }
        .site-header { background: rgba(8,45,27,.98); border-bottom: 1px solid rgba(255,255,255,.12); color: #fff; }
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
        .hero-grid { position: relative; min-height: 600px; display: grid; grid-template-columns: minmax(0, 1.35fr) minmax(320px, .65fr); align-items: center; gap: 4.5rem; padding: 5rem 0; }
        .eyebrow { margin: 0 0 .9rem; color: #b8d9c3; font-size: .8rem; font-weight: 700; letter-spacing: .09em; text-transform: uppercase; }
        h1 { margin: 0; max-width: 850px; font-size: clamp(3rem, 6vw, 5rem); line-height: .99; letter-spacing: -.05em; }
        .hero-copy { max-width: 700px; margin: 1.35rem 0 0; color: #d4e3d9; font-size: 1.08rem; }
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

        .scope-banner { background: var(--amber-50); border-bottom: 1px solid var(--amber-border); }
        .scope-banner-inner { padding: 15px 0; display: flex; gap: 10px; align-items: flex-start; color: #574800; font-size: .87rem; }
        .scope-banner strong { color: #443700; }

        .section { padding: 5.2rem 0; }
        .section-white { background: #fff; }
        .section-dark { background: #0d3421; color: #fff; }
        .section-grid { display: grid; grid-template-columns: 280px minmax(0, 1fr); gap: 4.5rem; }
        .section-kicker { margin: 0 0 .5rem; color: var(--green-700); font-size: .8rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
        .section-dark .section-kicker { color: #a9d2b6; }
        .section-title { margin: 0; font-size: clamp(2rem, 4vw, 3.1rem); line-height: 1.08; letter-spacing: -.035em; }
        .section-intro { margin: .75rem 0 0; color: var(--muted); max-width: 760px; }
        .section-dark .section-intro { color: #c8d8ce; }
        .side-note { position: sticky; top: 105px; align-self: start; }

        .feature-line { display: grid; grid-template-columns: repeat(4, 1fr); border: 1px solid var(--border); border-radius: 12px; overflow: hidden; background: var(--border); gap: 1px; }
        .feature { min-height: 160px; padding: 1.25rem; background: #fff; }
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
        .decision-box { margin-top: 1.5rem; padding: 1rem 1.1rem; border: 1px solid #bfd4c6; border-radius: 10px; background: var(--green-50); color: #334b3c; font-size: .88rem; }
        .decision-box strong { color: var(--green-900); }

        .role-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-top: 1.8rem; }
        .role { padding: 1.25rem 0 0; border-top: 2px solid rgba(255,255,255,.22); }
        .role h3 { margin: 0; font-size: 1.03rem; }
        .role p { margin: .45rem 0 0; color: #c8d8ce; font-size: .87rem; }
        .access-note { margin: 1.5rem 0 0; color: #f6e8ad; font-size: .88rem; }

        .output-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; }
        .output-card { padding: 1.25rem; border: 1px solid var(--border); border-radius: 12px; background: #fff; }
        .output-card h3 { margin: 0; font-size: 1rem; }
        .output-card p { margin: .45rem 0 0; color: var(--muted); font-size: .87rem; }

        .faq-list { border-top: 1px solid var(--border); }
        details { border-bottom: 1px solid var(--border); background: #fff; }
        summary { min-height: 60px; display: flex; align-items: center; position: relative; padding: 1rem 3rem 1rem 0; cursor: pointer; list-style: none; font-weight: 700; }
        summary::-webkit-details-marker { display: none; }
        summary::after { content: '+'; position: absolute; right: .35rem; color: var(--green-700); font-size: 1.45rem; font-weight: 400; }
        details[open] summary::after { content: '−'; }
        .faq-answer { max-width: 840px; padding: 0 3rem 1.15rem 0; color: var(--muted); font-size: .92rem; }
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
        .office-links { margin-top: 1rem; display: grid; border-top: 1px solid var(--border); }
        .office-link { min-height: 52px; display: flex; justify-content: space-between; align-items: center; gap: 1rem; border-bottom: 1px solid #edf1ee; color: var(--green-800); text-decoration: none; font-weight: 700; }
        .office-link:hover { text-decoration: underline; }

        footer { background: #071f14; color: #d9e7dd; }
        .footer-inner { min-height: 105px; display: flex; align-items: center; justify-content: space-between; gap: 1.5rem; }
        .footer-brand { display: flex; align-items: center; gap: .75rem; }
        .footer-brand img { width: 42px; height: 42px; }
        .footer-title { color: #fff; font-weight: 700; }
        .footer-sub { margin-top: .12rem; color: #9fb6a7; font-size: .76rem; }
        .footer-links { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: .25rem; }
        .footer-links a { min-height: 42px; display: inline-flex; align-items: center; padding: 0 .6rem; color: #c4d8ca; text-decoration: none; font-size: .8rem; }
        .footer-links a:hover { color: #fff; }

        @media (max-width: 980px) {
            .hero-grid { grid-template-columns: 1fr; gap: 2rem; min-height: 0; }
            .section-grid { grid-template-columns: 1fr; gap: 2rem; }
            .side-note { position: static; }
            .feature-line { grid-template-columns: 1fr 1fr; }
            .output-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 720px) {
            .hero-grid { padding: 4rem 0; }
            .section { padding: 4rem 0; }
            .feature-line,
            .requirement-groups,
            .role-grid,
            .office-grid { grid-template-columns: 1fr; }
            .additional { grid-column: auto; grid-template-columns: 1fr; }
            .contact-list li { grid-template-columns: 1fr; gap: .2rem; }
            .footer-inner { padding: 1.5rem 0; flex-direction: column; align-items: flex-start; }
            .footer-links { justify-content: flex-start; }
        }
    </style>
</head>
<body>
    <a class="skip-link" href="#main-content">Skip to main content</a>

    <div class="sticky-shell">
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
                    <a href="#access">Access</a>
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
                    <p class="eyebrow">Department of Agrarian Reform · Negros Oriental Provincial Office</p>
                    <h1 id="hero-title">Land Transfer Clearance and Monitoring System</h1>
                    <p class="hero-copy">A web-based administrative platform for land transfer clearance application processing, records management, status monitoring, reporting, and clearance result generation for authorized DAR Negros Oriental users and linked landowners.</p>
                    <div class="hero-actions">
                        <a href="{{ route('login') }}" class="button button-light">Sign In</a>
                        <a href="#process" class="button button-outline">View Process</a>
                    </div>
                </div>

                <aside class="hero-panel" aria-label="System overview">
                    <div class="hero-panel-head">
                        <img src="{{ asset('images/dar-logo.svg') }}" alt="">
                        <div>
                            <div class="panel-title">DAR-LTCMS</div>
                            <div class="panel-sub">Administrative processing and monitoring</div>
                        </div>
                    </div>
                    <div class="panel-body">
                        <div class="panel-row"><div class="panel-label">Office</div><div class="panel-value">DAR Negros Oriental Provincial Office</div></div>
                        <div class="panel-row"><div class="panel-label">Application intake</div><div class="panel-value">Manually encoded by authorized DAR Staff</div></div>
                        <div class="panel-row"><div class="panel-label">Final system status</div><div class="panel-value">Released or Denied</div></div>
                        <div class="panel-row"><div class="panel-label">Clearance output</div><div class="panel-value">LTC Form No. 5 · GRANTED / DENIED</div></div>
                    </div>
                </aside>
            </div>
        </section>

        <section class="scope-banner" aria-label="Important system scope">
            <div class="container scope-banner-inner">
                <span aria-hidden="true">ⓘ</span>
                <div><strong>Important:</strong> DAR-LTCMS processes, generates, records, and monitors land transfer clearance results. A Released or GRANTED clearance does not automatically transfer land ownership, alter registry records, or conclusively execute a legal land transfer. Those actions remain subject to separate legal and administrative procedures.</div>
            </div>
        </section>

        <section class="section section-white" id="about" aria-labelledby="about-title">
            <div class="container section-grid">
                <div class="side-note">
                    <p class="section-kicker">About the system</p>
                    <h2 class="section-title" id="about-title">Built for accountable clearance processing.</h2>
                    <p class="section-intro">The system supports office work while preserving DAR authority, traceability, and record integrity.</p>
                </div>
                <div class="feature-line">
                    <article class="feature"><span class="feature-number">01</span><h3>Application Processing</h3><p>DAR Staff manually encode applications, review supporting documents, and move records through the authorized office workflow.</p></article>
                    <article class="feature"><span class="feature-number">02</span><h3>Land Records</h3><p>Manage landowner, parcel, landholding, source-reference, and application-linked information without automatically changing ownership.</p></article>
                    <article class="feature"><span class="feature-number">03</span><h3>Monitoring & Reports</h3><p>Track application stages, final outcomes, parcel references, and administrative monitoring outputs.</p></article>
                    <article class="feature"><span class="feature-number">04</span><h3>Security & Auditability</h3><p>Role-based access, protected records, actor-based activity logs, and timestamped audit trails support accountability.</p></article>
                </div>
            </div>
        </section>

        <section class="section" id="requirements" aria-labelledby="requirements-title">
            <div class="container section-grid">
                <div class="side-note">
                    <p class="section-kicker">Requirements</p>
                    <h2 class="section-title" id="requirements-title">Prepare the applicable documents.</h2>
                    <p class="section-intro">The system tracks mandatory, reference, and case-dependent documents used during DAR review.</p>
                </div>
                <div>
                    <div class="requirement-groups">
                        <article class="requirement-card">
                            <div class="requirement-head"><h3>Transferor requirements</h3></div>
                            <ul class="requirement-list">
                                <li>Official Receipt (LTC Fee Payment)</li>
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
                            <strong>Reference / case-dependent</strong>
                            <span>Recent Tax Declaration (if available) · Death Certificate (if applicable) · City Assessor's Certificate of Aggregate Landholding when applicable · Other documents required after authorized DAR review.</span>
                        </div>
                    </div>
                    <p class="requirement-note">DAR personnel determine which requirements apply to the specific transaction. System checks assist processing and do not replace official legal or administrative review.</p>
                </div>
            </div>
        </section>

        <section class="section section-white" id="process" aria-labelledby="process-title">
            <div class="container section-grid">
                <div class="side-note">
                    <p class="section-kicker">Office workflow</p>
                    <h2 class="section-title" id="process-title">From encoding to a final clearance result.</h2>
                    <p class="section-intro">Applications are recorded and monitored through the current DAR-LTCMS workflow.</p>
                </div>
                <div>
                    <div class="timeline">
                        <div class="timeline-step"><div class="timeline-number">1</div><div class="timeline-copy"><h3>Pending Review by Legal Officer</h3><p>DAR Staff encode the application and supporting records for initial legal review.</p></div></div>
                        <div class="timeline-step"><div class="timeline-number">2</div><div class="timeline-copy"><h3>Endorsed to LTI Division</h3><p>The application is recorded as endorsed for the next authorized review stage.</p></div></div>
                        <div class="timeline-step"><div class="timeline-number">3</div><div class="timeline-copy"><h3>Endorsed to Chief Legal</h3><p>Processing continues with the application history and supporting records preserved.</p></div></div>
                        <div class="timeline-step"><div class="timeline-number">4</div><div class="timeline-copy"><h3>Endorsed to PARPO II</h3><p>The application proceeds to the authorized provincial decision stage.</p></div></div>
                        <div class="timeline-step"><div class="timeline-number">5</div><div class="timeline-copy"><h3>For Releasing</h3><p>A clearance prepared for release remains tracked until its final system outcome is recorded.</p></div></div>
                        <div class="timeline-step"><div class="timeline-number">6</div><div class="timeline-copy"><h3>Released or Denied</h3><p>The final decision is recorded, the record is locked against further application edits/uploads, and the clearance result remains available for authorized viewing, reporting, and audit.</p></div></div>
                    </div>
                    <div class="decision-box"><strong>Final decision freeze:</strong> Released and Denied are final system outcomes. DAR-LTCMS preserves the decision record and audit trail instead of automatically changing parcel ownership or registry information.</div>
                </div>
            </div>
        </section>

        <section class="section section-dark" id="access" aria-labelledby="access-title">
            <div class="container">
                <p class="section-kicker">Role-based access</p>
                <h2 class="section-title" id="access-title">Different users see only what their role allows.</h2>
                <div class="role-grid">
                    <article class="role"><h3>DAR Staff</h3><p>Encode and process clearance applications; manage authorized landowner, parcel, landholding, source, document, monitoring, report, and user records.</p></article>
                    <article class="role"><h3>Landowners</h3><p>View only parcel records and application status/results linked to their own account. Landowners do not create clearance applications in the system.</p></article>
                    <article class="role"><h3>Geodetic Personnel</h3><p>Use a limited technical workspace for parcel, reference, and map review. This role does not approve clearance applications or change land ownership records.</p></article>
                </div>
                <p class="access-note"><strong>No online self-application.</strong> Clearance applications are manually encoded by authorized DAR Staff. Significant actions are protected by access controls and recorded for traceability.</p>
            </div>
        </section>

        <section class="section" id="output" aria-labelledby="output-title">
            <div class="container section-grid">
                <div class="side-note">
                    <p class="section-kicker">Clearance result</p>
                    <h2 class="section-title" id="output-title">LTC Form No. 5</h2>
                    <p class="section-intro">Final application outcomes are documented in the system's clearance output.</p>
                </div>
                <div class="output-grid">
                    <article class="output-card"><h3>Released → GRANTED</h3><p>A Released application may generate the Form No. 5 result marked GRANTED. This documents the clearance result only.</p></article>
                    <article class="output-card"><h3>Denied → DENIED</h3><p>A Denied application keeps its final decision and corresponding DENIED clearance output available to authorized users.</p></article>
                    <article class="output-card"><h3>Preserved & traceable</h3><p>Final results, timestamps, responsible actors, and significant activity remain available for monitoring, reporting, archival use, and audit review.</p></article>
                </div>
            </div>
        </section>

        <section class="section section-white" id="faq" aria-labelledby="faq-title">
            <div class="container section-grid">
                <div class="side-note">
                    <p class="section-kicker">Frequently asked questions</p>
                    <h2 class="section-title" id="faq-title">Quick answers.</h2>
                </div>
                <div class="faq-list">
                    <details><summary>Can a landowner create a clearance application online?</summary><div class="faq-answer"><p>No. Authorized DAR Staff manually encode land transfer clearance applications. A linked landowner account is for viewing the user's own permitted records and application status/results.</p></div></details>
                    <details><summary>What does Released or GRANTED mean in DAR-LTCMS?</summary><div class="faq-answer"><p>It means the administrative clearance result has been recorded and may be generated as LTC Form No. 5. It does not itself transfer land ownership or alter Registry of Deeds records.</p></div></details>
                    <details><summary>Can a final Released or Denied application still be edited?</summary><div class="faq-answer"><p>No. Final outcomes are locked against further application editing and document uploads so the decision record and audit history remain preserved.</p></div></details>
                    <details><summary>Does DAR-LTCMS replace official legal decision-making?</summary><div class="faq-answer"><p>No. The system supports processing, validation, records management, monitoring, and reporting. Authorized DAR personnel and applicable legal or administrative procedures remain the official authority.</p></div></details>
                    <details><summary>What can Geodetic Personnel do?</summary><div class="faq-answer"><p>They have a limited technical workspace focused on parcel, reference, geometry, and map information. They are not primary clearance approving users and cannot change ownership records through DAR-LTCMS.</p></div></details>
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
                <a href="#about">About</a>
                <a href="#requirements">Requirements</a>
                <a href="#process">Process</a>
                <a href="#faq">FAQ</a>
                <a href="#office">Office</a>
                <a href="{{ route('login') }}">Sign In</a>
            </nav>
        </div>
    </footer>
</body>
</html>
