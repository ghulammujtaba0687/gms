<?php

namespace App\Http\Requests\Payment;

use App\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Payment::class);
    }

    public function rules(): array
    {
        return [
            'member_id' => ['required', 'exists:members,id'],
            'membership_id' => ['nullable', 'exists:memberships,id'],
            'amount_due' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'amount_paid' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'discount_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'discount_reason' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['required', 'in:cash,bank_transfer,easypaisa,jazzcash,other'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'payment_date' => ['required', 'date'],
            'proof' => ['nullable', 'file', 'mimes:jpeg,png,webp,pdf', 'max:3072'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
