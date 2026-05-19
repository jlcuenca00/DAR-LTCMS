@props([
    'title' => 'Landowner Portal',
    'active' => 'dashboard',
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title }} | DAR-LTCMS</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
        <link rel="shortcut icon" type="image/png" href="{{ asset('images/favicon.png') }}">
        <link rel="apple-touch-icon" href="{{ asset('images/favicon.png') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:opsz,wght@17..18,400..700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')

    <style>
        :root {
            --lo-green-950: #052e16;
            --lo-green-900: #14532d;
            --lo-green-800: #166534;
            --lo-green-700: #15803d;
            --lo-green-100: #dcfce7;
            --lo-green-50: #f0fdf4;
            --lo-ink: #07111f;
            --lo-text: #1f2937;
            --lo-muted: #667085;
            --lo-line: #d7ded9;
            --lo-soft-line: #e5e7eb;
            --lo-panel: #ffffff;
            --lo-bg: #f3f5f4;
            --heading-font: 'Google Sans', 'Product Sans', Arial, sans-serif;
            --body-font: 'Google Sans', 'Product Sans', Arial, sans-serif;
        }

        * { box-sizing: border-box; }

        html { min-height: 100%; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: var(--body-font);
            background: var(--lo-bg);
            color: var(--lo-text);
            text-rendering: optimizeLegibility;
        }

        a,
        button,
        input,
        select,
        textarea {
            font-family: inherit;
        }

        a { color: inherit; }

        .lo-shell {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 250px minmax(0, 1fr);
            background: var(--lo-bg);
        }

        .lo-sidebar {
            position: sticky;
            top: 0;
            height: 100vh;
            background: #0f4b25;
            color: #ecfdf5;
            display: flex;
            flex-direction: column;
            border-right: 1px solid rgba(255, 255, 255, 0.08);
            overflow-y: auto;
            z-index: 30;
        }

        .lo-brand {
            min-height: 72px;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 18px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.09);
        }

        .lo-brand-mark {
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

        .lo-brand-logo {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }

        .lo-brand-fallback {
            font-size: 10px;
            font-weight: 900;
            color: var(--lo-green-900);
        }

        .lo-brand-title {
            margin: 0;
            font-family: var(--heading-font);
            font-size: 14px;
            font-weight: 900;
            letter-spacing: 0.01em;
            color: #ffffff;
        }

        .lo-brand-subtitle {
            margin: 3px 0 0;
            font-size: 11px;
            color: #bbf7d0;
        }

        .lo-side-section {
            padding: 18px 14px 8px;
        }

        .lo-side-section.account {
            margin-top: auto;
            padding-top: 12px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
        }

        .lo-side-label {
            padding: 0 10px;
            margin-bottom: 10px;
            font-size: 10px;
            font-weight: 900;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: #86efac;
        }

        .lo-nav {
            display: grid;
            gap: 5px;
        }

        .lo-nav-link {
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
            line-height: 1.25;
            transition: 160ms ease;
        }

        .lo-nav-link:hover {
            background: rgba(255, 255, 255, 0.10);
            color: #ffffff;
        }

        .lo-nav-link.active {
            background: rgba(255, 255, 255, 0.16);
            color: #ffffff;
            font-weight: 500;
        }

        .lo-nav-link i {
            width: 20px;
            text-align: center;
            font-size: 14px;
            opacity: 0.95;
        }

        .lo-sidebar-footer {
            padding: 16px 14px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
        }

        .lo-logout-button {
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

        .lo-logout-button:hover {
            background: rgba(255, 255, 255, 0.10);
            color: #ffffff;
        }

        .lo-main {
            min-width: 0;
            display: flex;
            flex-direction: column;
        }

        .lo-topbar {
            min-height: 74px;
            background: #ffffff;
            border-bottom: 1px solid var(--lo-line);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 16px 28px;
        }

        .lo-eyebrow {
            margin: 0;
            font-size: 11px;
            font-weight: 900;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--lo-green-800);
        }

        .lo-page-title {
            margin: 3px 0 0;
            font-family: var(--heading-font);
            font-size: 22px;
            line-height: 1.15;
            font-weight: 900;
            color: var(--lo-ink);
        }

        .lo-topbar > div:first-child {
            min-width: 0;
        }

        .lo-topbar-right {
            display: flex;
            align-items: center;
            gap: 16px;
            flex: 0 0 auto;
        }

        .lo-access-chip {
            border: 1px solid #bbf7d0;
            background: var(--lo-green-50);
            color: var(--lo-green-800);
            border-radius: 999px;
            padding: 6px 11px;
            font-size: 12px;
            font-weight: 900;
            white-space: nowrap;
        }


        .notification-bell-link {
            position: relative;
            width: 38px;
            height: 38px;
            border-radius: 999px;
            border: 1px solid #bbf7d0;
            background: var(--lo-green-50);
            color: var(--lo-green-800);
            display: inline-grid;
            place-items: center;
            text-decoration: none;
            font-size: 15px;
        }

        .notification-bell-link:hover {
            background: var(--lo-green-100);
        }

        .notification-badge {
            position: absolute;
            top: -6px;
            right: -6px;
            min-width: 18px;
            height: 18px;
            padding: 0 5px;
            border-radius: 999px;
            background: #dc2626;
            color: #ffffff;
            border: 2px solid #ffffff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: 900;
            line-height: 1;
        }


        .notification-dropdown {
            position: relative;
            display: inline-block;
        }

        .notification-dropdown > summary {
            list-style: none;
            cursor: pointer;
        }

        .notification-dropdown > summary::-webkit-details-marker {
            display: none;
        }

        .notification-dropdown[open] .notification-bell-link {
            background: #dcfce7;
            color: #14532d;
            box-shadow: 0 0 0 3px rgba(22, 101, 52, 0.12);
        }

        .notification-dropdown-panel {
            position: absolute;
            top: calc(100% + 12px);
            right: 0;
            z-index: 80;
            width: min(380px, calc(100vw - 32px));
            overflow: hidden;
            border-radius: 18px;
            border: 1px solid #d9dee5;
            background: #ffffff;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.18);
        }

        .notification-dropdown-panel::before {
            content: '';
            position: absolute;
            top: -7px;
            right: 18px;
            width: 14px;
            height: 14px;
            transform: rotate(45deg);
            border-left: 1px solid #d9dee5;
            border-top: 1px solid #d9dee5;
            background: #ffffff;
        }

        .notification-dropdown-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            padding: 16px 16px 13px;
            border-bottom: 1px solid #eef2f7;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        }

        .notification-dropdown-kicker {
            margin: 0 0 3px;
            font-size: 10px;
            font-weight: 900;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: #15803d;
        }

        .notification-dropdown-header h2 {
            margin: 0;
            font-size: 15px;
            font-weight: 900;
            color: #111827;
        }

        .notification-dropdown-count {
            flex: 0 0 auto;
            border-radius: 999px;
            background: #dcfce7;
            padding: 5px 9px;
            font-size: 11px;
            font-weight: 900;
            color: #166534;
        }

        .notification-dropdown-count.is-clear {
            background: #f1f5f9;
            color: #64748b;
        }

        .notification-dropdown-list {
            max-height: 310px;
            overflow-y: auto;
            background: #ffffff;
        }

        .notification-dropdown-item {
            display: grid;
            grid-template-columns: 10px minmax(0, 1fr);
            gap: 11px;
            padding: 13px 16px;
            border-bottom: 1px solid #f1f5f9;
            color: inherit;
            text-decoration: none;
            transition: 150ms ease;
        }

        .notification-dropdown-item:hover {
            background: #f8fafc;
        }

        .notification-dropdown-item.is-unread {
            background: #f0fdf4;
        }

        .notification-dropdown-item.is-unread:hover {
            background: #dcfce7;
        }

        .notification-dropdown-dot {
            width: 8px;
            height: 8px;
            margin-top: 6px;
            border-radius: 999px;
            background: #cbd5e1;
        }

        .notification-dropdown-item.is-unread .notification-dropdown-dot {
            background: #16a34a;
            box-shadow: 0 0 0 4px rgba(22, 163, 74, 0.12);
        }

        .notification-dropdown-copy {
            min-width: 0;
        }

        .notification-dropdown-title-row {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
        }

        .notification-dropdown-title-row strong {
            color: #0f172a;
            font-size: 13px;
            font-weight: 900;
            line-height: 1.3;
        }

        .notification-dropdown-title-row i {
            margin-top: 2px;
            color: #94a3b8;
            font-size: 11px;
        }

        .notification-dropdown-copy p {
            margin: 4px 0 0;
            color: #475569;
            font-size: 12px;
            font-weight: 650;
            line-height: 1.45;
        }

        .notification-dropdown-copy span {
            display: block;
            margin-top: 6px;
            color: #94a3b8;
            font-size: 11px;
            font-weight: 800;
        }

        .notification-dropdown-empty {
            display: grid;
            place-items: center;
            gap: 5px;
            padding: 26px 18px;
            text-align: center;
            color: #64748b;
        }

        .notification-dropdown-empty i {
            font-size: 20px;
            color: #15803d;
        }

        .notification-dropdown-empty strong {
            color: #0f172a;
            font-size: 13px;
            font-weight: 900;
        }

        .notification-dropdown-empty span {
            font-size: 12px;
            font-weight: 650;
        }

        .notification-see-all {
            display: flex;
            min-height: 44px;
            align-items: center;
            justify-content: center;
            gap: 8px;
            border-top: 1px solid #eef2f7;
            background: #ffffff;
            color: #166534;
            text-decoration: none;
            font-size: 13px;
            font-weight: 900;
        }

        .notification-see-all:hover {
            background: #f0fdf4;
            color: #14532d;
        }

        .lo-user-chip {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            font-weight: 700;
            color: #374151;
            white-space: nowrap;
            min-width: 0;
        }

        .lo-avatar {
            width: 36px;
            height: 36px;
            border-radius: 999px;
            background: var(--lo-green-800);
            color: white;
            display: grid;
            place-items: center;
            font-weight: 900;
            flex: 0 0 auto;
        }

        .lo-user-name {
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .lo-content {
            padding: 26px 32px 38px;
            display: grid;
            gap: 20px;
        }

        .lo-scope-notice {
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

        .lo-scope-notice h3 {
            margin: 0;
            font-family: var(--heading-font);
            font-size: 14px;
            font-weight: 900;
        }

        .lo-scope-notice p {
            margin: 5px 0 0;
            max-width: 980px;
            font-size: 12.5px;
            line-height: 1.55;
            font-weight: 700;
        }

        .lo-scope-pill {
            flex: 0 0 auto;
            border: 1px solid #bbf7d0;
            background: #dcfce7;
            color: var(--lo-green-800);
            border-radius: 999px;
            padding: 5px 11px;
            font-size: 11px;
            font-weight: 900;
            white-space: nowrap;
        }

        .lo-panel {
            background: #ffffff;
            border: 1px solid var(--lo-line);
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }

        .lo-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-height: 38px;
            border-radius: 8px;
            padding: 0 14px;
            border: 1px solid var(--lo-line);
            background: #ffffff;
            color: #111827;
            text-decoration: none;
            font-size: 13px;
            font-weight: 900;
            transition: 160ms ease;
        }

        .lo-button:hover {
            background: #f8faf9;
            border-color: #c7d2cc;
        }

        .lo-button-primary {
            background: var(--lo-green-800);
            border-color: var(--lo-green-800);
            color: #ffffff;
        }

        .lo-button-primary:hover {
            background: var(--lo-green-900);
            border-color: var(--lo-green-900);
        }

        @media (max-width: 1100px) {
            .lo-shell { grid-template-columns: 1fr; }
            .lo-sidebar { position: static; height: auto; }
            .lo-nav { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .lo-side-section.account { margin-top: 0; }
        }

        @media (max-width: 760px) {
            .lo-topbar {
                height: auto;
                align-items: flex-start;
                flex-direction: column;
                padding: 18px;
            }

            .lo-topbar-right {
                width: 100%;
                justify-content: space-between;
                align-items: flex-start;
                flex-wrap: wrap;
            }

            .lo-content { padding: 18px; }
            .lo-nav { grid-template-columns: 1fr; }
            .lo-scope-notice { flex-direction: column; }
            .lo-scope-pill { align-self: flex-start; }
        }
    </style>
</head>

<body>
    @php
        $notificationUser = auth()->user();
        $notificationUnreadCount = $notificationUser?->unreadSystemNotifications()->count() ?? 0;
        $recentSystemNotifications = $notificationUser?->systemNotifications()->latest()->limit(5)->get() ?? collect();
    @endphp
    <div class="lo-shell">
        <aside class="lo-sidebar">
            <div class="lo-brand">
                <div class="lo-brand-mark">
                    @if (file_exists(public_path('images/dar-logo.png')))
                        <img src="{{ asset('images/dar-logo.png') }}" alt="Department of Agrarian Reform Logo" class="lo-brand-logo">
                    @elseif (file_exists(public_path('images/dar-logo.svg')))
                        <img src="{{ asset('images/dar-logo.svg') }}" alt="Department of Agrarian Reform Logo" class="lo-brand-logo">
                    @else
                        <span class="lo-brand-fallback">DAR</span>
                    @endif
                </div>

                <div>
                    <p class="lo-brand-title">DAR LTCMS</p>
                    <p class="lo-brand-subtitle">Landowner Portal</p>
                </div>
            </div>

            <div class="lo-side-section">
                <div class="lo-side-label">My Workspace</div>

                <nav class="lo-nav" aria-label="Landowner portal navigation">
                    <a href="{{ route('landowner.dashboard') }}" class="lo-nav-link {{ $active === 'dashboard' ? 'active' : '' }}">
                        <i class="fa-solid fa-gauge-high"></i>
                        Dashboard
                    </a>

                    <a href="{{ route('landowner.parcel-map.index') }}" class="lo-nav-link {{ $active === 'parcel-map' ? 'active' : '' }}">
                        <i class="fa-solid fa-map-location-dot"></i>
                        My Parcel Map
                    </a>

                    <a href="{{ route('landowner.parcels.index') }}" class="lo-nav-link {{ $active === 'parcels' ? 'active' : '' }}">
                        <i class="fa-solid fa-draw-polygon"></i>
                        My Parcel Records
                    </a>

                    <a href="{{ route('landowner.applications.index') }}" class="lo-nav-link {{ $active === 'applications' ? 'active' : '' }}">
                        <i class="fa-solid fa-file-lines"></i>
                        My Applications
                    </a>
                </nav>
            </div>

            <div class="lo-side-section account">
                <div class="lo-side-label">Account</div>

                <nav class="lo-nav" aria-label="Account navigation">
                    <a href="{{ route('profile.edit') }}" class="lo-nav-link {{ $active === 'profile' ? 'active' : '' }}">
                        <i class="fa-solid fa-user-gear"></i>
                        Profile Settings
                    </a>
                </nav>
            </div>

            <div class="lo-sidebar-footer">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="lo-logout-button">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
                        Logout
                    </button>
                </form>
            </div>
        </aside>

        <main class="lo-main">
            <header class="lo-topbar">
                <div>
                    <p class="lo-eyebrow">DAR Negros Oriental Provincial Office</p>
                    <h1 class="lo-page-title">{{ $title }}</h1>
                </div>

                <div class="lo-topbar-right">

                    <details class="notification-dropdown">
                        <summary class="notification-bell-link" aria-label="Open recent notifications">
                            <i class="fa-solid fa-bell"></i>
                            @if ($notificationUnreadCount > 0)
                                <span class="notification-badge">{{ $notificationUnreadCount > 99 ? '99+' : $notificationUnreadCount }}</span>
                            @endif
                        </summary>

                        @include('notifications.partials.panel', [
                            'notifications' => $recentSystemNotifications,
                            'unreadCount' => $notificationUnreadCount,
                        ])
                    </details>

                    <div class="lo-access-chip">
                        Own Records Only
                    </div>

                    <div class="lo-user-chip">
                        <div class="lo-avatar">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                        <span class="lo-user-name">{{ auth()->user()->name }}</span>
                    </div>
                </div>
            </header>

            <div class="lo-content">
                {{ $slot }}
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('click', function (event) {
            document.querySelectorAll('.notification-dropdown[open]').forEach(function (dropdown) {
                if (!dropdown.contains(event.target)) {
                    dropdown.removeAttribute('open');
                }
            });
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                document.querySelectorAll('.notification-dropdown[open]').forEach(function (dropdown) {
                    dropdown.removeAttribute('open');
                });
            }
        });
    </script>

    @stack('scripts')
</body>
</html>
