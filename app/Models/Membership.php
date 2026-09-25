<?php

namespace App\Models;

use App\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Membership extends Model
{
    use BelongsToBranch, HasFactory, SoftDeletes;

    protected $fillable = [
        'branch_id',
        'member_id',
        'membership_plan_id',
        'plan_name_snapshot',
        'plan_code_snapshot',
        'plan_price_snapshot',
        'plan_signup_fee_snapshot',
        'start_date',
        'end_date',
        'status',
        'notes',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
        'assigned_by',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'cancelled_at' => 'datetime',
            'plan_price_snapshot' => 'decimal:2',
            'plan_signup_fee_snapshot' => 'decimal:2',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(MembershipPlan::class, 'membership_plan_id');
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function attendances(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function freezes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(MembershipFreeze::class);
    }

    public function isCurrentlyFrozen(): bool
    {
        $today = \Carbon\Carbon::today()->format('Y-m-d');

        return $this->freezes()
            ->whereIn('status', ['approved', 'active'])
            ->where('freeze_start_date', '<=', $today)
            ->where('freeze_end_date', '>=', $today)
            ->whereNull('deleted_at')
            ->exists();
    }
}
