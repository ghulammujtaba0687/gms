<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Expense;
use Illuminate\Support\Facades\DB;

class ExpenseCodeService
{
    public function generate(int $branchId): string
    {
        return DB::transaction(function () use ($branchId) {
            $branch = Branch::findOrFail($branchId);
            $branchCode = strtoupper($branch->code);

            $lastExpense = Expense::where('branch_id', $branchId)
                ->withTrashed()
                ->lockForUpdate()
                ->orderBy('id', 'desc')
                ->first();

            $sequence = 1;

            if ($lastExpense && $lastExpense->expense_code) {
                $parts = explode('-EXP-', $lastExpense->expense_code);
                if (count($parts) === 2 && is_numeric($parts[1])) {
                    $sequence = ((int) $parts[1]) + 1;
                } else {
                    $sequence = Expense::where('branch_id', $branchId)->withTrashed()->count() + 1;
                }
            }

            $formattedSequence = str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);

            return "{$branchCode}-EXP-{$formattedSequence}";
        });
    }
}
