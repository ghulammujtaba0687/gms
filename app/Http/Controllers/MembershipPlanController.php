<?php

namespace App\Http\Controllers;

use App\Http\Requests\MembershipPlan\StoreMembershipPlanRequest;
use App\Http\Requests\MembershipPlan\UpdateMembershipPlanRequest;
use App\Models\MembershipPlan;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MembershipPlanController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', MembershipPlan::class);

        $user = auth()->user();
        $activeBranchId = session('active_branch_id');

        $query = MembershipPlan::with('branch');

        if (! $user->hasRole('owner')) {
            // Manager/Receptionist/Trainer see Global Plans OR their active branch plans
            $assignedBranchIds = $user->branches->pluck('id')->toArray();
            $query->where(function ($q) use ($activeBranchId, $assignedBranchIds) {
                $q->whereNull('branch_id');
                if ($activeBranchId) {
                    $q->orWhere('branch_id', $activeBranchId);
                } else {
                    $q->orWhereIn('branch_id', $assignedBranchIds);
                }
            });
        } else {
            // Owner in Specific Branch mode sees Global Plans + Specific Branch Plans
            if ($activeBranchId) {
                $query->where(function ($q) use ($activeBranchId) {
                    $q->whereNull('branch_id')->orWhere('branch_id', $activeBranchId);
                });
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $plans = $query->latest()->paginate(15)->withQueryString();

        return view('membership-plans.index', compact('plans'));
    }

    public function create(): View
    {
        $this->authorize('create', MembershipPlan::class);

        return view('membership-plans.create');
    }

    public function store(StoreMembershipPlanRequest $request): RedirectResponse
    {
        $this->authorize('create', MembershipPlan::class);

        $user = auth()->user();
        $branchId = null;

        if ($user->hasRole('owner') && $request->input('scope') === 'global') {
            $branchId = null;
        } else {
            $branchId = session('active_branch_id');
            if (! $branchId) {
                $firstBranch = $user->branches->first();
                $branchId = $firstBranch ? $firstBranch->id : null;
            }
        }

        if ($branchId && ! $user->hasBranchAccess($branchId)) {
            abort(403, 'Unauthorized branch selection.');
        }

        $plan = MembershipPlan::create([
            'branch_id' => $branchId,
            'code' => strtoupper($request->code),
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'duration_type' => $request->duration_type,
            'duration_value' => $request->duration_value,
            'signup_fee' => $request->signup_fee ?? 0.00,
            'is_active' => $request->boolean('is_active', true),
            'created_by' => $user->id,
        ]);

        AuditLogService::log('Membership Plans', 'Created Membership Plan: '.$plan->name.' ('.$plan->code.')', null, $plan->toArray());

        return redirect()->route('membership-plans.index')->with('success', 'Membership plan created successfully.');
    }

    public function edit(MembershipPlan $membershipPlan): View
    {
        $this->authorize('update', $membershipPlan);

        return view('membership-plans.edit', ['plan' => $membershipPlan]);
    }

    public function update(UpdateMembershipPlanRequest $request, MembershipPlan $membershipPlan): RedirectResponse
    {
        $this->authorize('update', $membershipPlan);

        $oldData = $membershipPlan->toArray();

        $membershipPlan->update([
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'duration_type' => $request->duration_type,
            'duration_value' => $request->duration_value,
            'signup_fee' => $request->signup_fee ?? 0.00,
            'is_active' => $request->boolean('is_active', true),
        ]);

        AuditLogService::log('Membership Plans', 'Updated Membership Plan: '.$membershipPlan->name.' ('.$membershipPlan->code.')', $oldData, $membershipPlan->toArray());

        return redirect()->route('membership-plans.index')->with('success', 'Membership plan updated successfully.');
    }

    public function destroy(MembershipPlan $membershipPlan): RedirectResponse
    {
        $this->authorize('delete', $membershipPlan);

        $oldData = $membershipPlan->toArray();
        $membershipPlan->delete();

        AuditLogService::log('Membership Plans', 'Soft-Deleted Membership Plan: '.$membershipPlan->name.' ('.$membershipPlan->code.')', $oldData, null);

        return redirect()->route('membership-plans.index')->with('success', 'Membership plan archived successfully.');
    }

    public function restore(int $id): RedirectResponse
    {
        $plan = MembershipPlan::withTrashed()->findOrFail($id);
        $this->authorize('restore', $plan);

        // Check if an active plan with the same code exists in the same scope
        $activeConflict = MembershipPlan::where('code', $plan->code)
            ->where('branch_id', $plan->branch_id)
            ->whereNull('deleted_at')
            ->exists();

        if ($activeConflict) {
            return redirect()->back()->with('error', 'Cannot restore plan. An active plan with code "'.$plan->code.'" already exists in this scope.');
        }

        $plan->restore();

        AuditLogService::log('Membership Plans', 'Restored Membership Plan: '.$plan->name.' ('.$plan->code.')', null, $plan->toArray());

        return redirect()->route('membership-plans.index')->with('success', 'Membership plan restored successfully.');
    }
}
