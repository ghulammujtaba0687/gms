<?php

namespace App\Policies;

use App\Models\StaffProfile;
use App\Models\User;

class StaffProfilePolicy
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
        if ($user->hasRole('trainer')) {
            return false;
        }

        return $user->hasPermission('staff.view') || $user->hasPermission('staff.manage');
    }

    public function view(User $user, StaffProfile $staffProfile): bool
    {
        if ($user->hasRole('trainer')) {
            // Trainers can view their own staff profile
            return $user->id === $staffProfile->user_id;
        }

        return $user->hasBranchAccess($staffProfile->branch_id);
    }

    public function create(User $user): bool
    {
        if ($user->hasRole('receptionist') || $user->hasRole('trainer')) {
            return false;
        }

        return $user->hasPermission('staff.manage');
    }

    public function update(User $user, StaffProfile $staffProfile): bool
    {
        if ($user->hasRole('receptionist') || $user->hasRole('trainer')) {
            return false;
        }

        return $user->hasPermission('staff.manage') && $user->hasBranchAccess($staffProfile->branch_id);
    }

    public function delete(User $user, StaffProfile $staffProfile): bool
    {
        if ($user->hasRole('receptionist') || $user->hasRole('trainer')) {
            return false;
        }

        return $user->hasPermission('staff.manage') && $user->hasBranchAccess($staffProfile->branch_id);
    }
}
