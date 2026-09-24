@php
    $user = auth()->user();
    $activeBranchId = session('active_branch_id');
@endphp

<form action="{{ route('branch.switch') }}" method="POST" class="inline-block">
    @csrf
    <select name="branch_id" onchange="this.form.submit()" class="text-sm border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 bg-white px-3 py-1.5 border">
        @if ($user->hasRole('owner'))
            <option value="all" {{ is_null($activeBranchId) ? 'selected' : '' }}>
                🏢 All Branches (Global)
            </option>
            @foreach (\App\Models\Branch::all() as $branch)
                <option value="{{ $branch->id }}" {{ $activeBranchId == $branch->id ? 'selected' : '' }}>
                    📍 {{ $branch->name }}
                </option>
            @endforeach
        @else
            @foreach ($user->branches as $branch)
                <option value="{{ $branch->id }}" {{ $activeBranchId == $branch->id ? 'selected' : '' }}>
                    📍 {{ $branch->name }}
                </option>
            @endforeach
        @endif
    </select>
</form>
