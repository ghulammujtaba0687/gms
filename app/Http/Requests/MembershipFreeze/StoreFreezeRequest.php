<?php

namespace App\Http\Requests\MembershipFreeze;

use Illuminate\Foundation\Http\FormRequest;

class StoreFreezeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\MembershipFreeze::class);
    }

    public function rules(): array
    {
        return [
            'membership_id' => ['required', 'exists:memberships,id'],
            'freeze_start_date' => ['required', 'date', 'after_or_equal:today'],
            'freeze_end_date' => ['required', 'date', 'after_or_equal:freeze_start_date'],
            'reason' => ['required', 'string', 'max:255'],
        ];
    }
}
