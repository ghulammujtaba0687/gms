@extends('layouts.app')

@section('content')
<div class="max-w-3xl space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-xl font-bold text-gray-900">Payment Receipt: {{ $payment->payment_code }}</h2>
            <span class="text-sm text-gray-500">Date: {{ $payment->payment_date->format('Y-m-d') }}</span>
        </div>

        <a href="{{ route('payments.receipt', $payment->id) }}" target="_blank" class="bg-gray-800 hover:bg-gray-900 text-white text-sm font-medium px-4 py-2 rounded-md shadow-sm">
            🖨 Printable Receipt
        </a>
    </div>

    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 space-y-4">
        <div class="flex justify-between items-start border-b pb-4">
            <div>
                <span class="text-xs text-gray-500 uppercase font-bold block">Member</span>
                <a href="{{ route('members.show', $payment->member_id) }}" class="text-lg font-bold text-indigo-600 hover:underline">
                    {{ $payment->member->full_name ?? 'Member' }}
                </a>
                <span class="text-sm font-mono text-gray-600 font-medium block">ID: {{ $payment->member->member_code ?? '' }}</span>
            </div>

            <div>
                @if($payment->status === 'paid')
                    <span class="bg-green-100 text-green-800 text-sm px-3 py-1 rounded-full font-semibold">Fully Paid</span>
                @elseif($payment->status === 'partial')
                    <span class="bg-yellow-100 text-yellow-800 text-sm px-3 py-1 rounded-full font-semibold">Partial Dues</span>
                @elseif($payment->status === 'pending_verification')
                    <span class="bg-blue-100 text-blue-800 text-sm px-3 py-1 rounded-full font-semibold">Pending Verification</span>
                @else
                    <span class="bg-red-100 text-red-800 text-sm px-3 py-1 rounded-full font-semibold">Refunded</span>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
            <div>
                <span class="text-gray-500 block">Amount Due</span>
                <span class="font-medium text-gray-900">PKR {{ number_format($payment->amount_due, 2) }}</span>
            </div>

            <div>
                <span class="text-gray-500 block">Discount</span>
                <span class="font-medium text-gray-900">PKR {{ number_format($payment->discount_amount, 2) }}</span>
            </div>

            <div>
                <span class="text-gray-500 block font-bold">Amount Paid</span>
                <span class="font-bold text-green-700 text-base">PKR {{ number_format($payment->amount_paid, 2) }}</span>
            </div>

            <div>
                <span class="text-gray-500 block font-bold">Remaining Balance</span>
                <span class="font-bold text-red-600 text-base">PKR {{ number_format($payment->remaining_balance, 2) }}</span>
            </div>

            <div>
                <span class="text-gray-500 block">Payment Method</span>
                <span class="font-medium text-gray-900 capitalize">{{ str_replace('_', ' ', $payment->payment_method) }}</span>
            </div>

            <div>
                <span class="text-gray-500 block">Transaction Ref TRX</span>
                <span class="font-mono text-gray-900">{{ $payment->reference_number ?? 'N/A' }}</span>
            </div>

            <div>
                <span class="text-gray-500 block">Recorded By</span>
                <span class="font-medium text-gray-900">{{ $payment->recorder->name ?? 'System' }}</span>
            </div>

            <div>
                <span class="text-gray-500 block">Branch</span>
                <span class="font-medium text-gray-900">{{ $payment->branch->name ?? 'N/A' }}</span>
            </div>
        </div>

        @if($payment->proof_path)
            <div class="pt-4 border-t">
                <span class="text-sm font-medium text-gray-700 block mb-2">Proof Attachment</span>
                <a href="{{ asset('storage/' . $payment->proof_path) }}" target="_blank" class="text-indigo-600 hover:underline text-sm font-medium">
                    📎 View Attached Transfer Proof
                </a>
            </div>
        @endif

        @can('verify', $payment)
            @if($payment->status === 'pending_verification')
                <div class="pt-4 border-t flex justify-end">
                    <form action="{{ route('payments.verify', $payment->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-medium text-sm px-4 py-2 rounded-md shadow-sm">
                            ✓ Verify Transfer Proof
                        </button>
                    </form>
                </div>
            @endif
        @endcan

        <!-- Refund Section for Managers/Owners -->
        @can('refund', $payment)
            @if($payment->status !== 'refunded')
                <div class="pt-6 border-t space-y-3">
                    <h4 class="text-sm font-bold text-gray-800">Issue Payment Refund</h4>
                    <form action="{{ route('payments.refund', $payment->id) }}" method="POST" class="grid grid-cols-1 sm:grid-cols-3 gap-3" onsubmit="return confirm('Process refund for this payment?')">
                        @csrf
                        <input type="number" step="0.01" name="refund_amount" max="{{ $payment->amount_paid }}" required value="{{ $payment->amount_paid }}" placeholder="Refund Amount" class="border border-gray-300 rounded-md px-3 py-1.5 text-sm">
                        <input type="text" name="refund_reason" required placeholder="Refund Reason *" class="border border-gray-300 rounded-md px-3 py-1.5 text-sm">
                        <select name="refund_method" required class="border border-gray-300 rounded-md px-3 py-1.5 text-sm bg-white">
                            <option value="cash">Cash</option>
                            <option value="bank_transfer">Bank Transfer</option>
                        </select>
                        <div class="sm:col-span-3 flex justify-end">
                            <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-medium text-sm px-4 py-2 rounded-md shadow-sm">
                                Issue Refund
                            </button>
                        </div>
                    </form>
                </div>
            @endif
        @endcan
    </div>
</div>
@endsection
