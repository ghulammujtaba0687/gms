<?php

namespace App\Models;

use App\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use BelongsToBranch, HasFactory, SoftDeletes;

    protected $fillable = [
        'branch_id',
        'member_id',
        'membership_id',
        'payment_code',
        'amount_due',
        'amount_paid',
        'discount_amount',
        'discount_reason',
        'remaining_balance',
        'payment_method',
        'reference_number',
        'proof_path',
        'payment_date',
        'status',
        'notes',
        'recorded_by',
        'verified_by',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'verified_at' => 'datetime',
            'amount_due' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'remaining_balance' => 'decimal:2',
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

    public function membership(): BelongsTo
    {
        return $this->belongsTo(Membership::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }
}
