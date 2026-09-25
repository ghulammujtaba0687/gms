@extends('layouts.app')

@section('content')
<div class="max-w-3xl space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-xl font-bold text-gray-900">{{ $expense->title }}</h2>
            <span class="text-sm font-mono text-indigo-600 font-medium">Code: {{ $expense->expense_code }}</span>
        </div>

        <div class="flex items-center gap-2">
            @can('update', $expense)
                <a href="{{ route('expenses.edit', $expense->id) }}" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-md shadow-sm">
                    Edit Expense
                </a>
            @endcan

            @can('delete', $expense)
                <form action="{{ route('expenses.destroy', $expense->id) }}" method="POST" onsubmit="return confirm('Archive this expense record?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 text-sm font-medium px-4 py-2 rounded-md">
                        Archive Record
                    </button>
                </form>
            @endcan
        </div>
    </div>

    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 space-y-4">
        <div class="flex justify-between items-start border-b pb-4">
            <div>
                <span class="text-xs text-gray-500 uppercase font-bold block">Expense Category</span>
                <span class="text-base font-bold text-gray-900">{{ $expense->category->name ?? 'Uncategorized' }}</span>
                <span class="text-xs text-gray-500 block">Branch: {{ $expense->branch->name ?? 'N/A' }}</span>
            </div>

            <div>
                @if($expense->status === 'approved')
                    <span class="bg-green-100 text-green-800 text-sm px-3 py-1 rounded-full font-semibold">Approved</span>
                @elseif($expense->status === 'recorded')
                    <span class="bg-yellow-100 text-yellow-800 text-sm px-3 py-1 rounded-full font-semibold">Pending Approval</span>
                @else
                    <span class="bg-red-100 text-red-800 text-sm px-3 py-1 rounded-full font-semibold">Cancelled</span>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
            <div>
                <span class="text-gray-500 block font-bold">Expense Amount</span>
                <span class="font-bold text-gray-900 text-lg">PKR {{ number_format($expense->amount, 2) }}</span>
            </div>

            <div>
                <span class="text-gray-500 block">Expense Date</span>
                <span class="font-medium text-gray-900">{{ $expense->expense_date->format('Y-m-d') }}</span>
            </div>

            <div>
                <span class="text-gray-500 block">Payment Method</span>
                <span class="font-medium text-gray-900 capitalize">{{ str_replace('_', ' ', $expense->payment_method) }}</span>
            </div>

            <div>
                <span class="text-gray-500 block">Vendor / Payee Name</span>
                <span class="font-medium text-gray-900">{{ $expense->vendor_name ?? 'N/A' }}</span>
            </div>

            <div>
                <span class="text-gray-500 block">Invoice / Reference TRX</span>
                <span class="font-mono text-gray-900">{{ $expense->reference_number ?? 'N/A' }}</span>
            </div>

            <div>
                <span class="text-gray-500 block">Recorded By</span>
                <span class="font-medium text-gray-900">{{ $expense->recorder->name ?? 'System' }}</span>
            </div>
        </div>

        @if($expense->description)
            <div class="pt-2 border-t">
                <span class="text-gray-500 text-sm block">Description / Details</span>
                <p class="text-sm text-gray-800">{{ $expense->description }}</p>
            </div>
        @endif

        @if($expense->receipt_path)
            <div class="pt-4 border-t">
                <span class="text-sm font-medium text-gray-700 block mb-2">Receipt Attachment</span>
                <a href="{{ asset('storage/' . $expense->receipt_path) }}" target="_blank" class="text-indigo-600 hover:underline text-sm font-medium">
                    📎 View Attached Receipt / Bill Proof
                </a>
            </div>
        @endif

        @can('approve', $expense)
            @if($expense->status === 'recorded')
                <div class="pt-4 border-t flex justify-end">
                    <form action="{{ route('expenses.approve', $expense->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-medium text-sm px-4 py-2 rounded-md shadow-sm">
                            ✓ Approve Expense Request
                        </button>
                    </form>
                </div>
            @endif
        @endcan
    </div>
</div>
@endsection
