<?php

namespace App\Http\Controllers;

use App\Http\Requests\Report\GenerateReportRequest;
use App\Models\GymProfile;
use App\Services\AuditLogService;
use App\Services\ReportExportService;
use App\Services\ReportQueryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();
        if ($user->hasRole('trainer')) {
            abort(403, 'Trainer cannot access reports dashboard.');
        }

        return view('reports.index');
    }

    public function revenue(GenerateReportRequest $request, ReportQueryService $queryService, ReportExportService $exportService)
    {
        $user = auth()->user();
        if ($user->hasRole('receptionist') || $user->hasRole('trainer')) {
            abort(403, 'Unauthorized access to financial revenue reports.');
        }

        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());
        $branchId = session('active_branch_id');

        $data = $queryService->getFinancialData($branchId, $startDate, $endDate);

        AuditLogService::log('Reports & Analytics', 'Generated Revenue & Financial Cashflow Report', null, ['start_date' => $startDate, 'end_date' => $endDate]);

        if ($request->input('export') === 'csv') {
            $rows = [];
            foreach ($data['daily_revenue'] as $rev) {
                $rows[] = [$rev->date, 'Revenue', $rev->total_revenue];
            }
            foreach ($data['daily_expenses'] as $exp) {
                $rows[] = [$exp->date, 'Expense', $exp->total_expense];
            }

            return $exportService->exportCsv('Financial_Revenue_Report', ['Date', 'Type', 'Amount (PKR)'], $rows);
        }

        if ($request->input('export') === 'print') {
            $gymProfile = GymProfile::first();

            return view('reports.printable', [
                'title' => 'Financial Revenue & Net Cashflow Report',
                'gymProfile' => $gymProfile,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'headers' => ['Date', 'Daily Revenue (PKR)', 'Daily Expenses (PKR)'],
                'data' => $data,
            ]);
        }

        return view('reports.revenue', compact('data', 'startDate', 'endDate'));
    }

    public function expiring(GenerateReportRequest $request, ReportQueryService $queryService, ReportExportService $exportService)
    {
        $user = auth()->user();
        if ($user->hasRole('trainer')) {
            abort(403, 'Trainer cannot access membership expiry reports.');
        }

        $daysThreshold = (int) $request->input('days_threshold', 30);
        $branchId = session('active_branch_id');

        $data = $queryService->getExpiringMembershipsData($branchId, $daysThreshold);

        AuditLogService::log('Reports & Analytics', 'Generated Expiring Memberships Report', null, ['days_threshold' => $daysThreshold]);

        if ($request->input('export') === 'csv') {
            $rows = [];
            foreach ($data['expiring_memberships'] as $ms) {
                $rows[] = [
                    $ms->member->member_code ?? '',
                    $ms->member->full_name ?? '',
                    $ms->plan_name_snapshot,
                    $ms->start_date->format('Y-m-d'),
                    $ms->end_date->format('Y-m-d'),
                    $ms->status,
                ];
            }

            return $exportService->exportCsv('Expiring_Memberships_Report', ['Member ID', 'Member Name', 'Plan', 'Start Date', 'Expiry Date', 'Status'], $rows);
        }

        return view('reports.expiring-memberships', compact('data', 'daysThreshold'));
    }

    public function dues(GenerateReportRequest $request, ReportQueryService $queryService, ReportExportService $exportService)
    {
        $user = auth()->user();
        if ($user->hasRole('trainer')) {
            abort(403, 'Trainer cannot access dues reports.');
        }

        $branchId = session('active_branch_id');
        $data = $queryService->getDuesData($branchId);

        AuditLogService::log('Reports & Analytics', 'Generated Outstanding Dues Report');

        if ($request->input('export') === 'csv') {
            $rows = [];
            foreach ($data['outstanding_payments'] as $pay) {
                $rows[] = [
                    $pay->payment_code,
                    $pay->member->member_code ?? '',
                    $pay->member->full_name ?? '',
                    $pay->amount_due,
                    $pay->amount_paid,
                    $pay->remaining_balance,
                    $pay->payment_date->format('Y-m-d'),
                ];
            }

            return $exportService->exportCsv('Outstanding_Dues_Report', ['Receipt Code', 'Member ID', 'Member Name', 'Total Due', 'Amount Paid', 'Remaining Dues (PKR)', 'Date'], $rows);
        }

        return view('reports.dues', compact('data'));
    }

    public function attendance(GenerateReportRequest $request, ReportQueryService $queryService, ReportExportService $exportService)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->toDateString());
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->toDateString());
        $branchId = session('active_branch_id');

        $data = $queryService->getAttendanceData($branchId, $startDate, $endDate);

        AuditLogService::log('Reports & Analytics', 'Generated Attendance Report', null, ['start_date' => $startDate, 'end_date' => $endDate]);

        if ($request->input('export') === 'csv') {
            $rows = [];
            foreach ($data['attendance_list'] as $att) {
                $rows[] = [
                    $att->check_in_date->format('Y-m-d'),
                    $att->member->member_code ?? '',
                    $att->member->full_name ?? '',
                    $att->check_in_time->format('h:i A'),
                    $att->check_out_time ? $att->check_out_time->format('h:i A') : 'N/A',
                    $att->status,
                ];
            }

            return $exportService->exportCsv('Attendance_Report', ['Date', 'Member ID', 'Member Name', 'Check-In', 'Check-Out', 'Status'], $rows);
        }

        return view('reports.attendance', compact('data', 'startDate', 'endDate'));
    }
}
