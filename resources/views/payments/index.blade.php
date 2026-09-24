@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <h2 class="text-lg font-semibold text-gray-800">Financial Payments & Dues</h2>
        @can('create', App\Models\Payment::class)
            <a href="{{ route('payments.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-md shadow-sm">
                + Record Payment
            </a>
        @endcan
    </div>

    <!-- Filter Bar -->
    <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
        <form method="GET" action="{{ route('payments.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-4">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search Code, Ref, Member..." class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm">

            <select name="status" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm bg-white">
                <option value="">All Statuses</option>
                <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Fully Paid</option>
                <option value="partial" {{ request('status') === 'partial' ? 'selected' : '' }}>Partial Dues</option>
                <option value="pending_verification" {{ request('status') === 'pending_verification' ? 'selected' : '' }}>Pending Proof Verification</option>
                <option value="refunded" {{ request('status') === 'refunded' ? 'selected' : '' }}>Refunded</option>
            </select>

            <select name="method" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm bg-white">
                <option value="">All Payment Methods</option>
                <option value="cash" {{ request('method') === 'cash' ? 'selected' : '' }}>Cash</option>
                <option value="bank_transfer" {{ request('method') === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                <option value="easypaisa" {{ request('method') === 'easypaisa' ? 'selected' : '' }}>Easypaisa</option>
                <option value="jazzcash" {{ request('method') === 'jazzcash' ? 'selected' : '' }}>JazzCash</option>
            </select>

            <div class="flex gap-2">
                <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 rounded-md text-sm font-medium shadow-sm">Filter</button>
                <a href="{{ route('payments.index') }}" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md text-sm border border-gray-300">Reset</a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
            <thead class="bg-gray-50 text-gray-600 font-medium">
                <tr>
                    <th class="px-6 py-3">Receipt Code</th>
                    <th class="px-6 py-3">Member</th>
                    <th class="px-6 py-3">Method</th>
                    <th class="px-6 py-3">Amount Paid</th>
                    <th class="px-6 py-3">Remaining Balance</th>
                    <th class="px-6 py-3">Date</th>
                    <th class="px-6 py-3">Status</th>
                    <th class="px-6 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 text-gray-700">
                @forelse($payments as $pay)
                    <tr>
                        <td class="px-6 py-4 font-mono font-medium text-indigo-600 text-xs">{{ $pay->payment_code }}</td>
                        <td class="px-6 py-4 font-medium text-gray-900">
                            <a href="{{ route('members.show', $pay->member_id) }}" class="hover:underline text-indigo-600">
                                {{ $pay->member->full_name ?? 'Member' }}
                            </a>
                        </td>
                        <td class="px-6 py-4 capitalize">{{ str_replace('_', ' ', $pay->payment_method) }}</td>
                        <td class="px-6 py-4 font-bold text-gray-900">PKR {{ number_format($pay->amount_paid, 2) }}</td>
                        <td class="px-6 py-4 font-semibold text-red-600">
                            @if($pay->remaining_balance > 0)
                                PKR {{ number_format($pay->remaining_balance, 2) }}
                            @else
                                <span class="text-green-600">Clear</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">{{ $pay->payment_date->format('Y-m-d') }}</td>
                        <td class="px-6 py-4">
                            @if($pay->status === 'paid')
                                <span class="bg-green-100 text-green-800 text-xs px-2.5 py-0.5 rounded-full font-medium">Paid</span>
                            @elseif($pay->status === 'partial')
                                <span class="bg-yellow-100 text-yellow-800 text-xs px-2.5 py-0.5 rounded-full font-medium">Partial Dues</span>
                            @elseif($pay->status === 'pending_verification')
                                <span class="bg-blue-100 text-blue-800 text-xs px-2.5 py-0.5 rounded-full font-medium">Pending Proof</span>
                            @else
                                <span class="bg-red-100 text-red-800 text-xs px-2.5 py-0.5 rounded-full font-medium">Refunded</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 space-x-2">
                            <a href="{{ route('payments.show', $pay->id) }}" class="text-indigo-600 hover:text-indigo-900 font-medium">View</a>
                            <a href="{{ route('payments.receipt', $pay->id) }}" target="_blank" class="text-gray-600 hover:text-gray-900 font-medium">Receipt</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-4 text-center text-gray-500">No payment records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="px-6 py-4 border-t border-gray-200">
            {{ $payments->links() }}
        </div>
    </div>
</div>
@endsection
