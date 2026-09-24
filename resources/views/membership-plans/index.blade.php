@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <h2 class="text-lg font-semibold text-gray-800">Membership Plans Catalog</h2>
        @can('create', App\Models\MembershipPlan::class)
            <a href="{{ route('membership-plans.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-md shadow-sm">
                + Create New Plan
            </a>
        @endcan
    </div>

    <!-- Filter Bar -->
    <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
        <form method="GET" action="{{ route('membership-plans.index') }}" class="flex gap-4">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search plan name or code..." class="w-full max-w-xs border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm">
            <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 rounded-md text-sm font-medium shadow-sm">Search</button>
            <a href="{{ route('membership-plans.index') }}" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md text-sm border border-gray-300">Reset</a>
        </form>
    </div>

    <!-- Plans Table -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
            <thead class="bg-gray-50 text-gray-600 font-medium">
                <tr>
                    <th class="px-6 py-3">Code</th>
                    <th class="px-6 py-3">Plan Name</th>
                    <th class="px-6 py-3">Scope / Branch</th>
                    <th class="px-6 py-3">Duration</th>
                    <th class="px-6 py-3">Price</th>
                    <th class="px-6 py-3">Signup Fee</th>
                    <th class="px-6 py-3">Status</th>
                    <th class="px-6 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 text-gray-700">
                @forelse($plans as $plan)
                    <tr>
                        <td class="px-6 py-4 font-mono font-medium text-xs text-indigo-600">{{ $plan->code }}</td>
                        <td class="px-6 py-4 font-medium text-gray-900">{{ $plan->name }}</td>
                        <td class="px-6 py-4 text-xs font-semibold">
                            @if($plan->isGlobal())
                                <span class="bg-purple-100 text-purple-800 px-2.5 py-0.5 rounded-full">Global (All Branches)</span>
                            @else
                                <span class="bg-blue-100 text-blue-800 px-2.5 py-0.5 rounded-full">{{ $plan->branch->name ?? 'Branch' }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 capitalize">{{ $plan->duration_value }} {{ $plan->duration_type }}</td>
                        <td class="px-6 py-4 font-semibold text-gray-900">PKR {{ number_format($plan->price, 2) }}</td>
                        <td class="px-6 py-4">PKR {{ number_format($plan->signup_fee, 2) }}</td>
                        <td class="px-6 py-4">
                            @if($plan->is_active)
                                <span class="bg-green-100 text-green-800 text-xs px-2.5 py-0.5 rounded-full font-medium">Active</span>
                            @else
                                <span class="bg-red-100 text-red-800 text-xs px-2.5 py-0.5 rounded-full font-medium">Inactive</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 space-x-2">
                            @can('update', $plan)
                                <a href="{{ route('membership-plans.edit', $plan->id) }}" class="text-indigo-600 hover:text-indigo-900 font-medium">Edit</a>
                            @else
                                <span class="text-gray-400">View Only</span>
                            @endcan

                            @can('delete', $plan)
                                <form action="{{ route('membership-plans.destroy', $plan->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Archive this plan?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 font-medium">Archive</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-4 text-center text-gray-500">No membership plans available.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="px-6 py-4 border-t border-gray-200">
            {{ $plans->links() }}
        </div>
    </div>
</div>
@endsection
