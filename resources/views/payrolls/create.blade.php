@extends('layouts.app')

@section('content')
<div class="max-w-2xl bg-white p-6 rounded-lg shadow-sm border border-gray-200">
    <h2 class="text-lg font-semibold text-gray-800 mb-6">Process Staff Monthly Salary</h2>

    @if ($errors->any())
        <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-600 rounded-md text-sm">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('payrolls.store') }}" method="POST" class="space-y-4">
        @csrf

        @if($selectedStaff)
            <input type="hidden" name="staff_profile_id" value="{{ $selectedStaff->id }}">
            <div class="p-4 bg-gray-50 border rounded-md">
                <span class="text-xs text-gray-500 block uppercase font-bold">Staff Member</span>
                <span class="text-base font-bold text-gray-900">{{ $selectedStaff->user->name }}</span>
                <span class="text-xs text-gray-600 block">{{ $selectedStaff->designation }} ({{ $selectedStaff->staff_code }})</span>
                <span class="text-sm font-bold text-indigo-600 block mt-1">Base Monthly Salary: PKR {{ number_format($selectedStaff->monthly_salary, 2) }}</span>
            </div>
        @else
            <div>
                <label for="staff_profile_id" class="block text-sm font-medium text-gray-700">Select Staff / Trainer *</label>
                <select name="staff_profile_id" id="staff_profile_id" required class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm bg-white">
                    <option value="">-- Choose Staff Member --</option>
                    @foreach($staffMembers as $stf)
                        <option value="{{ $stf->id }}" {{ old('staff_profile_id') == $stf->id ? 'selected' : '' }}>
                            {{ $stf->user->name ?? 'Staff' }} - {{ $stf->designation }} (PKR {{ number_format($stf->monthly_salary, 2) }})
                        </option>
                    @endforeach
                </select>
            </div>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="salary_month_year" class="block text-sm font-medium text-gray-700">Salary Month & Year *</label>
                <input type="month" name="salary_month_year" id="salary_month_year" required value="{{ old('salary_month_year', date('Y-m')) }}" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm font-bold">
            </div>

            <div>
                <label for="payment_date" class="block text-sm font-medium text-gray-700">Disbursement Date *</label>
                <input type="date" name="payment_date" id="payment_date" required value="{{ old('payment_date', date('Y-m-d')) }}" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm">
            </div>

            <div>
                <label for="bonus_amount" class="block text-sm font-medium text-gray-700">Bonus / Incentives (PKR)</label>
                <input type="number" step="0.01" name="bonus_amount" id="bonus_amount" min="0" value="{{ old('bonus_amount', '0.00') }}" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm text-green-700 font-bold">
            </div>

            <div>
                <label for="deduction_amount" class="block text-sm font-medium text-gray-700">Deductions (PKR)</label>
                <input type="number" step="0.01" name="deduction_amount" id="deduction_amount" min="0" value="{{ old('deduction_amount', '0.00') }}" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm text-red-600 font-bold">
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="payment_method" class="block text-sm font-medium text-gray-700">Payment Method *</label>
                <select name="payment_method" id="payment_method" required class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm bg-white">
                    <option value="cash" {{ old('payment_method') === 'cash' ? 'selected' : '' }}>Cash</option>
                    <option value="bank_transfer" {{ old('payment_method') === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                    <option value="easypaisa" {{ old('payment_method') === 'easypaisa' ? 'selected' : '' }}>Easypaisa</option>
                    <option value="jazzcash" {{ old('payment_method') === 'jazzcash' ? 'selected' : '' }}>JazzCash</option>
                </select>
            </div>

            <div>
                <label for="reference_number" class="block text-sm font-medium text-gray-700">Bank Ref / Cheque #</label>
                <input type="text" name="reference_number" id="reference_number" value="{{ old('reference_number') }}" placeholder="e.g. CHQ-99120" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm">
            </div>
        </div>

        <div>
            <label for="notes" class="block text-sm font-medium text-gray-700">Payroll Remarks / Notes</label>
            <textarea name="notes" id="notes" rows="2" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm">{{ old('notes') }}</textarea>
        </div>

        <p class="text-xs text-gray-500 bg-gray-50 p-2 border rounded">
            ℹ Note: Disbursing salary will automatically post a Salaries Expense entry in the Phase 8 Expense ledger.
        </p>

        <div class="flex justify-end gap-3 pt-4 border-t">
            <a href="{{ route('payrolls.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</a>
            <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md text-sm font-medium shadow-sm">Disburse Salary & Post Expense</button>
        </div>
    </form>
</div>
@endsection
