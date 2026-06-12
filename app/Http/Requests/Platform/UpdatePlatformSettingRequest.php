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
            'allow_registration' => ['nullable', 'boolean'],
        ];
    }
}
