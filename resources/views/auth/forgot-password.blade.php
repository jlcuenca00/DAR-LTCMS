<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot Password | DAR-LTCMS</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:opsz,wght@17..18,400..700&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --dar-green: #166b3a;
            --dar-green-dark: #005326;
            --text-dark: #111827;
            --text-muted: #6b7280;
            --border-soft: #dfe5e2;
            --font-body: 'Inter', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            --font-heading: 'Google Sans', 'Product Sans', 'Inter', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: var(--font-body) !important;
            background: #f8faf9;
            overflow: hidden;
        }

        .recovery-page {
            position: relative;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .recovery-bg {
            position: absolute;
            inset: 0;
            z-index: 0;
            overflow: hidden;
            pointer-events: none;
            background-image: url("{{ asset('images/login-bg.png') }}");
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-color: #f8faf9;
        }

        .recovery-bg::after {
            content: "";
            position: absolute;
            inset: 0;
            background: rgba(248, 250, 249, 0.08);
        }

        .recovery-content {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 455px;
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-top: -1.5rem;
        }

        .logo-slot {
            width: 145px;
            height: 112px;
            margin-bottom: 1.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo-image {
            max-width: 145px;
            max-height: 112px;
            object-fit: contain;
            display: block;
        }

        .logo-placeholder {
            width: 128px;
            height: 96px;
            border: 2px dashed rgba(22, 107, 58, 0.45);
            border-radius: 0.75rem;
            background: rgba(255, 255, 255, 0.78);
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: var(--dar-green);
            font-size: 0.75rem;
            font-weight: 900;
            letter-spacing: 0.12em;
            line-height: 1.3;
        }

        .recovery-card {
            width: 100%;
            background: rgba(255, 255, 255, 0.97);
            border: 1px solid var(--border-soft);
            border-radius: 0.7rem;
            box-shadow: 0 24px 65px rgba(15, 23, 42, 0.15);
            padding: 2.25rem;
        }

        .system-title {
            margin: 0;
            text-align: center;
            font-family: var(--font-heading) !important;
            font-size: 1.85rem;
            line-height: 1;
            font-weight: 800;
            letter-spacing: 0.16em;
            color: var(--text-dark);
            text-shadow:
                0.35px 0 0 currentColor,
                -0.35px 0 0 currentColor;
        }

        .system-subtitle {
            margin-top: 0.85rem;
            text-align: center;
            font-family: var(--font-heading) !important;
            font-size: 0.82rem;
            color: #374151;
        }

        .system-office {
            margin-top: 0.25rem;
            text-align: center;
            font-family: var(--font-heading) !important;
            font-size: 0.78rem;
            color: var(--text-muted);
        }

        .recovery-divider {
            height: 1px;
            background: #e5e7eb;
            margin: 1.55rem 0 1.45rem;
        }

        .recovery-heading {
            margin: 0;
            font-family: var(--font-heading) !important;
            font-size: 1.05rem;
            font-weight: 800;
            color: #111827;
        }

        .recovery-text {
            margin: 0.55rem 0 0;
            font-size: 0.86rem;
            line-height: 1.65;
            color: #4b5563;
        }

        .recovery-form {
            margin-top: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
            font-family: var(--font-heading) !important;
            font-size: 0.92rem;
            font-weight: 800;
            color: #1f2937;
            margin-bottom: 0.45rem;
        }

        .form-input {
            width: 100%;
            border: 1px solid #cbd5d1;
            border-radius: 0.45rem;
            padding: 0.85rem 0.95rem;
            font-size: 0.92rem;
            color: var(--text-dark);
            background: #ffffff;
            outline: none;
            transition: 150ms ease;
        }

        .form-input:focus {
            border-color: #15803d;
            box-shadow: 0 0 0 3px rgba(21, 128, 61, 0.14);
        }

        .recovery-button {
            width: 100%;
            border: none;
            border-radius: 0.45rem;
            background: #006b2e;
            color: #ffffff;
            padding: 0.9rem 1rem;
            font-size: 0.95rem;
            font-weight: 900;
            cursor: pointer;
            transition: 150ms ease;
        }

        .recovery-button:hover {
            background: var(--dar-green-dark);
        }

        .back-link {
            display: inline-flex;
            justify-content: center;
            width: 100%;
            margin-top: 1rem;
            color: #166534;
            font-size: 0.84rem;
            font-weight: 800;
            text-decoration: none;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .status-box {
            margin-top: 1.2rem;
            border: 1px solid #bbf7d0;
            background: #f0fdf4;
            color: #166534;
            border-radius: 0.5rem;
            padding: 0.85rem;
            font-size: 0.85rem;
            line-height: 1.5;
        }

        .error-box {
            margin-top: 1.2rem;
            border: 1px solid #fecaca;
            background: #fef2f2;
            color: #991b1b;
            border-radius: 0.5rem;
            padding: 0.85rem;
            font-size: 0.85rem;
            line-height: 1.5;
        }

        .recovery-footer {
            margin-top: 2rem;
            text-align: center;
            font-size: 0.78rem;
            line-height: 1.5;
            color: #9ca3af;
        }

        @media (max-width: 768px) {
            body {
                overflow: auto;
            }

            .recovery-page {
                align-items: flex-start;
                padding: 2rem 1rem;
            }

            .recovery-content {
                margin-top: 0;
            }

            .recovery-card {
                padding: 1.5rem;
            }

            .system-title {
                font-size: 1.45rem;
                letter-spacing: 0.15em;
            }

            .logo-slot {
                width: 120px;
                height: 92px;
                margin-bottom: 1.25rem;
            }

            .logo-placeholder {
                width: 112px;
                height: 82px;
            }
        }
    </style>
</head>

<body>
    <main class="recovery-page">
        <div class="recovery-bg" aria-hidden="true"></div>

        <div class="recovery-content">
            <div class="logo-slot">
                @if (file_exists(public_path('images/dar-logo.png')))
                    <img
                        src="{{ asset('images/dar-logo.png') }}"
                        alt="Department of Agrarian Reform Logo"
                        class="logo-image"
                    >
                @elseif (file_exists(public_path('images/dar-logo.svg')))
                    <img
                        src="{{ asset('images/dar-logo.svg') }}"
                        alt="Department of Agrarian Reform Logo"
                        class="logo-image"
                    >
                @else
                    <div class="logo-placeholder">
                        DAR<br>LOGO
                    </div>
                @endif
            </div>

            <section class="recovery-card">
                <h1 class="system-title">
                    DAR-LTCMS
                </h1>

                <p class="system-subtitle">
                    Land Transfer Clearance and Monitoring System
                </p>

                <p class="system-office">
                    DAR Negros Oriental Provincial Office
                </p>

                <div class="recovery-divider"></div>

                <h2 class="recovery-heading">
                    Password Recovery
                </h2>

                <p class="recovery-text">
                    Enter the email address assigned to your account. If the address is registered, the system will send a password reset link for account recovery.
                </p>

                @if (session('status'))
                    <div class="status-box">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="error-box">
                        <ul style="margin: 0; padding-left: 1.1rem;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('password.email') }}" class="recovery-form">
                    @csrf

                    <div class="form-group">
                        <label for="email" class="form-label">
                            Email
                        </label>

                        <input
                            id="email"
                            class="form-input"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            placeholder="Enter your email"
                            required
                            autofocus
                            autocomplete="username"
                        >
                    </div>

                    <button type="submit" class="recovery-button">
                        Email Password Reset Link
                    </button>

                    <a href="{{ route('login') }}" class="back-link">
                        Return to login
                    </a>
                </form>

                <div class="recovery-footer">
                    © {{ now()->year }} Department of Agrarian Reform<br>
                    Negros Oriental Provincial Office
                </div>
            </section>
        </div>
    </main>
</body>
</html>