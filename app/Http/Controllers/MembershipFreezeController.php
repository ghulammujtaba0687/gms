<?php

namespace App\Http\Controllers;

use App\Http\Requests\MembershipFreeze\CancelFreezeRequest;
use App\Http\Requests\MembershipFreeze\StoreFreezeRequest;
use App\Models\Member;
use App\Models\Membership;
use App\Models\MembershipFreeze;
use App\Services\AuditLogService;
use App\Services\MembershipFreezeService;
use App\Services\NotificationService;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MembershipFreezeController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', MembershipFreeze::class);

        // Pending Freezes Queue for Manager/Owner approval
        $pendingFreezes = MembershipFreeze::with(['member', 'membership', 'branch', 'requester'])
            ->where('status', 'pending')
            ->latest()
            ->paginate(15);

        return view('membership-freezes.index', compact('pendingFreezes'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', MembershipFreeze::class);

        $selectedMembership = null;
        if ($request->filled('membership_id')) {
            $selectedMembership = Membership::with('member')->find($request->membership_id);
            if ($selectedMembership && ! auth()->user()->hasBranchAccess($selectedMembership->branch_id)) {
                abort(403, 'Unauthorized cross-branch freeze selection.');
            }
        }

        return view('membership-freezes.create', compact('selectedMembership'));
    }

    public function store(StoreFreezeRequest $request, MembershipFreezeService $service): RedirectResponse
    {
        $this->authorize('create', MembershipFreeze::class);

        $membership = Membership::findOrFail($request->membership_id);

        if (! auth()->user()->hasBranchAccess($membership->branch_id)) {
            abort(403, 'Unauthorized cross-branch freeze attempt.');
        }

        $freeze = $service->requestFreeze(
            $membership->id,
            $request->freeze_start_date,
            $request->freeze_end_date,
            $request->reason
        );

        $msg = $freeze->status === 'approved'
            ? 'Membership freeze approved & expiry extended successfully.'
            : 'Membership freeze request submitted for Manager approval.';

        AuditLogService::log(
            'Membership Freezes',
            'Requested/Approved Freeze for Member: '.$membership->member->full_name.' ('.$freeze->frozen_days.' days)',
            null,
            $freeze->toArray()
        );

        return redirect()->route('members.show', $membership->member_id)->with('success', $msg);
    }

    public function approve(MembershipFreeze $membershipFreeze, MembershipFreezeService $service): RedirectResponse
    {
        $this->authorize('approve', $membershipFreeze);

        $oldData = $membershipFreeze->toArray();
        $approvedFreeze = $service->approveFreeze($membershipFreeze->id);

        AuditLogService::log(
            'Membership Freezes',
            'Approved Pending Freeze ID: '.$approvedFreeze->id.' for Member: '.$approvedFreeze->member->full_name,
            $oldData,
            $approvedFreeze->toArray()
        );

        // System Notification on Freeze Approval (Phase 13)
        if (SettingService::get('enable_freeze_notifications', true, $approvedFreeze->branch_id)) {
            app(NotificationService::class)->createNotification([
                'branch_id' => $approvedFreeze->branch_id,
                'type' => 'freeze_approved',
                'title' => 'Membership Freeze Approved',
                'message' => "Freeze approved for member {$approvedFreeze->member->full_name} for {$approvedFreeze->frozen_days} days ({$approvedFreeze->freeze_start_date} to {$approvedFreeze->freeze_end_date}).",
                'idempotency_key' => "freeze_app_{$approvedFreeze->id}",
                'notifiable_type' => Member::class,
                'notifiable_id' => $approvedFreeze->member_id,
            ]);
        }

        return redirect()->back()->with('success', 'Membership freeze approved & subscription expiry extended.');
    }

    public function cancel(CancelFreezeRequest $request, MembershipFreeze $membershipFreeze, MembershipFreezeService $service): RedirectResponse
    {
        $this->authorize('cancel', $membershipFreeze);

        $oldData = $membershipFreeze->toArray();
        $cancelledFreeze = $service->cancelFreeze($membershipFreeze->id, $request->cancellation_reason);

        AuditLogService::log(
            'Membership Freezes',
            'Cancelled Freeze ID: '.$cancelledFreeze->id.' for Member: '.$cancelledFreeze->member->full_name,
            $oldData,
            $cancelledFreeze->toArray()
        );

        return redirect()->back()->with('success', 'Membership freeze cancelled & original subscription end date restored.');
    }

    public function destroy(MembershipFreeze $membershipFreeze): RedirectResponse
    {
        $this->authorize('delete', $membershipFreeze);

        $oldData = $membershipFreeze->toArray();
        $membershipFreeze->delete();

        AuditLogService::log(
            'Membership Freezes',
            'Deleted Freeze Record for Member: '.$membershipFreeze->member->full_name,
            $oldData,
            null
        );

        return redirect()->back()->with('success', 'Freeze record deleted successfully.');
    }
}
