<?php

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        $staff = $this->route('staff') ?: $this->route('staff_profile');

        return $this->user()->can('update', $staff);
    }

    public function rules(): array
    {
        $staff = $this->route('staff') ?: $this->route('staff_profile');
        $userId = $staff ? $staff->user_id : null;

        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:150', 'unique:users,email,'.$userId],
            'password' => ['nullable', 'string', 'min:6'],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['required', 'in:manager,receptionist,trainer'],
            'designation' => ['required', 'string', 'max:100'],
            'cnic' => ['nullable', 'string', 'max:20'],
            'specialization' => ['nullable', 'string', 'max:150'],
            'monthly_salary' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'joining_date' => ['required', 'date'],
            'status' => ['required', 'in:active,inactive,on_leave'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
