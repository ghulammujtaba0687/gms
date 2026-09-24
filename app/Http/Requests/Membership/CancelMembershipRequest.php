<?php

namespace App\Http\Requests\Membership;

use Illuminate\Foundation\Http\FormRequest;

class CancelMembershipRequest extends FormRequest
{
    public function authorize(): bool
    {
        $membership = $this->route('membership');

        return $this->user()->can('cancel', $membership);
    }

    public function rules(): array
    {
        return [
            'cancellation_reason' => ['required', 'string', 'max:255'],
        ];
    }
}
