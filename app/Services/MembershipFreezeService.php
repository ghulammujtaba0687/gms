<?php

namespace App\Services;

use App\Models\Membership;
use App\Models\MembershipFreeze;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MembershipFreezeService
{
    public function requestFreeze(
        int $membershipId,
        string $startDateInput,
        string $endDateInput,
        string $reason
    ): MembershipFreeze {
        return DB::transaction(function () use ($membershipId, $startDateInput, $endDateInput, $reason) {
            $membership = Membership::where('id', $membershipId)->lockForUpdate()->firstOrFail();

            if ($membership->status !== 'active') {
                throw ValidationException::withMessages([
                    'membership' => 'Only active memberships can be frozen.',
                ]);
            }

            $startDate = Carbon::parse($startDateInput);
            $endDate = Carbon::parse($endDateInput);
            $today = Carbon::today();

            if ($startDate->lt($today)) {
                throw ValidationException::withMessages([
                    'freeze_start_date' => 'Freeze start date cannot be in the past.',
                ]);
            }

            if ($endDate->lt($startDate)) {
                throw ValidationException::withMessages([
                    'freeze_end_date' => 'Freeze end date must be on or after start date.',
                ]);
            }

            $frozenDays = $startDate->diffInDays($endDate) + 1;

            if ($frozenDays < 3) {
                throw ValidationException::withMessages([
                    'freeze_end_date' => 'Minimum freeze duration is 3 days.',
                ]);
            }

            if ($frozenDays > 30) {
                throw ValidationException::withMessages([
                    'freeze_end_date' => 'Maximum freeze duration is 30 days.',
                ]);
            }

            // Check for overlapping freezes on this membership
            $overlapping = MembershipFreeze::where('membership_id', $membership->id)
                ->whereIn('status', ['pending', 'approved', 'active'])
                ->whereNull('deleted_at')
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('freeze_start_date', [$startDate->toDateString(), $endDate->toDateString()])
                        ->orWhereBetween('freeze_end_date', [$startDate->toDateString(), $endDate->toDateString()])
                        ->orWhere(function ($sub) use ($startDate, $endDate) {
                            $sub->where('freeze_start_date', '<=', $startDate->toDateString())
                                ->where('freeze_end_date', '>=', $endDate->toDateString());
                        });
                })
                ->exists();

            if ($overlapping) {
                throw ValidationException::withMessages([
                    'freeze_start_date' => 'Requested freeze period overlaps with an existing freeze record.',
                ]);
            }

            $user = auth()->user();

            // Receptionist requests remain PENDING. Manager/Owner requests are AUTO-APPROVED immediately.
            if ($user->hasRole('receptionist')) {
                return MembershipFreeze::create([
                    'branch_id' => $membership->branch_id,
                    'member_id' => $membership->member_id,
                    'membership_id' => $membership->id,
                    'freeze_start_date' => $startDate->toDateString(),
                    'freeze_end_date' => $endDate->toDateString(),
                    'frozen_days' => $frozenDays,
                    'reason' => $reason,
                    'status' => 'pending',
                    'requested_by' => $user->id,
                ]);
            }

            // Manager / Owner -> Immediate Auto Approval + Membership End-Date Extension
            $freeze = MembershipFreeze::create([
                'branch_id' => $membership->branch_id,
                'member_id' => $membership->member_id,
                'membership_id' => $membership->id,
                'freeze_start_date' => $startDate->toDateString(),
                'freeze_end_date' => $endDate->toDateString(),
                'frozen_days' => $frozenDays,
                'reason' => $reason,
                'status' => 'approved',
                'requested_by' => $user->id,
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);

            // Extend membership end_date by frozen_days
            $newEndDate = Carbon::parse($membership->end_date)->addDays($frozenDays);
            $membership->update(['end_date' => $newEndDate->toDateString()]);

            return $freeze;
        });
    }

    public function approveFreeze(int $freezeId): MembershipFreeze
    {
        return DB::transaction(function () use ($freezeId) {
            $freeze = MembershipFreeze::where('id', $freezeId)->lockForUpdate()->firstOrFail();

            if ($freeze->status !== 'pending') {
                throw ValidationException::withMessages([
                    'freeze' => 'Only pending freeze requests can be approved.',
                ]);
            }

            $membership = Membership::where('id', $freeze->membership_id)->lockForUpdate()->firstOrFail();

            $freeze->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            // Extend membership end_date by frozen_days
            $newEndDate = Carbon::parse($membership->end_date)->addDays($freeze->frozen_days);
            $membership->update(['end_date' => $newEndDate->toDateString()]);

            return $freeze;
        });
    }

    public function cancelFreeze(int $freezeId, string $reason): MembershipFreeze
    {
        return DB::transaction(function () use ($freezeId, $reason) {
            $freeze = MembershipFreeze::where('id', $freezeId)->lockForUpdate()->firstOrFail();

            if ($freeze->status === 'cancelled') {
                throw ValidationException::withMessages([
                    'freeze' => 'Freeze record is already cancelled.',
                ]);
            }

            $membership = Membership::where('id', $freeze->membership_id)->lockForUpdate()->firstOrFail();

            // If freeze was APPROVED, restore the extended days back on membership end_date
            if (in_array($freeze->status, ['approved', 'active'])) {
                $newEndDate = Carbon::parse($membership->end_date)->subDays($freeze->frozen_days);
                $membership->update(['end_date' => $newEndDate->toDateString()]);
            }

            $freeze->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => auth()->id(),
                'cancellation_reason' => $reason,
            ]);

            return $freeze;
        });
    }
}
