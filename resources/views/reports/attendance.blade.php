@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <h2 class="text-lg font-semibold text-gray-800">Attendance Trends Report</h2>

        <a href="{{ route('reports.attendance', array_merge(request()->all(), ['export' => 'csv'])) }}" class="bg-green-600 hover:bg-green-700 text-white text-xs font-bold px-3 py-2 rounded shadow-sm">
            📥 Export CSV
        </a>
    </div>

    <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
        <form method="GET" action="{{ route('reports.attendance') }}" class="flex gap-4 items-center">
            <div>
                <label class="block text-xs font-bold text-gray-600">Start Date</label>
                <input type="date" name="start_date" value="{{ $startDate }}" class="border border-gray-300 rounded px-3 py-1.5 text-sm">
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-600">End Date</label>
                <input type="date" name="end_date" value="{{ $endDate }}" class="border border-gray-300 rounded px-3 py-1.5 text-sm">
            </div>

            <div class="pt-4">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-1.5 rounded shadow-sm">Filter</button>
            </div>
        </form>
    </div>

    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
        <span class="text-xs font-bold text-gray-500 uppercase block">Total Check-Ins in Period</span>
        <span class="text-3xl font-bold text-green-600">{{ $data['total_check_ins'] }}</span>
    </div>
</div>
@endsection
