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
            'purchase_id' => 'nullable|exists:purchases,id',
            'payment_date' => 'required|date',
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
