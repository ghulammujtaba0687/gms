@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <h2 class="text-lg font-semibold text-gray-800">Member Subscriptions</h2>
        @can('create', App\Models\Membership::class)
            <a href="{{ route('memberships.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-md shadow-sm">
                + Assign Plan
            </a>
        @endcan
    </div>

    <!-- Filter Bar -->
    <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
        <form method="GET" action="{{ route('memberships.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search member name or code..." class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm">

            <select name="status" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm bg-white">
                <option value="">All Statuses</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="scheduled" {{ request('status') === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expired</option>
                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>

            <div class="flex gap-2">
                <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 rounded-md text-sm font-medium shadow-sm">Filter</button>
                <a href="{{ route('memberships.index') }}" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md text-sm border border-gray-300">Reset</a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
            <thead class="bg-gray-50 text-gray-600 font-medium">
                <tr>
                    <th class="px-6 py-3">Member</th>
                    <th class="px-6 py-3">Plan</th>
                    <th class="px-6 py-3">Price Snapshot</th>
                    <th class="px-6 py-3">Start Date</th>
                    <th class="px-6 py-3">End Date</th>
                    <th class="px-6 py-3">Status</th>
                    <th class="px-6 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 text-gray-700">
                @forelse($memberships as $ms)
                    <tr>
                        <td class="px-6 py-4 font-medium text-gray-900">
                            <a href="{{ route('members.show', $ms->member_id) }}" class="text-indigo-600 hover:underline">
                                {{ $ms->member->full_name ?? 'Member' }}
                            </a>
                            <span class="text-xs text-gray-500 block font-mono">{{ $ms->member->member_code ?? '' }}</span>
                        </td>
                        <td class="px-6 py-4 font-medium">{{ $ms->plan_name_snapshot }}</td>
                        <td class="px-6 py-4 font-semibold text-gray-900">PKR {{ number_format($ms->plan_price_snapshot, 2) }}</td>
                        <td class="px-6 py-4">{{ $ms->start_date->format('Y-m-d') }}</td>
                        <td class="px-6 py-4">{{ $ms->end_date->format('Y-m-d') }}</td>
                        <td class="px-6 py-4">
                            @if($ms->status === 'active')
                                <span class="bg-green-100 text-green-800 text-xs px-2.5 py-0.5 rounded-full font-medium">Active</span>
                            @elseif($ms->status === 'scheduled')
                                <span class="bg-blue-100 text-blue-800 text-xs px-2.5 py-0.5 rounded-full font-medium">Scheduled</span>
                            @elseif($ms->status === 'expired')
                                <span class="bg-yellow-100 text-yellow-800 text-xs px-2.5 py-0.5 rounded-full font-medium">Expired</span>
                            @else
                                <span class="bg-red-100 text-red-800 text-xs px-2.5 py-0.5 rounded-full font-medium">Cancelled</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <a href="{{ route('memberships.show', $ms->id) }}" class="text-indigo-600 hover:text-indigo-900 font-medium">Details</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">No member subscriptions found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="px-6 py-4 border-t border-gray-200">
            {{ $memberships->links() }}
        </div>
    </div>
</div>
@endsection
