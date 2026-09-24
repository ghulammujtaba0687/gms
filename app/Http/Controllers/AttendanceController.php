<?php

namespace App\Http\Controllers;

use App\Http\Requests\Attendance\CheckInRequest;
use App\Http\Requests\Attendance\UpdateAttendanceRequest;
use App\Models\Attendance;
use App\Models\Member;
use App\Services\AttendanceService;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Attendance::class);

        $query = Attendance::with(['member', 'membership', 'branch', 'recorder']);

        if ($request->filled('date')) {
            $query->where('check_in_date', $request->date);
        } else {
            $query->where('check_in_date', now()->toDateString());
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('member', function ($mq) use ($search) {
                $mq->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('member_code', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $attendances = $query->latest('check_in_time')->paginate(15)->withQueryString();

        return view('attendances.index', compact('attendances'));
    }

    public function create(Request $request): View
    {
        $this->authorize('checkIn', Attendance::class);

        $user = auth()->user();
        $selectedMember = null;

        if ($request->filled('search')) {
            $search = $request->search;
            $selectedMember = Member::where('member_code', $search)
                ->orWhere('phone', $search)
                ->first();

            if ($selectedMember && ! $user->hasBranchAccess($selectedMember->branch_id)) {
                $selectedMember = null;
            }
        } elseif ($request->filled('member_id')) {
            $selectedMember = Member::find($request->member_id);
            if ($selectedMember && ! $user->hasBranchAccess($selectedMember->branch_id)) {
                $selectedMember = null;
            }
        }

        return view('attendances.create', compact('selectedMember'));
    }

    public function store(CheckInRequest $request, AttendanceService $service): RedirectResponse
    {
        $this->authorize('checkIn', Attendance::class);

        $user = auth()->user();
        $member = Member::findOrFail($request->member_id);

        if (! $user->hasBranchAccess($member->branch_id)) {
            abort(403, 'Unauthorized cross-branch check-in attempt.');
        }

        $attendance = $service->checkIn($member->id, $request->notes);

        AuditLogService::log(
            'Attendance',
            'Checked In Member: '.$member->full_name.' ('.$member->member_code.')',
            null,
            $attendance->toArray()
        );

        return redirect()->route('attendances.index')->with('success', 'Member '.$member->full_name.' checked in successfully.');
    }

    public function checkout(Attendance $attendance, AttendanceService $service): RedirectResponse
    {
        $this->authorize('checkOut', $attendance);

        $oldData = $attendance->toArray();
        $updatedAttendance = $service->checkOut($attendance->id);

        AuditLogService::log(
            'Attendance',
            'Checked Out Member: '.$attendance->member->full_name.' ('.$attendance->member->member_code.')',
            $oldData,
            $updatedAttendance->toArray()
        );

        return redirect()->back()->with('success', 'Member checked out successfully.');
    }

    public function edit(Attendance $attendance): View
    {
        $this->authorize('update', $attendance);

        return view('attendances.edit', compact('attendance'));
    }

    public function update(UpdateAttendanceRequest $request, Attendance $attendance): RedirectResponse
    {
        $this->authorize('update', $attendance);

        $oldData = $attendance->toArray();

        $attendance->update([
            'check_in_time' => $request->check_in_time,
            'check_out_time' => $request->check_out_time,
            'status' => $request->status,
            'notes' => $request->notes,
        ]);

        AuditLogService::log(
            'Attendance',
            'Updated Attendance Record for: '.$attendance->member->full_name,
            $oldData,
            $attendance->toArray()
        );

        return redirect()->route('attendances.index')->with('success', 'Attendance record updated successfully.');
    }

    public function destroy(Attendance $attendance): RedirectResponse
    {
        $this->authorize('delete', $attendance);

        $oldData = $attendance->toArray();
        $attendance->delete();

        AuditLogService::log(
            'Attendance',
            'Deleted Attendance Record for: '.$attendance->member->full_name,
            $oldData,
            null
        );

        return redirect()->back()->with('success', 'Attendance record deleted successfully.');
    }
}
