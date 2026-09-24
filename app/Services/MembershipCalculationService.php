<?php

namespace App\Services;

use App\Models\Member;
use App\Models\Membership;
use App\Models\MembershipPlan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MembershipCalculationService
{
    public function calculateEndDate(Carbon $startDate, string $durationType, int $durationValue): Carbon
    {
        $startDateCopy = $startDate->copy();

        if ($durationType === 'days') {
            return $startDateCopy->addDays($durationValue)->subDay();
        }

        if ($durationType === 'months') {
            return $startDateCopy->addMonthsNoOverflow($durationValue)->subDay();
        }

        if ($durationType === 'years') {
            return $startDateCopy->addYearsNoOverflow($durationValue)->subDay();
        }

        return $startDateCopy->addMonthsNoOverflow($durationValue)->subDay();
    }

    public function assignMembership(
        int $memberId,
        int $planId,
        ?string $startDateInput = null,
        string $conflictAction = 'stack',
        ?string $notes = null
    ): Membership {
        return DB::transaction(function () use ($memberId, $planId, $startDateInput, $conflictAction, $notes) {
            // Pessimistic locking on member to prevent dual simultaneous active memberships
            $member = Member::where('id', $memberId)->lockForUpdate()->firstOrFail();
            $plan = MembershipPlan::findOrFail($planId);

            // Fetch existing active or scheduled membership
            $existingActive = Membership::where('member_id', $memberId)
                ->whereIn('status', ['active', 'scheduled'])
                ->whereNull('deleted_at')
                ->orderBy('end_date', 'desc')
                ->lockForUpdate()
                ->first();

            $startDate = $startDateInput ? Carbon::parse($startDateInput) : Carbon::today();

            if ($existingActive) {
                if ($conflictAction === 'replace') {
                    $existingActive->update([
                        'status' => 'cancelled',
                        'cancelled_at' => now(),
                        'cancelled_by' => auth()->id(),
                        'cancellation_reason' => 'Replaced by new membership plan: '.$plan->name,
                    ]);
                } elseif ($conflictAction === 'stack' && ! $startDateInput) {
                    // Stacking without explicit start date input: start date becomes existing active end_date + 1 day
                    $latestActive = Membership::where('member_id', $memberId)
                        ->whereIn('status', ['active', 'scheduled'])
                        ->whereNull('deleted_at')
                        ->orderBy('end_date', 'desc')
                        ->first();

                    if ($latestActive) {
                        $startDate = Carbon::parse($latestActive->end_date)->addDay();
                    }
                }
            }

            $endDate = $this->calculateEndDate($startDate, $plan->duration_type, $plan->duration_value);
            $today = Carbon::today();
            $initialStatus = $startDate->gt($today) ? 'scheduled' : 'active';

            return Membership::create([
                'branch_id' => $member->branch_id,
                'member_id' => $member->id,
                'membership_plan_id' => $plan->id,
                'plan_name_snapshot' => $plan->name,
                'plan_code_snapshot' => $plan->code,
                'plan_price_snapshot' => $plan->price,
                'plan_signup_fee_snapshot' => $plan->signup_fee ?? 0.00,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'status' => $initialStatus,
                'notes' => $notes,
                'assigned_by' => auth()->id(),
            ]);
        });
    }
}
