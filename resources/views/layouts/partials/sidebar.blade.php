<aside class="w-full md:w-64 bg-slate-900 text-white flex-shrink-0">
    <div class="p-4 text-xl font-bold border-b border-slate-800 flex items-center justify-between">
        <span>GMS Gym System</span>
    </div>
    <nav class="p-4 space-y-1">
        <a href="{{ route('dashboard') }}" class="block px-3 py-2 rounded-md hover:bg-slate-800 text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-slate-800 text-white' : 'text-slate-300' }}">
            Dashboard
        </a>

        @can('branches.view')
            <a href="{{ route('branches.index') }}" class="block px-3 py-2 rounded-md hover:bg-slate-800 text-sm font-medium {{ request()->routeIs('branches.*') ? 'bg-slate-800 text-white' : 'text-slate-300' }}">
                Branches
            </a>
        @endcan
    </nav>
</aside>
