@props([
    'user',
    'administrationRoute' => null,
])

@php
    $accountName = $user?->name ?? 'User';
    $accountInitial = strtoupper(substr($accountName, 0, 1));
    $accountPhotoExists = $user?->profile_photo_path
        && \Illuminate\Support\Facades\Storage::disk('public')->exists($user->profile_photo_path);
    $accountPhotoUrl = $accountPhotoExists
        ? route('profile.photo', $user)
        : null;
@endphp

@once
    <style>
        /* Shared portal topbar control system.
           Bell, access scope, administration, and account controls all sit on
           the same 44px control track so Staff, Landowner, and Geodetic headers
           remain visually aligned at desktop and responsive widths. */
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
            @if ($administrationRoute)
                <a href="{{ $administrationRoute }}" class="account-menu-item">
                    <i class="fa-solid fa-user-gear" aria-hidden="true"></i>
                    <span>Manage Users</span>
                </a>
            @endif

            <a href="{{ route('profile.edit') }}" class="account-menu-item">
                <i class="fa-solid fa-user-pen" aria-hidden="true"></i>
                <span>Manage Profile</span>
            </a>

            <form method="POST" action="{{ route('logout') }}" class="account-menu-form">
                @csrf
                <button type="submit" class="account-menu-item account-menu-button">
                    <i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i>
                    <span>Log Out</span>
                </button>
            </form>
        </div>
    </details>
</div>