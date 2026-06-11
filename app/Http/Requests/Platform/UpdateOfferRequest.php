<?php

namespace App\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isPlatformAdmin() === true;
    }

    public function rules(): array
    {
        $offerId = $this->route('offer')?->id;
        $discountRules = ['required', 'numeric', 'min:0'];

        if ($this->input('discount_type') === 'percent') {
            $discountRules[] = 'max:100';
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('platform_offers', 'code')->ignore($offerId)],
            'description' => ['nullable', 'string', 'max:1000'],
            'discount_type' => ['required', Rule::in(['percent', 'fixed'])],
            'discount_value' => $discountRules,
            'redemption_limit' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['nullable', 'boolean'],
            'plans' => ['nullable', 'array'],
            'plans.*' => [Rule::exists('subscription_plans', 'id')],
        ];
    }
}
