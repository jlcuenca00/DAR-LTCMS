@props(['title', 'wide' => false])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} | DAR-LTCMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:opsz,wght@17..18,400..700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('auth.partials.styles')
    <style>
        body { overflow: auto; }
        .public-auth .login-bg { position: fixed; inset: 0; background-size: cover; background-position: center; }
        .public-auth .logo-slot { flex-shrink: 0; }
        .public-auth a.login-button { display: block; color: #fff; text-align: center; text-decoration: none; }
        .public-auth .registration-status-icon { display: grid; place-items: center; width: 56px; height: 56px; margin: 1rem auto; border-radius: 50%; background: #15803d; color: #fff; }
        .public-auth .registration-status-icon.declined { background: #b91c1c; }
        .public-auth .registration-status-icon svg { width: 28px; height: 28px; }
        .public-auth .login-content { margin-top: 0; }
        .public-auth .login-content.wide { max-width: 800px; }
        .public-auth .login-title { letter-spacing: 0 !important; line-height: 1.2; }
        .public-auth .auth-heading { margin: 1.5rem 0 .5rem; font-size: 1.25rem; font-weight: 700; text-align: center; color: #166b3a; }
        .public-auth .auth-intro { margin-bottom: 1.5rem; font-size: .875rem; line-height: 1.6; color: #4b5563; text-align: center; }
        .public-auth .registration-header { margin-top: 1.15rem; }
        .public-auth .registration-header .auth-heading { margin: 0 0 .45rem; }
        .public-auth .registration-header .auth-intro { max-width: 38rem; margin: 0 auto; }
        .public-auth .registration-google-section { margin-top: 1.25rem; text-align: center; }
        .public-auth .registration-google-error { margin: 0 0 .7rem; color: #b91c1c; font-size: .8rem; font-weight: 700; }
        .public-auth .registration-divider { display: flex; align-items: center; gap: .8rem; margin: 1.35rem 0 1.2rem; color: #9ca3af; font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; }
        .public-auth .registration-divider > span:first-child,
        .public-auth .registration-divider > span:last-child { height: 1px; flex: 1; background: #e5e7eb; }
        .public-auth .registration-form { display: grid; gap: 1rem; }
        .public-auth .registration-field { min-width: 0; }
        .public-auth .registration-form .form-label { margin-bottom: .38rem; font-size: .88rem; }
        .public-auth .registration-form .form-input { padding: .72rem .85rem; font-size: .9rem; }
        .public-auth .registration-help { margin: .35rem 0 0; color: #7b8492; font-size: .74rem; line-height: 1.45; }
        .public-auth .registration-consent-block { margin-top: .15rem; padding: .85rem .9rem; border: 1px solid #e3e9e5; border-radius: .6rem; background: #f8faf9; }
        .public-auth .registration-consent-block .consent { font-size: .8rem; line-height: 1.45; }
        .public-auth .registration-submit { margin-top: .05rem; }
        .public-auth .registration-signin { display: flex; align-items: center; justify-content: center; gap: .35rem; margin-top: .15rem; color: #6b7280; font-size: .82rem; }
        .public-auth .registration-signin a { font-weight: 700; text-decoration: none; }
        .public-auth .registration-signin a:hover { text-decoration: underline; }
        .public-auth .login-card { min-width: 0; }
        .public-auth .consent { display: flex; align-items: flex-start; gap: .6rem; font-size: .875rem; line-height: 1.5; color: #4b5563; }
        .public-auth .consent input { flex-shrink: 0; margin-top: .25rem; accent-color: #166b3a; }
        .public-auth a { color: #166b3a; text-decoration: underline; text-underline-offset: 3px; }
        .public-auth .form-input { font-family: inherit; }
        .public-auth .legal-copy { color: #374151; font-size: .95rem; line-height: 1.75; overflow-wrap: anywhere; }
        .public-auth .legal-copy h2 { margin: 1.75rem 0 .5rem; font-size: 1.1rem; font-weight: 700; color: #166b3a; }
        .public-auth .legal-copy p { margin: .75rem 0; }
        .public-auth .legal-copy ul { padding-left: 1.3rem; list-style: disc; }
        .public-auth .legal-copy li { margin-bottom: .5rem; }
        .public-auth .legal-copy h1 { font-size: 1.75rem; font-weight: 700; margin: 1.5rem 0 .5rem; color: #111827; }
        .public-auth :focus-visible { outline: 3px solid #166b3a; outline-offset: 3px; }
        @media (max-width: 480px) {
            .public-auth .login-card { padding: 1.25rem; }
            .public-auth .form-input { font-size: 1rem; }
            .public-auth .registration-header { margin-top: 1rem; }
            .public-auth .registration-divider { margin: 1.15rem 0 1rem; font-size: .68rem; }
            .public-auth .registration-form { gap: .9rem; }
            .public-auth .registration-consent-block { padding: .75rem .8rem; }
        }
    </style>
</head>
<body>
    <main class="login-page public-auth">
        <div class="login-bg" aria-hidden="true"></div>
        <div class="login-content {{ $wide ? 'wide' : '' }}">
            <a href="{{ route('home') }}" class="logo-slot" aria-label="DAR-LTCMS home">
                <img src="{{ asset('images/dar-logo.svg') }}" alt="Department of Agrarian Reform logo" class="logo-image">
            </a>
            <section class="login-card">
                <p class="login-title">DAR-LTCMS</p>
                <p class="login-subtitle">Land Transfer Clearance and Monitoring System</p>
                <p class="login-office">DAR Negros Oriental Provincial Office</p>
                {{ $slot }}
                <div class="login-footer">
                    @include('auth.partials.legal-links')
                    &copy; {{ now()->year }} Department of Agrarian Reform<br>
                    Negros Oriental Provincial Office
                </div>
            </section>
        </div>
    </main>
</body>
</html>
