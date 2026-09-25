<?php

namespace App\Http\Requests\Payroll;

use App\Models\Payroll;
use Illuminate\Foundation\Http\FormRequest;

class StorePayrollRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Payroll::class);
    }

    public function rules(): array
    {
        return [
            'staff_profile_id' => ['required', 'exists:staff_profiles,id'],
            'salary_month_year' => ['required', 'string', 'regex:/^\d{4}-\d{2}$/'], // YYYY-MM
            'base_salary_snapshot' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'bonus_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'deduction_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'payment_method' => ['required', 'in:cash,bank_transfer,easypaisa,jazzcash,other'],
            'payment_date' => ['required', 'date'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
