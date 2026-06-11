<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubscriptionCheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('owner') === true;
    }

    public function rules(): array
    {
        return [
            'billing_cycle' => ['required', Rule::in(['monthly', 'annual'])],
            'promo_code' => ['nullable', 'string', 'max:50', 'alpha_dash'],
        ];
    }
}
