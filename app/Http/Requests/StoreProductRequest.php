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
        $product = $this->route('product');
        $productId = $product?->id ?? $product;
        $tenantId = auth()->user()->tenant_id;

        return [

            'category_id' => [
                'required',
                Rule::exists('categories', 'id')
                    ->where('tenant_id', auth()->user()->tenant_id),
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'brand_id' => [
                'nullable',
                Rule::exists('brands', 'id')->where('tenant_id', $tenantId),
            ],

            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'is_online_enabled' => ['nullable', 'boolean'],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],

            'sku' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products')
                    ->where('tenant_id', $tenantId)
                    ->ignore($productId),
            ],

            'barcode' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('products')
                    ->where('tenant_id', $tenantId)
                    ->ignore($productId),
            ],

            'purchase_price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'selling_price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'stock_quantity' => [
                'required',
                'integer',
                'min:0',
            ],

            'low_stock_alert' => [
                'required',
                'integer',
                'min:0',
            ],

            'variants' => ['nullable', 'array', 'min:1'],
            'variants.*.id' => [
                'nullable',
                Rule::exists('product_variants', 'id')->where('tenant_id', $tenantId),
            ],
            'variants.*.name' => ['nullable', 'string', 'max:255'],
            'variants.*.sku' => [
                'required_with:variants',
                'string',
                'max:255',
                'distinct',
            ],
            'variants.*.barcode' => [
                'nullable',
                'string',
                'max:255',
                'distinct',
            ],
            'variants.*.unit_id' => [
                'required_with:variants',
                Rule::exists('units', 'id')->where('tenant_id', $tenantId),
            ],
            'variants.*.purchase_unit_id' => [
                'nullable',
                Rule::exists('units', 'id')->where('tenant_id', $tenantId),
            ],
            'variants.*.purchase_unit_factor' => ['nullable', 'numeric', 'min:0.001'],
            'variants.*.purchase_price' => ['required_with:variants', 'numeric', 'min:0'],
            'variants.*.selling_price' => ['required_with:variants', 'numeric', 'min:0'],
            'variants.*.compare_at_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.stock_quantity' => ['required_with:variants', 'integer', 'min:0'],
            'variants.*.low_stock_alert' => ['required_with:variants', 'integer', 'min:0'],
            'variants.*.track_stock' => ['nullable', 'boolean'],
            'variants.*.is_active' => ['nullable', 'boolean'],
            'variants.*.attribute_value_ids' => ['nullable', 'array'],
            'variants.*.attribute_value_ids.*' => [
                Rule::exists('product_attribute_values', 'id')
                    ->whereIn(
                        'product_attribute_id',
                        \App\Models\ProductAttribute::query()->pluck('id')
                    ),
            ],

        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active', true),
            'is_online_enabled' => $this->boolean('is_online_enabled'),
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            foreach ($this->input('variants', []) as $index => $variant) {
                $id = $variant['id'] ?? null;

                foreach (['sku', 'barcode'] as $field) {
                    $value = $variant[$field] ?? null;

                    if (! $value) {
                        continue;
                    }

                    $exists = \App\Models\ProductVariant::query()
                        ->where($field, $value)
                        ->when($id, fn ($query) => $query->whereKeyNot($id))
                        ->exists();

                    if ($exists) {
                        $validator->errors()->add(
                            "variants.$index.$field",
                            ucfirst($field).' already exists for your store.'
                        );
                    }
                }
            }
        });
    }

    public function messages(): array
    {
        return [

            'sku.unique' => 'This SKU already exists for your store.',

            'barcode.unique' => 'This barcode already exists for your store.',

            'purchase_price.min' => 'Purchase price cannot be negative.',

            'selling_price.min' => 'Selling price cannot be negative.',

            'stock_quantity.min' => 'Stock quantity cannot be negative.',

            'images.*.max' => 'Each product image must not exceed 5 MB.',

        ];
    }
}
