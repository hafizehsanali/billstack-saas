<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupplierPaymentRequest extends FormRequest
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
            'purchase_id' => [
                'nullable',
                Rule::exists('purchases', 'id')
                    ->where('tenant_id', auth()->user()->tenant_id),
            ],
            'payment_date' => 'required|date',
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'source' => ['nullable', 'string', 'max:50'],
        ];
    }
}
