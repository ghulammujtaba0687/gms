@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h2 class="text-xl font-bold text-gray-800">Reports & Analytics Portal</h2>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        @unless(auth()->user()->hasRole('receptionist'))
            <!-- Financial Revenue Report Card -->
            <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 hover:border-indigo-500 transition">
                <div class="text-indigo-600 font-bold text-lg mb-2">💰 Financial Revenue & Cashflow</div>
                <p class="text-gray-600 text-sm mb-4">View total gym revenue, approved expenses, and net cashflow trends.</p>
                <a href="{{ route('reports.revenue') }}" class="inline-block bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold px-4 py-2 rounded shadow-sm">
                    View Financial Report →
                </a>
            </div>
        @endunless

        <!-- Expiring Memberships Card -->
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 hover:border-indigo-500 transition">
            <div class="text-amber-600 font-bold text-lg mb-2">⏰ Expiring Subscriptions</div>
            <p class="text-gray-600 text-sm mb-4">Audit memberships expiring in the next 7, 15, or 30 days for proactive renewals.</p>
            <a href="{{ route('reports.expiring') }}" class="inline-block bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold px-4 py-2 rounded shadow-sm">
                View Expiry Audit →
            </a>
        </div>

        <!-- Dues & Balances Card -->
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 hover:border-indigo-500 transition">
            <div class="text-red-600 font-bold text-lg mb-2">⚠️ Outstanding Dues</div>
            <p class="text-gray-600 text-sm mb-4">Audit unpaid member balances and partial payments across active branches.</p>
            <a href="{{ route('reports.dues') }}" class="inline-block bg-red-600 hover:bg-red-700 text-white text-xs font-bold px-4 py-2 rounded shadow-sm">
                View Outstanding Dues →
            </a>
        </div>

        <!-- Attendance Summary Card -->
        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 hover:border-indigo-500 transition">
            <div class="text-green-600 font-bold text-lg mb-2">📊 Attendance Summary</div>
            <p class="text-gray-600 text-sm mb-4">Track daily and monthly member check-in trends and attendance counts.</p>
            <a href="{{ route('reports.attendance') }}" class="inline-block bg-green-600 hover:bg-green-700 text-white text-xs font-bold px-4 py-2 rounded shadow-sm">
                View Attendance Report →
            </a>
        </div>
    </div>
</div>
@endsection
