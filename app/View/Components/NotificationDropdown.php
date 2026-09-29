<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class NotificationDropdown extends Component
{
    public function render(): View
    {
        $user = auth()->user();

        return view('components.notification-dropdown', [
            'notificationUnreadCount' => $user?->unreadSystemNotifications()->count() ?? 0,
            'recentSystemNotifications' => $user?->systemNotifications()
                ->latest()
                ->limit(5)
                ->get() ?? collect(),
        ]);
    }
}
