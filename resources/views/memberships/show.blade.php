@extends('layouts.app')

@section('content')
<div class="max-w-3xl space-y-6">
    <div class="flex justify-between items-center">
        <h2 class="text-xl font-bold text-gray-900">Membership Details</h2>
        <a href="{{ route('members.show', $membership->member_id) }}" class="text-sm text-indigo-600 hover:underline">← Back to Member Profile</a>
    </div>

    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 space-y-4">
        <div class="flex justify-between items-start border-b pb-4">
            <div>
                <span class="text-xs text-gray-500 uppercase font-bold block">Member</span>
                <h3 class="text-lg font-bold text-gray-900">{{ $membership->member->full_name ?? 'N/A' }}</h3>
                <span class="text-sm font-mono text-indigo-600 font-medium">{{ $membership->member->member_code ?? '' }}</span>
            </div>

            <div>
                @if($membership->status === 'active')
                    <span class="bg-green-100 text-green-800 text-sm px-3 py-1 rounded-full font-semibold">Active</span>
                @elseif($membership->status === 'scheduled')
                    <span class="bg-blue-100 text-blue-800 text-sm px-3 py-1 rounded-full font-semibold">Scheduled</span>
                @elseif($membership->status === 'expired')
                    <span class="bg-yellow-100 text-yellow-800 text-sm px-3 py-1 rounded-full font-semibold">Expired</span>
                @else
                    <span class="bg-red-100 text-red-800 text-sm px-3 py-1 rounded-full font-semibold">Cancelled</span>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
            <div>
                <span class="text-gray-500 block">Plan Name (Snapshot)</span>
                <span class="font-bold text-gray-900">{{ $membership->plan_name_snapshot }}</span>
            </div>

            <div>
                <span class="text-gray-500 block">Plan Code (Snapshot)</span>
                <span class="font-mono text-gray-900 font-medium">{{ $membership->plan_code_snapshot }}</span>
            </div>

            <div>
                <span class="text-gray-500 block">Price Snapshot</span>
                <span class="font-bold text-gray-900">PKR {{ number_format($membership->plan_price_snapshot, 2) }}</span>
            </div>

            <div>
                <span class="text-gray-500 block">Signup Fee Snapshot</span>
                <span class="font-medium text-gray-900">PKR {{ number_format($membership->plan_signup_fee_snapshot, 2) }}</span>
            </div>

            <div>
                <span class="text-gray-500 block">Start Date</span>
                <span class="font-medium text-gray-900">{{ $membership->start_date->format('Y-m-d') }}</span>
            </div>

            <div>
                <span class="text-gray-500 block">End Date (Expiry)</span>
                <span class="font-medium text-gray-900">{{ $membership->end_date->format('Y-m-d') }}</span>
            </div>

            <div>
                <span class="text-gray-500 block">Assigned By</span>
                <span class="font-medium text-gray-900">{{ $membership->assigner->name ?? 'System' }}</span>
            </div>

            <div>
                <span class="text-gray-500 block">Branch</span>
                <span class="font-medium text-gray-900">{{ $membership->branch->name ?? 'N/A' }}</span>
            </div>
        </div>

        @if($membership->status === 'cancelled')
            <div class="p-4 bg-red-50 border border-red-200 rounded-md space-y-1 text-sm text-red-800">
                <span class="font-bold block">Cancellation Record</span>
                <p>Cancelled On: {{ $membership->cancelled_at ? $membership->cancelled_at->format('Y-m-d H:i') : 'N/A' }}</p>
                <p>Cancelled By: {{ $membership->canceller->name ?? 'N/A' }}</p>
                <p>Reason: {{ $membership->cancellation_reason ?? 'No reason provided' }}</p>
            </div>
        @endif

        @can('cancel', $membership)
            @if(in_array($membership->status, ['active', 'scheduled']))
                <div class="pt-4 border-t">
                    <form action="{{ route('memberships.cancel', $membership->id) }}" method="POST" class="space-y-3" onsubmit="return confirm('Are you sure you want to cancel this membership?')">
                        @csrf
                        <div>
                            <label for="cancellation_reason" class="block text-sm font-medium text-gray-700">Cancellation Reason *</label>
                            <input type="text" name="cancellation_reason" id="cancellation_reason" required placeholder="e.g. Member requested refund or plan change" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm">
                        </div>
                        <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-medium text-sm px-4 py-2 rounded-md shadow-sm">
                            Cancel Membership Subscription
                        </button>
                    </form>
                </div>
            @endif
        @endcan
    </div>
</div>
@endsection
