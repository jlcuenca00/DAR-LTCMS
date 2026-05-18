@props([
    'notifications' => collect(),
    'unreadCount' => 0,
])

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
                        <strong>{{ $notification->title }}</strong>
                        <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
                    </div>
                    <p>{{ $notification->message }}</p>
                    <span>{{ $notification->created_at?->timezone('Asia/Manila')->diffForHumans() }}</span>
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
