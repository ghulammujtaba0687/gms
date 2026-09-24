<?php

namespace App\Traits;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToBranch
{
    public static function bootBelongsToBranch(): void
    {
        static::creating(function ($model) {
            if (! $model->branch_id && session()->has('active_branch_id')) {
                $model->branch_id = session('active_branch_id');
            }
        });

        static::addGlobalScope('branch_scope', function (Builder $builder) {
            if (auth()->check()) {
                $user = auth()->user();

                if ($user->hasRole('owner')) {
                    // If owner selected a specific branch context, scope to it. Otherwise (All Branches mode), don't scope.
                    if (session()->has('active_branch_id') && session('active_branch_id') !== null) {
                        $builder->where($builder->getModel()->getTable().'.branch_id', session('active_branch_id'));
                    }
                } else {
                    // Non-owners are strictly constrained to their active session branch or assigned branches
                    if (session()->has('active_branch_id') && session('active_branch_id') !== null) {
                        $builder->where($builder->getModel()->getTable().'.branch_id', session('active_branch_id'));
                    } else {
                        $assignedBranchIds = $user->branches->pluck('id')->toArray();
                        $builder->whereIn($builder->getModel()->getTable().'.branch_id', $assignedBranchIds);
                    }
                }
            }
        });
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
