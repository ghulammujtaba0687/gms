<?php

namespace App\Policies;

use App\Models\Attendance;
use App\Models\User;

class AttendancePolicy
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
        return $user->hasPermission('attendance.manage') || $user->hasPermission('members.view');
    }

    public function view(User $user, Attendance $attendance): bool
    {
        return $user->hasBranchAccess($attendance->branch_id);
    }

    public function checkIn(User $user): bool
    {
        if ($user->hasRole('trainer')) {
            return false;
        }

        return $user->hasPermission('attendance.manage');
    }

    public function checkOut(User $user, Attendance $attendance): bool
    {
        if ($user->hasRole('trainer')) {
            return false;
        }

        return $user->hasPermission('attendance.manage') && $user->hasBranchAccess($attendance->branch_id);
    }

    public function update(User $user, Attendance $attendance): bool
    {
        if ($user->hasRole('receptionist') || $user->hasRole('trainer')) {
            return false;
        }

        return $user->hasPermission('attendance.manage') && $user->hasBranchAccess($attendance->branch_id);
    }

    public function delete(User $user, Attendance $attendance): bool
    {
        if ($user->hasRole('receptionist') || $user->hasRole('trainer')) {
            return false;
        }

        return $user->hasPermission('attendance.manage') && $user->hasBranchAccess($attendance->branch_id);
    }
}
