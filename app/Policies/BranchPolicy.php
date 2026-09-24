<?php

namespace App\Policies;

use App\Models\Branch;
use App\Models\User;

class BranchPolicy
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
        return $user->hasPermission('branches.view');
    }

    public function view(User $user, Branch $branch): bool
    {
        return $user->hasPermission('branches.view') && $user->hasBranchAccess($branch->id);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('branches.manage');
    }

    public function update(User $user, Branch $branch): bool
    {
        return $user->hasPermission('branches.manage') && $user->hasBranchAccess($branch->id);
    }

    public function delete(User $user, Branch $branch): bool
    {
        return $user->hasPermission('branches.manage');
    }
}
