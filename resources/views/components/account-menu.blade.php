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

<div class="account-topbar-cluster">
    @if ($administrationRoute)
        <a href="{{ $administrationRoute }}"
           class="account-admin-link"
           aria-label="Open Administration / User Management"
           title="Administration / User Management">
            <i class="fa-solid fa-user-gear" aria-hidden="true"></i>
            <span>Administration</span>
        </a>
    @endif

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