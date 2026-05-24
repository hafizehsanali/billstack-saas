<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupplierPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'reference' => ['nullable', 'string', 'max:100'],
            'note' => ['nullable', 'string'],
        ];
    }
}