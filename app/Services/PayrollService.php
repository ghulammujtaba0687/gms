<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Payroll;
use App\Models\StaffProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayrollService
{
    public function processPayroll(array $data, int $branchId): Payroll
    {
        return DB::transaction(function () use ($data, $branchId) {
            $staff = StaffProfile::where('id', $data['staff_profile_id'])->lockForUpdate()->firstOrFail();

            $monthYearParts = explode('-', $data['salary_month_year']);
            $year = (int) $monthYearParts[0];
            $month = (int) $monthYearParts[1];

            // DB Level duplicate check for same staff + month/year
            $duplicate = Payroll::where('staff_profile_id', $staff->id)
                ->where('payroll_year', $year)
                ->where('payroll_month', $month)
                ->whereNull('deleted_at')
                ->exists();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'salary_month_year' => 'Payroll for '.$staff->user->name.' for '.$data['salary_month_year'].' has already been generated.',
                ]);
            }

            $baseSalary = (float) ($data['base_salary_snapshot'] ?? $staff->monthly_salary);
            $bonus = (float) ($data['bonus_amount'] ?? 0.00);
            $deduction = (float) ($data['deduction_amount'] ?? 0.00);
            $netSalary = max(0, ($baseSalary + $bonus) - $deduction);

            $codeService = new PayrollCodeService;
            $payrollCode = $codeService->generate($branchId, $data['salary_month_year']);

            // Auto-post Salaries Expense in Phase 8 Expense ledger under CAT-SALARY category
            $salaryCategory = ExpenseCategory::where('code', 'CAT-SALARY')->first();
            $expenseCodeService = new ExpenseCodeService;
            $expenseCode = $expenseCodeService->generate($branchId);

            $expense = Expense::create([
                'branch_id' => $branchId,
                'expense_category_id' => $salaryCategory ? $salaryCategory->id : 1,
                'expense_code' => $expenseCode,
                'title' => 'Staff Salary: '.$staff->user->name.' ('.$data['salary_month_year'].')',
                'description' => 'Automated salary disbursement for '.$staff->designation.' ('.$staff->staff_code.')',
                'amount' => $netSalary,
                'expense_date' => $data['payment_date'],
                'payment_method' => $data['payment_method'] ?? 'cash',
                'vendor_name' => $staff->user->name,
                'reference_number' => $payrollCode,
                'status' => 'approved',
                'recorded_by' => auth()->id(),
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            return Payroll::create([
                'branch_id' => $branchId,
                'staff_profile_id' => $staff->id,
                'expense_id' => $expense->id,
                'payroll_code' => $payrollCode,
                'payroll_month' => $month,
                'payroll_year' => $year,
                'salary_month_year' => $data['salary_month_year'],
                'base_salary_snapshot' => $baseSalary,
                'bonus_amount' => $bonus,
                'deduction_amount' => $deduction,
                'net_salary' => $netSalary,
                'payment_method' => $data['payment_method'] ?? 'cash',
                'payment_date' => $data['payment_date'],
                'reference_number' => $data['reference_number'] ?? null,
                'status' => 'paid',
                'notes' => $data['notes'] ?? null,
                'processed_by' => auth()->id(),
            ]);
        });
    }
}
