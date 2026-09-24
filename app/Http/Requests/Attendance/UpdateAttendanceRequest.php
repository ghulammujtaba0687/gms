<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $attendance = $this->route('attendance');

        return $this->user()->can('update', $attendance);
    }

    public function rules(): array
    {
        return [
            'check_in_time' => ['required', 'date'],
            'check_out_time' => ['nullable', 'date', 'after:check_in_time'],
            'status' => ['required', 'in:present,checked_out'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
