@props([
    'title' => 'Landowner Portal',
    'eyebrow' => 'DAR Negros Oriental Provincial Office',
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

    @isset($head)
        {{ $head }}
    @endisset

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --dar-green: #14532d;
            --dar-green-dark: #0f3f23;
            --dar-green-soft: #166534;
            --page-bg: #f3f4f6;
            --panel: #ffffff;
            --border: #d9dee5;
            --text: #111827;
            --muted: #6b7280;
            --heading-font: 'Google Sans';
            --body-font: 'Google Sans';
        }

        * {
            box-sizing: border-box;
        }

        html {
            min-width: 320px;
        }

        body {
            margin: 0;
            font-family: var(--body-font);
            background: var(--page-bg);
            color: var(--text);
        }

        a,
        button,
        input,
        select,
        textarea {
            font-family: inherit;
        }

        .portal-shell {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 260px minmax(0, 1fr);
        }

        .sidebar {
            position: sticky;
            top: 0;
            height: 100vh;
            background: var(--dar-green);
            color: #d9fbe6;
            display: flex;
            flex-direction: column;
            border-right: 1px solid rgba(255, 255, 255, 0.08);
            overflow-y: auto;
            z-index: 30;
        }

        .brand {
            min-height: 82px;
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 18px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .brand-mark {
            width: 52px;
            height: 52px;
            flex: 0 0 52px;
            display: grid;
            place-items: center;
            border-radius: 14px;
            background: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.28);
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.16);
            padding: 6px;
            overflow: hidden;
        }

        .brand-logo {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
        }

        .brand-fallback {
            font-family: var(--heading-font);
            font-weight: 800;
            font-size: 10px;
            color: var(--dar-green);
        }

        .brand-title {
            margin: 0;
            font-family: var(--heading-font);
            font-size: 14px;
            font-weight: 800;
            letter-spacing: 0.01em;
            color: #ffffff;
            line-height: 1.1;
        }

        .brand-subtitle {
            margin: 4px 0 0;
            font-size: 11px;
            font-weight: 600;
            color: #bbf7d0;
        }

        .side-section {
            padding: 18px 14px;
        }

        .side-section.secondary {
            margin-top: auto;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            padding-top: 14px;
        }

        .side-label {
            padding: 0 10px;
            margin-bottom: 10px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: #86efac;
        }

        .side-nav {
            display: grid;
            gap: 5px;
        }

        .side-link {
            display: flex;
            align-items: center;
            gap: 12px;
            min-height: 42px;
            padding: 10px 12px;
            border-radius: 9px;
            color: #d9fbe6;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            line-height: 1.25;
            transition: 160ms ease;
        }

        .side-link:hover {
            background: rgba(255, 255, 255, 0.10);
            color: #ffffff;
        }

        .side-link.active {
            background: rgba(255, 255, 255, 0.16);
            color: #ffffff;
            font-weight: 650;
        }

        .side-icon {
            width: 20px;
            text-align: center;
            font-size: 15px;
            opacity: 0.95;
        }

        .sidebar-footer {
            padding: 14px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
        }

        .logout-button {
            width: 100%;
            min-height: 42px;
            display: flex;
            align-items: center;
            gap: 12px;
            border: 0;
            background: transparent;
            color: #d9fbe6;
            padding: 10px 12px;
            border-radius: 9px;
            font: inherit;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            text-align: left;
        }

        .logout-button:hover {
            background: rgba(255, 255, 255, 0.10);
            color: #ffffff;
        }

        .main-area {
            min-width: 0;
            display: flex;
            flex-direction: column;
        }

        .topbar {
            min-height: 74px;
            background: #ffffff;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 0 28px;
        }

        .page-eyebrow {
            margin: 0;
            font-size: 11px;
            font-weight: 900;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: #166534;
        }

        .page-title {
            margin: 4px 0 0;
            font-family: var(--heading-font);
            font-size: 22px;
            font-weight: 900;
            color: #111827;
            line-height: 1.2;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 14px;
            min-width: 0;
        }

        .access-chip {
            border: 1px solid #bbf7d0;
            background: #f0fdf4;
            color: #166534;
            border-radius: 999px;
            padding: 8px 12px;
            font-size: 12px;
            font-weight: 800;
            white-space: nowrap;
        }

        .user-chip {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            font-weight: 700;
            color: #374151;
            white-space: nowrap;
            min-width: 0;
        }

        .avatar {
            width: 36px;
            height: 36px;
            flex: 0 0 36px;
            border-radius: 999px;
            background: #166534;
            color: white;
            display: grid;
            place-items: center;
            font-weight: 900;
        }

        .user-name {
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .content {
            padding: 24px 28px 36px;
            display: grid;
            gap: 22px;
        }

        @media (max-width: 980px) {
            .portal-shell {
                grid-template-columns: 1fr;
            }

            .sidebar {
                position: static;
                height: auto;
            }

            .side-section {
                padding: 12px;
            }

            .side-nav {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .side-section.secondary {
                margin-top: 0;
            }

            .sidebar-footer {
                padding: 12px;
            }

            .topbar {
                min-height: auto;
                padding: 18px;
                align-items: flex-start;
                flex-direction: column;
            }

            .topbar-right {
                width: 100%;
                justify-content: space-between;
            }

            .content {
                padding: 18px;
            }
        }

        @media (max-width: 560px) {
            .brand {
                min-height: 72px;
            }

            .brand-mark {
                width: 44px;
                height: 44px;
                flex-basis: 44px;
            }

            .side-nav {
                grid-template-columns: 1fr;
            }

            .topbar-right {
                align-items: flex-start;
                flex-direction: column;
                gap: 10px;
            }

            .access-chip {
                white-space: normal;
            }
        }
    </style>
</head>
<body>
    <div class="portal-shell">
        <aside class="sidebar">
            <div class="brand">
                <div class="brand-mark">
                    @if (file_exists(public_path('images/dar-logo.png')))
                        <img src="{{ asset('images/dar-logo.png') }}" alt="Department of Agrarian Reform Logo" class="brand-logo">
                    @elseif (file_exists(public_path('images/dar-logo.svg')))
                        <img src="{{ asset('images/dar-logo.svg') }}" alt="Department of Agrarian Reform Logo" class="brand-logo">
                    @else
                        <span class="brand-fallback">DAR</span>
                    @endif
                </div>

                <div>
                    <p class="brand-title">DAR LTCMS</p>
                    <p class="brand-subtitle">Landowner Portal</p>
                </div>
            </div>

            <div class="side-section">
                <div class="side-label">My Workspace</div>

                <nav class="side-nav" aria-label="Landowner portal navigation">
                    <a href="{{ route('landowner.dashboard') }}" class="side-link {{ $active === 'dashboard' ? 'active' : '' }}">
                        <i class="fa-solid fa-gauge-high side-icon"></i>
                        Dashboard
                    </a>

                    <a href="{{ route('landowner.parcel-map.index') }}" class="side-link {{ $active === 'parcel-map' ? 'active' : '' }}">
                        <i class="fa-solid fa-map side-icon"></i>
                        My Parcel Map
                    </a>

                    <a href="{{ route('landowner.parcels.index') }}" class="side-link {{ $active === 'parcels' ? 'active' : '' }}">
                        <i class="fa-solid fa-map-location-dot side-icon"></i>
                        My Parcels
                    </a>

                    <a href="{{ route('landowner.applications.index') }}" class="side-link {{ $active === 'applications' ? 'active' : '' }}">
                        <i class="fa-solid fa-file-lines side-icon"></i>
                        My Applications
                    </a>
                </nav>
            </div>

            <div class="side-section secondary">
                <div class="side-label">Account</div>

                <nav class="side-nav" aria-label="Account navigation">
                    <a href="{{ route('profile.edit') }}" class="side-link {{ $active === 'profile' ? 'active' : '' }}">
                        <i class="fa-solid fa-user-gear side-icon"></i>
                        Profile Settings
                    </a>
                </nav>
            </div>

            <div class="sidebar-footer">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="logout-button">
                        <i class="fa-solid fa-arrow-right-from-bracket side-icon"></i>
                        Logout
                    </button>
                </form>
            </div>
        </aside>

        <main class="main-area">
            <header class="topbar">
                <div>
                    <p class="page-eyebrow">{{ $eyebrow }}</p>
                    <h1 class="page-title">{{ $title }}</h1>
                </div>

                <div class="topbar-right">
                    <div class="access-chip">
                        <i class="fa-solid fa-lock"></i>
                        Own linked records only
                    </div>

                    <div class="user-chip">
                        <div class="avatar">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                        <span class="user-name">{{ auth()->user()->name }}</span>
                    </div>
                </div>
            </header>

            <div class="content">
                {{ $slot }}
            </div>
        </main>
    </div>
</body>
</html>
