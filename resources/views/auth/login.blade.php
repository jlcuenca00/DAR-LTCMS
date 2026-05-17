<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | DAR-LTCMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Google+Sans:opsz,wght@17..18,400..700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --dar-green: #166b3a;
            --dar-green-dark: #005326;
            --dar-yellow: #facc15;
            --text-dark: #111827;
            --text-muted: #6b7280;
            --border-soft: #dfe5e2;
            --font-body: 'Google Sans';
            --font-heading: 'Google Sans';
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Google Sans' !important;
            background: #f8faf9;
            overflow: hidden;
        }

        .login-page {
            position: relative;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .login-bg {
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

.login-bg::after {
    content: "";
    position: absolute;
    inset: 0;
    background: rgba(248, 250, 249, 0.08);
}

        .login-content {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 470px;
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

        .logo-image {
            max-width: 145px;
            max-height: 112px;
            object-fit: contain;
            display: block;
        }

        .login-card {
            width: 100%;
            background: rgba(255, 255, 255, 0.97);
            border: 1px solid var(--border-soft);
            border-radius: 0.7rem;
            box-shadow: 0 24px 65px rgba(15, 23, 42, 0.15);
            padding: 2.25rem;
        }

        .login-title {
    margin: 0;
    text-align: center;
    font-family: 'Google Sans' !important;
    font-size: 2.15rem;
    line-height: 1;
    font-weight: 800;
    letter-spacing: 0.18em;
    color: var(--text-dark);
    text-shadow:
        0.35px 0 0 currentColor,
        -0.35px 0 0 currentColor;
}

.login-subtitle,
.form-label, .login-office {
    font-family: 'Google Sans' !important;
}
        .login-subtitle {
            margin-top: 0.9rem;
            text-align: center;
            font-size: 0.82rem;
            color: #374151;
        }

        .login-office {
            margin-top: 0.25rem;
            text-align: center;
            font-size: 0.78rem;
            color: var(--text-muted);
        }

        .login-form {
            margin-top: 2rem;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-label {
            display: block;
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
        .password-field {
    position: relative;
}

.password-field .form-input {
    padding-right: 3rem;
}

.password-toggle {
    position: absolute;
    top: 50%;
    right: 0.85rem;
    transform: translateY(-50%);
    border: none;
    background: transparent;
    color: #6b7280;
    cursor: pointer;
    padding: 0.25rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.password-toggle:hover {
    color: #166534;
}

.password-toggle svg {
    width: 1.25rem;
    height: 1.25rem;
}

        .login-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.25rem;
            font-size: 0.82rem;
        }

        .remember-label {
    display: inline-flex;
    align-items: center;
    gap: 0.55rem;
    color: #4b5563;
    cursor: pointer;
    user-select: none;
}

.remember-label {
    display: inline-flex;
    align-items: center;
    gap: 0.55rem;
    color: #4b5563;
    cursor: pointer;
    user-select: none;
}

.remember-checkbox {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.remember-control {
    width: 1.05rem;
    height: 1.05rem;
    border: 1.8px solid #9ca3af;
    border-radius: 999px;
    background: #ffffff;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: 150ms ease;
    flex-shrink: 0;
}

.remember-control::after {
    content: "";
    width: 0.45rem;
    height: 0.45rem;
    border-radius: 999px;
    background: #ffffff;
    transform: scale(0);
    transition: 120ms ease;
}

.remember-label:hover .remember-control {
    border-color: #166534;
}

.remember-checkbox:checked + .remember-control {
    background: #166534;
    border-color: #166534;
}

.remember-checkbox:checked + .remember-control::after {
    transform: scale(1);
}

.remember-checkbox:focus + .remember-control,
.remember-checkbox:focus-visible + .remember-control {
    border-color: #166534;
    box-shadow: 0 0 0 3px rgba(22, 101, 52, 0.18);
}

.remember-checkbox:active + .remember-control,
.remember-checkbox:checked:active + .remember-control {
    background: #166534;
    border-color: #166534;
}

        .forgot-link {
            color: #166534;
            font-weight: 800;
            text-decoration: none;
        }

        .forgot-link:hover {
            text-decoration: underline;
        }

        .login-button {
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

        .login-button:hover {
            background: var(--dar-green-dark);
        }

        .login-footer {
            margin-top: 2rem;
            text-align: center;
            font-size: 0.78rem;
            line-height: 1.5;
            color: #9ca3af;
        }

        .error-box {
            margin-top: 1.25rem;
            border: 1px solid #fecaca;
            background: #fef2f2;
            color: #991b1b;
            border-radius: 0.5rem;
            padding: 0.85rem;
            font-size: 0.85rem;
        }

        @media (max-width: 768px) {
            body {
                overflow: auto;
            }

            .login-page {
                align-items: flex-start;
                padding: 2rem 1rem;
            }

            .login-content {
                margin-top: 0;
            }

            .login-card {
                padding: 1.5rem;
            }

            .login-title {
                font-size: 1.55rem;
                letter-spacing: 0.16em;
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
    <main class="login-page">
        <div class="login-bg" aria-hidden="true"></div>

        <div class="login-content">
            <div class="logo-slot">
                @if (file_exists(public_path('images/dar-logo.svg')))
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

            <section class="login-card">
                <h1 class="login-title">
                    DAR-LTCMS
                </h1>

                <p class="login-subtitle">
                    Land Transfer Clearance and Monitoring System
                </p>

                <p class="login-office">
                    DAR Negros Oriental Provincial Office
                </p>

                @if ($errors->any())
                    <div class="error-box">
                        <ul style="margin: 0; padding-left: 1.1rem;">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if (session('status'))
                    <div class="error-box" style="border-color: #bbf7d0; background: #f0fdf4; color: #166534;">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="login-form">
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

                    <div class="form-group">
    <label for="password" class="form-label">
        Password
    </label>

    <div class="password-field">
        <input
            id="password"
            class="form-input"
            type="password"
            name="password"
            placeholder="Enter your password"
            required
            autocomplete="current-password"
        >

        <button
            type="button"
            class="password-toggle"
            id="toggle-password"
            aria-label="Show password"
        >
            <svg id="eye-open" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>

            <svg id="eye-closed" xmlns="http://www.w3.org/2000/svg" class="hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.956 9.956 0 012.223-3.592m3.31-2.13A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.542 7a9.973 9.973 0 01-4.132 5.236M15 12a3 3 0 00-3-3m0 0a3 3 0 00-3 3m3-3l9 9M3 3l18 18" />
            </svg>
        </button>
    </div>
</div>

                    <div class="login-options">
                        <label class="remember-label">
    <input
        type="checkbox"
        name="remember"
        class="remember-checkbox"
    >

    <span class="remember-control" aria-hidden="true"></span>

    <span>Remember me</span>
</label>

                        @if (Route::has('password.request'))
                            <a class="forgot-link" href="{{ route('password.request') }}">
                                Forgot password?
                            </a>
                        @endif
                    </div>

                    <button type="submit" class="login-button">
                        Login
                    </button>
                </form>

                <div class="login-footer">
                    © {{ now()->year }} Department of Agrarian Reform<br>
                    Negros Oriental Provincial Office
                </div>
            </section>
        </div>
    </main>
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        const passwordInput = document.getElementById('password');
        const toggleButton = document.getElementById('toggle-password');
        const eyeOpen = document.getElementById('eye-open');
        const eyeClosed = document.getElementById('eye-closed');

        toggleButton.addEventListener('click', function () {
            const isHidden = passwordInput.type === 'password';

            passwordInput.type = isHidden ? 'text' : 'password';

            eyeOpen.classList.toggle('hidden', isHidden);
            eyeClosed.classList.toggle('hidden', !isHidden);

            toggleButton.setAttribute(
                'aria-label',
                isHidden ? 'Hide password' : 'Show password'
            );
        });
    });
</script>
</body>
</html>