@php
    $passwordRecoveryVerified = $passwordRecoveryVerified ?? false;
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $passwordRecoveryVerified ? 'Reset Password' : 'Change Temporary Password' }} | DAR-LTCMS</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:opsz,wght@17..18,400..700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: 'Google Sans', system-ui, sans-serif;
            color: #0f172a;
            background: #f4f7f5;
        }
        .page {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 2rem 1rem;
            background:
                linear-gradient(rgba(244, 247, 245, .78), rgba(244, 247, 245, .92)),
                url("{{ asset('images/login-bg.png') }}") center / cover no-repeat;
        }
        .card {
            width: min(100%, 480px);
            padding: 2rem;
            border: 1px solid #d7e1db;
            border-radius: 1.25rem;
            background: rgba(255, 255, 255, .97);
            box-shadow: 0 24px 60px rgba(15, 23, 42, .12);
        }
        .brand {
            display: flex;
            align-items: center;
            gap: .9rem;
            padding-bottom: 1.25rem;
            border-bottom: 1px solid #e5e7eb;
        }
        .brand img { width: 52px; height: 52px; object-fit: contain; }
        .brand-name { margin: 0; font-size: 1.05rem; font-weight: 800; color: #075c2c; }
        .brand-office { margin: .2rem 0 0; font-size: .8rem; color: #64748b; }
        h1 { margin: 1.5rem 0 .45rem; font-size: 1.65rem; line-height: 1.2; }
        .intro { margin: 0; color: #64748b; line-height: 1.6; }
        .username {
            margin-top: 1rem;
            padding: .8rem 1rem;
            border: 1px solid #bbf7d0;
            border-radius: .8rem;
            background: #f0fdf4;
            color: #166534;
            font-size: .9rem;
            font-weight: 700;
        }
        form { margin-top: 1.4rem; }
        .field + .field { margin-top: 1rem; }
        label { display: block; margin-bottom: .4rem; font-size: .82rem; font-weight: 700; color: #334155; }
        input {
            width: 100%;
            padding: .8rem .9rem;
            border: 1px solid #cbd5e1;
            border-radius: .75rem;
            font: inherit;
            background: #fff;
        }
        input:focus { outline: 3px solid rgba(22, 107, 58, .15); border-color: #166b3a; }
        .error { margin-top: .35rem; color: #b91c1c; font-size: .8rem; }
        .note { margin: 1rem 0 0; color: #64748b; font-size: .8rem; line-height: 1.55; }
        .actions { display: flex; align-items: center; gap: .75rem; margin-top: 1.3rem; }
        .primary {
            flex: 1;
            border: 0;
            border-radius: .75rem;
            padding: .85rem 1rem;
            background: #166b3a;
            color: #fff;
            font: inherit;
            font-weight: 800;
            cursor: pointer;
        }
        .primary:hover { background: #0f5b31; }
        .logout { margin: 0; }
        .logout button {
            border: 1px solid #d1d5db;
            border-radius: .75rem;
            padding: .8rem 1rem;
            background: #fff;
            color: #334155;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <main class="page">
        <section class="card">
            <div class="brand">
                <img src="{{ asset('images/dar-logo.svg') }}" alt="Department of Agrarian Reform logo">
                <div>
                    <p class="brand-name">DAR-LTCMS</p>
                    <p class="brand-office">DAR Negros Oriental Provincial Office</p>
                </div>
            </div>

            <h1>Create a New Password</h1>
            <p class="intro">
                @if ($passwordRecoveryVerified)
                    Your registered email and verification code were confirmed. Create a new private password to complete account recovery.
                @else
                    Your account is using a temporary password. Create a private password before continuing to the system.
                @endif
            </p>
            <div class="username">Username: {{ auth()->user()->username }}</div>

            <form method="POST" action="{{ route('password.required.update') }}">
                @csrf
                @method('PUT')

                <div class="field">
                    <label for="password">New Password</label>
                    <input id="password" name="password" type="password" required autofocus autocomplete="new-password">
                    @error('password', 'forcedPassword')
                        <div class="error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="field">
                    <label for="password_confirmation">Confirm New Password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password">
                </div>

                <x-password-requirements password-id="password" confirmation-id="password_confirmation" />

                <p class="note">
                    Use at least eight characters with uppercase, lowercase, a number, and a symbol.
                    {{ $passwordRecoveryVerified ? 'Do not reuse your current password.' : 'Do not reuse the temporary password.' }}
                    Never share your password with staff.
                </p>

                <div class="actions">
                    <button type="submit" class="primary">Change Password and Continue</button>
                </div>
            </form>

            <form method="POST" action="{{ route('logout') }}" class="logout" style="margin-top: .75rem;">
                @csrf
                <button type="submit" style="width: 100%;">Sign Out</button>
            </form>
        </section>
    </main>
</body>
</html>
