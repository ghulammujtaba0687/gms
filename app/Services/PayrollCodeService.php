<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Payroll;
use Illuminate\Support\Facades\DB;

class PayrollCodeService
{
    public function generate(int $branchId, string $salaryMonthYear): string
    {
        return DB::transaction(function () use ($branchId, $salaryMonthYear) {
            $branch = Branch::findOrFail($branchId);
            $branchCode = strtoupper($branch->code);
            $formattedMonth = str_replace('-', '', $salaryMonthYear); // e.g., 2025-01 -> 202501

            $lastPayroll = Payroll::where('branch_id', $branchId)
                ->withTrashed()
                ->lockForUpdate()
                ->orderBy('id', 'desc')
                ->first();

            $sequence = 1;

            if ($lastPayroll && $lastPayroll->payroll_code) {
                $parts = explode("-PRL-{$formattedMonth}-", $lastPayroll->payroll_code);
                if (count($parts) === 2 && is_numeric($parts[1])) {
                    $sequence = ((int) $parts[1]) + 1;
                } else {
                    $sequence = Payroll::where('branch_id', $branchId)->withTrashed()->count() + 1;
                }
            }

            $formattedSequence = str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);

            return "{$branchCode}-PRL-{$formattedMonth}-{$formattedSequence}";
        });
    }
}
