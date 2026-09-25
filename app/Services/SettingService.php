<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingService
{
    public static function get(string $key, mixed $default = null, ?int $branchId = null): mixed
    {
        $targetBranchId = $branchId ?? session('active_branch_id');
        $cacheKey = "setting_{$key}_branch_".($targetBranchId ?? 'global');

        return Cache::remember($cacheKey, 3600, function () use ($key, $default, $targetBranchId) {
            // 1. Try Branch Specific Setting
            if ($targetBranchId) {
                $branchSetting = Setting::where('key', $key)
                    ->where('branch_id', $targetBranchId)
                    ->first();

                if ($branchSetting && $branchSetting->value !== null) {
                    return $branchSetting->value;
                }
            }

            // 2. Try Global Setting (branch_id IS NULL)
            $globalSetting = Setting::where('key', $key)
                ->whereNull('branch_id')
                ->first();

            if ($globalSetting && $globalSetting->value !== null) {
                return $globalSetting->value;
            }

            // 3. Fallback to application default
            return $default;
        });
    }

    public static function set(string $key, mixed $value, ?int $branchId = null, string $group = 'general'): Setting
    {
        $setting = Setting::updateOrCreate(
            ['key' => $key, 'branch_id' => $branchId],
            ['value' => $value, 'group' => $group]
        );

        // Clear Cache
        $cacheKey = "setting_{$key}_branch_".($branchId ?? 'global');
        Cache::forget($cacheKey);

        return $setting;
    }
}
