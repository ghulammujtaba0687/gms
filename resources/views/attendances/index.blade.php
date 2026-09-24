@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <h2 class="text-lg font-semibold text-gray-800">Daily Attendance & Check-In</h2>
        @can('checkIn', App\Models\Attendance::class)
            <a href="{{ route('attendances.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-md shadow-sm">
                + Check-In Member
            </a>
        @endcan
    </div>

    <!-- Quick Check-In & Filter Bar -->
    <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
        <form method="GET" action="{{ route('attendances.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-4">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search Member ID, Phone, Name..." class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm">

            <input type="date" name="date" value="{{ request('date', date('Y-m-d')) }}" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm">

            <select name="status" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm bg-white">
                <option value="">All Statuses</option>
                <option value="present" {{ request('status') === 'present' ? 'selected' : '' }}>Present (Open)</option>
                <option value="checked_out" {{ request('status') === 'checked_out' ? 'selected' : '' }}>Checked Out</option>
            </select>

            <div class="flex gap-2">
                <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 rounded-md text-sm font-medium shadow-sm">Filter</button>
                <a href="{{ route('attendances.index') }}" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md text-sm border border-gray-300">Today</a>
            </div>
        </form>
    </div>

    <!-- Attendance Table -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
            <thead class="bg-gray-50 text-gray-600 font-medium">
                <tr>
                    <th class="px-6 py-3">Member</th>
                    <th class="px-6 py-3">Branch</th>
                    <th class="px-6 py-3">Check-In Time</th>
                    <th class="px-6 py-3">Check-Out Time</th>
                    <th class="px-6 py-3">Status</th>
                    <th class="px-6 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 text-gray-700">
                @forelse($attendances as $att)
                    <tr>
                        <td class="px-6 py-4 font-medium text-gray-900">
                            <a href="{{ route('members.show', $att->member_id) }}" class="hover:underline text-indigo-600">
                                {{ $att->member->full_name ?? 'Member' }}
                            </a>
                            <span class="text-xs text-gray-500 block font-mono">{{ $att->member->member_code ?? '' }}</span>
                        </td>
                        <td class="px-6 py-4 text-xs font-semibold">{{ $att->branch->name ?? 'N/A' }}</td>
                        <td class="px-6 py-4 font-mono text-gray-900">{{ $att->check_in_time->format('h:i A') }}</td>
                        <td class="px-6 py-4 font-mono text-gray-900">
                            {{ $att->check_out_time ? $att->check_out_time->format('h:i A') : '—' }}
                        </td>
                        <td class="px-6 py-4">
                            @if($att->status === 'present')
                                <span class="bg-green-100 text-green-800 text-xs px-2.5 py-0.5 rounded-full font-bold">Present (In Gym)</span>
                            @else
                                <span class="bg-gray-100 text-gray-700 text-xs px-2.5 py-0.5 rounded-full font-medium">Checked Out</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 space-x-2">
                            @can('checkOut', $att)
                                @if($att->isOpen())
                                    <form action="{{ route('attendances.checkout', $att->id) }}" method="POST" class="inline-block">
                                        @csrf
                                        <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-white font-medium text-xs px-3 py-1 rounded shadow-sm">
                                            Check-Out
                                        </button>
                                    </form>
                                @endif
                            @endcan

                            @can('update', $att)
                                <a href="{{ route('attendances.edit', $att->id) }}" class="text-indigo-600 hover:underline font-medium text-xs">Edit</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">No attendance records found for selected date.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="px-6 py-4 border-t border-gray-200">
            {{ $attendances->links() }}
        </div>
    </div>
</div>
@endsection
