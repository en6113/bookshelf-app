<?php

namespace App\Policies;

use App\Models\Notification;
use App\Models\User;

class NotificationPolicy
{
    /**
     * 通知を既読にできるか
     */
    public function read(User $user, Notification $notification): bool
    {
        return $user->id === $notification->notifiable_id;
    }
}
