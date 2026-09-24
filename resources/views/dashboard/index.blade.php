@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800 mb-2">Welcome to Gym Management System</h2>
        <p class="text-gray-600 text-sm">
            Current Active Context:
            <span class="font-bold text-indigo-600">
                {{ $activeBranch ? $activeBranch->name : 'All Branches (Global Owner Mode)' }}
            </span>
        </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
            <div class="text-gray-500 text-sm font-medium">System Status</div>
            <div class="text-2xl font-bold text-gray-900 mt-2">Active</div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
            <div class="text-gray-500 text-sm font-medium">Current Role</div>
            <div class="text-2xl font-bold text-indigo-600 mt-2">
                {{ auth()->user()->roles->first()->display_name ?? 'User' }}
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
            <div class="text-gray-500 text-sm font-medium">Assigned Branches</div>
            <div class="text-2xl font-bold text-gray-900 mt-2">
                {{ auth()->user()->hasRole('owner') ? 'All (Owner)' : auth()->user()->branches->count() }}
            </div>
        </div>
    </div>
</div>
@endsection
