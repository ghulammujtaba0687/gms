@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <h2 class="text-lg font-semibold text-gray-800">Expiring Memberships Audit Report</h2>

        <a href="{{ route('reports.expiring', array_merge(request()->all(), ['export' => 'csv'])) }}" class="bg-green-600 hover:bg-green-700 text-white text-xs font-bold px-3 py-2 rounded shadow-sm">
            📥 Export CSV
        </a>
    </div>

    <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
        <form method="GET" action="{{ route('reports.expiring') }}" class="flex gap-4 items-center">
            <div>
                <label class="block text-xs font-bold text-gray-600">Expiring Within</label>
                <select name="days_threshold" onchange="this.form.submit()" class="border border-gray-300 rounded px-3 py-1.5 text-sm bg-white">
                    <option value="7" {{ $daysThreshold == 7 ? 'selected' : '' }}>Next 7 Days</option>
                    <option value="15" {{ $daysThreshold == 15 ? 'selected' : '' }}>Next 15 Days</option>
                    <option value="30" {{ $daysThreshold == 30 ? 'selected' : '' }}>Next 30 Days</option>
                </select>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-4 border-b bg-gray-50 font-bold text-gray-800">
            Subscriptions Expiring in Next {{ $daysThreshold }} Days ({{ $data['expiring_count'] }})
        </div>

        <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
            <thead class="bg-gray-50 text-gray-600 font-medium">
                <tr>
                    <th class="px-6 py-3">Member Code</th>
                    <th class="px-6 py-3">Member Name</th>
                    <th class="px-6 py-3">Plan</th>
                    <th class="px-6 py-3">Start Date</th>
                    <th class="px-6 py-3">Expiry Date</th>
                    <th class="px-6 py-3">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($data['expiring_memberships'] as $ms)
                    <tr>
                        <td class="px-6 py-4 font-mono font-bold text-indigo-600 text-xs">{{ $ms->member->member_code ?? '' }}</td>
                        <td class="px-6 py-4 font-medium">{{ $ms->member->full_name ?? '' }}</td>
                        <td class="px-6 py-4">{{ $ms->plan_name_snapshot }}</td>
                        <td class="px-6 py-4">{{ $ms->start_date->format('Y-m-d') }}</td>
                        <td class="px-6 py-4 font-bold text-red-600">{{ $ms->end_date->format('Y-m-d') }}</td>
                        <td class="px-6 py-4">
                            <a href="{{ route('members.show', $ms->member_id) }}" class="text-indigo-600 hover:underline">View Profile</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">No subscriptions expiring in this timeframe.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
