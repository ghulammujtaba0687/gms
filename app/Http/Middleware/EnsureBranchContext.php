<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureBranchContext
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check()) {
            $user = auth()->user();

            if (! session()->has('active_branch_id')) {
                if ($user->hasRole('owner')) {
                    // Owner defaults to "All Branches" mode (null)
                    session(['active_branch_id' => null]);
                } else {
                    $firstBranch = $user->branches()->first();
                    if ($firstBranch) {
                        session(['active_branch_id' => $firstBranch->id]);
                    }
                }
            }
        }

        return $next($request);
    }
}
