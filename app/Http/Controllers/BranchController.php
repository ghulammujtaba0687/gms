<?php

namespace App\Http\Controllers;

use App\Http\Requests\Branch\StoreBranchRequest;
use App\Models\Branch;
use App\Models\GymProfile;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BranchController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Branch::class);

        $user = auth()->user();

        if ($user->hasRole('owner')) {
            $branches = Branch::with('gymProfile')->get();
        } else {
            $branches = $user->branches()->with('gymProfile')->get();
        }

        return view('branches.index', compact('branches'));
    }

    public function create(): View
    {
        $this->authorize('create', Branch::class);

        return view('branches.create');
    }

    public function store(StoreBranchRequest $request): RedirectResponse
    {
        $this->authorize('create', Branch::class);

        $gymProfile = GymProfile::first();

        $branch = Branch::create([
            'gym_profile_id' => $gymProfile->id,
            'code' => $request->code,
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'address' => $request->address,
            'is_active' => $request->boolean('is_active', true),
            'is_main' => $request->boolean('is_main', false),
        ]);

        AuditLogService::log('Branch Management', 'Created Branch: '.$branch->name, null, $branch->toArray());

        return redirect()->route('branches.index')->with('success', 'Branch created successfully.');
    }

    public function edit(Branch $branch): View
    {
        $this->authorize('update', $branch);

        return view('branches.edit', compact('branch'));
    }

    public function update(StoreBranchRequest $request, Branch $branch): RedirectResponse
    {
        $this->authorize('update', $branch);

        $oldData = $branch->toArray();

        $branch->update([
            'code' => $request->code,
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'address' => $request->address,
            'is_active' => $request->boolean('is_active', true),
            'is_main' => $request->boolean('is_main', false),
        ]);

        AuditLogService::log('Branch Management', 'Updated Branch: '.$branch->name, $oldData, $branch->toArray());

        return redirect()->route('branches.index')->with('success', 'Branch updated successfully.');
    }
}
