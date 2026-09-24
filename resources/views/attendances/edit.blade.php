@extends('layouts.app')

@section('content')
<div class="max-w-xl bg-white p-6 rounded-lg shadow-sm border border-gray-200 space-y-6">
    <h2 class="text-lg font-semibold text-gray-800">Edit Attendance Record</h2>

    @if ($errors->any())
        <div class="p-4 bg-red-50 border border-red-200 text-red-600 rounded-md text-sm">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('attendances.update', $attendance->id) }}" method="POST" class="space-y-4">
        @csrf
        @method('PUT')

        <div class="p-3 bg-gray-50 border rounded-md">
            <span class="text-xs text-gray-500 block uppercase font-bold">Member</span>
            <span class="text-base font-bold text-gray-900">{{ $attendance->member->full_name ?? '' }}</span>
            <span class="text-xs font-mono text-indigo-600 block">{{ $attendance->member->member_code ?? '' }}</span>
        </div>

        <div>
            <label for="check_in_time" class="block text-sm font-medium text-gray-700">Check-In Time *</label>
            <input type="datetime-local" name="check_in_time" id="check_in_time" required value="{{ old('check_in_time', $attendance->check_in_time->format('Y-m-d\TH:i')) }}" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm">
        </div>

        <div>
            <label for="check_out_time" class="block text-sm font-medium text-gray-700">Check-Out Time</label>
            <input type="datetime-local" name="check_out_time" id="check_out_time" value="{{ old('check_out_time', $attendance->check_out_time ? $attendance->check_out_time->format('Y-m-d\TH:i') : '') }}" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm">
        </div>

        <div>
            <label for="status" class="block text-sm font-medium text-gray-700">Status *</label>
            <select name="status" id="status" required class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm bg-white">
                <option value="present" {{ old('status', $attendance->status) === 'present' ? 'selected' : '' }}>Present (Open Session)</option>
                <option value="checked_out" {{ old('status', $attendance->status) === 'checked_out' ? 'selected' : '' }}>Checked Out</option>
            </select>
        </div>

        <div>
            <label for="notes" class="block text-sm font-medium text-gray-700">Notes</label>
            <textarea name="notes" id="notes" rows="2" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm">{{ old('notes', $attendance->notes) }}</textarea>
        </div>

        <div class="flex justify-end gap-3 pt-4 border-t">
            <a href="{{ route('attendances.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</a>
            <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md text-sm font-medium shadow-sm">Update Attendance</button>
        </div>
    </form>
</div>
@endsection
