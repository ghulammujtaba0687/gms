<?php

namespace App\Http\Requests\Attendance;

use App\Models\Attendance;
use Illuminate\Foundation\Http\FormRequest;

class CheckInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('checkIn', Attendance::class);
    }

    public function rules(): array
    {
        return [
            'member_id' => ['required', 'exists:members,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
