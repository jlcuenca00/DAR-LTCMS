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

    @include('auth.partials.styles')
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
                        <label for="username" class="form-label">
                            Username
                        </label>

                        <input
                            id="username"
                            class="form-input"
                            type="text"
                            name="username"
                            value="{{ old('username') }}"
                            placeholder="Enter your username"
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

                @if ($googleClientId)
                    <div class="auth-divider" aria-hidden="true">
                        <span></span>
                        <span class="auth-divider-text">or</span>
                        <span></span>
                    </div>

                    <div class="google-signin-wrap">
                        <script src="https://accounts.google.com/gsi/client?hl=en" async defer></script>
                        <div id="g_id_onload"
                            data-client_id="{{ $googleClientId }}"
                            data-callback="handleGoogleLogin"
                            data-ux_mode="popup"
                            data-auto_prompt="false">
                        </div>
                        <div class="g_id_signin"
                            data-type="standard"
                            data-size="large"
                            data-theme="outline"
                            data-text="continue_with"
                            data-shape="pill"
                            data-locale="en"
                            data-logo_alignment="left">
                        </div>
                        <form id="google-login-form" method="POST" action="{{ route('register.google') }}" style="display: none;">
                            @csrf
                            <input id="google-login-credential" type="hidden" name="credential">
                            <input type="hidden" name="intent" value="login">
                        </form>
                    </div>
                @endif

                <div class="registration-prompt">
                    <span class="registration-copy">New landowner?</span>
                    <a href="{{ route('register') }}" class="registration-link">
                        Create an account
                    </a>
                </div>

                <div class="login-footer">
                    @include('auth.partials.legal-links')
                    © {{ now()->year }} Department of Agrarian Reform<br>
                    Negros Oriental Provincial Office
                </div>
            </section>
        </div>
    </main>
    <script>
    window.handleGoogleLogin = function (response) {
        document.getElementById('google-login-credential').value = response.credential;
        document.getElementById('google-login-form').submit();
    };

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
