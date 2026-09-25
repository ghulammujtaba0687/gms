<?php

namespace App\Http\Requests\Staff;

use Illuminate\Foundation\Http\FormRequest;

class StoreStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\StaffProfile::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['required', 'in:manager,receptionist,trainer'],
            'is_trainer' => ['boolean'],
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
