<?php

namespace App\Http\Controllers;

use App\Http\Requests\Staff\AssignTrainerRequest;
use App\Models\Member;
use App\Models\StaffProfile;
use App\Models\TrainerMemberAssignment;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;

class TrainerAssignmentController extends Controller
{
    public function assign(AssignTrainerRequest $request): RedirectResponse
    {
        $user = auth()->user();
        $member = Member::findOrFail($request->member_id);
        $trainer = StaffProfile::where('is_trainer', true)->findOrFail($request->staff_profile_id);

        if (! $user->hasBranchAccess($member->branch_id) || ! $user->hasBranchAccess($trainer->branch_id)) {
            abort(403, 'Unauthorized cross-branch PT assignment attempt.');
        }

        // Complete any existing active PT assignment for this member
        TrainerMemberAssignment::where('member_id', $member->id)
            ->where('status', 'active')
            ->update(['status' => 'completed']);

        $assignment = TrainerMemberAssignment::create([
            'branch_id' => $member->branch_id,
            'staff_profile_id' => $trainer->id,
            'member_id' => $member->id,
            'assignment_date' => now()->toDateString(),
            'status' => 'active',
            'notes' => $request->notes,
            'assigned_by' => $user->id,
        ]);

        AuditLogService::log(
            'Trainer Assignment',
            'Assigned Trainer: '.$trainer->user->name.' to Member: '.$member->full_name,
            null,
            $assignment->toArray()
        );

        return redirect()->back()->with('success', 'Personal Trainer assigned to member successfully.');
    }
}
