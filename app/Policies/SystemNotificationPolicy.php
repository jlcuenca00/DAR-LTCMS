<?php

namespace App\Policies;

use App\Models\SystemNotification;
use App\Models\User;

class SystemNotificationPolicy
{
    public function view(User $user, SystemNotification $notification): bool
    {
        return (int) $notification->user_id === (int) $user->id;
    }
}
