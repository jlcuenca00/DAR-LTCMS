<details class="notification-dropdown"
    data-notification-dropdown
    data-read-visible-url="{{ route('notifications.read-visible') }}"
    data-csrf-token="{{ csrf_token() }}">
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
