@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h2 class="text-lg font-semibold text-gray-800">Gym Branches</h2>
        @can('branches.manage')
            <a href="{{ route('branches.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-md shadow-sm">
                + Add Branch
            </a>
        @endcan
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
            <thead class="bg-gray-50 text-gray-600 font-medium">
                <tr>
                    <th class="px-6 py-3">Code</th>
                    <th class="px-6 py-3">Name</th>
                    <th class="px-6 py-3">Phone</th>
                    <th class="px-6 py-3">Type</th>
                    <th class="px-6 py-3">Status</th>
                    <th class="px-6 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 text-gray-700">
                @forelse($branches as $branch)
                    <tr>
                        <td class="px-6 py-4 font-mono text-xs">{{ $branch->code }}</td>
                        <td class="px-6 py-4 font-medium text-gray-900">{{ $branch->name }}</td>
                        <td class="px-6 py-4">{{ $branch->phone ?? 'N/A' }}</td>
                        <td class="px-6 py-4">
                            @if($branch->is_main)
                                <span class="bg-blue-100 text-blue-800 text-xs px-2.5 py-0.5 rounded-full font-medium">Main Branch</span>
                            @else
                                <span class="bg-gray-100 text-gray-800 text-xs px-2.5 py-0.5 rounded-full font-medium">Branch</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($branch->is_active)
                                <span class="bg-green-100 text-green-800 text-xs px-2.5 py-0.5 rounded-full font-medium">Active</span>
                            @else
                                <span class="bg-red-100 text-red-800 text-xs px-2.5 py-0.5 rounded-full font-medium">Inactive</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @can('branches.manage')
                                <a href="{{ route('branches.edit', $branch->id) }}" class="text-indigo-600 hover:text-indigo-900 font-medium">
                                    Edit
                                </a>
                            @else
                                <span class="text-gray-400">Read Only</span>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">No branches found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
