<?php

namespace App\Http\Controllers;

use App\Http\Requests\Expense\StoreExpenseRequest;
use App\Http\Requests\Expense\UpdateExpenseRequest;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Services\AuditLogService;
use App\Services\ExpenseCodeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Expense::class);

        $query = Expense::with(['category', 'branch', 'recorder', 'approver']);

        if ($request->filled('category_id')) {
            $query->where('expense_category_id', $request->category_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('expense_code', 'like', "%{$search}%")
                    ->orWhere('vendor_name', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%");
            });
        }

        $expenses = $query->latest('expense_date')->paginate(15)->withQueryString();

        return view('expenses.index', compact('expenses'));
    }

    public function create(): View
    {
        $this->authorize('create', Expense::class);

        $user = auth()->user();
        $activeBranchId = session('active_branch_id');

        // Categories: Global categories OR active branch categories
        $assignedBranchIds = $user->branches->pluck('id')->toArray();
        $categories = ExpenseCategory::where('is_active', true)
            ->where(function ($q) use ($activeBranchId, $assignedBranchIds) {
                $q->whereNull('branch_id');
                if ($activeBranchId) {
                    $q->orWhere('branch_id', $activeBranchId);
                } else {
                    $q->orWhereIn('branch_id', $assignedBranchIds);
                }
            })->get();

        return view('expenses.create', compact('categories'));
    }

    public function store(StoreExpenseRequest $request, ExpenseCodeService $codeService): RedirectResponse
    {
        $this->authorize('create', Expense::class);

        $user = auth()->user();
        $branchId = session('active_branch_id');

        if (! $branchId) {
            $firstBranch = $user->branches->first();
            $branchId = $firstBranch ? $firstBranch->id : null;
        }

        if (! $branchId) {
            return redirect()->back()->withErrors(['branch' => 'Please select an active branch first.']);
        }

        if (! $user->hasBranchAccess($branchId)) {
            abort(403, 'Unauthorized cross-branch expense recording attempt.');
        }

        $receiptPath = null;
        if ($request->hasFile('receipt')) {
            $file = $request->file('receipt');
            $filename = Str::random(40).'.'.$file->getClientOriginalExtension();
            $receiptPath = $file->storeAs("expenses/receipts/{$branchId}", $filename, 'public');
        }

        $expenseCode = $codeService->generate($branchId);

        // Auto-approve if created by Manager or Owner; set 'recorded' if created by Receptionist
        $status = 'recorded';
        $approvedBy = null;
        $approvedAt = null;

        if ($user->hasRole('owner') || $user->hasRole('manager')) {
            $status = 'approved';
            $approvedBy = $user->id;
            $approvedAt = now();
        }

        $expense = Expense::create([
            'branch_id' => $branchId,
            'expense_category_id' => $request->expense_category_id,
            'expense_code' => $expenseCode,
            'title' => $request->title,
            'description' => $request->description,
            'amount' => $request->amount,
            'expense_date' => $request->expense_date,
            'payment_method' => $request->payment_method,
            'vendor_name' => $request->vendor_name,
            'reference_number' => $request->reference_number,
            'receipt_path' => $receiptPath,
            'status' => $status,
            'notes' => $request->notes,
            'recorded_by' => $user->id,
            'approved_by' => $approvedBy,
            'approved_at' => $approvedAt,
        ]);

        AuditLogService::log(
            'Expense Management',
            'Recorded Expense: '.$expense->title.' (Code: '.$expenseCode.', Amount: PKR '.$expense->amount.')',
            null,
            $expense->toArray()
        );

        $msg = $status === 'approved'
            ? 'Expense recorded and auto-approved successfully.'
            : 'Expense recorded successfully. Pending Manager approval.';

        return redirect()->route('expenses.show', $expense->id)->with('success', $msg);
    }

    public function show(Expense $expense): View
    {
        $this->authorize('view', $expense);

        return view('expenses.show', compact('expense'));
    }

    public function edit(Expense $expense): View
    {
        $this->authorize('update', $expense);

        $user = auth()->user();
        $assignedBranchIds = $user->branches->pluck('id')->toArray();
        $categories = ExpenseCategory::where('is_active', true)
            ->where(function ($q) use ($expense, $assignedBranchIds) {
                $q->whereNull('branch_id')->orWhere('branch_id', $expense->branch_id)->orWhereIn('branch_id', $assignedBranchIds);
            })->get();

        return view('expenses.edit', compact('expense', 'categories'));
    }

    public function update(UpdateExpenseRequest $request, Expense $expense): RedirectResponse
    {
        $this->authorize('update', $expense);

        $oldData = $expense->toArray();

        $receiptPath = $expense->receipt_path;
        if ($request->hasFile('receipt')) {
            $file = $request->file('receipt');
            $filename = Str::random(40).'.'.$file->getClientOriginalExtension();
            $receiptPath = $file->storeAs("expenses/receipts/{$expense->branch_id}", $filename, 'public');
        }

        $expense->update([
            'expense_category_id' => $request->expense_category_id,
            'title' => $request->title,
            'description' => $request->description,
            'amount' => $request->amount,
            'expense_date' => $request->expense_date,
            'payment_method' => $request->payment_method,
            'vendor_name' => $request->vendor_name,
            'reference_number' => $request->reference_number,
            'receipt_path' => $receiptPath,
            'notes' => $request->notes,
        ]);

        AuditLogService::log(
            'Expense Management',
            'Updated Expense: '.$expense->expense_code,
            $oldData,
            $expense->toArray()
        );

        return redirect()->route('expenses.show', $expense->id)->with('success', 'Expense updated successfully.');
    }

    public function approve(Expense $expense): RedirectResponse
    {
        $this->authorize('approve', $expense);

        $oldData = $expense->toArray();

        $expense->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        AuditLogService::log(
            'Expense Management',
            'Approved Pending Expense: '.$expense->expense_code,
            $oldData,
            $expense->toArray()
        );

        return redirect()->back()->with('success', 'Expense approved successfully.');
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $this->authorize('delete', $expense);

        $oldData = $expense->toArray();
        $expense->delete();

        AuditLogService::log(
            'Expense Management',
            'Soft-Deleted Expense: '.$expense->expense_code,
            $oldData,
            null
        );

        return redirect()->route('expenses.index')->with('success', 'Expense record archived successfully.');
    }
}
