<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Payment;
use App\Models\Refund;
use Illuminate\Support\Facades\DB;

class PaymentCodeService
{
    public function generatePaymentCode(int $branchId): string
    {
        return DB::transaction(function () use ($branchId) {
            $branch = Branch::findOrFail($branchId);
            $branchCode = strtoupper($branch->code);

            $lastPayment = Payment::where('branch_id', $branchId)
                ->withTrashed()
                ->lockForUpdate()
                ->orderBy('id', 'desc')
                ->first();

            $sequence = 1;

            if ($lastPayment && $lastPayment->payment_code) {
                $parts = explode('-PAY-', $lastPayment->payment_code);
                if (count($parts) === 2 && is_numeric($parts[1])) {
                    $sequence = ((int) $parts[1]) + 1;
                } else {
                    $sequence = Payment::where('branch_id', $branchId)->withTrashed()->count() + 1;
                }
            }

            $formattedSequence = str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);

            return "{$branchCode}-PAY-{$formattedSequence}";
        });
    }

    public function generateRefundCode(int $branchId): string
    {
        return DB::transaction(function () use ($branchId) {
            $branch = Branch::findOrFail($branchId);
            $branchCode = strtoupper($branch->code);

            $lastRefund = Refund::where('branch_id', $branchId)
                ->withTrashed()
                ->lockForUpdate()
                ->orderBy('id', 'desc')
                ->first();

            $sequence = 1;

            if ($lastRefund && $lastRefund->refund_code) {
                $parts = explode('-RFD-', $lastRefund->refund_code);
                if (count($parts) === 2 && is_numeric($parts[1])) {
                    $sequence = ((int) $parts[1]) + 1;
                } else {
                    $sequence = Refund::where('branch_id', $branchId)->withTrashed()->count() + 1;
                }
            }

            $formattedSequence = str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);

            return "{$branchCode}-RFD-{$formattedSequence}";
        });
    }
}
