@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <h2 class="text-lg font-semibold text-gray-800">Staff Payroll & Salary Disbursements</h2>
        @can('create', App\Models\Payroll::class)
            <a href="{{ route('payrolls.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-md shadow-sm">
                + Process Salary
            </a>
        @endcan
    </div>

    <!-- Filter Bar -->
    <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
        <form method="GET" action="{{ route('payrolls.index') }}" class="flex gap-4">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search Payroll Code or Staff Name..." class="w-full max-w-xs border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm">
            <input type="month" name="month_year" value="{{ request('month_year') }}" class="border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm">
            <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 rounded-md text-sm font-medium shadow-sm">Filter</button>
            <a href="{{ route('payrolls.index') }}" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md text-sm border border-gray-300">Reset</a>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
            <thead class="bg-gray-50 text-gray-600 font-medium">
                <tr>
                    <th class="px-6 py-3">Payroll Code</th>
                    <th class="px-6 py-3">Staff Name</th>
                    <th class="px-6 py-3">Month / Year</th>
                    <th class="px-6 py-3">Base Salary</th>
                    <th class="px-6 py-3">Net Disbursed</th>
                    <th class="px-6 py-3">Payment Date</th>
                    <th class="px-6 py-3">Status</th>
                    <th class="px-6 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 text-gray-700">
                @forelse($payrolls as $pr)
                    <tr>
                        <td class="px-6 py-4 font-mono font-medium text-indigo-600 text-xs">{{ $pr->payroll_code }}</td>
                        <td class="px-6 py-4 font-medium text-gray-900">
                            {{ $pr->staffProfile->user->name ?? 'Staff' }}
                            <span class="text-xs text-gray-500 block font-mono">{{ $pr->staffProfile->staff_code ?? '' }}</span>
                        </td>
                        <td class="px-6 py-4 font-medium">{{ $pr->salary_month_year }}</td>
                        <td class="px-6 py-4">PKR {{ number_format($pr->base_salary_snapshot, 2) }}</td>
                        <td class="px-6 py-4 font-bold text-gray-900">PKR {{ number_format($pr->net_salary, 2) }}</td>
                        <td class="px-6 py-4">{{ $pr->payment_date->format('Y-m-d') }}</td>
                        <td class="px-6 py-4">
                            @if($pr->status === 'paid')
                                <span class="bg-green-100 text-green-800 text-xs px-2.5 py-0.5 rounded-full font-semibold">Disbursed</span>
                            @else
                                <span class="bg-red-100 text-red-800 text-xs px-2.5 py-0.5 rounded-full font-semibold">Cancelled</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 space-x-2">
                            <a href="{{ route('payrolls.show', $pr->id) }}" class="text-indigo-600 hover:text-indigo-900 font-medium">View</a>
                            <a href="{{ route('payrolls.payslip', $pr->id) }}" target="_blank" class="text-gray-600 hover:text-gray-900 font-medium">Payslip</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-4 text-center text-gray-500">No payroll records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="px-6 py-4 border-t border-gray-200">
            {{ $payrolls->links() }}
        </div>
    </div>
</div>
@endsection
