@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h2 class="text-lg font-semibold text-gray-800">Application & Gym Settings</h2>
    </div>

    @if(session('success'))
        <div class="p-4 text-sm text-green-800 rounded-lg bg-green-50 border border-green-200" role="alert">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 text-sm text-red-800 rounded-lg bg-red-50 border border-red-200" role="alert">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <!-- General Branding -->
        <div class="p-6 bg-white shadow rounded-lg border border-gray-200 space-y-4">
            <h3 class="text-base font-semibold text-gray-900 border-b pb-2">Branding & Identity</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="gym_name" class="block text-sm font-medium text-gray-700">Gym / Organization Name</label>
                    <input id="gym_name" name="gym_name" type="text" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm px-3 py-2 text-sm" value="{{ old('gym_name', $settings['gym_name']) }}" required />
                </div>

                <div>
                    <label for="currency_symbol" class="block text-sm font-medium text-gray-700">Currency Symbol</label>
                    <input id="currency_symbol" name="currency_symbol" type="text" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm px-3 py-2 text-sm" value="{{ old('currency_symbol', $settings['currency_symbol']) }}" required />
                </div>

                <div class="col-span-2">
                    <label for="receipt_footer_terms" class="block text-sm font-medium text-gray-700">Receipt & Payslip Footer Terms</label>
                    <textarea id="receipt_footer_terms" name="receipt_footer_terms" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm px-3 py-2 text-sm">{{ old('receipt_footer_terms', $settings['receipt_footer_terms']) }}</textarea>
                </div>

                <div class="col-span-2">
                    <label for="logo" class="block text-sm font-medium text-gray-700">Gym Logo</label>
                    @if(!empty($settings['gym_logo']))
                        <div class="mb-2">
                            <img src="{{ Storage::url($settings['gym_logo']) }}" alt="Gym Logo" class="h-16 w-auto object-contain border p-1 rounded">
                        </div>
                    @endif
                    <input id="logo" name="logo" type="file" accept="image/*" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                </div>
            </div>
        </div>

        <!-- Freeze System Rules -->
        <div class="p-6 bg-white shadow rounded-lg border border-gray-200 space-y-4">
            <h3 class="text-base font-semibold text-gray-900 border-b pb-2">Membership Freeze Rules</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="freeze_max_days" class="block text-sm font-medium text-gray-700">Maximum Days per Freeze Request</label>
                    <input id="freeze_max_days" name="freeze_max_days" type="number" min="1" max="90" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm px-3 py-2 text-sm" value="{{ old('freeze_max_days', $settings['freeze_max_days']) }}" required />
                    <p class="text-xs text-gray-500 mt-1">Default is 30 days. Maximum allowed window for a single pause.</p>
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-4 py-2 rounded-md text-sm shadow-sm">
                Save Settings
            </button>
        </div>
    </form>
</div>
@endsection
