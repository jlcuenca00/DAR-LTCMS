@php
    $recovery = $recovery ?? [];
    $step = $recovery['step'] ?? 'username';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Password Recovery | DAR-LTCMS</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:opsz,wght@17..18,400..700&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --dar-green: #166b3a;
            --dar-green-dark: #005326;
            --text-dark: #111827;
            --text-muted: #6b7280;
            --border-soft: #dfe5e2;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Google Sans', system-ui, sans-serif;
            background: #f8faf9;
            color: var(--text-dark);
        }

        .auth-page {
            position: relative;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        .auth-bg {
            position: absolute;
            inset: 0;
            background:
                linear-gradient(rgba(248, 250, 249, .25), rgba(248, 250, 249, .4)),
                url("{{ asset('images/login-bg.png') }}") center / cover no-repeat;
        }

        .auth-content {
            position: relative;
            z-index: 1;
            width: min(100%, 500px);
        }

        .auth-card {
            width: 100%;
            padding: 2rem;
            border: 1px solid var(--border-soft);
            border-radius: 1rem;
            background: rgba(255, 255, 255, .98);
            box-shadow: 0 24px 65px rgba(15, 23, 42, .15);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: .9rem;
            padding-bottom: 1.2rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .brand img {
            width: 54px;
            height: 54px;
            object-fit: contain;
        }

        .brand-name {
            margin: 0;
            color: #075c2c;
            font-size: 1.05rem;
            font-weight: 900;
        }

        .brand-office {
            margin: .2rem 0 0;
            color: #64748b;
            font-size: .78rem;
        }

        h1 {
            margin: 1.45rem 0 .5rem;
            font-size: 1.7rem;
            line-height: 1.2;
        }

        .intro {
            margin: 0;
            color: #64748b;
            font-size: .9rem;
            line-height: 1.6;
        }

        .step-chip {
            display: inline-flex;
            margin-top: 1rem;
            padding: .4rem .7rem;
            border: 1px solid #bbf7d0;
            border-radius: 999px;
            background: #f0fdf4;
            color: #166534;
            font-size: .72rem;
            font-weight: 900;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .masked-email {
            margin-top: 1rem;
            padding: .9rem 1rem;
            border: 1px solid #bbf7d0;
            border-radius: .8rem;
            background: #f0fdf4;
            color: #166534;
            font-size: 1rem;
            font-weight: 900;
            word-break: break-word;
        }

        form { margin-top: 1.25rem; }

        .field + .field { margin-top: 1rem; }

        label {
            display: block;
            margin-bottom: .42rem;
            color: #334155;
            font-size: .82rem;
            font-weight: 800;
        }

        input {
            width: 100%;
            padding: .85rem .95rem;
            border: 1px solid #cbd5e1;
            border-radius: .75rem;
            background: #fff;
            color: #111827;
            font: inherit;
        }

        input:focus {
            outline: 3px solid rgba(22, 107, 58, .14);
            border-color: #166b3a;
        }

        .otp-input {
            text-align: center;
            font-size: 1.45rem;
            font-weight: 900;
            letter-spacing: .35em;
        }

        .primary {
            width: 100%;
            margin-top: 1rem;
            border: 0;
            border-radius: .75rem;
            padding: .9rem 1rem;
            background: var(--dar-green);
            color: #fff;
            font: inherit;
            font-weight: 900;
            cursor: pointer;
        }

        .primary:hover { background: var(--dar-green-dark); }

        .secondary {
            width: 100%;
            border: 1px solid #d1d5db;
            border-radius: .75rem;
            padding: .75rem 1rem;
            background: #fff;
            color: #334155;
            font: inherit;
            font-weight: 800;
            cursor: pointer;
        }

        .status,
        .errors,
        .note {
            margin-top: 1rem;
            padding: .85rem 1rem;
            border-radius: .75rem;
            font-size: .84rem;
            line-height: 1.55;
        }

        .status {
            border: 1px solid #bbf7d0;
            background: #f0fdf4;
            color: #166534;
            font-weight: 750;
        }

        .errors {
            border: 1px solid #fecaca;
            background: #fef2f2;
            color: #991b1b;
        }

        .errors ul {
            margin: 0;
            padding-left: 1.1rem;
        }

        .note {
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            color: #64748b;
        }

        .link-row {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 1rem;
            flex-wrap: wrap;
        }

        .recovery-footer-action {
            border: 0;
            background: transparent;
            color: #166534;
            font-family: 'Google Sans', system-ui, sans-serif;
            font-size: 13px;
            font-style: normal;
            font-weight: 700;
            line-height: 20px;
            letter-spacing: 0;
            text-decoration: none;
            cursor: pointer;
            padding: 0;
        }

        .recovery-footer-action:hover { text-decoration: underline; }

        .inline-form { margin: 0; }

        @media (max-width: 640px) {
            .auth-card { padding: 1.4rem; }
            h1 { font-size: 1.45rem; }
        }
    </style>
</head>
<body>
    <main class="auth-page">
        <div class="auth-bg" aria-hidden="true"></div>

        <div class="auth-content">
            <section class="auth-card">
                <div class="brand">
                    <img src="{{ asset('images/dar-logo.svg') }}" alt="Department of Agrarian Reform logo">
                    <div>
                        <p class="brand-name">DAR-LTCMS</p>
                        <p class="brand-office">DAR Negros Oriental Provincial Office</p>
                    </div>
                </div>

                <h1>Recover Your Account</h1>
                <p class="intro">Use your username first. If the account has a registered email address, confirm it and receive a one-time verification code. Accounts without email remain eligible for DAR Staff-assisted password reset.</p>

                @if (session('status'))
                    <div class="status">{{ session('status') }}</div>
                @endif

                @if ($errors->any())
                    <div class="errors">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($step === 'confirm_email')
                    <span class="step-chip">Step 2 of 3 · Confirm Email</span>
                    <div class="masked-email">Registered email: {{ $recovery['masked_email'] ?? 'Unavailable' }}</div>
                    <p class="note">Enter the complete email address that matches the masked address above. The system will never display the full stored email.</p>

                    <form method="POST" action="{{ route('password.recovery.confirm-email') }}">
                        @csrf
                        <div class="field">
                            <label for="email">Registered Email Address</label>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email" placeholder="name@example.com">
                        </div>
                        <button type="submit" class="primary">Confirm Email and Send Code</button>
                    </form>

                    <div class="link-row">
                        <form method="POST" action="{{ route('password.recovery.restart') }}" class="inline-form">
                            @csrf
                            <button type="submit" class="link-button recovery-footer-action">Start over</button>
                        </form>
                        <a href="{{ route('login') }}" class="recovery-footer-action">Back to login</a>
                    </div>
                @elseif ($step === 'otp')
                    <span class="step-chip">Step 3 of 3 · Verify Code</span>
                    <div class="masked-email">Code sent to: {{ $recovery['masked_email'] ?? 'registered email' }}</div>
                    <p class="note">Enter the 6-digit code sent to your email. It expires after 10 minutes. Requesting a new code invalidates the previous one.</p>

                    <form method="POST" action="{{ route('password.recovery.verify-code') }}">
                        @csrf
                        <div class="field">
                            <label for="code">6-Digit Verification Code</label>
                            <input id="code" name="code" type="text" class="otp-input" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autofocus autocomplete="one-time-code" placeholder="000000">
                        </div>
                        <button type="submit" class="primary">Verify Code</button>
                    </form>

                    <form method="POST" action="{{ route('password.recovery.resend-code') }}">
                        @csrf
                        <button type="submit" class="secondary">Resend Code</button>
                    </form>

                    <div class="link-row">
                        <form method="POST" action="{{ route('password.recovery.restart') }}" class="inline-form">
                            @csrf
                            <button type="submit" class="link-button recovery-footer-action">Start over</button>
                        </form>
                        <a href="{{ route('login') }}" class="recovery-footer-action">Back to login</a>
                    </div>
                @else
                    <span class="step-chip">Step 1 of 3 · Identify Account</span>

                    <form method="POST" action="{{ route('password.recovery.identify') }}">
                        @csrf
                        <div class="field">
                            <label for="username">Username</label>
                            <input id="username" name="username" type="text" value="{{ old('username') }}" required autofocus autocomplete="username" placeholder="Enter your DAR-LTCMS username">
                        </div>
                        <button type="submit" class="primary">Continue</button>
                    </form>

                    <p class="note">If your account has no registered email address, the system will direct you to authorized DAR Staff for password assistance.</p>

                    <div class="link-row">
                        <a href="{{ route('login') }}" class="recovery-footer-action">Back to login</a>
                    </div>
                @endif
            </section>
        </div>
    </main>
</body>
</html>
