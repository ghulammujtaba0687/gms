<?php

namespace App\Http\Requests\MembershipPlan;

use App\Models\MembershipPlan;
use Illuminate\Foundation\Http\FormRequest;

class StoreMembershipPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', MembershipPlan::class);
    }

    public function rules(): array
    {
        $user = $this->user();
        $scope = $this->input('scope', 'branch');
        $branchId = null;

        if ($user->hasRole('owner') && $scope === 'global') {
            $branchId = null;
        } else {
            $branchId = session('active_branch_id') ?: ($user->branches->first() ? $user->branches->first()->id : null);
        }

        return [
            'code' => [
                'required',
                'string',
                'max:50',
                function ($attribute, $value, $fail) use ($branchId) {
                    $exists = MembershipPlan::where('code', $value)
                        ->where('branch_id', $branchId)
                        ->whereNull('deleted_at')
                        ->exists();

                    if ($exists) {
                        $fail('The plan code has already been taken in this scope.');
                    }
                },
            ],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'duration_type' => ['required', 'in:days,months,years'],
            'duration_value' => ['required', 'integer', 'min:1', 'max:10000'],
            'signup_fee' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'is_active' => ['boolean'],
            'scope' => ['required', 'in:global,branch'],
        ];
    }
}
