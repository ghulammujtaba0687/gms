<?php

namespace App\Http\Controllers;

use App\Http\Requests\Payroll\StorePayrollRequest;
use App\Models\GymProfile;
use App\Models\Payroll;
use App\Models\StaffProfile;
use App\Services\AuditLogService;
use App\Services\PayrollService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PayrollController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Payroll::class);

        $query = Payroll::with(['staffProfile.user', 'branch', 'expense']);

        if ($request->filled('month_year')) {
            $query->where('salary_month_year', $request->month_year);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('payroll_code', 'like', "%{$search}%")
                    ->orWhereHas('staffProfile.user', function ($sq) use ($search) {
                        $sq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $payrolls = $query->latest('payment_date')->paginate(15)->withQueryString();

        return view('payrolls.index', compact('payrolls'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Payroll::class);

        $user = auth()->user();
        $selectedStaff = null;

        if ($request->filled('staff_profile_id')) {
            $selectedStaff = StaffProfile::with('user')->find($request->staff_profile_id);
            if ($selectedStaff && ! $user->hasBranchAccess($selectedStaff->branch_id)) {
                abort(403, 'Unauthorized cross-branch staff selection.');
            }
        }

        $assignedBranchIds = $user->branches->pluck('id')->toArray();
        $activeBranchId = session('active_branch_id');

        $staffMembers = StaffProfile::with('user')
            ->where('status', 'active')
            ->where(function ($q) use ($activeBranchId, $assignedBranchIds, $user) {
                if ($user->hasRole('owner') && ! $activeBranchId) {
                    // Owner in All Branches mode sees all active staff
                } elseif ($activeBranchId) {
                    $q->where('branch_id', $activeBranchId);
                } else {
                    $q->whereIn('branch_id', $assignedBranchIds);
                }
            })->get();

        return view('payrolls.create', compact('selectedStaff', 'staffMembers'));
    }

    public function store(StorePayrollRequest $request, PayrollService $service): RedirectResponse
    {
        $this->authorize('create', Payroll::class);

        $user = auth()->user();
        $staff = StaffProfile::findOrFail($request->staff_profile_id);

        if (! $user->hasBranchAccess($staff->branch_id)) {
            abort(403, 'Unauthorized cross-branch payroll generation.');
        }

        $payroll = $service->processPayroll($request->validated(), $staff->branch_id);

        AuditLogService::log(
            'Staff Payroll',
            'Processed Monthly Salary: '.$payroll->payroll_code.' (PKR '.$payroll->net_salary.') for Staff: '.$staff->user->name,
            null,
            $payroll->toArray()
        );

        return redirect()->route('payrolls.show', $payroll->id)->with('success', 'Payroll disbursed and posted to Expenses ledger successfully.');
    }

    public function show(Payroll $payroll): View
    {
        $this->authorize('view', $payroll);

        $payroll->load(['staffProfile.user', 'branch', 'expense', 'processor']);

        return view('payrolls.show', compact('payroll'));
    }

    public function payslip(Payroll $payroll): View
    {
        $this->authorize('view', $payroll);

        $payroll->load(['staffProfile.user', 'branch']);
        $gymProfile = GymProfile::first();

        return view('payrolls.payslip', compact('payroll', 'gymProfile'));
    }

    public function cancel(Payroll $payroll): RedirectResponse
    {
        $this->authorize('cancel', $payroll);

        $oldData = $payroll->toArray();

        // Void associated expense record if exists
        if ($payroll->expense) {
            $payroll->expense->update(['status' => 'cancelled']);
        }

        $payroll->update(['status' => 'cancelled']);

        AuditLogService::log(
            'Staff Payroll',
            'Cancelled Payroll Code: '.$payroll->payroll_code,
            $oldData,
            $payroll->toArray()
        );

        return redirect()->back()->with('success', 'Payroll cancelled and associated salary expense voided.');
    }

    public function destroy(Payroll $payroll): RedirectResponse
    {
        $this->authorize('delete', $payroll);

        $oldData = $payroll->toArray();

        if ($payroll->expense) {
            $payroll->expense->delete();
        }

        $payroll->delete();

        AuditLogService::log(
            'Staff Payroll',
            'Soft-Deleted Payroll Record: '.$payroll->payroll_code,
            $oldData,
            null
        );

        return redirect()->route('payrolls.index')->with('success', 'Payroll record archived successfully.');
    }
}
