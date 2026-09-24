<?php

namespace App\Policies;

use App\Models\Membership;
use App\Models\User;

class MembershipPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('owner')) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('memberships.manage') || $user->hasPermission('members.view');
    }

    public function view(User $user, Membership $membership): bool
    {
        return $user->hasBranchAccess($membership->branch_id);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('memberships.manage');
    }

    public function cancel(User $user, Membership $membership): bool
    {
        if ($user->hasRole('receptionist') || $user->hasRole('trainer')) {
            return false;
        }

        return $user->hasPermission('memberships.manage') && $user->hasBranchAccess($membership->branch_id);
    }

    public function delete(User $user, Membership $membership): bool
    {
        if ($user->hasRole('receptionist') || $user->hasRole('trainer')) {
            return false;
        }

        return $user->hasPermission('memberships.manage') && $user->hasBranchAccess($membership->branch_id);
    }
}
