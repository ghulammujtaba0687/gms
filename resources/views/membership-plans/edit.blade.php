@extends('layouts.app')

@section('content')
<div class="max-w-2xl bg-white p-6 rounded-lg shadow-sm border border-gray-200">
    <h2 class="text-lg font-semibold text-gray-800 mb-6">Edit Membership Plan: {{ $plan->name }} ({{ $plan->code }})</h2>

    @if ($errors->any())
        <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-600 rounded-md text-sm">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('membership-plans.update', $plan->id) }}" method="POST" class="space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Plan Code (Immutable)</label>
            <input type="text" value="{{ $plan->code }}" disabled class="w-full border border-gray-200 bg-gray-100 rounded-md px-3 py-2 text-sm font-mono text-gray-600 cursor-not-allowed">
        </div>

        <div>
            <label for="name" class="block text-sm font-medium text-gray-700">Plan Name *</label>
            <input type="text" name="name" id="name" required value="{{ old('name', $plan->name) }}" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="duration_value" class="block text-sm font-medium text-gray-700">Duration Value *</label>
                <input type="number" name="duration_value" id="duration_value" required min="1" value="{{ old('duration_value', $plan->duration_value) }}" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm">
            </div>

            <div>
                <label for="duration_type" class="block text-sm font-medium text-gray-700">Duration Type *</label>
                <select name="duration_type" id="duration_type" required class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm bg-white">
                    <option value="days" {{ old('duration_type', $plan->duration_type) === 'days' ? 'selected' : '' }}>Days</option>
                    <option value="months" {{ old('duration_type', $plan->duration_type) === 'months' ? 'selected' : '' }}>Months</option>
                    <option value="years" {{ old('duration_type', $plan->duration_type) === 'years' ? 'selected' : '' }}>Years</option>
                </select>
            </div>

            <div>
                <label for="price" class="block text-sm font-medium text-gray-700">Plan Price (PKR) *</label>
                <input type="number" step="0.01" name="price" id="price" required min="0" value="{{ old('price', $plan->price) }}" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm">
            </div>

            <div>
                <label for="signup_fee" class="block text-sm font-medium text-gray-700">Signup Fee (PKR)</label>
                <input type="number" step="0.01" name="signup_fee" id="signup_fee" min="0" value="{{ old('signup_fee', $plan->signup_fee) }}" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm">
            </div>
        </div>

        <div>
            <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
            <textarea name="description" id="description" rows="2" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm">{{ old('description', $plan->description) }}</textarea>
        </div>

        <div>
            <label class="flex items-center text-sm text-gray-700">
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $plan->is_active) ? 'checked' : '' }} class="mr-2 border-gray-300 rounded text-indigo-600">
                Active Plan
            </label>
        </div>

        <div class="flex justify-end gap-3 pt-4 border-t">
            <a href="{{ route('membership-plans.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</a>
            <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md text-sm font-medium shadow-sm">Update Plan</button>
        </div>
    </form>
</div>
@endsection
