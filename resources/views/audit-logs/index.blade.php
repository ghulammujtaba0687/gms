@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h2 class="text-lg font-semibold text-gray-800">System Audit Logs</h2>
    </div>

    <!-- Filter Panel -->
    <div class="bg-white p-4 shadow rounded-lg border border-gray-200">
        <form method="GET" action="{{ route('audit-logs.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-4">
            <div>
                <label for="module" class="block text-xs font-medium text-gray-700">Module</label>
                <input id="module" name="module" type="text" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm px-3 py-1.5 text-sm" value="{{ request('module') }}" placeholder="e.g. member, payment" />
            </div>

            <div>
                <label for="action" class="block text-xs font-medium text-gray-700">Action</label>
                <input id="action" name="action" type="text" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm px-3 py-1.5 text-sm" value="{{ request('action') }}" placeholder="e.g. create, update" />
            </div>

            <div>
                <label for="user_id" class="block text-xs font-medium text-gray-700">User ID</label>
                <input id="user_id" name="user_id" type="number" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm px-3 py-1.5 text-sm" value="{{ request('user_id') }}" placeholder="User ID" />
            </div>

            <div>
                <label for="ip_address" class="block text-xs font-medium text-gray-700">IP Address</label>
                <input id="ip_address" name="ip_address" type="text" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm px-3 py-1.5 text-sm" value="{{ request('ip_address') }}" placeholder="127.0.0.1" />
            </div>

            <div class="flex items-end space-x-2">
                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-md text-xs uppercase tracking-widest">Filter</button>
                <a href="{{ route('audit-logs.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md text-xs uppercase tracking-widest hover:bg-gray-300">Reset</a>
            </div>
        </form>
    </div>

    <!-- Logs Table -->
    <div class="bg-white shadow rounded-lg border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-gray-500 font-medium">
                <tr>
                    <th class="px-4 py-3 text-left">Timestamp</th>
                    <th class="px-4 py-3 text-left">User</th>
                    <th class="px-4 py-3 text-left">Branch</th>
                    <th class="px-4 py-3 text-left">Module</th>
                    <th class="px-4 py-3 text-left">Action</th>
                    <th class="px-4 py-3 text-left">Subject</th>
                    <th class="px-4 py-3 text-left">IP Address</th>
                    <th class="px-4 py-3 text-left">Details</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                @forelse($auditLogs as $log)
                    <tr>
                        <td class="px-4 py-3 text-xs text-gray-500 whitespace-nowrap">{{ $log->created_at?->format('Y-m-d H:i:s') }}</td>
                        <td class="px-4 py-3 text-xs font-semibold text-gray-800">{{ $log->user?->name ?? 'System' }}</td>
                        <td class="px-4 py-3 text-xs text-gray-600">{{ $log->branch?->name ?? 'Global' }}</td>
                        <td class="px-4 py-3 text-xs text-indigo-600 font-medium uppercase">{{ $log->module }}</td>
                        <td class="px-4 py-3 text-xs font-bold text-gray-700 uppercase">{{ $log->action }}</td>
                        <td class="px-4 py-3 text-xs text-gray-500">{{ $log->description ?? '-' }}</td>
                        <td class="px-4 py-3 text-xs text-gray-500">{{ $log->ip_address }}</td>
                        <td class="px-4 py-3 text-xs font-mono text-gray-600">
                            <details>
                                <summary class="cursor-pointer text-indigo-600 hover:underline">View Changes</summary>
                                <pre class="mt-2 p-2 bg-gray-100 rounded max-w-xs overflow-x-auto text-xxs">{{ json_encode(['old' => $log->old_values, 'new' => $log->new_values], JSON_PRETTY_PRINT) }}</pre>
                            </details>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-6 text-center text-gray-500">No audit logs found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="p-4 border-t border-gray-200">
            {{ $auditLogs->links() }}
        </div>
    </div>
</div>
@endsection
