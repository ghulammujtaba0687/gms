<?php

namespace App\Http\Requests\MembershipFreeze;

use Illuminate\Foundation\Http\FormRequest;

class CancelFreezeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $freeze = $this->route('membership_freeze') ?: $this->route('freeze');

        return $this->user()->can('cancel', $freeze);
    }

    public function rules(): array
    {
        return [
            'cancellation_reason' => ['required', 'string', 'max:255'],
        ];
    }
}
