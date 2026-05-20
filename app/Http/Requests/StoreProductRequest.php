<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
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
        return [

                    'category_id' => [
                        'required',
                        'exists:categories,id'
                    ],

                    'name' => [
                        'required',
                        'string',
                        'max:255'
                    ],

                    'sku' => [
                        'required',
                        'string',
                        'max:255',
                         Rule::unique('products')
                              ->where('tenant_id', auth()->user()->tenant_id ),
                    ],

                    'purchase_price' => [
                        'required',
                        'numeric',
                        'min:0'
                    ],

                    'selling_price' => [
                        'required',
                        'numeric',
                        'min:0'
                    ],

                    'stock_quantity' => [
                        'required',
                        'integer',
                        'min:0'
                    ],

                    'low_stock_alert' => [
                        'required',
                        'integer',
                        'min:0'
                    ],

                ];
    }

    public function messages(): array
    {
        return [

            'sku.unique' =>
                'This SKU already exists for your store.',

            'purchase_price.min' =>
                'Purchase price cannot be negative.',

            'selling_price.min' =>
                'Selling price cannot be negative.',

            'stock_quantity.min' =>
                'Stock quantity cannot be negative.',

        ];
    }
}
