<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Member;
use Illuminate\Support\Facades\DB;

class MemberCodeService
{
    public function generate(int $branchId): string
    {
        return DB::transaction(function () use ($branchId) {
            $branch = Branch::findOrFail($branchId);
            $branchCode = strtoupper($branch->code);

            // Fetch last member for this branch including soft-deleted ones with pessimistic locking
            $lastMember = Member::where('branch_id', $branchId)
                ->withTrashed()
                ->lockForUpdate()
                ->orderBy('id', 'desc')
                ->first();

            $sequence = 1;

            if ($lastMember && $lastMember->member_code) {
                // Extract sequence from code e.g. DHA-M-00005 -> 5
                $parts = explode('-M-', $lastMember->member_code);
                if (count($parts) === 2 && is_numeric($parts[1])) {
                    $sequence = ((int) $parts[1]) + 1;
                } else {
                    $sequence = Member::where('branch_id', $branchId)->withTrashed()->count() + 1;
                }
            }

            $formattedSequence = str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);

            return "{$branchCode}-M-{$formattedSequence}";
        });
    }
}
