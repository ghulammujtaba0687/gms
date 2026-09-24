<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Member;
use App\Models\Membership;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    public function checkIn(int $memberId, ?string $notes = null): Attendance
    {
        return DB::transaction(function () use ($memberId, $notes) {
            // Pessimistic lock on member record
            $member = Member::where('id', $memberId)->lockForUpdate()->firstOrFail();

            if ($member->status !== 'active') {
                throw ValidationException::withMessages([
                    'member' => 'Member status is inactive. Check-in is blocked.',
                ]);
            }

            // Check if member has an active membership subscription
            $today = Carbon::today()->format('Y-m-d');
            $activeMembership = Membership::where('member_id', $member->id)
                ->where('status', 'active')
                ->where('start_date', '<=', $today)
                ->where('end_date', '>=', $today)
                ->whereNull('deleted_at')
                ->first();

            if (! $activeMembership) {
                // Check if membership is scheduled, expired or cancelled
                $hasScheduled = Membership::where('member_id', $member->id)->where('status', 'scheduled')->exists();
                $hasExpired = Membership::where('member_id', $member->id)->where('status', 'expired')->exists();
                $hasCancelled = Membership::where('member_id', $member->id)->where('status', 'cancelled')->exists();

                if ($hasScheduled) {
                    $msg = 'Check-in blocked: Member subscription is scheduled for a future start date.';
                } elseif ($hasExpired) {
                    $msg = 'Check-in blocked: Member subscription has expired.';
                } elseif ($hasCancelled) {
                    $msg = 'Check-in blocked: Member subscription is cancelled.';
                } else {
                    $msg = 'Check-in blocked: No active membership subscription found for this member.';
                }

                throw ValidationException::withMessages(['membership' => $msg]);
            }

            // Check if member ALREADY has an open attendance session (status = present)
            $openAttendance = Attendance::where('member_id', $member->id)
                ->where('status', 'present')
                ->whereNull('check_out_time')
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->first();

            if ($openAttendance) {
                throw ValidationException::withMessages([
                    'attendance' => 'Member already has an open check-in session from '.$openAttendance->check_in_time->format('h:i A').'. Please check out first.',
                ]);
            }

            $now = Carbon::now();

            return Attendance::create([
                'branch_id' => $member->branch_id,
                'member_id' => $member->id,
                'membership_id' => $activeMembership->id,
                'check_in_time' => $now,
                'check_in_date' => $now->toDateString(),
                'status' => 'present',
                'notes' => $notes,
                'recorded_by' => auth()->id(),
            ]);
        });
    }

    public function checkOut(int $attendanceId): Attendance
    {
        return DB::transaction(function () use ($attendanceId) {
            $attendance = Attendance::where('id', $attendanceId)->lockForUpdate()->firstOrFail();

            if ($attendance->status === 'checked_out' || $attendance->check_out_time !== null) {
                throw ValidationException::withMessages([
                    'attendance' => 'Attendance session has already been checked out.',
                ]);
            }

            $now = Carbon::now();
            $attendance->update([
                'check_out_time' => $now,
                'status' => 'checked_out',
            ]);

            return $attendance;
        });
    }
}
