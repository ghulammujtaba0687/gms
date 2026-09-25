@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <h2 class="text-lg font-semibold text-gray-800">Outstanding Member Dues Report</h2>

        <a href="{{ route('reports.dues', array_merge(request()->all(), ['export' => 'csv'])) }}" class="bg-green-600 hover:bg-green-700 text-white text-xs font-bold px-3 py-2 rounded shadow-sm">
            📥 Export CSV
        </a>
    </div>

    <div class="bg-red-50 p-4 rounded-lg border border-red-200 flex justify-between items-center">
        <div>
            <span class="text-xs text-red-600 font-bold uppercase block">Total Unpaid Dues Balance</span>
            <span class="text-2xl font-bold text-red-700">PKR {{ number_format($data['total_outstanding'], 2) }}</span>
        </div>
        <span class="text-sm font-bold text-red-600">Unpaid Records: {{ $data['outstanding_count'] }}</span>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
            <thead class="bg-gray-50 text-gray-600 font-medium">
                <tr>
                    <th class="px-6 py-3">Receipt Code</th>
                    <th class="px-6 py-3">Member</th>
                    <th class="px-6 py-3">Total Due</th>
                    <th class="px-6 py-3">Amount Paid</th>
                    <th class="px-6 py-3">Remaining Balance</th>
                    <th class="px-6 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($data['outstanding_payments'] as $pay)
                    <tr>
                        <td class="px-6 py-4 font-mono font-medium text-xs text-indigo-600">{{ $pay->payment_code }}</td>
                        <td class="px-6 py-4 font-medium">{{ $pay->member->full_name ?? 'Member' }}</td>
                        <td class="px-6 py-4">PKR {{ number_format($pay->amount_due, 2) }}</td>
                        <td class="px-6 py-4">PKR {{ number_format($pay->amount_paid, 2) }}</td>
                        <td class="px-6 py-4 font-bold text-red-600">PKR {{ number_format($pay->remaining_balance, 2) }}</td>
                        <td class="px-6 py-4">
                            <a href="{{ route('members.show', $pay->member_id) }}" class="text-indigo-600 hover:underline">Collect Dues</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">No outstanding dues records.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
