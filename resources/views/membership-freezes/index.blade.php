@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h2 class="text-lg font-semibold text-gray-800">Pending Freeze Approval Queue</h2>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
            <thead class="bg-gray-50 text-gray-600 font-medium">
                <tr>
                    <th class="px-6 py-3">Member</th>
                    <th class="px-6 py-3">Plan</th>
                    <th class="px-6 py-3">Freeze Start</th>
                    <th class="px-6 py-3">Freeze End</th>
                    <th class="px-6 py-3">Days</th>
                    <th class="px-6 py-3">Reason</th>
                    <th class="px-6 py-3">Requested By</th>
                    <th class="px-6 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 text-gray-700">
                @forelse($pendingFreezes as $fz)
                    <tr>
                        <td class="px-6 py-4 font-medium text-gray-900">
                            <a href="{{ route('members.show', $fz->member_id) }}" class="text-indigo-600 hover:underline">
                                {{ $fz->member->full_name ?? 'Member' }}
                            </a>
                        </td>
                        <td class="px-6 py-4 font-medium">{{ $fz->membership->plan_name_snapshot ?? '' }}</td>
                        <td class="px-6 py-4">{{ $fz->freeze_start_date->format('Y-m-d') }}</td>
                        <td class="px-6 py-4">{{ $fz->freeze_end_date->format('Y-m-d') }}</td>
                        <td class="px-6 py-4 font-bold text-gray-900">{{ $fz->frozen_days }} Days</td>
                        <td class="px-6 py-4 text-xs text-gray-600">{{ $fz->reason }}</td>
                        <td class="px-6 py-4 text-xs">{{ $fz->requester->name ?? 'Staff' }}</td>
                        <td class="px-6 py-4 space-x-2">
                            @can('approve', $fz)
                                <form action="{{ route('membership-freezes.approve', $fz->id) }}" method="POST" class="inline-block">
                                    @csrf
                                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-medium text-xs px-3 py-1 rounded shadow-sm">
                                        Approve
                                    </button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-4 text-center text-gray-500">No pending freeze approval requests in queue.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="px-6 py-4 border-t border-gray-200">
            {{ $pendingFreezes->links() }}
        </div>
    </div>
</div>
@endsection
