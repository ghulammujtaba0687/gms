<?php

namespace App\Http\Requests\Setting;

use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Setting::class);
    }

    public function rules(): array
    {
        return [
            'gym_name' => ['nullable', 'string', 'max:150'],
            'gym_phone' => ['nullable', 'string', 'max:50'],
            'gym_email' => ['nullable', 'email', 'max:150'],
            'gym_address' => ['nullable', 'string'],
            'receipt_footer' => ['nullable', 'string', 'max:500'],
            'currency_symbol' => ['nullable', 'string', 'max:10'],
            'freeze_max_days' => ['nullable', 'integer', 'min:1', 'max:180'],
            'expiry_reminder_days' => ['nullable', 'integer', 'min:1', 'max:90'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:2048'],
        ];
    }
}
