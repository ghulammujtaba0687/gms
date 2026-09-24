<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckBranchAccess
{
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check()) {
            $user = auth()->user();

            if (! $user->hasRole('owner')) {
                $activeBranchId = session('active_branch_id');

                if ($activeBranchId && ! $user->hasBranchAccess($activeBranchId)) {
                    session()->forget('active_branch_id');
                    abort(403, 'Unauthorized branch access attempt detected.');
                }
            }
        }

        return $next($request);
    }
}
