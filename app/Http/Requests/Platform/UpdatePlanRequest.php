<?php

namespace App\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isPlatformAdmin() === true;
    }

    public function rules(): array
    {
        $planId = $this->route('plan')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('subscription_plans', 'slug')->ignore($planId)],
            'description' => ['nullable', 'string', 'max:1000'],
            'monthly_price' => ['required', 'numeric', 'min:0'],
            'annual_price' => ['required', 'numeric', 'min:0'],
            'user_limit' => ['nullable', 'integer', 'min:1'],
            'trial_days' => ['required', 'integer', 'min:0', 'max:365'],
            'is_public' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'features' => ['nullable', 'array'],
            'features.*' => [Rule::exists('plan_features', 'id')],
        ];
    }
}
