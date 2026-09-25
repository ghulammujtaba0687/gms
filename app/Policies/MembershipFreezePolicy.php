<?php

namespace App\Policies;

use App\Models\MembershipFreeze;
use App\Models\User;

class MembershipFreezePolicy
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

    public function create(User $user): bool
    {
        return $user->hasPermission('memberships.manage');
    }

    public function approve(User $user, MembershipFreeze $freeze): bool
    {
        if ($user->hasRole('receptionist') || $user->hasRole('trainer')) {
            return false;
        }

        return $user->hasPermission('memberships.manage') && $user->hasBranchAccess($freeze->branch_id);
    }

    public function cancel(User $user, MembershipFreeze $freeze): bool
    {
        if ($user->hasRole('receptionist') || $user->hasRole('trainer')) {
            return false;
        }

        return $user->hasPermission('memberships.manage') && $user->hasBranchAccess($freeze->branch_id);
    }

    public function delete(User $user, MembershipFreeze $freeze): bool
    {
        if ($user->hasRole('receptionist') || $user->hasRole('trainer')) {
            return false;
        }

        return $user->hasPermission('memberships.manage') && $user->hasBranchAccess($freeze->branch_id);
    }
}
