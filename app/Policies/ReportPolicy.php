<?php

namespace App\Policies;

use App\Models\User;

class ReportPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('owner')) {
            return true;
        }

        return null;
    }

    public function viewDashboard(User $user): bool
    {
        return $user->hasPermission('reports.view') || $user->hasPermission('members.view');
    }

    public function viewFinancial(User $user): bool
    {
        if ($user->hasRole('receptionist') || $user->hasRole('trainer')) {
            return false; // Receptionist & Trainer blocked from Financial Reports
        }

        return $user->hasPermission('reports.view') || $user->hasPermission('expenses.manage');
    }

    public function viewNonFinancial(User $user): bool
    {
        if ($user->hasRole('trainer')) {
            return false;
        }

        return $user->hasPermission('reports.view') || $user->hasPermission('members.view') || $user->hasPermission('attendance.manage');
    }

    public function viewAttendance(User $user): bool
    {
        return $user->hasPermission('reports.view') || $user->hasPermission('attendance.manage');
    }

    public function exportFinancial(User $user): bool
    {
        if ($user->hasRole('receptionist') || $user->hasRole('trainer')) {
            return false;
        }

        return $user->hasPermission('reports.view') || $user->hasPermission('expenses.manage');
    }

    public function exportNonFinancial(User $user): bool
    {
        if ($user->hasRole('trainer')) {
            return false;
        }

        return $user->hasPermission('reports.view') || $user->hasPermission('members.view');
    }
}
