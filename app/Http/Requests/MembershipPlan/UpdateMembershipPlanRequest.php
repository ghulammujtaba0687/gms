<?php

namespace App\Http\Requests\MembershipPlan;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMembershipPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        $plan = $this->route('membership_plan') ?: $this->route('membershipPlan');

        return $this->user()->can('update', $plan);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'duration_type' => ['required', 'in:days,months,years'],
            'duration_value' => ['required', 'integer', 'min:1', 'max:10000'],
            'signup_fee' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'is_active' => ['boolean'],
        ];
    }
}
