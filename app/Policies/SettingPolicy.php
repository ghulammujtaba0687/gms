<?php

namespace App\Policies;

use App\Models\Setting;
use App\Models\User;

class SettingPolicy
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

        return $user->hasPermission('settings.manage') || $user->hasPermission('audit_logs.view');
    }

    public function view(User $user, Setting $setting): bool
    {
        if ($user->hasRole('receptionist') || $user->hasRole('trainer')) {
            return false;
        }

        if ($setting->isGlobal()) {
            return true;
        }

        return $user->hasBranchAccess($setting->branch_id);
    }

    public function update(User $user, ?Setting $setting = null): bool
    {
        if ($user->hasRole('receptionist') || $user->hasRole('trainer')) {
            return false;
        }

        if (! $setting || $setting->isGlobal()) {
            // Only Owner can update Global settings
            return $user->hasRole('owner');
        }

        return $user->hasPermission('settings.manage') && $user->hasBranchAccess($setting->branch_id);
    }
}
