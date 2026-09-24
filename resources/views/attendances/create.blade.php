@extends('layouts.app')

@section('content')
<div class="max-w-2xl bg-white p-6 rounded-lg shadow-sm border border-gray-200 space-y-6">
    <h2 class="text-lg font-semibold text-gray-800">Member Check-In Terminal</h2>

    @if ($errors->any())
        <div class="p-4 bg-red-50 border border-red-200 text-red-600 rounded-md text-sm">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Member Search Input -->
    <form method="GET" action="{{ route('attendances.create') }}" class="flex gap-2">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Enter Member Code (e.g. DHA-M-00001) or Phone..." required class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm font-mono">
        <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-md shadow-sm">Find Member</button>
    </form>

    @if($selectedMember)
        <div class="p-5 bg-gray-50 border rounded-lg space-y-4">
            <div class="flex items-center gap-4">
                @if($selectedMember->photo_path)
                    <img src="{{ asset('storage/' . $selectedMember->photo_path) }}" class="w-16 h-16 rounded-full object-cover border-2 border-indigo-500" alt="Photo">
                @else
                    <div class="w-16 h-16 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center font-bold text-xl">
                        {{ substr($selectedMember->first_name, 0, 1) }}{{ substr($selectedMember->last_name, 0, 1) }}
                    </div>
                @endif

                <div>
                    <h3 class="text-lg font-bold text-gray-900">{{ $selectedMember->full_name }}</h3>
                    <span class="text-sm font-mono text-indigo-600 font-medium">Code: {{ $selectedMember->member_code }}</span>
                    <span class="text-xs text-gray-500 block">Phone: {{ $selectedMember->phone }}</span>
                </div>
            </div>

            <form action="{{ route('attendances.store') }}" method="POST" class="pt-4 border-t space-y-4">
                @csrf
                <input type="hidden" name="member_id" value="{{ $selectedMember->id }}">

                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700">Check-In Notes / Remarks</label>
                    <input type="text" name="notes" id="notes" placeholder="Optional notes (e.g. Locker key assigned)" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm">
                </div>

                <div class="flex justify-end gap-3">
                    <a href="{{ route('attendances.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</a>
                    <button type="submit" class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white font-bold rounded-md text-sm shadow-sm">
                        Confirm Check-In
                    </button>
                </div>
            </form>
        </div>
    @endif
</div>
@endsection
