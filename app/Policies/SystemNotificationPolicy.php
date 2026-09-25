<?php

namespace App\Policies;

use App\Models\SystemNotification;
use App\Models\User;

class SystemNotificationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole('owner') || $user->hasRole('manager') || $user->hasRole('receptionist') || $user->hasRole('trainer');
    }

    public function view(User $user, SystemNotification $notification): bool
    {
        if ($user->hasRole('owner')) {
            return true;
        }

        if (! $user->hasBranchAccess($notification->branch_id)) {
            return false;
        }

        if ($user->hasRole('manager') || $user->hasRole('receptionist')) {
            return true;
        }

        if ($user->hasRole('trainer')) {
            if (is_null($notification->user_id) || $notification->user_id === $user->id) {
                return true;
            }
        }

        return false;
    }

    public function markAsRead(User $user, SystemNotification $notification): bool
    {
        return $this->view($user, $notification);
    }

    public function markAllAsRead(User $user): bool
    {
        return $this->viewAny($user);
    }
}
