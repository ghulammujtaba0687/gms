<?php

namespace App\Http\Requests\Report;

use Illuminate\Foundation\Http\FormRequest;

class GenerateReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'report_type' => ['required', 'string', 'in:revenue,expiring,dues,attendance,members,trainers'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'days_threshold' => ['nullable', 'integer', 'in:7,15,30,60'],
            'export' => ['nullable', 'string', 'in:csv,print'],
        ];
    }
}
