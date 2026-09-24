@extends('layouts.app')

@section('content')
<div class="max-w-2xl bg-white p-6 rounded-lg shadow-sm border border-gray-200">
    <h2 class="text-lg font-semibold text-gray-800 mb-6">Assign Membership Plan</h2>

    @if ($errors->any())
        <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-600 rounded-md text-sm">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('memberships.store') }}" method="POST" class="space-y-4">
        @csrf

        @if($selectedMember)
            <input type="hidden" name="member_id" value="{{ $selectedMember->id }}">
            <div class="p-4 bg-gray-50 border rounded-md">
                <span class="text-xs text-gray-500 block uppercase font-bold">Selected Member</span>
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

        <div>
            <label for="membership_plan_id" class="block text-sm font-medium text-gray-700">Select Membership Plan *</label>
            <select name="membership_plan_id" id="membership_plan_id" required class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm bg-white">
                <option value="">-- Choose Plan --</option>
                @foreach($plans as $plan)
                    <option value="{{ $plan->id }}" {{ old('membership_plan_id') == $plan->id ? 'selected' : '' }}>
                        {{ $plan->name }} (PKR {{ number_format($plan->price, 2) }} - {{ $plan->duration_value }} {{ $plan->duration_type }})
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="start_date" class="block text-sm font-medium text-gray-700">Start Date (Leave blank for Today)</label>
            <input type="date" name="start_date" id="start_date" value="{{ old('start_date', date('Y-m-d')) }}" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">If Member Has An Active Plan *</label>
            <div class="space-y-2">
                <label class="flex items-center text-sm text-gray-700">
                    <input type="radio" name="conflict_action" value="stack" {{ old('conflict_action', 'stack') === 'stack' ? 'checked' : '' }} class="mr-2 text-indigo-600">
                    Stack / Schedule Start After Current Plan Expires (Recommended)
                </label>
                <label class="flex items-center text-sm text-gray-700">
                    <input type="radio" name="conflict_action" value="replace" {{ old('conflict_action') === 'replace' ? 'checked' : '' }} class="mr-2 text-indigo-600">
                    Cancel Existing Active Plan Immediately & Start New Plan
                </label>
            </div>
        </div>

        <div>
            <label for="notes" class="block text-sm font-medium text-gray-700">Notes / Remarks</label>
            <textarea name="notes" id="notes" rows="2" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm">{{ old('notes') }}</textarea>
        </div>

        <div class="flex justify-end gap-3 pt-4 border-t">
            <a href="{{ $selectedMember ? route('members.show', $selectedMember->id) : route('memberships.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</a>
            <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md text-sm font-medium shadow-sm">Assign Membership</button>
        </div>
    </form>
</div>
@endsection
