<?php

namespace App\Http\Controllers;

use App\Models\Branch;

class DashboardController extends Controller
{
    public function index()
    {
        $activeBranchId = session('active_branch_id');
        $activeBranch = $activeBranchId ? Branch::find($activeBranchId) : null;

        $today = \Carbon\Carbon::today()->toDateString();
        $startOfMonth = \Carbon\Carbon::now()->startOfMonth()->toDateString();
        $endOfMonth = \Carbon\Carbon::now()->endOfMonth()->toDateString();

        // Calculate Revenue Metrics
        $todayRevenue = \App\Models\Payment::where('payment_date', $today)
            ->whereIn('status', ['paid', 'partial'])
            ->sum('amount_paid');

        $monthRevenue = \App\Models\Payment::whereBetween('payment_date', [$startOfMonth, $endOfMonth])
            ->whereIn('status', ['paid', 'partial'])
            ->sum('amount_paid');

        // Calculate Expense Metrics
        $todayExpense = \App\Models\Expense::where('expense_date', $today)
            ->where('status', 'approved')
            ->sum('amount');

        $monthExpense = \App\Models\Expense::whereBetween('expense_date', [$startOfMonth, $endOfMonth])
            ->where('status', 'approved')
            ->sum('amount');

        $netMonthlyCashflow = $monthRevenue - $monthExpense;

        return view('dashboard.index', compact(
            'activeBranch',
            'todayRevenue',
            'monthRevenue',
            'todayExpense',
            'monthExpense',
            'netMonthlyCashflow'
        ));
    }
}
