<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\StaffProfile;
use Illuminate\Support\Facades\DB;

class StaffCodeService
{
    public function generate(int $branchId, bool $isTrainer = false): string
    {
        return DB::transaction(function () use ($branchId, $isTrainer) {
            $branch = Branch::findOrFail($branchId);
            $branchCode = strtoupper($branch->code);
            $prefix = $isTrainer ? 'TRN' : 'STF';

            $lastStaff = StaffProfile::where('branch_id', $branchId)
                ->where('is_trainer', $isTrainer)
                ->withTrashed()
                ->lockForUpdate()
                ->orderBy('id', 'desc')
                ->first();

            $sequence = 1;

            if ($lastStaff && $lastStaff->staff_code) {
                $parts = explode("-{$prefix}-", $lastStaff->staff_code);
                if (count($parts) === 2 && is_numeric($parts[1])) {
                    $sequence = ((int) $parts[1]) + 1;
                } else {
                    $sequence = StaffProfile::where('branch_id', $branchId)->where('is_trainer', $isTrainer)->withTrashed()->count() + 1;
                }
            }

            $formattedSequence = str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);

            return "{$branchCode}-{$prefix}-{$formattedSequence}";
        });
    }
}
