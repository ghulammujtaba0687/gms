<?php

namespace App\Http\Controllers;

use App\Http\Requests\Staff\StoreStaffRequest;
use App\Http\Requests\Staff\UpdateStaffRequest;
use App\Models\Role;
use App\Models\StaffProfile;
use App\Services\AuditLogService;
use App\Services\StaffService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', StaffProfile::class);

        $query = StaffProfile::with(['user', 'branch']);

        if ($request->boolean('trainers_only')) {
            $query->where('is_trainer', true);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('staff_code', 'like', "%{$search}%")
                    ->orWhere('designation', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $staffProfiles = $query->latest()->paginate(15)->withQueryString();

        return view('staff.index', compact('staffProfiles'));
    }

    public function create(): View
    {
        $this->authorize('create', StaffProfile::class);

        return view('staff.create');
    }

    public function store(StoreStaffRequest $request, StaffService $service): RedirectResponse
    {
        $this->authorize('create', StaffProfile::class);

        $user = auth()->user();
        $branchId = session('active_branch_id');

        if (! $branchId) {
            $firstBranch = $user->branches->first();
            $branchId = $firstBranch ? $firstBranch->id : null;
        }

        if (! $branchId) {
            return redirect()->back()->withErrors(['branch' => 'Please select an active branch first.']);
        }

        if (! $user->hasBranchAccess($branchId)) {
            abort(403, 'Unauthorized cross-branch staff creation attempt.');
        }

        $staff = $service->createStaffWithUser($request->validated(), $branchId);

        AuditLogService::log(
            'Staff Management',
            'Created Staff/Trainer Profile: '.$staff->user->name.' (Code: '.$staff->staff_code.')',
            null,
            $staff->toArray()
        );

        return redirect()->route('staff.show', $staff->id)->with('success', 'Staff profile and user account created successfully with Code: '.$staff->staff_code);
    }

    public function show(StaffProfile $staff): View
    {
        $this->authorize('view', $staff);

        $staff->load(['user', 'branch', 'ptAssignments.member']);

        return view('staff.show', compact('staff'));
    }

    public function edit(StaffProfile $staff): View
    {
        $this->authorize('update', $staff);

        $staff->load('user');

        return view('staff.edit', compact('staff'));
    }

    public function update(UpdateStaffRequest $request, StaffProfile $staff): RedirectResponse
    {
        $this->authorize('update', $staff);

        $oldData = $staff->toArray();

        // Update User account
        $user = $staff->user;
        $userPayload = [
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'status' => $request->status,
        ];

        if ($request->filled('password')) {
            $userPayload['password'] = Hash::make($request->password);
        }

        $user->update($userPayload);

        // Update Role
        $role = Role::where('name', $request->role)->first();
        if ($role) {
            $user->roles()->sync([$role->id]);
        }

        // Update Staff Profile
        $staff->update([
            'designation' => $request->designation,
            'cnic' => $request->cnic,
            'specialization' => $request->specialization,
            'monthly_salary' => $request->monthly_salary ?? 0.00,
            'joining_date' => $request->joining_date,
            'status' => $request->status,
            'notes' => $request->notes,
        ]);

        AuditLogService::log(
            'Staff Management',
            'Updated Staff Profile: '.$staff->staff_code,
            $oldData,
            $staff->toArray()
        );

        return redirect()->route('staff.show', $staff->id)->with('success', 'Staff profile updated successfully.');
    }

    public function destroy(StaffProfile $staff): RedirectResponse
    {
        $this->authorize('delete', $staff);

        $oldData = $staff->toArray();

        // Soft delete both Staff Profile and linked User account
        $staff->delete();
        if ($staff->user) {
            $staff->user->delete();
        }

        AuditLogService::log(
            'Staff Management',
            'Soft-Deleted Staff Profile: '.$staff->staff_code,
            $oldData,
            null
        );

        return redirect()->route('staff.index')->with('success', 'Staff profile archived successfully.');
    }
}
