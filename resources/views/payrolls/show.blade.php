@extends('layouts.app')

@section('content')
<div class="max-w-3xl space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-xl font-bold text-gray-900">Salary Disbursement: {{ $payroll->payroll_code }}</h2>
            <span class="text-sm font-medium text-indigo-600">Month: {{ $payroll->salary_month_year }}</span>
        </div>

        <a href="{{ route('payrolls.payslip', $payroll->id) }}" target="_blank" class="bg-gray-800 hover:bg-gray-900 text-white text-sm font-medium px-4 py-2 rounded-md shadow-sm">
            📄 Printable Payslip
        </a>
    </div>

    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 space-y-4">
        <div class="flex justify-between items-start border-b pb-4">
            <div>
                <span class="text-xs text-gray-500 uppercase font-bold block">Staff Member</span>
                <h3 class="text-lg font-bold text-gray-900">{{ $payroll->staffProfile->user->name ?? 'Staff' }}</h3>
                <span class="text-xs text-gray-600 block">{{ $payroll->staffProfile->designation ?? '' }} ({{ $payroll->staffProfile->staff_code ?? '' }})</span>
            </div>

            <div>
                @if($payroll->status === 'paid')
                    <span class="bg-green-100 text-green-800 text-sm px-3 py-1 rounded-full font-semibold">Disbursed / Paid</span>
                @else
                    <span class="bg-red-100 text-red-800 text-sm px-3 py-1 rounded-full font-semibold">Cancelled</span>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
            <div>
                <span class="text-gray-500 block">Base Monthly Salary</span>
                <span class="font-medium text-gray-900">PKR {{ number_format($payroll->base_salary_snapshot, 2) }}</span>
            </div>

            <div>
                <span class="text-gray-500 block">Bonus / Incentives</span>
                <span class="font-medium text-green-700">+ PKR {{ number_format($payroll->bonus_amount, 2) }}</span>
            </div>

            <div>
                <span class="text-gray-500 block">Deductions</span>
                <span class="font-medium text-red-600">- PKR {{ number_format($payroll->deduction_amount, 2) }}</span>
            </div>

            <div>
                <span class="text-gray-500 block font-bold">Net Disbursed Salary</span>
                <span class="font-bold text-gray-900 text-lg">PKR {{ number_format($payroll->net_salary, 2) }}</span>
            </div>

            <div>
                <span class="text-gray-500 block">Payment Method</span>
                <span class="font-medium text-gray-900 capitalize">{{ str_replace('_', ' ', $payroll->payment_method) }}</span>
            </div>

            <div>
                <span class="text-gray-500 block">Payment Date</span>
                <span class="font-medium text-gray-900">{{ $payroll->payment_date->format('Y-m-d') }}</span>
            </div>

            <div>
                <span class="text-gray-500 block">Processed By</span>
                <span class="font-medium text-gray-900">{{ $payroll->processor->name ?? 'System' }}</span>
            </div>

            <div>
                <span class="text-gray-500 block">Linked Expense Record</span>
                @if($payroll->expense)
                    <a href="{{ route('expenses.show', $payroll->expense_id) }}" class="text-indigo-600 font-bold hover:underline">
                        {{ $payroll->expense->expense_code }} (View Ledger)
                    </a>
                @else
                    <span class="text-gray-400">N/A</span>
                @endif
            </div>
        </div>

        @can('cancel', $payroll)
            @if($payroll->status === 'paid')
                <div class="pt-4 border-t flex justify-end">
                    <form action="{{ route('payrolls.cancel', $payroll->id) }}" method="POST" onsubmit="return confirm('Cancel this payroll disbursement and void associated expense?')">
                        @csrf
                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-medium text-sm px-4 py-2 rounded-md shadow-sm">
                            Cancel Payroll Disbursement
                        </button>
                    </form>
                </div>
            @endif
        @endcan
    </div>
</div>
@endsection
