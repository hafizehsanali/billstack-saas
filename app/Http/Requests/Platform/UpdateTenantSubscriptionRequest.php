<?php

namespace App\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTenantSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isPlatformAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'subscription_plan_id' => [
                'required',
                Rule::exists('subscription_plans', 'id')->where('is_active', true),
            ],
            'business_preset_id' => [
                'nullable',
                Rule::exists('business_presets', 'id')->where('is_active', true),
            ],
            'enabled_module_ids' => ['array'],
            'enabled_module_ids.*' => [
                'integer',
                Rule::exists('business_modules', 'id')->where('is_active', true),
            ],
            'status' => ['required', Rule::in(['active', 'paused', 'cancelled'])],
            'trial_ends_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
        ];
    }
}
