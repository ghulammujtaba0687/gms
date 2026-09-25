@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <h2 class="text-lg font-semibold text-gray-800">Gym Staff & Trainers Directory</h2>
        @can('create', App\Models\StaffProfile::class)
            <a href="{{ route('staff.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-md shadow-sm">
                + Add Staff / Trainer
            </a>
        @endcan
    </div>

    <!-- Filter Bar -->
    <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
        <form method="GET" action="{{ route('staff.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-4">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search Code, Name, Role..." class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm">

            <select name="status" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm bg-white">
                <option value="">All Statuses</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                <option value="on_leave" {{ request('status') === 'on_leave' ? 'selected' : '' }}>On Leave</option>
            </select>

            <div class="flex items-center pt-2 sm:pt-0">
                <label class="flex items-center text-sm text-gray-700">
                    <input type="checkbox" name="trainers_only" value="1" {{ request('trainers_only') ? 'checked' : '' }} onchange="this.form.submit()" class="mr-2 border-gray-300 rounded text-indigo-600">
                    Trainers Only
                </label>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 rounded-md text-sm font-medium shadow-sm">Filter</button>
                <a href="{{ route('staff.index') }}" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md text-sm border border-gray-300">Reset</a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
            <thead class="bg-gray-50 text-gray-600 font-medium">
                <tr>
                    <th class="px-6 py-3">Staff ID</th>
                    <th class="px-6 py-3">Name</th>
                    <th class="px-6 py-3">Designation / Role</th>
                    <th class="px-6 py-3">Branch</th>
                    <th class="px-6 py-3">Type</th>
                    <th class="px-6 py-3">Status</th>
                    <th class="px-6 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 text-gray-700">
                @forelse($staffProfiles as $stf)
                    <tr>
                        <td class="px-6 py-4 font-mono font-medium text-indigo-600 text-xs">{{ $stf->staff_code }}</td>
                        <td class="px-6 py-4 font-medium text-gray-900">
                            {{ $stf->user->name ?? 'N/A' }}
                            <span class="text-xs text-gray-500 block">{{ $stf->user->email ?? '' }}</span>
                        </td>
                        <td class="px-6 py-4 font-medium">{{ $stf->designation }}</td>
                        <td class="px-6 py-4 text-xs font-semibold text-gray-600">{{ $stf->branch->name ?? 'N/A' }}</td>
                        <td class="px-6 py-4">
                            @if($stf->is_trainer)
                                <span class="bg-purple-100 text-purple-800 text-xs px-2.5 py-0.5 rounded-full font-bold">Trainer</span>
                            @else
                                <span class="bg-blue-100 text-blue-800 text-xs px-2.5 py-0.5 rounded-full font-medium">Staff</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($stf->status === 'active')
                                <span class="bg-green-100 text-green-800 text-xs px-2.5 py-0.5 rounded-full font-medium">Active</span>
                            @elseif($stf->status === 'on_leave')
                                <span class="bg-yellow-100 text-yellow-800 text-xs px-2.5 py-0.5 rounded-full font-medium">On Leave</span>
                            @else
                                <span class="bg-red-100 text-red-800 text-xs px-2.5 py-0.5 rounded-full font-medium">Inactive</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 space-x-2">
                            <a href="{{ route('staff.show', $stf->id) }}" class="text-indigo-600 hover:text-indigo-900 font-medium">View</a>
                            @can('update', $stf)
                                <a href="{{ route('staff.edit', $stf->id) }}" class="text-gray-600 hover:text-gray-900 font-medium">Edit</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">No staff/trainer records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="px-6 py-4 border-t border-gray-200">
            {{ $staffProfiles->links() }}
        </div>
    </div>
</div>
@endsection
