<?php

namespace App\Policies;

use App\Models\Payroll;
use App\Models\User;

class PayrollPolicy
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
        if ($user->hasRole('receptionist') || $user->hasRole('trainer')) {
            return false;
        }

        return $user->hasPermission('staff.manage') || $user->hasPermission('expenses.manage');
    }

    public function view(User $user, Payroll $payroll): bool
    {
        if ($user->hasRole('trainer')) {
            // Trainers can view their own payslip
            return $user->staffProfile && $user->staffProfile->id === $payroll->staff_profile_id;
        }

        if ($user->hasRole('receptionist')) {
            return false;
        }

        return $user->hasBranchAccess($payroll->branch_id);
    }

    public function create(User $user): bool
    {
        if ($user->hasRole('receptionist') || $user->hasRole('trainer')) {
            return false;
        }

        return $user->hasPermission('staff.manage');
    }

    public function cancel(User $user, Payroll $payroll): bool
    {
        if ($user->hasRole('receptionist') || $user->hasRole('trainer')) {
            return false;
        }

        return $user->hasPermission('staff.manage') && $user->hasBranchAccess($payroll->branch_id);
    }

    public function delete(User $user, Payroll $payroll): bool
    {
        if ($user->hasRole('receptionist') || $user->hasRole('trainer')) {
            return false;
        }

        return $user->hasPermission('staff.manage') && $user->hasBranchAccess($payroll->branch_id);
    }
}
