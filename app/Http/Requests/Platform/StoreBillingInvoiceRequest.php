<?php

namespace App\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreBillingInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isPlatformAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', Rule::exists('tenants', 'id')->whereNull('deleted_at')],
            'billing_period' => ['required', 'string', 'max:100'],
            'subtotal' => ['required', 'numeric', 'min:0.01', 'decimal:0,2'],
            'discount' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'tax' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'issued_on' => ['required', 'date'],
            'due_on' => ['nullable', 'date', 'after_or_equal:issued_on'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ((float) $this->input('discount', 0) > (float) $this->input('subtotal', 0)) {
                    $validator->errors()->add(
                        'discount',
                        'Discount cannot be greater than the invoice subtotal.'
                    );
                }
            },
        ];
    }
}
