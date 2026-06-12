<?php

namespace App\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlatformSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isPlatformAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'platform_name' => ['required', 'string', 'max:100'],
            'support_email' => ['nullable', 'email', 'max:255'],
            'support_phone' => ['nullable', 'string', 'max:50'],
            'currency_code' => ['required', 'string', 'size:3', 'alpha'],
            'payment_instructions' => ['nullable', 'string', 'max:2000'],
            'payment_channels' => ['nullable', 'array', 'max:10'],
            'payment_channels.*.key' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9_]+$/', 'distinct'],
            'payment_channels.*.label' => ['required', 'string', 'max:100'],
            'payment_channels.*.account_title' => ['nullable', 'string', 'max:150'],
            'payment_channels.*.account_number' => ['nullable', 'string', 'max:150'],
            'payment_channels.*.instructions' => ['nullable', 'string', 'max:500'],
            'payment_channels.*.is_active' => ['nullable', 'boolean'],
            'allow_registration' => ['nullable', 'boolean'],
        ];
    }
}
