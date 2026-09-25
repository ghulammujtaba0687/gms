<?php

namespace App\Policies;

use App\Models\Expense;
use App\Models\User;

class ExpensePolicy
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

        return $user->hasPermission('expenses.manage') || $user->hasPermission('reports.view');
    }

    public function view(User $user, Expense $expense): bool
    {
        if ($user->hasRole('trainer')) {
            return false;
        }

        return $user->hasBranchAccess($expense->branch_id);
    }

    public function create(User $user): bool
    {
        if ($user->hasRole('trainer')) {
            return false;
        }

        return $user->hasPermission('expenses.manage');
    }

    public function update(User $user, Expense $expense): bool
    {
        if ($user->hasRole('receptionist') || $user->hasRole('trainer')) {
            return false;
        }

        return $user->hasPermission('expenses.manage') && $user->hasBranchAccess($expense->branch_id);
    }

    public function approve(User $user, Expense $expense): bool
    {
        if ($user->hasRole('receptionist') || $user->hasRole('trainer')) {
            return false;
        }

        return $user->hasPermission('expenses.manage') && $user->hasBranchAccess($expense->branch_id);
    }

    public function delete(User $user, Expense $expense): bool
    {
        if ($user->hasRole('receptionist') || $user->hasRole('trainer')) {
            return false;
        }

        return $user->hasPermission('expenses.manage') && $user->hasBranchAccess($expense->branch_id);
    }
}
