<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
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
        return $user->hasPermission('payments.manage') || $user->hasPermission('members.view');
    }

    public function view(User $user, Payment $payment): bool
    {
        return $user->hasBranchAccess($payment->branch_id);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('payments.manage');
    }

    public function verify(User $user, Payment $payment): bool
    {
        if ($user->hasRole('receptionist') || $user->hasRole('trainer')) {
            return false;
        }

        return $user->hasPermission('payments.manage') && $user->hasBranchAccess($payment->branch_id);
    }

    public function refund(User $user, Payment $payment): bool
    {
        if ($user->hasRole('receptionist') || $user->hasRole('trainer')) {
            return false;
        }

        return $user->hasPermission('payments.manage') && $user->hasBranchAccess($payment->branch_id);
    }

    public function delete(User $user, Payment $payment): bool
    {
        if ($user->hasRole('receptionist') || $user->hasRole('trainer')) {
            return false;
        }

        return $user->hasPermission('payments.manage') && $user->hasBranchAccess($payment->branch_id);
    }
}
