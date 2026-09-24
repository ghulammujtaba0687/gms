<?php

namespace App\Http\Requests\Payment;

use Illuminate\Foundation\Http\FormRequest;

class RefundPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $payment = $this->route('payment');

        return $this->user()->can('refund', $payment);
    }

    public function rules(): array
    {
        $payment = $this->route('payment');
        $maxRefundable = $payment ? $payment->amount_paid : 9999999999.99;

        return [
            'refund_amount' => ['required', 'numeric', 'min:0.01', 'max:'.$maxRefundable],
            'refund_reason' => ['required', 'string', 'max:255'],
            'refund_method' => ['required', 'in:cash,bank_transfer,easypaisa,jazzcash,other'],
        ];
    }
}
