@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <div>
            <h2 class="text-lg font-semibold text-gray-800">In-App Notification Center</h2>
            <p class="text-xs text-gray-500">Member expiry, dues, payment, and freeze operational alerts.</p>
        </div>

        @if($unreadCount > 0)
            <form action="{{ route('notifications.read-all') }}" method="POST">
                @csrf
                <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white text-xs font-medium px-3 py-2 rounded-md shadow-sm">
                    Mark All as Read ({{ $unreadCount }})
                </button>
            </form>
        @endif
    </div>

    @if(session('success'))
        <div class="p-4 text-sm text-green-800 rounded-lg bg-green-50 border border-green-200" role="alert">
            {{ session('success') }}
        </div>
    @endif

    <!-- Filter Bar -->
    <div class="bg-white p-4 rounded-lg shadow-sm border border-gray-200">
        <form method="GET" action="{{ route('notifications.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <select name="status" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm bg-white">
                <option value="">All Statuses</option>
                <option value="unread" {{ request('status') === 'unread' ? 'selected' : '' }}>Unread</option>
                <option value="read" {{ request('status') === 'read' ? 'selected' : '' }}>Read</option>
            </select>

            <select name="type" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm bg-white">
                <option value="">All Notification Types</option>
                <option value="expiry_reminder" {{ request('type') === 'expiry_reminder' ? 'selected' : '' }}>Expiry Reminder</option>
                <option value="expired" {{ request('type') === 'expired' ? 'selected' : '' }}>Expired Membership</option>
                <option value="dues_reminder" {{ request('type') === 'dues_reminder' ? 'selected' : '' }}>Outstanding Dues</option>
                <option value="payment_received" {{ request('type') === 'payment_received' ? 'selected' : '' }}>Payment Confirmation</option>
                <option value="freeze_approved" {{ request('type') === 'freeze_approved' ? 'selected' : '' }}>Freeze Approved</option>
                <option value="freeze_ending" {{ request('type') === 'freeze_ending' ? 'selected' : '' }}>Freeze Ending</option>
            </select>

            <div class="flex gap-2">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium shadow-sm">Filter</button>
                <a href="{{ route('notifications.index') }}" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-md text-sm border border-gray-300">Reset</a>
            </div>
        </form>
    </div>

    <!-- Notification List -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden divide-y divide-gray-200">
        @forelse($notifications as $n)
            <div class="p-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 {{ is_null($n->read_at) ? 'bg-indigo-50/50 font-medium' : 'bg-white text-gray-600' }}">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <span class="text-xs uppercase px-2 py-0.5 rounded font-bold
                            @if(in_array($n->type, ['expiry_reminder', 'expired'])) bg-amber-100 text-amber-800
                            @elseif($n->type === 'dues_reminder') bg-red-100 text-red-800
                            @elseif($n->type === 'payment_received') bg-green-100 text-green-800
                            @else bg-blue-100 text-blue-800 @endif">
                            {{ str_replace('_', ' ', $n->type) }}
                        </span>
                        <span class="text-xs text-gray-500 font-normal">{{ $n->created_at->diffForHumans() }}</span>
                        <span class="text-xs text-gray-400 font-mono">({{ $n->branch->name ?? 'Global' }})</span>
                    </div>
                    <h4 class="text-sm font-semibold text-gray-900">{{ $n->title }}</h4>
                    <p class="text-xs text-gray-600">{{ $n->message }}</p>
                </div>

                <div class="flex items-center gap-2">
                    @if(is_null($n->read_at))
                        <form action="{{ route('notifications.read', $n->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="text-xs text-indigo-600 hover:text-indigo-900 border border-indigo-200 bg-white hover:bg-indigo-50 px-3 py-1 rounded">
                                Mark Read
                            </button>
                        </form>
                    @else
                        <span class="text-xs text-gray-400 italic">Read</span>
                    @endif
                </div>
            </div>
        @empty
            <div class="p-8 text-center text-gray-500 text-sm">
                No notifications found.
            </div>
        @endforelse
    </div>

    <div class="px-6 py-4 bg-white border border-gray-200 rounded-lg">
        {{ $notifications->links() }}
    </div>
</div>
@endsection
