@props([
    'user',
    'administrationRoute' => null,
])

@php
    $accountName = $user?->name ?? 'User';
    $accountInitial = strtoupper(substr($accountName, 0, 1));
    $accountUsername = $user?->username ?: 'No username';
    $accountRoleLabel = match ($user?->role) {
        \App\Models\User::ROLE_STAFF => 'DAR Staff',
        \App\Models\User::ROLE_LANDOWNER => 'Landowner',
        \App\Models\User::ROLE_GEODETIC => 'Geodetic Personnel',
        default => 'Authorized User',
    };
    $accountPhotoPath = $user?->profile_photo_path;
    $accountPhotoExists = $accountPhotoPath
        && (
            \Illuminate\Support\Facades\Storage::disk('local')->exists($accountPhotoPath)
            || \Illuminate\Support\Facades\Storage::disk('public')->exists($accountPhotoPath)
        );
    $accountPhotoUrl = $accountPhotoExists
        ? route('profile.photo', $user)
        : null;
    $profileInformationUrl = route('profile.edit') . '#profile-information';
    $accountSecurityUrl = route('profile.edit') . '#account-security';
@endphp

@once
    <style>
        /* Shared portal topbar control system. */
        :where(.staff-topbar-actions, .geo-topbar-right, .lo-topbar-right) {
            align-items: center !important;
            gap: 10px !important;
        }

        :where(.staff-topbar, .geo-topbar, .lo-topbar) .notification-dropdown {
            line-height: 1;
        }

        :where(.staff-topbar, .geo-topbar, .lo-topbar) .notification-bell-link {
            width: 44px !important;
            min-width: 44px !important;
            height: 44px !important;
            min-height: 44px !important;
            flex: 0 0 44px;
            box-sizing: border-box;
            line-height: 1;
        }

        :where(.staff-topbar, .geo-topbar, .lo-topbar) .onboarding-help-button {
            width: 44px !important;
            min-width: 44px !important;
            height: 44px !important;
            min-height: 44px !important;
            flex: 0 0 44px !important;
            box-sizing: border-box;
            border: 1px solid #bbd7c4 !important;
            background: #f0fdf4 !important;
            color: #166534 !important;
            font-size: 15px !important;
            line-height: 1 !important;
            transform: none !important;
            box-shadow: none !important;
        }

        :where(.staff-topbar, .geo-topbar, .lo-topbar) .onboarding-help-button:hover {
            background: #dcfce7 !important;
            color: #14532d !important;
            transform: none !important;
            box-shadow: none !important;
        }

        :where(.geo-topbar, .lo-topbar) :where(.geo-access-chip, .lo-access-chip) {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            height: 44px !important;
            min-height: 44px !important;
            box-sizing: border-box;
            padding: 0 13px !important;
            line-height: 1.15 !important;
            font-weight: 700 !important;
        }

        .account-topbar-cluster {
            min-height: 44px;
            align-items: center !important;
            gap: 10px !important;
        }

        .account-admin-link,
        .account-menu-trigger {
            height: 44px !important;
            min-height: 44px !important;
            box-sizing: border-box;
            padding-top: 5px !important;
            padding-bottom: 5px !important;
            line-height: 1 !important;
            font-weight: 700 !important;
        }

        .account-menu-avatar {
            width: 32px !important;
            height: 32px !important;
            flex: 0 0 32px !important;
            line-height: 1;
        }

        .account-menu-name,
        .account-menu-chevron {
            line-height: 1;
        }

        /* Facebook-inspired hierarchy, adapted to DAR-LTCMS account functions. */
        .account-menu-panel {
            width: 360px !important;
            max-width: calc(100vw - 32px) !important;
            padding: 10px !important;
            border: 1px solid #d7ded9 !important;
            border-radius: 16px !important;
            background: #ffffff !important;
            box-shadow: 0 24px 60px rgba(15, 23, 42, .20) !important;
        }

        .account-panel-identity {
            display: grid;
            grid-template-columns: 56px minmax(0, 1fr);
            gap: 12px;
            align-items: center;
            padding: 10px 10px 14px;
        }

        .account-panel-avatar {
            width: 56px;
            height: 56px;
            border-radius: 999px;
            overflow: hidden;
            display: grid;
            place-items: center;
            background: #166534;
            color: #ffffff;
            font-size: 18px;
            font-weight: 700;
            border: 2px solid #dcfce7;
        }

        .account-panel-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .account-panel-identity-copy {
            min-width: 0;
        }

        .account-panel-name {
            margin: 0;
            color: #111827;
            font-size: 16px;
            line-height: 1.25;
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        .account-panel-meta {
            margin: 4px 0 0;
            color: #667085;
            font-size: 12px;
            line-height: 1.35;
            font-weight: 600;
            overflow-wrap: anywhere;
        }

        .account-panel-role {
            display: inline-flex;
            align-items: center;
            width: fit-content;
            max-width: 100%;
            min-height: 24px;
            margin-top: 7px;
            padding: 0 9px;
            border: 1px solid #bbf7d0;
            border-radius: 999px;
            background: #f0fdf4;
            color: #166534;
            font-size: 10.5px;
            line-height: 1.15;
            font-weight: 700;
        }

        .account-panel-group {
            padding: 7px 0;
            border-top: 1px solid #eef2f0;
        }

        .account-panel-action,
        .account-panel-disclosure > summary {
            width: 100%;
            min-height: 52px;
            display: grid;
            grid-template-columns: 38px minmax(0, 1fr) auto;
            gap: 10px;
            align-items: center;
            border: 0;
            border-radius: 10px;
            background: transparent;
            color: #1f2937;
            padding: 7px 9px;
            text-decoration: none;
            text-align: left;
            cursor: pointer;
            box-sizing: border-box;
        }

        .account-panel-action:hover,
        .account-panel-action:focus-visible,
        .account-panel-disclosure > summary:hover,
        .account-panel-disclosure > summary:focus-visible,
        .account-panel-disclosure[open] > summary {
            background: #f3f6f4;
            color: #14532d;
        }

        .account-panel-icon {
            width: 38px;
            height: 38px;
            border-radius: 999px;
            display: grid;
            place-items: center;
            background: #edf2ef;
            color: #344054;
            font-size: 14px;
            flex: 0 0 auto;
        }

        .account-panel-action:hover .account-panel-icon,
        .account-panel-disclosure > summary:hover .account-panel-icon,
        .account-panel-disclosure[open] > summary .account-panel-icon {
            background: #dcfce7;
            color: #166534;
        }

        .account-panel-action-copy {
            min-width: 0;
        }

        .account-panel-action-title {
            display: block;
            color: inherit;
            font-size: 13.5px;
            line-height: 1.25;
            font-weight: 700;
        }

        .account-panel-action-note {
            display: block;
            margin-top: 2px;
            color: #667085;
            font-size: 10.5px;
            line-height: 1.3;
            font-weight: 500;
        }

        .account-panel-chevron {
            color: #98a2b3;
            font-size: 11px;
            transition: transform 150ms ease;
        }

        .account-panel-disclosure {
            margin: 0;
        }

        .account-panel-disclosure > summary {
            list-style: none;
        }

        .account-panel-disclosure > summary::-webkit-details-marker {
            display: none;
        }

        .account-panel-disclosure[open] > summary .account-panel-chevron {
            transform: rotate(90deg);
        }

        .account-panel-disclosure-body {
            margin: 2px 9px 8px 57px;
            padding: 10px 11px;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            background: #fbfcfb;
            color: #475569;
            font-size: 11.5px;
            line-height: 1.5;
        }

        .account-panel-setting {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .account-panel-setting strong {
            display: block;
            color: #1f2937;
            font-size: 12px;
            font-weight: 700;
        }

        .account-panel-setting span {
            display: block;
            margin-top: 2px;
            color: #667085;
            font-size: 10.5px;
        }

        .account-panel-switch {
            position: relative;
            width: 42px;
            height: 24px;
            flex: 0 0 42px;
        }

        .account-panel-switch input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .account-panel-switch-track {
            position: absolute;
            inset: 0;
            border-radius: 999px;
            background: #d1d5db;
            transition: 150ms ease;
            cursor: pointer;
        }

        .account-panel-switch-track::after {
            content: '';
            position: absolute;
            width: 18px;
            height: 18px;
            left: 3px;
            top: 3px;
            border-radius: 999px;
            background: #ffffff;
            box-shadow: 0 1px 3px rgba(15, 23, 42, .25);
            transition: 150ms ease;
        }

        .account-panel-switch input:checked + .account-panel-switch-track {
            background: #166534;
        }

        .account-panel-switch input:checked + .account-panel-switch-track::after {
            transform: translateX(18px);
        }

        .account-panel-switch input:focus-visible + .account-panel-switch-track {
            outline: 3px solid rgba(21, 128, 61, .28);
            outline-offset: 2px;
        }

        .account-panel-help-copy {
            margin: 0;
        }

        .account-panel-help-copy + .account-panel-help-copy {
            margin-top: 8px;
        }

        .account-panel-logout {
            width: 100%;
            margin: 0;
        }

        .account-panel-logout .account-panel-action {
            font: inherit;
        }

        .account-panel-footer {
            padding: 9px 10px 3px;
            border-top: 1px solid #eef2f0;
            color: #98a2b3;
            font-size: 10px;
            line-height: 1.4;
            text-align: center;
        }

        .dar-reduce-motion *,
        .dar-reduce-motion *::before,
        .dar-reduce-motion *::after {
            scroll-behavior: auto !important;
            animation-duration: 0.01ms !important;
            animation-iteration-count: 1 !important;
            transition-duration: 0.01ms !important;
        }

        /* Staff phone header: keep persistent utilities on one row. */
        @media screen and (max-width: 640px) {
            .staff-topbar {
                padding-left: 16px !important;
                padding-right: 16px !important;
            }

            .staff-topbar-actions {
                width: 100% !important;
                display: grid !important;
                grid-template-columns: 44px minmax(0, 1fr) auto !important;
                align-items: center !important;
                gap: 10px !important;
                flex-wrap: nowrap !important;
            }

            .staff-topbar-actions > .notification-dropdown {
                grid-column: 1 !important;
                grid-row: 1 !important;
                width: 44px !important;
                min-width: 44px !important;
                justify-self: start;
            }

            .staff-topbar-actions > .account-topbar-cluster {
                grid-column: 3 !important;
                grid-row: 1 !important;
                width: auto !important;
                max-width: 100% !important;
                min-width: 0 !important;
                flex: 0 1 auto !important;
                justify-self: end;
            }

            .staff-topbar-actions > .account-topbar-cluster .account-menu,
            .staff-topbar-actions > .account-topbar-cluster .account-menu-trigger {
                width: auto !important;
                max-width: 100% !important;
                min-width: 0 !important;
            }

            .staff-topbar-actions > .account-topbar-cluster .account-menu-trigger {
                justify-content: flex-start !important;
            }

            .staff-topbar-actions > .account-topbar-cluster .account-menu-name {
                max-width: min(170px, 42vw) !important;
                min-width: 0 !important;
                flex: 0 1 auto !important;
                text-align: left;
            }

            .staff-topbar-actions > :not(.notification-dropdown):not(.account-topbar-cluster) {
                grid-column: 1 / -1 !important;
                width: 100% !important;
                min-width: 0 !important;
            }

            .staff-content {
                padding-left: 16px !important;
                padding-right: 16px !important;
            }

            /* Account panel becomes a roomy bottom sheet on phones. */
            :where(.staff-topbar, .geo-topbar, .lo-topbar) .account-menu-panel {
                position: fixed !important;
                top: auto !important;
                bottom: 12px !important;
                left: 16px !important;
                right: 16px !important;
                width: auto !important;
                max-width: none !important;
                max-height: calc(100dvh - 88px);
                overflow-y: auto;
                overscroll-behavior: contain;
                border-radius: 18px !important;
            }

            .account-panel-disclosure-body {
                margin-left: 9px;
            }
        }

        @media screen and (max-width: 390px) {
            .account-menu-name {
                display: none;
            }

            .account-menu-trigger {
                width: 44px !important;
                min-width: 44px !important;
                padding-left: 5px !important;
                padding-right: 5px !important;
                gap: 0 !important;
            }

            .account-menu-trigger .account-menu-chevron {
                display: none;
            }
        }

        /* Keep dashboard attention counters readable at high browser zoom. */
        .stale-callout .stale-label {
            min-width: 0;
            overflow-wrap: anywhere;
        }

        .stale-callout .stale-value {
            flex: 0 0 auto;
            min-width: 2.5ch;
            white-space: nowrap;
            word-break: normal;
            overflow-wrap: normal;
            text-align: right;
            line-height: 1;
        }
    </style>
@endonce

<div class="account-topbar-cluster">
    <details class="account-menu" data-account-menu>
        <summary class="account-menu-trigger" aria-label="Open account menu for {{ $accountName }}" title="Account menu">
            <span class="account-menu-name">{{ $accountName }}</span>
            <span class="account-menu-avatar" aria-hidden="true">
                @if ($accountPhotoUrl)
                    <img src="{{ $accountPhotoUrl }}" alt="">
                @else
                    {{ $accountInitial }}
                @endif
            </span>
            <i class="fa-solid fa-chevron-down account-menu-chevron" aria-hidden="true"></i>
        </summary>

        <div class="account-menu-panel">
            <section class="account-panel-identity" aria-label="Signed-in account">
                <div class="account-panel-avatar" aria-hidden="true">
                    @if ($accountPhotoUrl)
                        <img src="{{ $accountPhotoUrl }}" alt="">
                    @else
                        {{ $accountInitial }}
                    @endif
                </div>

                <div class="account-panel-identity-copy">
                    <p class="account-panel-name">{{ $accountName }}</p>
                    <p class="account-panel-meta">{{ $accountUsername }}</p>
                    <span class="account-panel-role">{{ $accountRoleLabel }}</span>
                </div>
            </section>

            <div class="account-panel-group">
                <a href="{{ $profileInformationUrl }}" class="account-panel-action">
                    <span class="account-panel-icon"><i class="fa-solid fa-user-pen" aria-hidden="true"></i></span>
                    <span class="account-panel-action-copy">
                        <span class="account-panel-action-title">Manage Profile</span>
                        <span class="account-panel-action-note">Photo, name, and recovery email</span>
                    </span>
                    <i class="fa-solid fa-chevron-right account-panel-chevron" aria-hidden="true"></i>
                </a>

                <a href="{{ $accountSecurityUrl }}" class="account-panel-action">
                    <span class="account-panel-icon"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i></span>
                    <span class="account-panel-action-copy">
                        <span class="account-panel-action-title">Account &amp; Security</span>
                        <span class="account-panel-action-note">Password and account access</span>
                    </span>
                    <i class="fa-solid fa-chevron-right account-panel-chevron" aria-hidden="true"></i>
                </a>

                @if (\Illuminate\Support\Facades\Route::has('notifications.index'))
                    <a href="{{ route('notifications.index') }}" class="account-panel-action">
                        <span class="account-panel-icon"><i class="fa-solid fa-bell" aria-hidden="true"></i></span>
                        <span class="account-panel-action-copy">
                            <span class="account-panel-action-title">Notifications</span>
                            <span class="account-panel-action-note">Review recent system activity</span>
                        </span>
                        <i class="fa-solid fa-chevron-right account-panel-chevron" aria-hidden="true"></i>
                    </a>
                @endif
            </div>

            @if ($administrationRoute)
                <div class="account-panel-group">
                    <a href="{{ $administrationRoute }}" class="account-panel-action">
                        <span class="account-panel-icon"><i class="fa-solid fa-user-gear" aria-hidden="true"></i></span>
                        <span class="account-panel-action-copy">
                            <span class="account-panel-action-title">User Management</span>
                            <span class="account-panel-action-note">Authorized Staff administration</span>
                        </span>
                        <i class="fa-solid fa-chevron-right account-panel-chevron" aria-hidden="true"></i>
                    </a>
                </div>
            @endif

            <div class="account-panel-group">
                <details class="account-panel-disclosure">
                    <summary>
                        <span class="account-panel-icon"><i class="fa-solid fa-circle-half-stroke" aria-hidden="true"></i></span>
                        <span class="account-panel-action-copy">
                            <span class="account-panel-action-title">Display &amp; Accessibility</span>
                            <span class="account-panel-action-note">Personal display preferences</span>
                        </span>
                        <i class="fa-solid fa-chevron-right account-panel-chevron" aria-hidden="true"></i>
                    </summary>

                    <div class="account-panel-disclosure-body">
                        <div class="account-panel-setting">
                            <div>
                                <strong>Reduce motion</strong>
                                <span>Minimize interface animations and transitions on this device.</span>
                            </div>

                            <label class="account-panel-switch" title="Reduce motion">
                                <input type="checkbox" data-account-reduce-motion aria-label="Reduce interface motion">
                                <span class="account-panel-switch-track" aria-hidden="true"></span>
                            </label>
                        </div>
                    </div>
                </details>

                <details class="account-panel-disclosure">
                    <summary>
                        <span class="account-panel-icon"><i class="fa-solid fa-circle-question" aria-hidden="true"></i></span>
                        <span class="account-panel-action-copy">
                            <span class="account-panel-action-title">Help &amp; Support</span>
                            <span class="account-panel-action-note">Account and system assistance</span>
                        </span>
                        <i class="fa-solid fa-chevron-right account-panel-chevron" aria-hidden="true"></i>
                    </summary>

                    <div class="account-panel-disclosure-body">
                        <p class="account-panel-help-copy"><strong>Account assistance:</strong> approach authorized DAR Staff at the DAR Negros Oriental Provincial Office for username, access, or recovery concerns.</p>
                        <p class="account-panel-help-copy"><strong>System concerns:</strong> report unexpected errors or access problems to the designated DAR-LTCMS system administrator.</p>
                    </div>
                </details>
            </div>

            <div class="account-panel-group">
                <form method="POST" action="{{ route('logout') }}" class="account-panel-logout">
                    @csrf
                    <button type="submit" class="account-panel-action">
                        <span class="account-panel-icon"><i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i></span>
                        <span class="account-panel-action-copy">
                            <span class="account-panel-action-title">Log Out</span>
                            <span class="account-panel-action-note">End this signed-in session</span>
                        </span>
                    </button>
                </form>
            </div>

            <div class="account-panel-footer">DAR-LTCMS · DAR Negros Oriental Provincial Office</div>
        </div>
    </details>
</div>

@once
    <script>
        (function () {
            const storageKey = 'dar-ltcms-reduce-motion';
            let reduceMotion = false;

            try {
                reduceMotion = window.localStorage.getItem(storageKey) === '1';
            } catch (error) {
                reduceMotion = false;
            }

            document.documentElement.classList.toggle('dar-reduce-motion', reduceMotion);

            function initializeAccountDisplayPreferences() {
                document.querySelectorAll('[data-account-reduce-motion]').forEach(function (control) {
                    control.checked = reduceMotion;

                    control.addEventListener('change', function () {
                        reduceMotion = control.checked;
                        document.documentElement.classList.toggle('dar-reduce-motion', reduceMotion);

                        try {
                            window.localStorage.setItem(storageKey, reduceMotion ? '1' : '0');
                        } catch (error) {
                            // The preference remains active for the current page if storage is unavailable.
                        }

                        document.querySelectorAll('[data-account-reduce-motion]').forEach(function (other) {
                            other.checked = reduceMotion;
                        });
                    });
                });
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initializeAccountDisplayPreferences, { once: true });
            } else {
                initializeAccountDisplayPreferences();
            }
        })();
    </script>
@endonce
