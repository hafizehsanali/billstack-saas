<?php

namespace App\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;

class StoreFeatureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isPlatformAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'key' => ['nullable', 'string', 'max:255', 'unique:plan_features,key'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_paid' => ['nullable', 'boolean'],
        ];
    }
}
