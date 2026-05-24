<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'supplier_id' => ['required', 'exists:suppliers,id'],
            'purchase_date' => ['required', 'date'],

            'products' => ['required', 'array', 'min:1'],

            'products.*.product_id' => [
                'required',
                'exists:products,id'
            ],

            'products.*.quantity' => [
                'required',
                'numeric',
                'min:1'
            ],

            'products.*.purchase_price' => [
                'required',
                'numeric',
                'min:0'
            ],

            'discount' => ['nullable', 'numeric', 'min:0'],
            'extra_expense' => ['nullable', 'numeric', 'min:0'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string'],
        ];
    }
}
