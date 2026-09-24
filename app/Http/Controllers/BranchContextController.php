<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BranchContextController extends Controller
{
    public function switch(Request $request): RedirectResponse
    {
        $request->validate([
            'branch_id' => ['nullable'],
        ]);

        $user = auth()->user();
        $branchIdInput = $request->input('branch_id');

        if ($branchIdInput === 'all' || $branchIdInput === null || $branchIdInput === '') {
            if (! $user->hasRole('owner')) {
                abort(403, 'Only Gym Owner can access All Branches mode.');
            }

            session(['active_branch_id' => null]);
            AuditLogService::log('Branch Context', 'Switched to All Branches Context');

            return redirect()->back()->with('success', 'Switched to All Branches view.');
        }

        $branchId = (int) $branchIdInput;

        if (! $user->hasBranchAccess($branchId)) {
            abort(403, 'Unauthorized branch selection.');
        }

        $branch = Branch::findOrFail($branchId);

        session(['active_branch_id' => $branch->id]);
        AuditLogService::log('Branch Context', 'Switched Branch Context to: '.$branch->name);

        return redirect()->back()->with('success', 'Switched to '.$branch->name);
    }
}
