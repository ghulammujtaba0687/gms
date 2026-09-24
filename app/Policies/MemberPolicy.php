<?php

namespace App\Policies;

use App\Models\Member;
use App\Models\User;

class MemberPolicy
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
        return $user->hasPermission('members.view');
    }

    public function view(User $user, Member $member): bool
    {
        return $user->hasPermission('members.view') && $user->hasBranchAccess($member->branch_id);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('members.manage');
    }

    public function update(User $user, Member $member): bool
    {
        return $user->hasPermission('members.manage') && $user->hasBranchAccess($member->branch_id);
    }

    public function delete(User $user, Member $member): bool
    {
        // Only Owner and Manager can archive/delete members in assigned branch
        if ($user->hasRole('receptionist') || $user->hasRole('trainer')) {
            return false;
        }

        return $user->hasPermission('members.manage') && $user->hasBranchAccess($member->branch_id);
    }

    public function restore(User $user, Member $member): bool
    {
        if ($user->hasRole('receptionist') || $user->hasRole('trainer')) {
            return false;
        }

        return $user->hasPermission('members.manage') && $user->hasBranchAccess($member->branch_id);
    }
}
