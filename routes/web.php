<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BranchContextController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\TrainerAssignmentController;
use App\Http\Controllers\MembershipFreezeController;
use App\Http\Controllers\MembershipController;
use App\Http\Controllers\MembershipPlanController;
use App\Http\Controllers\PaymentController;
use App\Http\Middleware\CheckBranchAccess;
use App\Http\Middleware\EnsureBranchContext;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Support\Facades\Route;

// Guest Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

// Authenticated Routes
Route::middleware(['auth', EnsureUserIsActive::class, EnsureBranchContext::class, CheckBranchAccess::class])->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    Route::get('/', function () {
        return redirect()->route('dashboard');
    });

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Branch Context Switcher
    Route::post('/branch/switch', [BranchContextController::class, 'switch'])->name('branch.switch');

    // Branch Management
    Route::resource('branches', BranchController::class)->except(['show', 'destroy']);

    // Member Management
    Route::post('/members/{id}/restore', [MemberController::class, 'restore'])->name('members.restore');
    Route::resource('members', MemberController::class);

    // Membership Plans Catalog Management
    Route::post('/membership-plans/{id}/restore', [MembershipPlanController::class, 'restore'])->name('membership-plans.restore');
    Route::resource('membership-plans', MembershipPlanController::class)->except(['show']);

    // Member Subscriptions (Phase 4)
    Route::post('/memberships/{membership}/cancel', [MembershipController::class, 'cancel'])->name('memberships.cancel');
    Route::resource('memberships', MembershipController::class)->except(['edit', 'update']);

    // Payments & Dues Management (Phase 5)
    Route::post('/payments/{payment}/verify', [PaymentController::class, 'verify'])->name('payments.verify');
    Route::post('/payments/{payment}/refund', [PaymentController::class, 'refund'])->name('payments.refund');
    Route::get('/payments/{payment}/receipt', [PaymentController::class, 'receipt'])->name('payments.receipt');
    Route::resource('payments', PaymentController::class)->except(['edit', 'update', 'destroy']);

    // Attendance & Member Check-In (Phase 6)
    Route::post('/attendances/{attendance}/checkout', [AttendanceController::class, 'checkout'])->name('attendances.checkout');
    Route::resource('attendances', AttendanceController::class)->except(['show']);

    // Membership Freezes / Pause Management (Phase 7)
    Route::post('/membership-freezes/{membershipFreeze}/approve', [MembershipFreezeController::class, 'approve'])->name('membership-freezes.approve');
    Route::post('/membership-freezes/{membershipFreeze}/cancel', [MembershipFreezeController::class, 'cancel'])->name('membership-freezes.cancel');
    Route::resource('membership-freezes', MembershipFreezeController::class)->except(['show', 'edit', 'update']);

    // Expense Management System (Phase 8)
    Route::post('/expenses/{expense}/approve', [ExpenseController::class, 'approve'])->name('expenses.approve');
    Route::resource('expenses', ExpenseController::class);

    // Staff & Trainer Management (Phase 9)
    Route::post('/trainer/assign', [TrainerAssignmentController::class, 'assign'])->name('trainer.assign');
    Route::resource('staff', StaffController::class);

    // Staff Payroll & Salary Disbursement (Phase 10)
    Route::get('/payrolls/{payroll}/payslip', [PayrollController::class, 'payslip'])->name('payrolls.payslip');
    Route::post('/payrolls/{payroll}/cancel', [PayrollController::class, 'cancel'])->name('payrolls.cancel');
    Route::resource('payrolls', PayrollController::class);

    // Reports & Financial Analytics System (Phase 11)
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/revenue', [ReportController::class, 'revenue'])->name('reports.revenue');
    Route::get('/reports/expiring', [ReportController::class, 'expiring'])->name('reports.expiring');
    Route::get('/reports/dues', [ReportController::class, 'dues'])->name('reports.dues');
    Route::get('/reports/attendance', [ReportController::class, 'attendance'])->name('reports.attendance');
});
