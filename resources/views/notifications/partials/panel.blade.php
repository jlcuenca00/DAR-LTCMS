@props([
    'notifications' => collect(),
    'unreadCount' => 0,
])

@php
    $notificationTypeLabel = function (?string $type): string {
        return match ((string) $type) {
            'application_created' => 'Application encoded',
            'application_submitted', 'application_status_updated' => 'Application status updated',
            'application_approved', 'application_released' => 'Clearance released',
            'application_not_approved', 'application_denied' => 'Application denied',
            default => ucwords(str_replace('_', ' ', (string) $type)),
        };
    };

    $normalizeNotificationText = function (?string $value): string {
        return strtr((string) $value, [
            'Not Approved' => 'Denied',
            'not approved' => 'denied',
            'NOT APPROVED' => 'DENIED',
            'not-approved' => 'denied',
            'Not-approved' => 'Denied',
            'Approved Clearance' => 'Released Clearance',
            'approved clearance' => 'released clearance',
            'APPROVED CLEARANCE' => 'RELEASED CLEARANCE',
            'Application approved' => 'Clearance released',
            'application approved' => 'clearance released',
            'APPLICATION APPROVED' => 'CLEARANCE RELEASED',
            'approved' => 'released',
            'Approved' => 'Released',
            'APPROVED' => 'RELEASED',
        ]);
    };
@endphp

<div class="notification-dropdown-panel" role="menu" aria-label="Recent notifications">
    <div class="notification-dropdown-header">
        <div>
            <p class="notification-dropdown-kicker">Notifications</p>
            <h2>Recent activity</h2>
        </div>
        @if ($unreadCount > 0)
            <span class="notification-dropdown-count">{{ $unreadCount > 99 ? '99+' : $unreadCount }} unread</span>
        @else
            <span class="notification-dropdown-count is-clear">All read</span>
        @endif
    </div>

    <div class="notification-dropdown-list">
        @forelse ($notifications as $notification)
            <a href="{{ route('notifications.open', $notification) }}"
               class="notification-dropdown-item {{ $notification->read_at ? '' : 'is-unread' }}"
               role="menuitem">
                <div class="notification-dropdown-dot" aria-hidden="true"></div>
                <div class="notification-dropdown-copy">
                    <div class="notification-dropdown-title-row">
                        <strong>{{ $normalizeNotificationText($notification->title) }}</strong>
                        <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
                    </div>
                    <p>{{ $normalizeNotificationText($notification->message) }}</p>
                    <span>
                        {{ $notificationTypeLabel($notification->type) }} ·
                        {{ $notification->created_at?->timezone('Asia/Manila')->diffForHumans() }}
                    </span>
                </div>
            </a>
        @empty
            <div class="notification-dropdown-empty">
                <i class="fa-regular fa-bell"></i>
                <strong>No notifications yet</strong>
                <span>Recent system activity notices will appear here.</span>
            </div>
        @endforelse
    </div>

    <a href="{{ route('notifications.index') }}" class="notification-see-all">
        See all notifications
        <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
    </a>
</div>
