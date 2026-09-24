<?php

namespace App\Http\Controllers;

use App\Http\Requests\Membership\CancelMembershipRequest;
use App\Http\Requests\Membership\StoreMembershipRequest;
use App\Models\Member;
use App\Models\Membership;
use App\Models\MembershipPlan;
use App\Services\AuditLogService;
use App\Services\MembershipCalculationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MembershipController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Membership::class);

        $query = Membership::with(['member', 'plan', 'branch']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('member', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('member_code', 'like', "%{$search}%");
            });
        }

        $memberships = $query->latest()->paginate(15)->withQueryString();

        return view('memberships.index', compact('memberships'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Membership::class);

        $user = auth()->user();
        $selectedMember = null;

        if ($request->filled('member_id')) {
            $selectedMember = Member::find($request->member_id);
            if ($selectedMember && ! $user->hasBranchAccess($selectedMember->branch_id)) {
                abort(403, 'Unauthorized member branch selection.');
            }
        }

        $plans = MembershipPlan::where('is_active', true)->get();

        return view('memberships.create', compact('selectedMember', 'plans'));
    }

    public function store(StoreMembershipRequest $request, MembershipCalculationService $service): RedirectResponse
    {
        $this->authorize('create', Membership::class);

        $user = auth()->user();
        $member = Member::findOrFail($request->member_id);

        if (! $user->hasBranchAccess($member->branch_id)) {
            abort(403, 'Unauthorized cross-branch membership assignment.');
        }

        $membership = $service->assignMembership(
            $member->id,
            $request->membership_plan_id,
            $request->start_date,
            $request->input('conflict_action', 'stack'),
            $request->notes
        );

        AuditLogService::log(
            'Membership Subscriptions',
            'Assigned Membership Plan: '.$membership->plan_name_snapshot.' to Member: '.$member->full_name,
            null,
            $membership->toArray()
        );

        return redirect()->route('members.show', $member->id)->with('success', 'Membership assigned successfully.');
    }

    public function show(Membership $membership): View
    {
        $this->authorize('view', $membership);

        return view('memberships.show', compact('membership'));
    }

    public function cancel(CancelMembershipRequest $request, Membership $membership): RedirectResponse
    {
        $this->authorize('cancel', $membership);

        $oldData = $membership->toArray();

        $membership->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancelled_by' => auth()->id(),
            'cancellation_reason' => $request->cancellation_reason,
        ]);

        AuditLogService::log(
            'Membership Subscriptions',
            'Cancelled Membership: '.$membership->plan_name_snapshot.' for Member: '.$membership->member->full_name,
            $oldData,
            $membership->toArray()
        );

        return redirect()->back()->with('success', 'Membership cancelled successfully.');
    }

    public function destroy(Membership $membership): RedirectResponse
    {
        $this->authorize('delete', $membership);

        $oldData = $membership->toArray();
        $membership->delete();

        AuditLogService::log(
            'Membership Subscriptions',
            'Soft-Deleted Membership record for Member: '.$membership->member->full_name,
            $oldData,
            null
        );

        return redirect()->back()->with('success', 'Membership record archived successfully.');
    }
}
