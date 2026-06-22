<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $category = $this->route('category');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories')
                    ->where('tenant_id', auth()->user()->tenant_id)
                    ->ignore($category),
            ],
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')
                    ->where('tenant_id', auth()->user()->tenant_id),
                Rule::notIn(array_filter([$category?->id])),
            ],
        ];
    }
    public function messages(): array
    {
        return [

            'name.unique' =>
                'Category already exists.',

            'name.required' =>
                'Category name is required.',

        ];
    }
}
