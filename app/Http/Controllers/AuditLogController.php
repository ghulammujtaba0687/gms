<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();

        if ($user->hasRole('receptionist') || $user->hasRole('trainer')) {
            abort(403, 'Unauthorized access to system audit logs.');
        }

        $query = ActivityLog::with(['user', 'branch']);

        if (! $user->hasRole('owner')) {
            // Managers view only logs from their assigned branches
            $assignedBranchIds = $user->branches->pluck('id')->toArray();
            $query->whereIn('branch_id', $assignedBranchIds);
        }

        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $auditLogs = $query->latest()->paginate(20)->withQueryString();

        return view('audit-logs.index', compact('auditLogs'));
    }
}
