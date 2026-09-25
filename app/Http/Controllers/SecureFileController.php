<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Payment;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SecureFileController extends Controller
{
    public function paymentProof(Payment $payment): StreamedResponse
    {
        $this->authorize('view', $payment);

        if (! auth()->user()->hasBranchAccess($payment->branch_id)) {
            abort(403, 'Unauthorized cross-branch file access.');
        }

        if (! $payment->proof_path || ! Storage::disk('public')->exists($payment->proof_path)) {
            abort(404, 'Payment proof file not found.');
        }

        return Storage::disk('public')->response($payment->proof_path);
    }

    public function expenseReceipt(Expense $expense): StreamedResponse
    {
        $this->authorize('view', $expense);

        if (! auth()->user()->hasBranchAccess($expense->branch_id)) {
            abort(403, 'Unauthorized cross-branch file access.');
        }

        if (! $expense->receipt_path || ! Storage::disk('public')->exists($expense->receipt_path)) {
            abort(404, 'Expense receipt file not found.');
        }

        return Storage::disk('public')->response($expense->receipt_path);
    }
}
