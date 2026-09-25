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
            <div class="text-gray-500 text-sm font-medium">Today's Revenue</div>
            <div class="text-2xl font-bold text-green-600 mt-2">PKR {{ number_format($todayRevenue, 2) }}</div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
            <div class="text-gray-500 text-sm font-medium">Today's Expenses</div>
            <div class="text-2xl font-bold text-red-600 mt-2">PKR {{ number_format($todayExpense, 2) }}</div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
            <div class="text-gray-500 text-sm font-medium">Net Monthly Cashflow</div>
            <div class="text-2xl font-bold {{ $netMonthlyCashflow >= 0 ? 'text-indigo-600' : 'text-red-700' }} mt-2">
                PKR {{ number_format($netMonthlyCashflow, 2) }}
            </div>
        </div>
    </div>
</div>
@endsection
