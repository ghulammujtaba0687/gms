@extends('layouts.app')

@section('content')
<div class="max-w-2xl bg-white p-6 rounded-lg shadow-sm border border-gray-200">
    <h2 class="text-lg font-semibold text-gray-800 mb-6">Record New Gym Expense</h2>

    @if ($errors->any())
        <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-600 rounded-md text-sm">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('expenses.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
        @csrf

        <div>
            <label for="expense_category_id" class="block text-sm font-medium text-gray-700">Expense Category *</label>
            <select name="expense_category_id" id="expense_category_id" required class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm bg-white">
                <option value="">-- Choose Category --</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ old('expense_category_id') == $cat->id ? 'selected' : '' }}>
                        {{ $cat->name }} {{ $cat->isGlobal() ? '(Global)' : '(Branch Specific)' }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="title" class="block text-sm font-medium text-gray-700">Expense Title / Subject *</label>
            <input type="text" name="title" id="title" required value="{{ old('title') }}" placeholder="e.g. Monthly Electricity Bill or Treadmill Service" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="amount" class="block text-sm font-medium text-gray-700">Expense Amount (PKR) *</label>
                <input type="number" step="0.01" name="amount" id="amount" required min="0.01" value="{{ old('amount') }}" placeholder="15000.00" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm font-bold text-gray-900">
            </div>

            <div>
                <label for="expense_date" class="block text-sm font-medium text-gray-700">Expense Date *</label>
                <input type="date" name="expense_date" id="expense_date" required value="{{ old('expense_date', date('Y-m-d')) }}" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm">
            </div>

            <div>
                <label for="payment_method" class="block text-sm font-medium text-gray-700">Payment Method *</label>
                <select name="payment_method" id="payment_method" required class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm bg-white">
                    <option value="cash" {{ old('payment_method') === 'cash' ? 'selected' : '' }}>Cash</option>
                    <option value="bank_transfer" {{ old('payment_method') === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                    <option value="easypaisa" {{ old('payment_method') === 'easypaisa' ? 'selected' : '' }}>Easypaisa</option>
                    <option value="jazzcash" {{ old('payment_method') === 'jazzcash' ? 'selected' : '' }}>JazzCash</option>
                    <option value="other" {{ old('payment_method') === 'other' ? 'selected' : '' }}>Other</option>
                </select>
            </div>

            <div>
                <label for="vendor_name" class="block text-sm font-medium text-gray-700">Vendor / Payee Name</label>
                <input type="text" name="vendor_name" id="vendor_name" value="{{ old('vendor_name') }}" placeholder="e.g. LESCO / PTCL" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm">
            </div>
        </div>

        <div>
            <label for="reference_number" class="block text-sm font-medium text-gray-700">Invoice / Reference Number</label>
            <input type="text" name="reference_number" id="reference_number" value="{{ old('reference_number') }}" placeholder="e.g. INV-998811" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm">
        </div>

        <div>
            <label for="receipt" class="block text-sm font-medium text-gray-700 mb-1">Receipt / Proof Attachment (JPEG, PNG, WebP, PDF - Max 3MB)</label>
            <input type="file" name="receipt" id="receipt" accept="image/jpeg,image/png,image/webp,application/pdf" class="text-sm text-gray-600">
        </div>

        <div>
            <label for="description" class="block text-sm font-medium text-gray-700">Description / Details</label>
            <textarea name="description" id="description" rows="2" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm">{{ old('description') }}</textarea>
        </div>

        @if(auth()->user()->hasRole('receptionist'))
            <p class="text-xs text-amber-700 bg-amber-50 p-2 border border-amber-200 rounded">
                ℹ Note: Expenses recorded by Receptionist require Manager/Owner approval before final ledger posting.
            </p>
        @endif

        <div class="flex justify-end gap-3 pt-4 border-t">
            <a href="{{ route('expenses.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</a>
            <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md text-sm font-medium shadow-sm">Record Expense</button>
        </div>
    </form>
</div>
@endsection
