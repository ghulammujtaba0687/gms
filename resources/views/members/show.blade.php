@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-xl font-bold text-gray-900">{{ $member->full_name }}</h2>
            <span class="font-mono text-sm text-indigo-600 font-medium">ID: {{ $member->member_code }}</span>
        </div>

        <div class="flex items-center gap-3">
            @can('members.manage')
                <a href="{{ route('members.edit', $member->id) }}" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-md shadow-sm">
                    Edit Profile
                </a>

                @if(!$member->trashed())
                    <form action="{{ route('members.destroy', $member->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to archive (soft-delete) this member?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 text-sm font-medium px-4 py-2 rounded-md">
                            Archive Member
                        </button>
                    </form>
                @else
                    <form action="{{ route('members.restore', $member->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white text-sm font-medium px-4 py-2 rounded-md shadow-sm">
                            Restore Member
                        </button>
                    </form>
                @endif
            @endcan
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Profile Card -->
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 flex flex-col items-center text-center">
            @if($member->photo_path)
                <img src="{{ asset('storage/' . $member->photo_path) }}" class="w-32 h-32 rounded-full object-cover border-2 border-indigo-500 mb-4" alt="Photo">
            @else
                <div class="w-32 h-32 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center font-bold text-3xl mb-4">
                    {{ substr($member->first_name, 0, 1) }}{{ substr($member->last_name, 0, 1) }}
                </div>
            @endif

            <h3 class="text-lg font-bold text-gray-800">{{ $member->full_name }}</h3>
            <p class="text-sm text-gray-500">{{ $member->branch->name ?? 'N/A' }}</p>

            <div class="mt-4">
                @if($member->status === 'active')
                    <span class="bg-green-100 text-green-800 text-xs px-3 py-1 rounded-full font-semibold">Active Member</span>
                @else
                    <span class="bg-red-100 text-red-800 text-xs px-3 py-1 rounded-full font-semibold">Inactive Member</span>
                @endif
            </div>
        </div>

        <!-- Details Grid -->
        <div class="md:col-span-2 bg-white p-6 rounded-lg shadow-sm border border-gray-200 space-y-4">
            <h4 class="text-md font-semibold text-gray-800 border-b pb-2">Member Information</h4>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="text-gray-500 block">Phone</span>
                    <span class="font-medium text-gray-900">{{ $member->phone }}</span>
                </div>

                <div>
                    <span class="text-gray-500 block">WhatsApp</span>
                    <span class="font-medium text-gray-900">{{ $member->whatsapp ?? 'N/A' }}</span>
                </div>

                <div>
                    <span class="text-gray-500 block">CNIC / National ID</span>
                    <span class="font-medium text-gray-900">{{ $member->cnic ?? 'N/A' }}</span>
                </div>

                <div>
                    <span class="text-gray-500 block">Gender</span>
                    <span class="font-medium text-gray-900 capitalize">{{ $member->gender }}</span>
                </div>

                <div>
                    <span class="text-gray-500 block">Email Address</span>
                    <span class="font-medium text-gray-900">{{ $member->email ?? 'N/A' }}</span>
                </div>

                <div>
                    <span class="text-gray-500 block">Date of Birth</span>
                    <span class="font-medium text-gray-900">{{ $member->dob ? $member->dob->format('Y-m-d') : 'N/A' }}</span>
                </div>

                <div>
                    <span class="text-gray-500 block">Join Date</span>
                    <span class="font-medium text-gray-900">{{ $member->join_date ? $member->join_date->format('Y-m-d') : 'N/A' }}</span>
                </div>

                <div>
                    <span class="text-gray-500 block">Registered By</span>
                    <span class="font-medium text-gray-900">{{ $member->creator->name ?? 'System' }}</span>
                </div>
            </div>

            <div class="pt-2 border-t">
                <span class="text-gray-500 text-sm block">Home Address</span>
                <span class="font-medium text-gray-900 text-sm">{{ $member->address ?? 'N/A' }}</span>
            </div>

            <div class="pt-2 border-t grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="text-gray-500 block">Emergency Contact Name</span>
                    <span class="font-medium text-gray-900">{{ $member->emergency_contact_name ?? 'N/A' }}</span>
                </div>

                <div>
                    <span class="text-gray-500 block">Emergency Contact Phone</span>
                    <span class="font-medium text-gray-900">{{ $member->emergency_contact_phone ?? 'N/A' }}</span>
                </div>
            </div>

            @if($member->notes)
                <div class="pt-2 border-t">
                    <span class="text-gray-500 text-sm block">Notes</span>
                    <p class="text-sm text-gray-700">{{ $member->notes }}</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Financial Dues Summary Card -->
    @if($member->total_outstanding_dues > 0)
        <div class="bg-red-50 p-4 rounded-lg border border-red-200 flex justify-between items-center">
            <div>
                <span class="text-xs text-red-600 font-bold uppercase block">Outstanding Dues Balance</span>
                <span class="text-xl font-bold text-red-700">PKR {{ number_format($member->total_outstanding_dues, 2) }}</span>
            </div>
            @can('create', App\Models\Payment::class)
                <a href="{{ route('payments.create', ['member_id' => $member->id]) }}" class="bg-red-600 hover:bg-red-700 text-white text-xs font-bold px-4 py-2 rounded shadow-sm">
                    Clear Dues / Pay
                </a>
            @endcan
        </div>
    @endif

    <!-- Active & Past Memberships Section -->
    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 space-y-4">
        <div class="flex justify-between items-center border-b pb-3">
            <h3 class="text-md font-bold text-gray-800">Subscriptions & Memberships History</h3>
            @can('create', App\Models\Membership::class)
                <a href="{{ route('memberships.create', ['member_id' => $member->id]) }}" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-medium px-3 py-1.5 rounded shadow-sm">
                    + Assign Plan
                </a>
            @endcan
        </div>

        <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
            <thead class="bg-gray-50 text-gray-600 font-medium">
                <tr>
                    <th class="px-4 py-2">Plan</th>
                    <th class="px-4 py-2">Price</th>
                    <th class="px-4 py-2">Start Date</th>
                    <th class="px-4 py-2">End Date</th>
                    <th class="px-4 py-2">Status</th>
                    <th class="px-4 py-2">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($member->memberships()->latest()->get() as $ms)
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $ms->plan_name_snapshot }}</td>
                        <td class="px-4 py-3 font-semibold">PKR {{ number_format($ms->plan_price_snapshot, 2) }}</td>
                        <td class="px-4 py-3">{{ $ms->start_date->format('Y-m-d') }}</td>
                        <td class="px-4 py-3">{{ $ms->end_date->format('Y-m-d') }}</td>
                        <td class="px-4 py-3">
                            @if($ms->status === 'active')
                                <span class="bg-green-100 text-green-800 text-xs px-2 py-0.5 rounded-full font-medium">Active</span>
                            @elseif($ms->status === 'scheduled')
                                <span class="bg-blue-100 text-blue-800 text-xs px-2 py-0.5 rounded-full font-medium">Scheduled</span>
                            @elseif($ms->status === 'expired')
                                <span class="bg-yellow-100 text-yellow-800 text-xs px-2 py-0.5 rounded-full font-medium">Expired</span>
                            @else
                                <span class="bg-red-100 text-red-800 text-xs px-2 py-0.5 rounded-full font-medium">Cancelled</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('memberships.show', $ms->id) }}" class="text-indigo-600 hover:underline">Details</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-3 text-center text-gray-500">No memberships assigned yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
