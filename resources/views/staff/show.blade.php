@extends('layouts.app')

@section('content')
<div class="max-w-3xl space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-xl font-bold text-gray-900">{{ $staff->user->name }}</h2>
            <span class="text-sm font-mono text-indigo-600 font-medium">Code: {{ $staff->staff_code }}</span>
        </div>

        <div class="flex items-center gap-2">
            @can('update', $staff)
                <a href="{{ route('staff.edit', $staff->id) }}" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-md shadow-sm">
                    Edit Profile
                </a>
            @endcan

            @can('delete', $staff)
                <form action="{{ route('staff.destroy', $staff->id) }}" method="POST" onsubmit="return confirm('Archive this staff profile and user account?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 text-sm font-medium px-4 py-2 rounded-md">
                        Archive Profile
                    </button>
                </form>
            @endcan
        </div>
    </div>

    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 space-y-4">
        <div class="flex justify-between items-start border-b pb-4">
            <div>
                <span class="text-xs text-gray-500 uppercase font-bold block">Designation</span>
                <span class="text-base font-bold text-gray-900">{{ $staff->designation }}</span>
                <span class="text-xs text-gray-500 block">Branch: {{ $staff->branch->name ?? 'N/A' }}</span>
            </div>

            <div>
                @if($staff->is_trainer)
                    <span class="bg-purple-100 text-purple-800 text-sm px-3 py-1 rounded-full font-bold">Personal Trainer</span>
                @else
                    <span class="bg-blue-100 text-blue-800 text-sm px-3 py-1 rounded-full font-medium">Gym Staff</span>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
            <div>
                <span class="text-gray-500 block">Login Email</span>
                <span class="font-medium text-gray-900">{{ $staff->user->email }}</span>
            </div>

            <div>
                <span class="text-gray-500 block">Phone Number</span>
                <span class="font-medium text-gray-900">{{ $staff->user->phone ?? 'N/A' }}</span>
            </div>

            <div>
                <span class="text-gray-500 block">CNIC / ID Card</span>
                <span class="font-medium text-gray-900">{{ $staff->cnic ?? 'N/A' }}</span>
            </div>

            <div>
                <span class="text-gray-500 block">Joining Date</span>
                <span class="font-medium text-gray-900">{{ $staff->joining_date->format('Y-m-d') }}</span>
            </div>

            <div>
                <span class="text-gray-500 block">Monthly Salary</span>
                <span class="font-bold text-gray-900">PKR {{ number_format($staff->monthly_salary, 2) }}</span>
            </div>

            <div>
                <span class="text-gray-500 block">Status</span>
                <span class="font-medium text-gray-900 capitalize">{{ $staff->status }}</span>
            </div>
        </div>

        @if($staff->is_trainer && $staff->specialization)
            <div class="pt-2 border-t">
                <span class="text-gray-500 text-sm block">Specialization</span>
                <p class="text-sm font-semibold text-purple-900">{{ $staff->specialization }}</p>
            </div>
        @endif

        @if($staff->is_trainer)
            <!-- Assigned Personal Training Clients -->
            <div class="pt-4 border-t space-y-3">
                <h4 class="text-sm font-bold text-gray-800">Assigned Personal Training Clients ({{ $staff->ptAssignments->where('status', 'active')->count() }})</h4>

                <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                    <thead class="bg-gray-50 text-gray-600 font-medium">
                        <tr>
                            <th class="px-4 py-2">Member</th>
                            <th class="px-4 py-2">Member ID</th>
                            <th class="px-4 py-2">Assigned Date</th>
                            <th class="px-4 py-2">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($staff->ptAssignments as $asgn)
                            <tr>
                                <td class="px-4 py-2 font-medium">
                                    <a href="{{ route('members.show', $asgn->member_id) }}" class="text-indigo-600 hover:underline">
                                        {{ $asgn->member->full_name ?? 'Member' }}
                                    </a>
                                </td>
                                <td class="px-4 py-2 font-mono text-xs">{{ $asgn->member->member_code ?? '' }}</td>
                                <td class="px-4 py-2">{{ $asgn->assignment_date->format('Y-m-d') }}</td>
                                <td class="px-4 py-2 capitalize">{{ $asgn->status }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-2 text-center text-gray-500">No PT clients currently assigned.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
