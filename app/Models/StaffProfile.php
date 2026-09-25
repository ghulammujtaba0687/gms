<?php

namespace App\Models;

use App\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class StaffProfile extends Model
{
    use BelongsToBranch, HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'branch_id',
        'staff_code',
        'is_trainer',
        'designation',
        'cnic',
        'specialization',
        'monthly_salary',
        'joining_date',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'joining_date' => 'date',
            'is_trainer' => 'boolean',
            'monthly_salary' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function ptAssignments(): HasMany
    {
        return $this->hasMany(TrainerMemberAssignment::class, 'staff_profile_id');
    }

    public function payrolls(): HasMany
    {
        return $this->hasMany(Payroll::class);
    }
}
