@extends('layouts.app')

@section('content')
<div class="max-w-2xl bg-white p-6 rounded-lg shadow-sm border border-gray-200">
    <h2 class="text-lg font-semibold text-gray-800 mb-6">Record Member Payment</h2>

    @if ($errors->any())
        <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-600 rounded-md text-sm">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('payments.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
        @csrf

        @if($selectedMember)
            <input type="hidden" name="member_id" value="{{ $selectedMember->id }}">
            <div class="p-4 bg-gray-50 border rounded-md">
                <span class="text-xs text-gray-500 block uppercase font-bold">Member</span>
                <span class="text-base font-bold text-gray-900">{{ $selectedMember->full_name }}</span>
                <span class="text-sm font-mono text-indigo-600 font-medium block">ID: {{ $selectedMember->member_code }}</span>
            </div>
        @else
            <div>
                <label for="member_id" class="block text-sm font-medium text-gray-700">Select Member *</label>
                <select name="member_id" id="member_id" required class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm bg-white">
                    <option value="">-- Choose Member --</option>
                    @foreach(\App\Models\Member::all() as $m)
                        <option value="{{ $m->id }}" {{ old('member_id') == $m->id ? 'selected' : '' }}>
                            {{ $m->full_name }} ({{ $m->member_code }})
                        </option>
                    @endforeach
                </select>
            </div>
        @endif

        @if($selectedMembership)
            <input type="hidden" name="membership_id" value="{{ $selectedMembership->id }}">
            <div class="p-3 bg-indigo-50 border border-indigo-100 rounded-md text-sm">
                <span class="text-xs text-indigo-700 block uppercase font-bold">Associated Subscription</span>
                <span class="font-bold text-gray-900">{{ $selectedMembership->plan_name_snapshot }}</span>
                <span class="text-xs text-gray-600 block">Plan Price: PKR {{ number_format($selectedMembership->plan_price_snapshot, 2) }}</span>
            </div>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label for="amount_due" class="block text-sm font-medium text-gray-700">Total Amount Due (PKR) *</label>
                <input type="number" step="0.01" name="amount_due" id="amount_due" required min="0" value="{{ old('amount_due', $selectedMembership ? $selectedMembership->plan_price_snapshot : '') }}" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm font-bold">
            </div>

            <div>
                <label for="discount_amount" class="block text-sm font-medium text-gray-700">Discount (PKR)</label>
                <input type="number" step="0.01" name="discount_amount" id="discount_amount" min="0" value="{{ old('discount_amount', '0.00') }}" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm">
            </div>

            <div>
                <label for="amount_paid" class="block text-sm font-medium text-gray-700">Amount Paid (PKR) *</label>
                <input type="number" step="0.01" name="amount_paid" id="amount_paid" required min="0" value="{{ old('amount_paid') }}" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm font-bold text-indigo-600">
            </div>
        </div>

        <div>
            <label for="discount_reason" class="block text-sm font-medium text-gray-700">Discount Reason (If applicable)</label>
            <input type="text" name="discount_reason" id="discount_reason" value="{{ old('discount_reason') }}" placeholder="e.g. Promotional offer or student discount" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
                <label for="payment_date" class="block text-sm font-medium text-gray-700">Payment Date *</label>
                <input type="date" name="payment_date" id="payment_date" required value="{{ old('payment_date', date('Y-m-d')) }}" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm">
            </div>
        </div>

        <div>
            <label for="reference_number" class="block text-sm font-medium text-gray-700">Reference / Transaction TRX ID</label>
            <input type="text" name="reference_number" id="reference_number" value="{{ old('reference_number') }}" placeholder="e.g. TRX-99882211" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm">
        </div>

        <div>
            <label for="proof" class="block text-sm font-medium text-gray-700 mb-1">Proof Attachment (Image/PDF for Online Transfers - Max 3MB)</label>
            <input type="file" name="proof" id="proof" accept="image/jpeg,image/png,image/webp,application/pdf" class="text-sm text-gray-600">
        </div>

        <div>
            <label for="notes" class="block text-sm font-medium text-gray-700">Payment Notes / Remarks</label>
            <textarea name="notes" id="notes" rows="2" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm">{{ old('notes') }}</textarea>
        </div>

        <div class="flex justify-end gap-3 pt-4 border-t">
            <a href="{{ route('payments.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</a>
            <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md text-sm font-medium shadow-sm">Submit Payment</button>
        </div>
    </form>
</div>
@endsection
