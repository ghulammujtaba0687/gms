@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <h2 class="text-lg font-semibold text-gray-800">Expenses Directory</h2>
        @can('create', App\Models\Expense::class)
            <a href="{{ route('expenses.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-md shadow-sm">
                + Record Expense
            </a>
        @endcan
    </div>

    <!-- Filter Bar -->
    <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
        <form method="GET" action="{{ route('expenses.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-4">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search Code, Title, Vendor..." class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm">

            <select name="category_id" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm bg-white">
                <option value="">All Categories</option>
                @foreach(\App\Models\ExpenseCategory::all() as $cat)
                    <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>
                        {{ $cat->name }}
                    </option>
                @endforeach
            </select>

            <select name="status" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm bg-white">
                <option value="">All Statuses</option>
                <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                <option value="recorded" {{ request('status') === 'recorded' ? 'selected' : '' }}>Pending Approval</option>
                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>

            <div class="flex gap-2">
                <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 rounded-md text-sm font-medium shadow-sm">Filter</button>
                <a href="{{ route('expenses.index') }}" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md text-sm border border-gray-300">Reset</a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
            <thead class="bg-gray-50 text-gray-600 font-medium">
                <tr>
                    <th class="px-6 py-3">Expense Code</th>
                    <th class="px-6 py-3">Title</th>
                    <th class="px-6 py-3">Category</th>
                    <th class="px-6 py-3">Amount</th>
                    <th class="px-6 py-3">Date</th>
                    <th class="px-6 py-3">Status</th>
                    <th class="px-6 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 text-gray-700">
                @forelse($expenses as $exp)
                    <tr>
                        <td class="px-6 py-4 font-mono font-medium text-indigo-600 text-xs">{{ $exp->expense_code }}</td>
                        <td class="px-6 py-4 font-medium text-gray-900">{{ $exp->title }}</td>
                        <td class="px-6 py-4 text-xs font-semibold text-gray-600">{{ $exp->category->name ?? 'Uncategorized' }}</td>
                        <td class="px-6 py-4 font-bold text-gray-900">PKR {{ number_format($exp->amount, 2) }}</td>
                        <td class="px-6 py-4">{{ $exp->expense_date->format('Y-m-d') }}</td>
                        <td class="px-6 py-4">
                            @if($exp->status === 'approved')
                                <span class="bg-green-100 text-green-800 text-xs px-2.5 py-0.5 rounded-full font-medium">Approved</span>
                            @elseif($exp->status === 'recorded')
                                <span class="bg-yellow-100 text-yellow-800 text-xs px-2.5 py-0.5 rounded-full font-medium">Pending Approval</span>
                            @else
                                <span class="bg-red-100 text-red-800 text-xs px-2.5 py-0.5 rounded-full font-medium">Cancelled</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 space-x-2">
                            <a href="{{ route('expenses.show', $exp->id) }}" class="text-indigo-600 hover:text-indigo-900 font-medium">View</a>
                            @can('update', $exp)
                                <a href="{{ route('expenses.edit', $exp->id) }}" class="text-gray-600 hover:text-gray-900 font-medium">Edit</a>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">No expense records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="px-6 py-4 border-t border-gray-200">
            {{ $expenses->links() }}
        </div>
    </div>
</div>
@endsection
