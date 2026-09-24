@extends('layouts.app')

@section('content')
<div class="max-w-3xl bg-white p-6 rounded-lg shadow-sm border border-gray-200">
    <h2 class="text-lg font-semibold text-gray-800 mb-6">Edit Member: {{ $member->full_name }} ({{ $member->member_code }})</h2>

    @if ($errors->any())
        <div class="mb-6 p-4 bg-red-50 border border-red-200 text-red-600 rounded-md text-sm">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('members.update', $member->id) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="first_name" class="block text-sm font-medium text-gray-700">First Name *</label>
                <input type="text" name="first_name" id="first_name" required value="{{ old('first_name', $member->first_name) }}" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm">
            </div>

            <div>
                <label for="last_name" class="block text-sm font-medium text-gray-700">Last Name *</label>
                <input type="text" name="last_name" id="last_name" required value="{{ old('last_name', $member->last_name) }}" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm">
            </div>

            <div>
                <label for="cnic" class="block text-sm font-medium text-gray-700">CNIC / ID Card</label>
                <input type="text" name="cnic" id="cnic" value="{{ old('cnic', $member->cnic) }}" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm">
            </div>

            <div>
                <label for="gender" class="block text-sm font-medium text-gray-700">Gender *</label>
                <select name="gender" id="gender" required class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm bg-white">
                    <option value="male" {{ old('gender', $member->gender) === 'male' ? 'selected' : '' }}>Male</option>
                    <option value="female" {{ old('gender', $member->gender) === 'female' ? 'selected' : '' }}>Female</option>
                    <option value="other" {{ old('gender', $member->gender) === 'other' ? 'selected' : '' }}>Other</option>
                </select>
            </div>

            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700">Phone Number *</label>
                <input type="text" name="phone" id="phone" required value="{{ old('phone', $member->phone) }}" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm">
            </div>

            <div>
                <label for="whatsapp" class="block text-sm font-medium text-gray-700">WhatsApp Number</label>
                <input type="text" name="whatsapp" id="whatsapp" value="{{ old('whatsapp', $member->whatsapp) }}" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm">
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                <input type="email" name="email" id="email" value="{{ old('email', $member->email) }}" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm">
            </div>

            <div>
                <label for="dob" class="block text-sm font-medium text-gray-700">Date of Birth</label>
                <input type="date" name="dob" id="dob" value="{{ old('dob', $member->dob ? $member->dob->format('Y-m-d') : '') }}" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm">
            </div>

            <div>
                <label for="join_date" class="block text-sm font-medium text-gray-700">Join Date *</label>
                <input type="date" name="join_date" id="join_date" required value="{{ old('join_date', $member->join_date ? $member->join_date->format('Y-m-d') : '') }}" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm">
            </div>

            <div>
                <label for="status" class="block text-sm font-medium text-gray-700">Status *</label>
                <select name="status" id="status" required class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm bg-white">
                    <option value="active" {{ old('status', $member->status) === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ old('status', $member->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
        </div>

        <div>
            <label for="address" class="block text-sm font-medium text-gray-700">Home Address</label>
            <textarea name="address" id="address" rows="2" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm">{{ old('address', $member->address) }}</textarea>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2 border-t">
            <div>
                <label for="emergency_contact_name" class="block text-sm font-medium text-gray-700">Emergency Contact Name</label>
                <input type="text" name="emergency_contact_name" id="emergency_contact_name" value="{{ old('emergency_contact_name', $member->emergency_contact_name) }}" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm">
            </div>

            <div>
                <label for="emergency_contact_phone" class="block text-sm font-medium text-gray-700">Emergency Contact Phone</label>
                <input type="text" name="emergency_contact_phone" id="emergency_contact_phone" value="{{ old('emergency_contact_phone', $member->emergency_contact_phone) }}" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm">
            </div>
        </div>

        <div class="pt-2 border-t flex items-center gap-4">
            @if($member->photo_path)
                <img src="{{ asset('storage/' . $member->photo_path) }}" class="w-16 h-16 rounded-md object-cover border" alt="Current Photo">
            @endif
            <div>
                <label for="photo" class="block text-sm font-medium text-gray-700 mb-1">Update Member Photo</label>
                <input type="file" name="photo" id="photo" accept="image/jpeg,image/png,image/webp" class="text-sm text-gray-600">
            </div>
        </div>

        <div>
            <label for="notes" class="block text-sm font-medium text-gray-700">Additional Notes</label>
            <textarea name="notes" id="notes" rows="2" class="mt-1 w-full border border-gray-300 rounded-md px-3 py-2 text-sm shadow-sm">{{ old('notes', $member->notes) }}</textarea>
        </div>

        <div class="flex justify-end gap-3 pt-4 border-t">
            <a href="{{ route('members.show', $member->id) }}" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</a>
            <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-md text-sm font-medium shadow-sm">Update Member Details</button>
        </div>
    </form>
</div>
@endsection
