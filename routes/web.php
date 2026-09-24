<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BranchContextController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\MembershipPlanController;
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
});
