<?php

namespace App\Services;

use App\Models\ActivityLog;

class AuditLogService
{
    public static function log(string $module, string $action, ?array $oldValues = null, ?array $newValues = null): void
    {
        if (! auth()->check()) {
            return;
        }

        ActivityLog::create([
            'user_id' => auth()->id(),
            'branch_id' => session('active_branch_id'),
            'module' => $module,
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
