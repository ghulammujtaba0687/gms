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
            if (! array_key_exists('branch_id', $model->getAttributes()) && session()->has('active_branch_id')) {
                $model->branch_id = session('active_branch_id');
            }
        });

        static::addGlobalScope('branch_scope', function (Builder $builder) {
            if (auth()->check()) {
                $user = auth()->user();
                $table = $builder->getModel()->getTable();
                $supportsGlobal = in_array('branch_id', $builder->getModel()->getFillable()) && method_exists($builder->getModel(), 'isGlobal');

                if ($user->hasRole('owner')) {
                    // If owner selected a specific branch context
                    if (session()->has('active_branch_id') && session('active_branch_id') !== null) {
                        if ($supportsGlobal) {
                            $builder->where(function ($q) use ($table) {
                                $q->whereNull($table.'.branch_id')
                                    ->orWhere($table.'.branch_id', session('active_branch_id'));
                            });
                        } else {
                            $builder->where($table.'.branch_id', session('active_branch_id'));
                        }
                    }
                } else {
                    // Non-owners are strictly constrained to active branch / assigned branches + global records if supported
                    if (session()->has('active_branch_id') && session('active_branch_id') !== null) {
                        if ($supportsGlobal) {
                            $builder->where(function ($q) use ($table) {
                                $q->whereNull($table.'.branch_id')
                                    ->orWhere($table.'.branch_id', session('active_branch_id'));
                            });
                        } else {
                            $builder->where($table.'.branch_id', session('active_branch_id'));
                        }
                    } else {
                        $assignedBranchIds = $user->branches->pluck('id')->toArray();
                        if ($supportsGlobal) {
                            $builder->where(function ($q) use ($table, $assignedBranchIds) {
                                $q->whereNull($table.'.branch_id')
                                    ->orWhereIn($table.'.branch_id', $assignedBranchIds);
                            });
                        } else {
                            $builder->whereIn($table.'.branch_id', $assignedBranchIds);
                        }
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
