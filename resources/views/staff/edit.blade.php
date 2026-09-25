@extends('layouts.app')

@section('content')
<div class="max-w-2xl bg-white p-6 rounded-lg shadow-sm border border-gray-200">
    <h2 class="text-lg font-semibold text-gray-800 mb-6">Edit Staff Profile: {{ $staff->user->name }} ({{ $staff->staff_code }})</h2>

    @if ($errors->any())
        <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-600 rounded-md text-sm">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('staff.update', $staff->id) }}" method="POST" class="space-y-4">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Full Name *</label>
                <input type="text" name="name" id="name" required value="{{ old('name', $staff->user->name) }}" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm">
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Login Email *</label>
                <input type="email" name="email" id="email" required value="{{ old('email', $staff->user->email) }}" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700">New Password (Leave blank to keep current)</label>
                <input type="password" name="password" id="password" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm">
            </div>

            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700">Phone Number</label>
                <input type="text" name="phone" id="phone" value="{{ old('phone', $staff->user->phone) }}" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm">
            </div>

            <div>
                <label for="role" class="block text-sm font-medium text-gray-700">System Role *</label>
                <select name="role" id="role" required class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm bg-white">
                    <option value="receptionist" {{ old('role', $staff->user->roles->first()->name ?? '') === 'receptionist' ? 'selected' : '' }}>Receptionist</option>
                    <option value="manager" {{ old('role', $staff->user->roles->first()->name ?? '') === 'manager' ? 'selected' : '' }}>Branch Manager</option>
                    <option value="trainer" {{ old('role', $staff->user->roles->first()->name ?? '') === 'trainer' ? 'selected' : '' }}>Trainer</option>
                </select>
            </div>

            <div>
                <label for="designation" class="block text-sm font-medium text-gray-700">Designation / Title *</label>
                <input type="text" name="designation" id="designation" required value="{{ old('designation', $staff->designation) }}" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm">
            </div>

            <div>
                <label for="cnic" class="block text-sm font-medium text-gray-700">CNIC / National ID</label>
                <input type="text" name="cnic" id="cnic" value="{{ old('cnic', $staff->cnic) }}" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm">
            </div>

            <div>
                <label for="joining_date" class="block text-sm font-medium text-gray-700">Joining Date *</label>
                <input type="date" name="joining_date" id="joining_date" required value="{{ old('joining_date', $staff->joining_date->format('Y-m-d')) }}" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm">
            </div>

            <div>
                <label for="monthly_salary" class="block text-sm font-medium text-gray-700">Monthly Salary (PKR)</label>
                <input type="number" step="0.01" name="monthly_salary" id="monthly_salary" value="{{ old('monthly_salary', $staff->monthly_salary) }}" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm">
            </div>

            <div>
                <label for="status" class="block text-sm font-medium text-gray-700">Status *</label>
                <select name="status" id="status" required class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm bg-white">
                    <option value="active" {{ old('status', $staff->status) === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status', $staff->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    <option value="on_leave" {{ old('status', $staff->status) === 'on_leave' ? 'selected' : '' }}>On Leave</option>
                </select>
            </div>
        </div>

        @if($staff->is_trainer)
            <div class="pt-2 border-t">
                <label for="specialization" class="block text-sm font-medium text-gray-700">Trainer Specialization</label>
                <input type="text" name="specialization" id="specialization" value="{{ old('specialization', $staff->specialization) }}" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm">
            </div>
        @endif

        <div>
            <label for="notes" class="block text-sm font-medium text-gray-700">Notes / Remarks</label>
            <textarea name="notes" id="notes" rows="2" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm">{{ old('notes', $staff->notes) }}</textarea>
        </div>

        <div class="flex justify-end gap-3 pt-4 border-t">
            <a href="{{ route('staff.show', $staff->id) }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</a>
            <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md text-sm font-medium shadow-sm">Update Staff Profile</button>
        </div>
    </form>
</div>
@endsection
