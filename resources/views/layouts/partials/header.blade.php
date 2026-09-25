<header class="bg-white border-b border-gray-200 px-6 py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div class="flex items-center gap-4">
        <h1 class="text-xl font-semibold text-gray-800">
            {{ $title ?? 'Dashboard' }}
        </h1>
    </div>

    <div class="flex items-center gap-4">
        <!-- Notification Bell -->
        @include('layouts.partials.notification-bell')

        <!-- Branch Switcher -->
        @include('layouts.partials.branch-switcher')

        <!-- User Info & Logout -->
        <div class="flex items-center gap-3 border-l pl-4 border-gray-200">
            <span class="text-sm font-medium text-gray-700">
                {{ auth()->user()->name }}
                <span class="text-xs text-gray-500 block">({{ auth()->user()->roles->first()->display_name ?? 'User' }})</span>
            </span>

            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="text-sm text-red-600 hover:text-red-800 font-medium">
                    Logout
                </button>
            </form>
        </div>
    </div>
</header>
