<?php

namespace App\Http\Controllers;

use App\Models\Branch;

class DashboardController extends Controller
{
    public function index()
    {
        $activeBranchId = session('active_branch_id');
        $activeBranch = $activeBranchId ? Branch::find($activeBranchId) : null;

        return view('dashboard.index', compact('activeBranch'));
    }
}
