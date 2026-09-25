<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\Payment;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $activeBranchId = session('active_branch_id');
        $activeBranch = $activeBranchId ? Branch::find($activeBranchId) : null;

        $today = Carbon::today()->toDateString();
        $startOfMonth = Carbon::now()->startOfMonth()->toDateString();
        $endOfMonth = Carbon::now()->endOfMonth()->toDateString();

        // Calculate Revenue Metrics
        $todayRevenue = Payment::where('payment_date', $today)
            ->whereIn('status', ['paid', 'partial'])
            ->sum('amount_paid');

        $monthRevenue = Payment::whereBetween('payment_date', [$startOfMonth, $endOfMonth])
            ->whereIn('status', ['paid', 'partial'])
            ->sum('amount_paid');

        // Calculate Expense Metrics
        $todayExpense = Expense::where('expense_date', $today)
            ->where('status', 'approved')
            ->sum('amount');

        $monthExpense = Expense::whereBetween('expense_date', [$startOfMonth, $endOfMonth])
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
