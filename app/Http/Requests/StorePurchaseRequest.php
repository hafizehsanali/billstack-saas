<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'supplier_id' => [
                'required',
                Rule::exists('suppliers', 'id')
                    ->where('tenant_id', auth()->user()->tenant_id),
            ],
            'purchase_date' => ['required', 'date'],
            'purchase_no' => [
                'required',
                'string',
                'max:255',
                Rule::unique('purchases', 'purchase_no')
                    ->where('tenant_id', auth()->user()->tenant_id)
                    ->ignore($this->route('purchase')),
            ],
            'subtotal' => ['required', 'numeric', 'min:0'],
            'extra_expense' => ['nullable', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'total' => ['nullable', 'numeric', 'min:0'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'payment_date' => ['nullable', 'date'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'payment_notes' => ['nullable', 'string'],
            'remaining_amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', Rule::in(['unpaid', 'partial', 'paid'])],
            'notes' => ['nullable', 'string'],
            'products' => ['required', 'array', 'min:1'],
            'products.*.product_id' => [
                'required',
                Rule::exists('products', 'id')
                    ->where('tenant_id', auth()->user()->tenant_id),
            ],
            'products.*.product_variant_id' => [
                'nullable',
                Rule::exists('product_variants', 'id')
                    ->where('tenant_id', auth()->user()->tenant_id),
            ],
            'products.*.unit_id' => [
                'nullable',
                Rule::exists('units', 'id')
                    ->where('tenant_id', auth()->user()->tenant_id),
            ],
            'products.*.unit_factor' => ['nullable', 'numeric', 'min:0.001'],
            'products.*.quantity' => ['required','numeric','min:0.001',],
            'products.*.purchase_price' => ['required','numeric','min:0'],
            'products.*.batch_number' => ['nullable', 'string', 'max:255'],
            'products.*.expiry_date' => ['nullable', 'date'],
            'products.*.manufacturing_date' => ['nullable', 'date'],
            'products.*.serial_numbers' => ['nullable', 'string'],
        ];
    }
}
