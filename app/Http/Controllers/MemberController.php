<?php

namespace App\Http\Controllers;

use App\Http\Requests\Member\StoreMemberRequest;
use App\Http\Requests\Member\UpdateMemberRequest;
use App\Models\Member;
use App\Services\AuditLogService;
use App\Services\MemberCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MemberController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Member::class);

        $query = Member::with('branch');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('member_code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $members = $query->latest()->paginate(15)->withQueryString();

        return view('members.index', compact('members'));
    }

    public function create(): View
    {
        $this->authorize('create', Member::class);

        return view('members.create');
    }

    public function store(StoreMemberRequest $request, MemberCodeService $codeService): RedirectResponse
    {
        $this->authorize('create', Member::class);

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
            abort(403, 'Unauthorized branch selection during member creation.');
        }

        $memberCode = $codeService->generate($branchId);
        $photoPath = null;

        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $filename = Str::random(40).'.'.$file->getClientOriginalExtension();
            $photoPath = $file->storeAs("members/photos/{$branchId}", $filename, 'public');
        }

        $member = Member::create([
            'branch_id' => $branchId,
            'member_code' => $memberCode,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'cnic' => $request->cnic,
            'photo_path' => $photoPath,
            'gender' => $request->gender,
            'dob' => $request->dob,
            'phone' => $request->phone,
            'whatsapp' => $request->whatsapp,
            'email' => $request->email,
            'address' => $request->address,
            'emergency_contact_name' => $request->emergency_contact_name,
            'emergency_contact_phone' => $request->emergency_contact_phone,
            'join_date' => $request->join_date,
            'status' => $request->status,
            'notes' => $request->notes,
            'created_by' => $user->id,
        ]);

        AuditLogService::log('Member Management', 'Created Member: '.$member->full_name.' ('.$member->member_code.')', null, $member->toArray());

        return redirect()->route('members.show', $member->id)->with('success', 'Member registered successfully with Code: '.$memberCode);
    }

    public function show(Member $member): View
    {
        $this->authorize('view', $member);

        return view('members.show', compact('member'));
    }

    public function edit(Member $member): View
    {
        $this->authorize('update', $member);

        return view('members.edit', compact('member'));
    }

    public function update(UpdateMemberRequest $request, Member $member): RedirectResponse
    {
        $this->authorize('update', $member);

        $oldData = $member->toArray();
        $photoPath = $member->photo_path;

        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $filename = Str::random(40).'.'.$file->getClientOriginalExtension();
            $photoPath = $file->storeAs("members/photos/{$member->branch_id}", $filename, 'public');
        }

        $member->update([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'cnic' => $request->cnic,
            'photo_path' => $photoPath,
            'gender' => $request->gender,
            'dob' => $request->dob,
            'phone' => $request->phone,
            'whatsapp' => $request->whatsapp,
            'email' => $request->email,
            'address' => $request->address,
            'emergency_contact_name' => $request->emergency_contact_name,
            'emergency_contact_phone' => $request->emergency_contact_phone,
            'join_date' => $request->join_date,
            'status' => $request->status,
            'notes' => $request->notes,
        ]);

        AuditLogService::log('Member Management', 'Updated Member: '.$member->full_name.' ('.$member->member_code.')', $oldData, $member->toArray());

        return redirect()->route('members.show', $member->id)->with('success', 'Member details updated successfully.');
    }

    public function destroy(Member $member): RedirectResponse
    {
        $this->authorize('delete', $member);

        $oldData = $member->toArray();
        $member->delete();

        AuditLogService::log('Member Management', 'Soft-Deleted Member: '.$member->full_name.' ('.$member->member_code.')', $oldData, null);

        return redirect()->route('members.index')->with('success', 'Member archived (soft-deleted) successfully.');
    }

    public function restore(int $id): RedirectResponse
    {
        $member = Member::withTrashed()->findOrFail($id);
        $this->authorize('restore', $member);

        $member->restore();

        AuditLogService::log('Member Management', 'Restored Member: '.$member->full_name.' ('.$member->member_code.')', null, $member->toArray());

        return redirect()->route('members.show', $member->id)->with('success', 'Member restored successfully.');
    }
}
