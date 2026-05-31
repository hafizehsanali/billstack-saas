<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
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
            'invoice_no' => [
                'required',
                'string',
                'max:255',
                'unique:invoices,invoice_no',
            ],

            'sale_date' => [
                'required',
                'date',
            ],

            'customer_id' => [
                'required',
                'exists:customers,id'
            ],

            'products' => [
                'required',
                'array',
                'min:1'
            ],

            'products.*.product_id' => [
                'required',
                'exists:products,id'
            ],

            'products.*.quantity' => [
                'required',
                'integer',
                'min:1'
            ],

            'products.*.price' => [
                'required',
                'numeric',
                'min:0'
            ],

            'tax' => [
                'nullable',
                'numeric',
                'min:0'
            ],

            'discount' => [
                'nullable',
                'numeric',
                'min:0'
            ],

            'extra_expense' => [
                'nullable',
                'numeric',
                'min:0'
            ],

            'paid_amount' => [
                'nullable',
                'numeric',
                'min:0'
            ],

            'payment_method' => [
                'nullable',
                'string',
                'max:50'
            ],

            'payment_date' => [
                'nullable',
                'date'
            ],

            'reference_no' => [
                'nullable',
                'string',
                'max:100'
            ],

            'payment_notes' => [
                'nullable',
                'string'
            ],

            'notes' => [
                'nullable',
                'string'
            ],

        ];
    }
     public function messages(): array
    {
        return [

            'customer_id.required' =>
                'Customer is required.',

            'products.required' =>
                'Select at least one product.',

            'quantities.*.min' =>
                'Quantity must be at least 1.',

        ];
    }
}
