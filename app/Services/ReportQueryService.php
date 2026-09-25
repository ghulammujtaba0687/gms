<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Expense;
use App\Models\Member;
use App\Models\Membership;
use App\Models\Payment;
use App\Models\StaffProfile;
use App\Models\TrainerMemberAssignment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportQueryService
{
    public function getFinancialData(?int $branchId, string $startDate, string $endDate): array
    {
        $paymentsQuery = Payment::whereBetween('payment_date', [$startDate, $endDate])
            ->whereIn('status', ['paid', 'partial']);

        $expensesQuery = Expense::whereBetween('expense_date', [$startDate, $endDate])
            ->where('status', 'approved');

        if ($branchId) {
            $paymentsQuery->where('branch_id', $branchId);
            $expensesQuery->where('branch_id', $branchId);
        }

        $totalRevenue = (float) $paymentsQuery->sum('amount_paid');
        $totalExpenses = (float) $expensesQuery->sum('amount');
        $netCashflow = $totalRevenue - $totalExpenses;

        $dailyRevenue = Payment::whereBetween('payment_date', [$startDate, $endDate])
            ->whereIn('status', ['paid', 'partial'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->select(DB::raw('payment_date as date'), DB::raw('SUM(amount_paid) as total_revenue'))
            ->groupBy('payment_date')
            ->orderBy('date')
            ->get();

        $dailyExpenses = Expense::whereBetween('expense_date', [$startDate, $endDate])
            ->where('status', 'approved')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->select(DB::raw('expense_date as date'), DB::raw('SUM(amount) as total_expense'))
            ->groupBy('expense_date')
            ->orderBy('date')
            ->get();

        return [
            'total_revenue' => $totalRevenue,
            'total_expenses' => $totalExpenses,
            'net_cashflow' => $netCashflow,
            'daily_revenue' => $dailyRevenue,
            'daily_expenses' => $dailyExpenses,
        ];
    }

    public function getMembersData(?int $branchId, string $startDate, string $endDate): array
    {
        $newRegistrations = Member::whereBetween('join_date', [$startDate, $endDate])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->count();

        $activeMembers = Member::where('status', 'active')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->count();

        $inactiveMembers = Member::where('status', 'inactive')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->count();

        $memberList = Member::with('branch')
            ->whereBetween('join_date', [$startDate, $endDate])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->latest('join_date')
            ->get();

        return [
            'new_registrations' => $newRegistrations,
            'active_members' => $activeMembers,
            'inactive_members' => $inactiveMembers,
            'member_list' => $memberList,
        ];
    }

    public function getExpiringMembershipsData(?int $branchId, int $daysThreshold = 30): array
    {
        $today = Carbon::today()->toDateString();
        $targetDate = Carbon::today()->addDays($daysThreshold)->toDateString();

        $expiringQuery = Membership::with(['member', 'plan', 'branch'])
            ->where('status', 'active')
            ->whereBetween('end_date', [$today, $targetDate]);

        if ($branchId) {
            $expiringQuery->where('branch_id', $branchId);
        }

        $expiringMemberships = $expiringQuery->orderBy('end_date')->get();

        $expiredQuery = Membership::with(['member', 'plan', 'branch'])
            ->where('status', 'expired');

        if ($branchId) {
            $expiredQuery->where('branch_id', $branchId);
        }

        $expiredMemberships = $expiredQuery->latest('end_date')->limit(50)->get();

        return [
            'days_threshold' => $daysThreshold,
            'expiring_count' => $expiringMemberships->count(),
            'expiring_memberships' => $expiringMemberships,
            'expired_memberships' => $expiredMemberships,
        ];
    }

    public function getDuesData(?int $branchId): array
    {
        $duesQuery = Payment::with(['member', 'branch'])
            ->where('remaining_balance', '>', 0)
            ->whereIn('status', ['paid', 'partial', 'pending_verification']);

        if ($branchId) {
            $duesQuery->where('branch_id', $branchId);
        }

        $outstandingPayments = $duesQuery->latest('payment_date')->get();
        $totalOutstanding = (float) $outstandingPayments->sum('remaining_balance');

        return [
            'total_outstanding' => $totalOutstanding,
            'outstanding_count' => $outstandingPayments->count(),
            'outstanding_payments' => $outstandingPayments,
        ];
    }

    public function getAttendanceData(?int $branchId, string $startDate, string $endDate): array
    {
        $attendanceQuery = Attendance::with(['member', 'branch'])
            ->whereBetween('check_in_date', [$startDate, $endDate]);

        if ($branchId) {
            $attendanceQuery->where('branch_id', $branchId);
        }

        $totalCheckIns = $attendanceQuery->count();

        $dailyCheckIns = Attendance::whereBetween('check_in_date', [$startDate, $endDate])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->select('check_in_date', DB::raw('COUNT(*) as total'))
            ->groupBy('check_in_date')
            ->orderBy('check_in_date')
            ->get();

        $attendanceList = $attendanceQuery->latest('check_in_time')->limit(100)->get();

        return [
            'total_check_ins' => $totalCheckIns,
            'daily_check_ins' => $dailyCheckIns,
            'attendance_list' => $attendanceList,
        ];
    }

    public function getTrainerData(?int $branchId): array
    {
        $trainersQuery = StaffProfile::with(['user', 'branch', 'ptAssignments' => function ($q) {
            $q->where('status', 'active');
        }])->where('is_trainer', true)->where('status', 'active');

        if ($branchId) {
            $trainersQuery->where('branch_id', $branchId);
        }

        $trainers = $trainersQuery->get();

        $totalPtAssignments = TrainerMemberAssignment::where('status', 'active')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->count();

        return [
            'total_trainers' => $trainers->count(),
            'total_active_pt_clients' => $totalPtAssignments,
            'trainers' => $trainers,
        ];
    }
}
