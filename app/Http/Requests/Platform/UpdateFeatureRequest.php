<?php

namespace App\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFeatureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isPlatformAdmin() === true;
    }

    public function rules(): array
    {
        $featureId = $this->route('feature')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'key' => ['nullable', 'string', 'max:255', Rule::unique('plan_features', 'key')->ignore($featureId)],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_paid' => ['nullable', 'boolean'],
        ];
    }
}
