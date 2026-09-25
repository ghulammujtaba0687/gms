@extends('layouts.app')

@section('content')
<div class="max-w-2xl bg-white p-6 rounded-lg shadow-sm border border-gray-200">
    <h2 class="text-lg font-semibold text-gray-800 mb-6">Request Membership Freeze / Pause</h2>

    @if ($errors->any())
        <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-600 rounded-md text-sm">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('membership-freezes.store') }}" method="POST" class="space-y-4">
        @csrf

        @if($selectedMembership)
            <input type="hidden" name="membership_id" value="{{ $selectedMembership->id }}">
            <div class="p-4 bg-gray-50 border rounded-md text-sm space-y-1">
                <span class="text-xs text-gray-500 uppercase font-bold block">Target Member & Subscription</span>
                <span class="text-base font-bold text-gray-900 block">{{ $selectedMembership->member->full_name }} ({{ $selectedMembership->member->member_code }})</span>
                <span class="text-sm font-medium text-indigo-600 block">Plan: {{ $selectedMembership->plan_name_snapshot }}</span>
                <span class="text-xs text-gray-500 block">Current Expiry: {{ $selectedMembership->end_date->format('Y-m-d') }}</span>
            </div>
        @else
            <div>
                <label for="membership_id" class="block text-sm font-medium text-gray-700">Select Active Subscription *</label>
                <select name="membership_id" id="membership_id" required class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm bg-white">
                    <option value="">-- Choose Active Membership --</option>
                    @foreach(\App\Models\Membership::where('status', 'active')->get() as $ms)
                        <option value="{{ $ms->id }}" {{ old('membership_id') == $ms->id ? 'selected' : '' }}>
                            {{ $ms->member->full_name ?? 'Member' }} - {{ $ms->plan_name_snapshot }} (Expires: {{ $ms->end_date->format('Y-m-d') }})
                        </option>
                    @endforeach
                </select>
            </div>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="freeze_start_date" class="block text-sm font-medium text-gray-700">Freeze Start Date *</label>
                <input type="date" name="freeze_start_date" id="freeze_start_date" required value="{{ old('freeze_start_date', date('Y-m-d')) }}" min="{{ date('Y-m-d') }}" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm">
            </div>

            <div>
                <label for="freeze_end_date" class="block text-sm font-medium text-gray-700">Freeze End Date * (Min 3 days - Max 30 days)</label>
                <input type="date" name="freeze_end_date" id="freeze_end_date" required value="{{ old('freeze_end_date', date('Y-m-d', strtotime('+6 days'))) }}" min="{{ date('Y-m-d') }}" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm">
            </div>
        </div>

        <div>
            <label for="reason" class="block text-sm font-medium text-gray-700">Freeze Reason / Purpose *</label>
            <input type="text" name="reason" id="reason" required value="{{ old('reason') }}" placeholder="e.g. Medical leave, exam period, or travel" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm">
        </div>

        @if(auth()->user()->hasRole('receptionist'))
            <p class="text-xs text-amber-700 bg-amber-50 p-2 border border-amber-200 rounded">
                ℹ Note: Receptionist freeze requests will be sent to the Manager for approval before extending subscription expiry.
            </p>
        @endif

        <div class="flex justify-end gap-3 pt-4 border-t">
            <a href="{{ $selectedMembership ? route('members.show', $selectedMembership->member_id) : route('dashboard') }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</a>
            <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md text-sm font-medium shadow-sm">
                Submit Freeze Request
            </button>
        </div>
    </form>
</div>
@endsection
