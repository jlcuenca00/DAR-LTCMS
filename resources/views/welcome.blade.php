<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Public information for the DAR Negros Oriental Land Transfer Clearance and Monitoring System, including clearance preparation guidance, processing steps, and authorized access information.">
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
            --green-50: #f1f8f3;
            --ink: #18231d;
            --muted: #5f6d64;
            --border: #d7e1da;
            --surface: #ffffff;
            --page: #f6f8f6;
            --yellow: #f5c842;
        }

        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; scroll-padding-top: 86px; }
        body {
            margin: 0;
            font-family: 'Google Sans', Arial, sans-serif;
            color: var(--ink);
            background: var(--page);
            line-height: 1.6;
        }
        a { color: inherit; }
        button, input { font: inherit; }
        .container { width: min(1200px, calc(100% - 2rem)); margin: 0 auto; }

        .site-header {
            position: sticky;
            top: 0;
            z-index: 50;
            background: rgba(8, 45, 27, .97);
            border-bottom: 1px solid rgba(255,255,255,.12);
            color: #fff;
        }
        .header-inner {
            min-height: 72px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.5rem;
        }
        .brand {
            display: inline-flex;
            align-items: center;
            gap: .75rem;
            text-decoration: none;
            flex-shrink: 0;
        }
        .brand img { width: 44px; height: 44px; object-fit: contain; }
        .brand-name { display: block; font-weight: 700; line-height: 1.15; }
        .brand-office { display: block; color: #c7d8cd; font-size: .72rem; margin-top: .12rem; }
        .nav { display: flex; align-items: center; justify-content: flex-end; gap: 1.2rem; }
        .nav a { text-decoration: none; color: #dce9e0; font-size: .88rem; font-weight: 600; }
        .nav a:hover { color: #fff; }
        .nav .sign-in {
            color: #102117;
            background: #fff;
            border: 1px solid #fff;
            border-radius: .45rem;
            padding: .55rem .85rem;
        }

        .hero {
            position: relative;
            overflow: hidden;
            color: #fff;
            background: var(--green-950);
            border-bottom: 1px solid #123e29;
        }
        .hero::before {
            content: "";
            position: absolute;
            inset: 0;
            pointer-events: none;
            opacity: .12;
            background-image:
                linear-gradient(rgba(255,255,255,.18) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.18) 1px, transparent 1px);
            background-size: 64px 64px;
            mask-image: linear-gradient(to bottom right, black, transparent 72%);
        }
        .hero-grid {
            position: relative;
            min-height: 650px;
            display: grid;
            grid-template-columns: minmax(0, 1.35fr) minmax(320px, .65fr);
            gap: 5rem;
            align-items: center;
            padding: 5.5rem 0;
        }
        .eyebrow {
            margin: 0 0 1rem;
            color: #b8d9c3;
            text-transform: uppercase;
            letter-spacing: .1em;
            font-size: .82rem;
            font-weight: 700;
        }
        h1 {
            margin: 0;
            max-width: 860px;
            font-size: clamp(3rem, 6.2vw, 5.5rem);
            line-height: .98;
            letter-spacing: -.05em;
        }
        .hero-copy {
            max-width: 760px;
            margin: 1.6rem 0 0;
            color: #d4e3d9;
            font-size: clamp(1rem, 1.7vw, 1.22rem);
        }
        .hero-actions { display: flex; flex-wrap: wrap; gap: .8rem; margin-top: 2rem; }
        .button {
            min-height: 48px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: .75rem 1.05rem;
            border-radius: .5rem;
            text-decoration: none;
            font-weight: 700;
        }
        .button-light { background: #fff; color: #153020; border: 1px solid #fff; }
        .button-outline { color: #fff; border: 1px solid rgba(255,255,255,.42); background: rgba(255,255,255,.04); }
        .button-outline:hover { border-color: #fff; background: rgba(255,255,255,.08); }
        .hero-panel {
            background: rgba(255,255,255,.06);
            border: 1px solid rgba(255,255,255,.17);
            border-radius: .75rem;
            overflow: hidden;
            box-shadow: 0 28px 70px rgba(0,0,0,.18);
        }
        .hero-panel-head {
            display: flex;
            align-items: center;
            gap: .8rem;
            padding: 1.15rem 1.25rem;
            border-bottom: 1px solid rgba(255,255,255,.12);
            background: rgba(255,255,255,.04);
        }
        .hero-panel-head img { width: 50px; height: 50px; object-fit: contain; }
        .panel-title { font-weight: 700; line-height: 1.2; }
        .panel-sub { color: #b9cec0; font-size: .78rem; margin-top: .15rem; }
        .hero-panel-body { padding: .4rem 1.25rem 1.1rem; }
        .panel-row { padding: 1rem 0; border-bottom: 1px solid rgba(255,255,255,.11); }
        .panel-row:last-child { border-bottom: 0; }
        .panel-label { color: #9fc5ab; font-size: .76rem; text-transform: uppercase; letter-spacing: .07em; font-weight: 700; }
        .panel-value { margin-top: .2rem; color: #fff; font-size: .94rem; }

        .section { padding: 6rem 0; }
        .section-white { background: #fff; }
        .section-dark { background: #0d3421; color: #fff; }
        .section-grid {
            display: grid;
            grid-template-columns: 300px minmax(0, 1fr);
            gap: 5rem;
        }
        .section-kicker { margin: 0 0 .55rem; color: var(--green-700); font-weight: 700; font-size: .84rem; text-transform: uppercase; letter-spacing: .08em; }
        .section-dark .section-kicker { color: #a9d2b6; }
        .section-title { margin: 0; font-size: clamp(2rem, 4vw, 3.25rem); line-height: 1.07; letter-spacing: -.035em; }
        .section-intro { margin: 1rem 0 0; color: var(--muted); max-width: 780px; font-size: 1.02rem; }
        .section-dark .section-intro { color: #c8d8ce; }
        .side-note { position: sticky; top: 110px; align-self: start; }
        .side-note p { color: var(--muted); font-size: .92rem; margin: .8rem 0 0; }

        .scope-callout {
            margin-top: 2rem;
            border: 1px solid #bcd3c3;
            border-left: 4px solid var(--green-700);
            border-radius: .55rem;
            background: var(--green-50);
            padding: 1.15rem 1.25rem;
        }
        .scope-callout strong { color: var(--green-900); }
        .scope-callout p { margin: .35rem 0 0; color: #46574d; }

        .requirement-groups { display: grid; gap: 1rem; }
        .requirement-card {
            background: #fff;
            border: 1px solid var(--border);
            border-radius: .7rem;
            overflow: hidden;
        }
        .requirement-head {
            padding: 1.2rem 1.35rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }
        .requirement-head h3 { margin: 0; font-size: 1.08rem; }
        .tag { border-radius: 999px; padding: .3rem .55rem; font-size: .72rem; font-weight: 700; background: var(--green-100); color: var(--green-900); white-space: nowrap; }
        .requirement-list { list-style: none; margin: 0; padding: .45rem 1.35rem 1rem; }
        .requirement-list li {
            position: relative;
            padding: .8rem 0 .8rem 1.7rem;
            border-bottom: 1px solid #edf1ee;
        }
        .requirement-list li:last-child { border-bottom: 0; }
        .requirement-list li::before {
            content: "✓";
            position: absolute;
            left: 0;
            top: .78rem;
            width: 1.15rem;
            height: 1.15rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: var(--green-100);
            color: var(--green-800);
            font-size: .72rem;
            font-weight: 700;
        }
        .requirement-note { margin: 1rem 0 0; color: var(--muted); font-size: .9rem; }

        .timeline { position: relative; margin-top: .4rem; }
        .timeline::before {
            content: "";
            position: absolute;
            left: 20px;
            top: 25px;
            bottom: 25px;
            width: 2px;
            background: #cfe0d4;
        }
        .timeline-step { position: relative; display: grid; grid-template-columns: 42px 1fr; gap: 1rem; padding: .45rem 0 1.6rem; }
        .timeline-step:last-child { padding-bottom: 0; }
        .timeline-number {
            position: relative;
            z-index: 1;
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: var(--green-800);
            color: #fff;
            font-weight: 700;
            border: 5px solid #fff;
            box-shadow: 0 0 0 1px #bcd3c3;
        }
        .timeline-copy { padding-top: .4rem; }
        .timeline-copy h3 { margin: 0; font-size: 1.04rem; }
        .timeline-copy p { margin: .25rem 0 0; color: var(--muted); }

        .role-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-top: 2.2rem; }
        .role-card { border: 1px solid rgba(255,255,255,.16); border-radius: .7rem; padding: 1.4rem; background: rgba(255,255,255,.04); }
        .role-card h3 { margin: 0; font-size: 1.05rem; }
        .role-card p { margin: .6rem 0 0; color: #c8d8ce; font-size: .92rem; }
        .no-apply { margin-top: 1.4rem; padding: 1rem 1.15rem; border-radius: .55rem; background: rgba(245,200,66,.1); border: 1px solid rgba(245,200,66,.38); color: #f6e8ad; }

        .faq-list { border-top: 1px solid var(--border); }
        details { border-bottom: 1px solid var(--border); background: #fff; }
        summary {
            list-style: none;
            cursor: pointer;
            padding: 1.35rem 3rem 1.35rem 0;
            font-weight: 700;
            position: relative;
        }
        summary::-webkit-details-marker { display: none; }
        summary::after { content: "+"; position: absolute; right: .3rem; top: 1.1rem; color: var(--green-700); font-size: 1.5rem; font-weight: 400; }
        details[open] summary::after { content: "−"; }
        .faq-answer { padding: 0 3rem 1.35rem 0; color: var(--muted); max-width: 850px; }
        .faq-answer p { margin: 0; }

        .office-card {
            margin-top: 2rem;
            display: grid;
            grid-template-columns: 1fr auto;
            gap: 2rem;
            align-items: center;
            padding: 1.4rem 1.5rem;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: .7rem;
        }
        .office-card h3 { margin: 0; }
        .office-card p { margin: .4rem 0 0; color: var(--muted); }
        .verification-badge { font-size: .78rem; color: #6f5d12; background: #fff9dc; border: 1px solid #eadb8e; border-radius: 999px; padding: .35rem .6rem; white-space: nowrap; }

        footer { background: #071f14; color: #dce9e1; border-top: 1px solid #113522; }
        .footer-top { padding: 3rem 0 2rem; display: grid; grid-template-columns: 1fr auto; gap: 2rem; }
        .footer-brand { display: flex; gap: .8rem; align-items: center; }
        .footer-brand img { width: 50px; height: 50px; object-fit: contain; }
        .footer-title { font-weight: 700; }
        .footer-sub { color: #9db7a7; font-size: .8rem; }
        .footer-links { display: flex; flex-wrap: wrap; align-items: center; gap: 1rem; }
        .footer-links a { text-decoration: none; color: #bcd0c3; font-size: .85rem; }
        .footer-bottom { padding: 1rem 0 1.5rem; border-top: 1px solid rgba(255,255,255,.09); color: #8fa99a; font-size: .78rem; }

        @media (max-width: 980px) {
            .nav a:not(.sign-in) { display: none; }
            .hero-grid { grid-template-columns: 1fr; gap: 2.5rem; min-height: auto; }
            .hero-panel { max-width: 620px; }
            .section-grid { grid-template-columns: 1fr; gap: 2.2rem; }
            .side-note { position: static; }
            .role-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 640px) {
            .container { width: min(100% - 1.25rem, 1200px); }
            .brand-office { display: none; }
            .header-inner { min-height: 64px; }
            .brand img { width: 38px; height: 38px; }
            .nav .sign-in { padding: .48rem .7rem; font-size: .82rem; }
            .hero-grid { padding: 4rem 0; }
            h1 { font-size: clamp(2.7rem, 14vw, 4rem); }
            .section { padding: 4.5rem 0; }
            .hero-actions .button { width: 100%; }
            .requirement-head { align-items: flex-start; flex-direction: column; }
            .office-card { grid-template-columns: 1fr; gap: 1rem; }
            .verification-badge { justify-self: start; white-space: normal; }
            .footer-top { grid-template-columns: 1fr; }
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

            <nav class="nav" aria-label="Public information navigation">
                <a href="#about">About</a>
                <a href="#requirements">Requirements</a>
                <a href="#process">Process</a>
                <a href="#access">Who can log in</a>
                <a href="#faq">FAQ</a>
                <a href="{{ route('login') }}" class="sign-in">Sign In</a>
            </nav>
        </div>
    </header>

    <main>
        <section class="hero" id="about">
            <div class="container hero-grid">
                <div>
                    <p class="eyebrow">Department of Agrarian Reform · Negros Oriental Provincial Office</p>
                    <h1>Land transfer clearance, made easier to understand and monitor.</h1>
                    <p class="hero-copy">
                        DAR-LTCMS is the web-based administrative processing and monitoring platform used to support land transfer clearance records, document review, application progress, clearance generation, and reporting within the DAR Negros Oriental Provincial Office.
                    </p>
                    <div class="hero-actions">
                        <a href="{{ route('login') }}" class="button button-light">Sign In</a>
                        <a href="#requirements" class="button button-outline">View Clearance Requirements</a>
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
                    <div class="hero-panel-body">
                        <div class="panel-row">
                            <div class="panel-label">Service</div>
                            <div class="panel-value">Land Transfer Clearance processing and monitoring</div>
                        </div>
                        <div class="panel-row">
                            <div class="panel-label">Office</div>
                            <div class="panel-value">DAR Negros Oriental Provincial Office</div>
                        </div>
                        <div class="panel-row">
                            <div class="panel-label">Applications</div>
                            <div class="panel-value">Encoded and processed by authorized DAR personnel</div>
                        </div>
                        <div class="panel-row">
                            <div class="panel-label">Public portal</div>
                            <div class="panel-value">Information and preparation guidance; no online self-application</div>
                        </div>
                    </div>
                </aside>
            </div>
        </section>

        <section class="section section-white">
            <div class="container section-grid">
                <div class="side-note">
                    <p class="section-kicker">About the service</p>
                    <h2 class="section-title">A digital front desk for clearance information.</h2>
                </div>
                <div>
                    <p class="section-intro" style="margin-top:0; font-size:1.1rem;">
                        Land transfer clearance processing involves the administrative review of records and supporting documents before DAR issues the appropriate clearance decision or output. DAR-LTCMS helps authorized personnel organize that work, preserve traceable records, and monitor each application through the office workflow.
                    </p>
                    <div class="scope-callout">
                        <strong>Important scope reminder</strong>
                        <p>
                            Clearance approval and generation through DAR-LTCMS does not itself execute the legal transfer of land ownership or alter registry ownership records. Any actual transfer, registry alteration, or ownership mutation remains subject to separate legal and administrative procedures.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <section class="section" id="requirements">
            <div class="container section-grid">
                <div class="side-note">
                    <p class="section-kicker">Clearance requirements</p>
                    <h2 class="section-title">Prepare before visiting DAR.</h2>
                    <p>
                        This is a curated public preparation guide based on the current DAR-LTCMS clearance configuration. It is not an automatic publication of internal database records.
                    </p>
                </div>

                <div>
                    <div class="requirement-groups">
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
                                <h3>Case-dependent or additional documents</h3>
                                <span class="tag">When applicable</span>
                            </div>
                            <ul class="requirement-list">
                                <li>Death Certificate, when applicable to a person identified in the transfer instrument</li>
                                <li>City Assessor's Certificate of Aggregate Landholding, depending on location or jurisdiction</li>
                                <li>Recent Tax Declaration, when available, as a supporting reference document</li>
                                <li>Other documents requested by authorized DAR personnel after official review of the transaction circumstances</li>
                            </ul>
                        </article>
                    </div>

                    <p class="requirement-note">
                        Additional documents may be required depending on the circumstances of the transaction. DAR personnel remain responsible for official document review, validation, and determination of applicable requirements.
                    </p>
                </div>
            </div>
        </section>

        <section class="section section-white" id="process">
            <div class="container section-grid">
                <div class="side-note">
                    <p class="section-kicker">How processing works</p>
                    <h2 class="section-title">From preparation to clearance output.</h2>
                    <p>The application itself is encoded by authorized DAR staff. Landowners do not create clearance applications through this website.</p>
                </div>

                <div class="timeline" aria-label="Public clearance processing overview">
                    <div class="timeline-step">
                        <div class="timeline-number">1</div>
                        <div class="timeline-copy">
                            <h3>Prepare Requirements</h3>
                            <p>Gather the core and applicable supporting documents before coordinating with the DAR office.</p>
                        </div>
                    </div>
                    <div class="timeline-step">
                        <div class="timeline-number">2</div>
                        <div class="timeline-copy">
                            <h3>Visit or Coordinate with DAR Negros Oriental</h3>
                            <p>Present the transaction information and documents for the appropriate office process.</p>
                        </div>
                    </div>
                    <div class="timeline-step">
                        <div class="timeline-number">3</div>
                        <div class="timeline-copy">
                            <h3>DAR Staff Encodes the Application</h3>
                            <p>Authorized staff create and maintain the application record in DAR-LTCMS.</p>
                        </div>
                    </div>
                    <div class="timeline-step">
                        <div class="timeline-number">4</div>
                        <div class="timeline-copy">
                            <h3>Requirements and Records Are Reviewed</h3>
                            <p>DAR personnel review submitted documents, parcel information, landholding records, and other applicable references.</p>
                        </div>
                    </div>
                    <div class="timeline-step">
                        <div class="timeline-number">5</div>
                        <div class="timeline-copy">
                            <h3>Application Progress Is Monitored</h3>
                            <p>The system records and presents the administrative status as the application proceeds through authorized office review.</p>
                        </div>
                    </div>
                    <div class="timeline-step">
                        <div class="timeline-number">6</div>
                        <div class="timeline-copy">
                            <h3>Clearance Decision or Output</h3>
                            <p>The final clearance result is recorded and, where applicable, the official clearance output is generated for authorized access.</p>
                        </div>
                    </div>

                    <div class="scope-callout" style="margin-left:58px;">
                        <strong>After DAR-LTCMS</strong>
                        <p>Any further legal, conveyance, registration, or registry procedures required to complete an actual land ownership transfer occur separately from DAR-LTCMS.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="section section-dark" id="access">
            <div class="container">
                <p class="section-kicker">Authorized access</p>
                <h2 class="section-title">Who can sign in?</h2>
                <p class="section-intro">DAR-LTCMS is intended for office processing and authorized stakeholder access. Accounts are created and managed by authorized DAR personnel.</p>

                <div class="role-grid">
                    <article class="role-card">
                        <h3>DAR Staff</h3>
                        <p>Encode applications, manage authorized records and documents, process clearance applications, record workflow actions, and prepare monitoring outputs and reports.</p>
                    </article>
                    <article class="role-card">
                        <h3>Landowners</h3>
                        <p>View only their own linked parcel records, application status, final decision information, and available clearance output. Landowners cannot create applications themselves.</p>
                    </article>
                    <article class="role-card">
                        <h3>Geodetic Personnel</h3>
                        <p>Use limited, read-only access for parcel, reference, and map-based review. This role is not a primary approval or broad record-editing role.</p>
                    </article>
                </div>

                <div class="no-apply">
                    <strong>No online self-application.</strong> The public landing page provides information and preparation guidance only. Clearance applications are encoded by authorized DAR staff.
                </div>
            </div>
        </section>

        <section class="section section-white" id="faq">
            <div class="container section-grid">
                <div class="side-note">
                    <p class="section-kicker">Frequently asked questions</p>
                    <h2 class="section-title">Common questions before you proceed.</h2>
                </div>

                <div class="faq-list">
                    <details>
                        <summary>Can I submit a land transfer clearance application online?</summary>
                        <div class="faq-answer"><p>No. Landowners do not create applications through DAR-LTCMS. Authorized DAR staff encode and process clearance applications through the office workflow.</p></div>
                    </details>
                    <details>
                        <summary>Can I see my application status?</summary>
                        <div class="faq-answer"><p>Yes. A landowner account may view its own linked application status and records when the account has been properly associated with the relevant landowner record. It cannot access another landowner's records.</p></div>
                    </details>
                    <details>
                        <summary>Does an approved or released clearance mean ownership has already transferred?</summary>
                        <div class="faq-answer"><p>No. A clearance decision or generated output does not itself execute a legal transfer of ownership or alter registry ownership records. Those actions remain subject to separate legal and administrative procedures.</p></div>
                    </details>
                    <details>
                        <summary>What should I bring to DAR?</summary>
                        <div class="faq-answer"><p>Start with the transferor and transferee preparation lists shown on this page, plus any case-dependent documents that apply to your transaction. DAR personnel make the official determination of complete and applicable requirements.</p></div>
                    </details>
                    <details>
                        <summary>Can the system decide whether my transaction is legally valid?</summary>
                        <div class="faq-answer"><p>No. DAR-LTCMS supports administrative processing, record management, validation assistance, monitoring, and clearance generation. Official legal and administrative determinations remain with authorized DAR personnel and the applicable government procedures.</p></div>
                    </details>
                    <details>
                        <summary>I have an account but cannot access another person's parcel or application. Is that expected?</summary>
                        <div class="faq-answer"><p>Yes. Access is role-based. Landowner accounts are restricted to records tied to their own authorized profile, while geodetic personnel have limited read-only access appropriate to their role.</p></div>
                    </details>
                </div>
            </div>
        </section>

        <section class="section" id="office">
            <div class="container">
                <p class="section-kicker">Office information</p>
                <h2 class="section-title">DAR Negros Oriental Provincial Office</h2>
                <p class="section-intro">Official address, contact number, office hours, email, and map information should be published here only after they are verified against the office's current official details.</p>

                <div class="office-card">
                    <div>
                        <h3>Department of Agrarian Reform</h3>
                        <p>Negros Oriental Provincial Office · Land Transfer Clearance and Monitoring System</p>
                    </div>
                    <span class="verification-badge">Contact details pending official verification</span>
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
            <div class="footer-links">
                <a href="#requirements">Requirements</a>
                <a href="#process">Process</a>
                <a href="#faq">FAQ</a>
                <a href="{{ route('login') }}">Sign In</a>
            </div>
        </div>
        <div class="container footer-bottom">
            © {{ now()->year }} Department of Agrarian Reform. DAR-LTCMS is an administrative clearance processing and monitoring platform for authorized use within the DAR Negros Oriental Provincial Office.
        </div>
    </footer>
</body>
</html>
