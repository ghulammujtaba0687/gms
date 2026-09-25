<?php

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

class AssignTrainerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('members.manage') || $this->user()->can('staff.manage');
    }

    public function rules(): array
    {
        return [
            'member_id' => ['required', 'exists:members,id'],
            'staff_profile_id' => ['required', 'exists:staff_profiles,id'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
