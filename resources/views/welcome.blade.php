<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="DAR-LTCMS helps DAR Negros Oriental review land transfer clearance applications, track their progress, and prepare clearance results.">
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
        body { margin: 0; font-family: 'Google Sans', Arial, sans-serif; color: var(--ink); background: var(--page); line-height: 1.5; }
        a { color: inherit; }
        img { max-width: 100%; }
        .container { width: min(1160px, calc(100% - 2rem)); margin: 0 auto; }

        .skip-link { position: fixed; left: 1rem; top: 1rem; z-index: 100; transform: translateY(-170%); padding: .7rem 1rem; background: #fff; color: var(--green-950); border: 2px solid var(--focus); border-radius: 8px; font-weight: 700; text-decoration: none; }
        .skip-link:focus { transform: translateY(0); }
        :where(a, button):focus-visible { outline: 3px solid var(--focus); outline-offset: 3px; border-radius: 6px; }

        .sticky-shell { position: sticky; top: 0; z-index: 50; }
        .site-header { background: rgba(8,45,27,.98); border-bottom: 1px solid rgba(255,255,255,.12); color: #fff; }
        .header-inner { min-height: 72px; display: flex; align-items: center; justify-content: space-between; gap: 1.25rem; }
        .brand { min-height: 48px; display: inline-flex; align-items: center; gap: .75rem; text-decoration: none; }
        .brand img { width: 44px; height: 44px; object-fit: contain; }
        .brand-name { display: block; font-weight: 700; line-height: 1.1; }
        .brand-office { display: block; margin-top: .15rem; color: #c7d8cd; font-size: .72rem; }
        .nav { display: flex; align-items: center; gap: .18rem; }
        .nav a { min-height: 44px; display: inline-flex; align-items: center; padding: 0 .7rem; border-radius: 8px; color: #dce9e0; text-decoration: none; font-size: .86rem; font-weight: 600; }
        .nav a:hover { background: rgba(255,255,255,.08); color: #fff; }
        .nav .sign-in { margin-left: .4rem; padding-inline: .95rem; background: #fff; color: #102117; }

        .hero { position: relative; overflow: hidden; background: var(--green-950); color: #fff; }
        .hero::before { content: ''; position: absolute; inset: 0; opacity: .1; pointer-events: none; background-image: linear-gradient(rgba(255,255,255,.18) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,.18) 1px, transparent 1px); background-size: 64px 64px; mask-image: linear-gradient(to bottom right, black, transparent 78%); }
        .hero-grid { position: relative; min-height: 520px; display: grid; grid-template-columns: minmax(0, 1.35fr) minmax(300px, .65fr); align-items: center; gap: 4rem; padding: 4.5rem 0; }
        .eyebrow { margin: 0 0 .85rem; color: #b8d9c3; font-size: .78rem; font-weight: 700; letter-spacing: .09em; text-transform: uppercase; }
        h1 { margin: 0; max-width: 820px; font-size: clamp(2.8rem, 6vw, 4.8rem); line-height: .99; letter-spacing: -.05em; }
        .hero-copy { max-width: 610px; margin: 1.2rem 0 0; color: #d4e3d9; font-size: 1.02rem; }
        .hero-actions { display: flex; flex-wrap: wrap; gap: .7rem; margin-top: 1.6rem; }
        .button { min-height: 48px; display: inline-flex; align-items: center; justify-content: center; padding: .75rem 1rem; border-radius: 8px; text-decoration: none; font-weight: 700; }
        .button-light { background: #fff; color: #153020; }
        .button-outline { border: 1px solid rgba(255,255,255,.42); color: #fff; background: rgba(255,255,255,.04); }

        .hero-panel { overflow: hidden; border: 1px solid rgba(255,255,255,.17); border-radius: 12px; background: rgba(255,255,255,.055); }
        .hero-panel-head { display: flex; align-items: center; gap: .75rem; padding: 1rem 1.1rem; border-bottom: 1px solid rgba(255,255,255,.12); }
        .hero-panel-head img { width: 46px; height: 46px; object-fit: contain; }
        .panel-title { font-weight: 700; }
        .panel-sub { margin-top: .1rem; color: #b9cec0; font-size: .75rem; }
        .panel-body { padding: .35rem 1.1rem .8rem; }
        .panel-row { padding: .8rem 0; border-bottom: 1px solid rgba(255,255,255,.1); }
        .panel-row:last-child { border-bottom: 0; }
        .panel-label { color: #9fc5ab; font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .07em; }
        .panel-value { margin-top: .14rem; color: #fff; font-size: .88rem; }

        .scope-banner { background: #fff9e8; border-bottom: 1px solid #eadb8e; }
        .scope-banner-inner { padding: 13px 0; display: flex; gap: 10px; align-items: flex-start; color: #574800; font-size: .85rem; }

        .section { padding: 4.5rem 0; }
        .section-white { background: #fff; }
        .section-dark { background: #0d3421; color: #fff; }
        .section-grid { display: grid; grid-template-columns: 250px minmax(0, 1fr); gap: 4rem; }
        .section-kicker { margin: 0 0 .45rem; color: var(--green-700); font-size: .78rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; }
        .section-dark .section-kicker { color: #a9d2b6; }
        .section-title { margin: 0; font-size: clamp(1.9rem, 4vw, 2.85rem); line-height: 1.08; letter-spacing: -.035em; }
        .section-intro { margin: .65rem 0 0; color: var(--muted); font-size: .92rem; }
        .side-note { position: sticky; top: 104px; align-self: start; }

        .feature-line { display: grid; grid-template-columns: repeat(4, 1fr); border: 1px solid var(--border); border-radius: 12px; overflow: hidden; background: var(--border); gap: 1px; }
        .feature { min-height: 130px; padding: 1.1rem; background: #fff; }
        .feature-number { color: var(--green-700); font-size: .72rem; font-weight: 700; }
        .feature h3 { margin: .55rem 0 0; font-size: .98rem; }
        .feature p { margin: .28rem 0 0; color: var(--muted); font-size: .8rem; }

        .requirement-groups { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        .requirement-card { overflow: hidden; border: 1px solid var(--border); border-radius: 12px; background: #fff; }
        .requirement-head { padding: 1rem 1.15rem; border-bottom: 1px solid var(--border); }
        .requirement-head h3 { margin: 0; font-size: 1rem; }
        .requirement-list { list-style: none; margin: 0; padding: .25rem 1.15rem .7rem; }
        .requirement-list li { position: relative; padding: .58rem 0 .58rem 1.35rem; border-bottom: 1px solid #edf1ee; font-size: .84rem; }
        .requirement-list li:last-child { border-bottom: 0; }
        .requirement-list li::before { content: '✓'; position: absolute; left: 0; color: var(--green-700); font-weight: 700; }
        .additional { grid-column: 1 / -1; padding: .95rem 1.1rem; border: 1px solid #bfd4c6; border-radius: 12px; background: var(--green-50); color: #46564d; font-size: .83rem; }
        .additional strong { color: var(--green-900); }

        .workflow-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .7rem; }
        .workflow-step { display: grid; grid-template-columns: 32px minmax(0, 1fr); gap: .7rem; align-items: center; min-height: 64px; padding: .8rem .9rem; border: 1px solid var(--border); border-radius: 10px; background: #fff; }
        .workflow-number { width: 32px; height: 32px; display: grid; place-items: center; border-radius: 50%; background: var(--green-800); color: #fff; font-size: .78rem; font-weight: 700; }
        .workflow-step strong { font-size: .88rem; line-height: 1.25; }
        .decision-note { margin-top: 1rem; padding: .85rem 1rem; border: 1px solid #bfd4c6; border-radius: 10px; background: var(--green-50); color: #334b3c; font-size: .84rem; }

        .role-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-top: 1.35rem; }
        .role { padding-top: 1rem; border-top: 2px solid rgba(255,255,255,.22); }
        .role h3 { margin: 0; font-size: 1rem; }
        .role p { margin: .35rem 0 0; color: #c8d8ce; font-size: .82rem; }

        .office-grid { display: grid; grid-template-columns: 1.1fr .9fr; gap: 1rem; }
        .office-card { padding: 1.2rem; border: 1px solid var(--border); border-radius: 12px; background: #fff; }
        .office-card h3 { margin: 0; font-size: 1rem; }
        .contact-list { list-style: none; margin: .8rem 0 0; padding: 0; border-top: 1px solid var(--border); }
        .contact-list li { display: grid; grid-template-columns: 105px 1fr; gap: .8rem; padding: .6rem 0; border-bottom: 1px solid #edf1ee; font-size: .85rem; }
        .contact-label { color: var(--muted); font-size: .76rem; font-weight: 700; }
        .contact-value { overflow-wrap: anywhere; }
        .contact-value a { color: var(--green-800); font-weight: 700; text-decoration: none; }
        .office-links { margin-top: .8rem; display: grid; gap: .55rem; }
        .office-link { min-height: 44px; display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: .7rem .8rem; border: 1px solid var(--border); border-radius: 8px; color: var(--green-800); text-decoration: none; font-size: .84rem; font-weight: 700; }

        footer { background: #072819; color: #dce9e0; }
        .footer-inner { min-height: 92px; display: flex; align-items: center; justify-content: space-between; gap: 1.5rem; }
        .footer-brand { display: flex; align-items: center; gap: .75rem; }
        .footer-brand img { width: 38px; height: 38px; }
        .footer-title { color: #fff; font-size: .9rem; font-weight: 700; }
        .footer-sub { margin-top: .1rem; color: #a9c0b0; font-size: .72rem; }
        .footer-links { display: flex; flex-wrap: wrap; gap: .9rem; }
        .footer-links a { color: #c9dbcf; text-decoration: none; font-size: .8rem; font-weight: 600; }

        @media (max-width: 960px) {
            .hero-grid,
            .section-grid { grid-template-columns: 1fr; gap: 2rem; }
            .hero-grid { min-height: auto; }
            .side-note { position: static; }
            .feature-line { grid-template-columns: 1fr 1fr; }
        }

        @media (max-width: 720px) {
            .hero-grid { padding: 3.5rem 0; }
            .section { padding: 3.5rem 0; }
            .feature-line,
            .requirement-groups,
            .workflow-grid,
            .role-grid,
            .office-grid { grid-template-columns: 1fr; }
            .additional { grid-column: auto; }
            .contact-list li { grid-template-columns: 1fr; gap: .15rem; }
            .footer-inner { padding: 1.35rem 0; flex-direction: column; align-items: flex-start; }
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
                    <p class="hero-copy">A website used by DAR Negros Oriental to review land transfer clearance applications, check their progress, and prepare clearance results.</p>
                    <div class="hero-actions">
                        <a href="{{ route('login') }}" class="button button-light">Sign In</a>
                        <a href="#requirements" class="button button-outline">View Requirements</a>
                    </div>
                </div>

                <aside class="hero-panel" aria-label="System overview">
                    <div class="hero-panel-head">
                        <img src="{{ asset('images/dar-logo.svg') }}" alt="">
                        <div>
                            <div class="panel-title">DAR-LTCMS</div>
                            <div class="panel-sub">Land transfer clearance service</div>
                        </div>
                    </div>
                    <div class="panel-body">
                        <div class="panel-row"><div class="panel-label">Applications</div><div class="panel-value">Entered by authorized DAR Staff</div></div>
                        <div class="panel-row"><div class="panel-label">Final result</div><div class="panel-value">Released or Denied</div></div>
                        <div class="panel-row"><div class="panel-label">Clearance form</div><div class="panel-value">LTC Form No. 5 · GRANTED / DENIED</div></div>
                    </div>
                </aside>
            </div>
        </section>

        <section class="scope-banner" aria-label="Important information">
            <div class="container scope-banner-inner">
                <span aria-hidden="true">ⓘ</span>
                <div>A GRANTED clearance does not mean the land has already changed owners. The actual land transfer and any changes to official land records are handled separately through the proper legal and government process.</div>
            </div>
        </section>

        <section class="section section-white" id="about" aria-labelledby="about-title">
            <div class="container section-grid">
                <div class="side-note">
                    <p class="section-kicker">About</p>
                    <h2 class="section-title" id="about-title">What DAR-LTCMS helps with.</h2>
                </div>
                <div class="feature-line">
                    <article class="feature"><span class="feature-number">01</span><h3>Applications</h3><p>DAR Staff record and review land transfer clearance applications.</p></article>
                    <article class="feature"><span class="feature-number">02</span><h3>Land Information</h3><p>Keep landowner, parcel, and landholding information together.</p></article>
                    <article class="feature"><span class="feature-number">03</span><h3>Updates</h3><p>See where an application is in the DAR process and view its final result.</p></article>
                    <article class="feature"><span class="feature-number">04</span><h3>Privacy</h3><p>Users only see the information allowed for their account.</p></article>
                </div>
            </div>
        </section>

        <section class="section" id="requirements" aria-labelledby="requirements-title">
            <div class="container section-grid">
                <div class="side-note">
                    <p class="section-kicker">Requirements</p>
                    <h2 class="section-title" id="requirements-title">Documents you may need.</h2>
                    <p class="section-intro">Prepare the documents that apply to the land transfer.</p>
                </div>
                <div class="requirement-groups">
                    <article class="requirement-card">
                        <div class="requirement-head"><h3>Transferor — person transferring the land</h3></div>
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
                        <div class="requirement-head"><h3>Transferee — person receiving the land</h3></div>
                        <ul class="requirement-list">
                            <li>Affidavit of Transferee</li>
                            <li>Municipal Assessor's Certificate of Aggregate Landholding</li>
                            <li>Provincial Assessor's Certificate of Aggregate Landholding</li>
                            <li>MARPO Certification (LTC Form No. 2)</li>
                        </ul>
                    </article>
                    <div class="additional"><strong>When needed:</strong> Recent Tax Declaration, Death Certificate, City Assessor's Certificate, and other documents requested by DAR during review.</div>
                </div>
            </div>
        </section>

        <section class="section section-white" id="process" aria-labelledby="process-title">
            <div class="container section-grid">
                <div class="side-note">
                    <p class="section-kicker">Process</p>
                    <h2 class="section-title" id="process-title">How an application moves through DAR.</h2>
                    <p class="section-intro">DAR Staff update the application as it passes through each office step.</p>
                </div>
                <div>
                    <div class="workflow-grid">
                        <div class="workflow-step"><span class="workflow-number">1</span><strong>Pending Review by Legal Officer</strong></div>
                        <div class="workflow-step"><span class="workflow-number">2</span><strong>Endorsed to LTI Division</strong></div>
                        <div class="workflow-step"><span class="workflow-number">3</span><strong>Endorsed to Chief Legal</strong></div>
                        <div class="workflow-step"><span class="workflow-number">4</span><strong>Endorsed to PARPO II</strong></div>
                        <div class="workflow-step"><span class="workflow-number">5</span><strong>For Releasing</strong></div>
                        <div class="workflow-step"><span class="workflow-number">6</span><strong>Released or Denied</strong></div>
                    </div>
                    <div class="decision-note">Once an application is Released or Denied, it is final in DAR-LTCMS. Its information and uploaded documents can no longer be changed.</div>
                </div>
            </div>
        </section>

        <section class="section section-dark" aria-labelledby="access-title">
            <div class="container">
                <p class="section-kicker">Users</p>
                <h2 class="section-title" id="access-title">Who can use DAR-LTCMS?</h2>
                <div class="role-grid">
                    <article class="role"><h3>DAR Staff</h3><p>Enter and review applications, manage DAR information, and prepare reports.</p></article>
                    <article class="role"><h3>Landowners</h3><p>View only their own linked land information and application progress. Landowners do not create applications in the system.</p></article>
                    <article class="role"><h3>Geodetic Personnel</h3><p>View parcel and map information needed for checking land details. They do not approve clearance applications.</p></article>
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
                        <h3>Contact</h3>
                        <ul class="contact-list">
                            <li><span class="contact-label">Telephone</span><span class="contact-value"><a href="tel:5227144">522-7144</a></span></li>
                            <li><span class="contact-label">Cellphone</span><span class="contact-value"><a href="tel:+639168763071">0916-876-3071</a></span></li>
                            <li><span class="contact-label">Email</span><span class="contact-value"><a href="mailto:dar_legal_orneg@yahoo.com">dar_legal_orneg@yahoo.com</a></span></li>
                            <li><span class="contact-label">Regional Office</span><span class="contact-value"><a href="tel:+63322536498">(032) 253-6498</a></span></li>
                        </ul>
                    </article>
                    <article class="office-card">
                        <h3>Online</h3>
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
                <a href="#office">Office</a>
                <a href="{{ route('login') }}">Sign In</a>
            </nav>
        </div>
    </footer>
</body>
</html>