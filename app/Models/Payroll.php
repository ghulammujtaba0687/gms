<?php

namespace App\Models;

use App\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payroll extends Model
{
    use BelongsToBranch, HasFactory, SoftDeletes;

    protected $fillable = [
        'branch_id',
        'staff_profile_id',
        'expense_id',
        'payroll_code',
        'payroll_month',
        'payroll_year',
        'salary_month_year',
        'base_salary_snapshot',
        'bonus_amount',
        'deduction_amount',
        'net_salary',
        'payment_method',
        'payment_date',
        'reference_number',
        'status',
        'notes',
        'processed_by',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'base_salary_snapshot' => 'decimal:2',
            'bonus_amount' => 'decimal:2',
            'deduction_amount' => 'decimal:2',
            'net_salary' => 'decimal:2',
            'payroll_month' => 'integer',
            'payroll_year' => 'integer',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function staffProfile(): BelongsTo
    {
        return $this->belongsTo(StaffProfile::class);
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
