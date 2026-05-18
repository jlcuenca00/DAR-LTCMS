@props([
    'title' => 'Geodetic Dashboard',
    'subtitle' => null,
    'active' => 'dashboard',
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title }} | DAR-LTCMS</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:opsz,wght@17..18,400..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')

    <style>
        :root {
            --geo-green-950: #052e16;
            --geo-green-900: #14532d;
            --geo-green-800: #166534;
            --geo-green-700: #15803d;
            --geo-green-100: #dcfce7;
            --geo-green-50: #f0fdf4;
            --geo-ink: #07111f;
            --geo-text: #1f2937;
            --geo-muted: #667085;
            --geo-line: #d7ded9;
            --geo-soft-line: #e5e7eb;
            --geo-panel: #ffffff;
            --geo-bg: #f3f5f4;
            --heading-font: 'Google Sans', 'Product Sans', Arial, sans-serif;
            --body-font: 'Google Sans', 'Product Sans', Arial, sans-serif;
        }

        * { box-sizing: border-box; }

        html { min-height: 100%; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: var(--body-font);
            background: var(--geo-bg);
            color: var(--geo-text);
            text-rendering: optimizeLegibility;
        }

        a { color: inherit; }

        .geo-shell {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 250px minmax(0, 1fr);
            background: var(--geo-bg);
        }

        .geo-sidebar {
            position: sticky;
            top: 0;
            height: 100vh;
            background: #0f4b25;
            color: #ecfdf5;
            display: flex;
            flex-direction: column;
            border-right: 1px solid rgba(255, 255, 255, 0.08);
        }

        .geo-brand {
            min-height: 72px;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 18px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.09);
        }

        .geo-brand-mark {
            width: 42px;
            height: 42px;
            border-radius: 11px;
            background: #ffffff;
            display: grid;
            place-items: center;
            padding: 6px;
            flex: 0 0 auto;
            box-shadow: 0 10px 22px rgba(0, 0, 0, 0.16);
        }

        .geo-brand-logo {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }

        .geo-brand-fallback {
            font-size: 10px;
            font-weight: 900;
            color: var(--geo-green-900);
        }

        .geo-brand-title {
            margin: 0;
            font-family: var(--heading-font);
            font-size: 14px;
            font-weight: 900;
            letter-spacing: 0.01em;
            color: #ffffff;
        }

        .geo-brand-subtitle {
            margin: 3px 0 0;
            font-size: 11px;
            color: #bbf7d0;
        }

        .geo-side-section {
            padding: 18px 14px 8px;
        }

        .geo-side-section.account {
            margin-top: auto;
            padding-top: 12px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
        }

        .geo-side-label {
            padding: 0 10px;
            margin-bottom: 10px;
            font-size: 10px;
            font-weight: 900;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: #86efac;
        }

        .geo-nav {
            display: grid;
            gap: 5px;
        }

        .geo-nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            min-height: 42px;
            padding: 10px 12px;
            border-radius: 9px;
            color: #dcfce7;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: 160ms ease;
        }

        .geo-nav-link:hover {
            background: rgba(255, 255, 255, 0.10);
            color: #ffffff;
        }

        .geo-nav-link.active {
            background: rgba(255, 255, 255, 0.16);
            color: #ffffff;
            font-weight: 500;
        }

        .geo-nav-link i {
            width: 20px;
            text-align: center;
            font-size: 14px;
            opacity: 0.95;
        }

        .geo-sidebar-footer {
            padding: 16px 14px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
        }

        .geo-logout-button {
            width: 100%;
            display: flex;
            align-items: center;
            gap: 10px;
            border: 0;
            background: transparent;
            color: #dcfce7;
            padding: 10px 12px;
            border-radius: 9px;
            font: inherit;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-align: left;
        }

        .geo-logout-button:hover {
            background: rgba(255, 255, 255, 0.10);
            color: #ffffff;
        }

        .geo-main {
            min-width: 0;
            display: flex;
            flex-direction: column;
        }

        .geo-topbar {
            min-height: 74px;
            background: #ffffff;
            border-bottom: 1px solid var(--geo-line);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 16px 28px;
        }

        .geo-eyebrow {
            margin: 0;
            font-size: 11px;
            font-weight: 900;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--geo-green-800);
        }

        .geo-page-title {
            margin: 3px 0 0;
            font-family: var(--heading-font);
            font-size: 22px;
            line-height: 1.15;
            font-weight: 900;
            color: var(--geo-ink);
        }

        .geo-topbar > div:first-child {
            min-width: 0;
        }

        .geo-topbar-right {
            display: flex;
            align-items: center;
            gap: 16px;
            flex: 0 0 auto;
        }

        .geo-access-chip {
            border: 1px solid #bbf7d0;
            background: var(--geo-green-50);
            color: var(--geo-green-800);
            border-radius: 999px;
            padding: 6px 11px;
            font-size: 12px;
            font-weight: 900;
            white-space: nowrap;
        }

        .geo-user-chip {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            font-weight: 700;
            color: #374151;
            white-space: nowrap;
        }

        .geo-avatar {
            width: 36px;
            height: 36px;
            border-radius: 999px;
            background: var(--geo-green-800);
            color: white;
            display: grid;
            place-items: center;
            font-weight: 900;
        }

        .geo-content {
            padding: 26px 32px 38px;
            display: grid;
            gap: 20px;
        }

        .geo-scope-notice {
            display: flex;
            justify-content: space-between;
            gap: 22px;
            align-items: flex-start;
            border: 1px solid #bbf7d0;
            background: #effaf2;
            border-radius: 10px;
            padding: 14px 18px;
            color: #064e3b;
        }

        .geo-scope-notice h3 {
            margin: 0;
            font-family: var(--heading-font);
            font-size: 14px;
            font-weight: 900;
        }

        .geo-scope-notice p {
            margin: 5px 0 0;
            max-width: 980px;
            font-size: 12.5px;
            line-height: 1.55;
            font-weight: 700;
        }

        .geo-scope-pill {
            flex: 0 0 auto;
            border: 1px solid #bbf7d0;
            background: #dcfce7;
            color: var(--geo-green-800);
            border-radius: 999px;
            padding: 5px 11px;
            font-size: 11px;
            font-weight: 900;
            white-space: nowrap;
        }

        .geo-panel {
            background: #ffffff;
            border: 1px solid var(--geo-line);
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }

        .geo-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 38px;
            border-radius: 8px;
            padding: 0 14px;
            border: 1px solid var(--geo-line);
            background: #ffffff;
            color: #111827;
            text-decoration: none;
            font-size: 13px;
            font-weight: 900;
            transition: 160ms ease;
        }

        .geo-button:hover {
            background: #f8faf9;
            border-color: #c7d2cc;
        }

        .geo-button-primary {
            background: var(--geo-green-800);
            border-color: var(--geo-green-800);
            color: #ffffff;
        }

        .geo-button-primary:hover {
            background: var(--geo-green-900);
            border-color: var(--geo-green-900);
        }

        @media (max-width: 1100px) {
            .geo-shell { grid-template-columns: 1fr; }
            .geo-sidebar { position: static; height: auto; }
            .geo-nav { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .geo-side-section.account { margin-top: 0; }
        }

        @media (max-width: 760px) {
            .geo-topbar {
                height: auto;
                align-items: flex-start;
                flex-direction: column;
                padding: 18px;
            }

            .geo-topbar-right {
                width: 100%;
                justify-content: space-between;
                align-items: flex-start;
                flex-wrap: wrap;
            }

            .geo-content { padding: 18px; }
            .geo-nav { grid-template-columns: 1fr; }
            .geo-scope-notice { flex-direction: column; }
            .geo-scope-pill { align-self: flex-start; }
        }
    </style>
</head>

<body>
    <div class="geo-shell">
        <aside class="geo-sidebar">
            <div class="geo-brand">
                <div class="geo-brand-mark">
                    @if (file_exists(public_path('images/dar-logo.png')))
                        <img src="{{ asset('images/dar-logo.png') }}" alt="Department of Agrarian Reform Logo" class="geo-brand-logo">
                    @elseif (file_exists(public_path('images/dar-logo.svg')))
                        <img src="{{ asset('images/dar-logo.svg') }}" alt="Department of Agrarian Reform Logo" class="geo-brand-logo">
                    @else
                        <span class="geo-brand-fallback">DAR</span>
                    @endif
                </div>

                <div>
                    <p class="geo-brand-title">DAR LTCMS</p>
                    <p class="geo-brand-subtitle">Geodetic Portal</p>
                </div>
            </div>

            <div class="geo-side-section">
                <div class="geo-side-label">Reference Workspace</div>

                <nav class="geo-nav">
                    <a href="{{ route('geodetic.dashboard') }}" class="geo-nav-link {{ $active === 'dashboard' ? 'active' : '' }}">
                        <i class="fa-solid fa-gauge-high"></i>
                        Dashboard
                    </a>

                    <a href="{{ route('geodetic.parcel-map.index') }}" class="geo-nav-link {{ $active === 'parcel-map' ? 'active' : '' }}">
                        <i class="fa-solid fa-map-location-dot"></i>
                        Parcel Map
                    </a>

                    <a href="{{ route('geodetic.parcels.index') }}" class="geo-nav-link {{ $active === 'parcels' ? 'active' : '' }}">
                        <i class="fa-solid fa-draw-polygon"></i>
                        Parcel References
                    </a>
                </nav>
            </div>

            <div class="geo-side-section account">
                <div class="geo-side-label">Account</div>

                <nav class="geo-nav" aria-label="Account navigation">
                    <a href="{{ route('profile.edit') }}" class="geo-nav-link {{ $active === 'profile' ? 'active' : '' }}">
                        <i class="fa-solid fa-user-gear"></i>
                        Profile Settings
                    </a>
                </nav>
            </div>

            <div class="geo-sidebar-footer">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="geo-logout-button">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
                        Logout
                    </button>
                </form>
            </div>
        </aside>

        <main class="geo-main">
            <header class="geo-topbar">
                <div>
                    <p class="geo-eyebrow">DAR Negros Oriental Provincial Office</p>
                    <h1 class="geo-page-title">{{ $title }}</h1>
                </div>

                <div class="geo-topbar-right">
                    <div class="geo-access-chip">
                        Read-only Access
                    </div>

                    <div class="geo-user-chip">
                        <div class="geo-avatar">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                        <span>{{ auth()->user()->name }}</span>
                    </div>
                </div>
            </header>

            <div class="geo-content">
                <section class="geo-scope-notice">
                    <div>
                        <h3>Geodetic Access Scope</h3>
                        <p>
                            This portal is for read-only parcel, landholding, and map reference review. It does not edit ownership records, update parcel records, generate clearance outputs, or mutate registry records.
                        </p>
                    </div>

                    <span class="geo-scope-pill">Reference Review Only</span>
                </section>

                {{ $slot }}
            </div>
        </main>
    </div>

    @stack('scripts')
</body>
</html>
