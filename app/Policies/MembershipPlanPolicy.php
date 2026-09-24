<?php

namespace App\Policies;

use App\Models\MembershipPlan;
use App\Models\User;

class MembershipPlanPolicy
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
        return $user->hasPermission('membership_plans.manage') || $user->hasPermission('memberships.manage') || $user->hasPermission('members.view');
    }

    public function view(User $user, MembershipPlan $membershipPlan): bool
    {
        if ($membershipPlan->isGlobal()) {
            return true;
        }

        return $user->hasBranchAccess($membershipPlan->branch_id);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('membership_plans.manage');
    }

    public function update(User $user, MembershipPlan $membershipPlan): bool
    {
        if ($membershipPlan->isGlobal()) {
            return false; // Manager cannot update Global Plans
        }

        return $user->hasPermission('membership_plans.manage') && $user->hasBranchAccess($membershipPlan->branch_id);
    }

    public function delete(User $user, MembershipPlan $membershipPlan): bool
    {
        if ($membershipPlan->isGlobal()) {
            return false;
        }

        return ($user->hasPermission('membership_plans.manage') || $user->hasPermission('memberships.manage'))
            && $user->hasBranchAccess($membershipPlan->branch_id);
    }

    public function restore(User $user, MembershipPlan $membershipPlan): bool
    {
        if ($membershipPlan->isGlobal()) {
            return false;
        }

        return ($user->hasPermission('membership_plans.manage') || $user->hasPermission('memberships.manage'))
            && $user->hasBranchAccess($membershipPlan->branch_id);
    }
}
