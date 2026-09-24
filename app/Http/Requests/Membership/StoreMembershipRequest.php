<?php

namespace App\Http\Requests\Membership;

use App\Models\Membership;
use Illuminate\Foundation\Http\FormRequest;

class StoreMembershipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Membership::class);
    }

    public function rules(): array
    {
        return [
            'member_id' => ['required', 'exists:members,id'],
            'membership_plan_id' => ['required', 'exists:membership_plans,id'],
            'start_date' => ['nullable', 'date'],
            'conflict_action' => ['nullable', 'in:stack,replace'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
