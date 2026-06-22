<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductVariant;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class ProductImportService
{
    public const FIELDS = [
        'name' => 'Product Name',
        'product_group' => 'Product Group SKU',
        'variant_name' => 'Variant Name',
        'attribute_1_name' => 'Attribute 1',
        'attribute_1_value' => 'Option 1',
        'attribute_2_name' => 'Attribute 2',
        'attribute_2_value' => 'Option 2',
        'attribute_3_name' => 'Attribute 3',
        'attribute_3_value' => 'Option 3',
        'parent_category' => 'Parent Category',
        'category' => 'Category',
        'subcategory' => 'Subcategory',
        'brand' => 'Brand',
        'sku' => 'SKU',
        'barcode' => 'Barcode',
        'description' => 'Description',
        'customer_unit' => 'Customer Unit',
        'supplier_unit' => 'Supplier Unit',
        'units_per_supplier' => 'Customer Units in One Supplier Unit',
        'purchase_price' => 'Purchase Price',
        'selling_price' => 'Selling Price',
        'regular_price' => 'Regular Price',
        'opening_stock' => 'Opening Supplier Quantity',
        'additional_customer_stock' => 'Additional Customer-Unit Stock',
        'low_stock_alert' => 'Low Stock Alert',
        'status' => 'Status',
    ];

    public const REQUIRED_FIELDS = [
        'name',
        'category',
        'sku',
        'customer_unit',
        'purchase_price',
        'selling_price',
    ];

    public function import(array $rows, array $mapping, bool $createMissing = true): array
    {
        $result = ['imported' => 0, 'failed' => 0, 'errors' => []];
        $mappedRows = collect($rows)->map(function ($row, $offset) use ($mapping) {
            return [
                'line' => $offset + 2,
                'data' => $this->mapRow($row, $mapping),
            ];
        });
        $groups = $mappedRows->groupBy(function ($row) {
            $group = trim((string) ($row['data']['product_group'] ?? ''));

            return $group !== '' ? 'group:'.Str::lower($group) : 'line:'.$row['line'];
        });

        foreach ($groups as $groupRows) {
            try {
                foreach ($groupRows as $row) {
                    $this->validateRow($row['data'], $row['line']);
                }
                $this->validateGroup($groupRows->all());
                $this->createProductGroup($groupRows->pluck('data')->all(), $createMissing);
                $result['imported']++;
            } catch (Throwable $exception) {
                foreach ($groupRows as $row) {
                    $result['failed']++;
                    $result['errors'][] = [
                        'line' => $row['line'],
                        'sku' => $row['data']['sku'] ?? '',
                        'product_name' => $row['data']['name'] ?? '',
                        'error' => $this->errorMessage($exception),
                    ];
                }
            }
        }

        return $result;
    }

    public function suggestedMapping(array $headers): array
    {
        $aliases = [
            'name' => ['productname', 'product', 'itemname', 'item'],
            'product_group' => ['productgroupsku', 'parentsku', 'groupcode', 'productgroup'],
            'variant_name' => ['variantname', 'variationname', 'optionname'],
            'attribute_1_name' => ['attribute1', 'attribute1name'],
            'attribute_1_value' => ['option1', 'attribute1value'],
            'attribute_2_name' => ['attribute2', 'attribute2name'],
            'attribute_2_value' => ['option2', 'attribute2value'],
            'attribute_3_name' => ['attribute3', 'attribute3name'],
            'attribute_3_value' => ['option3', 'attribute3value'],
            'parent_category' => ['parentcategory', 'maincategory', 'department'],
            'category' => ['category', 'categoryname', 'childcategory'],
            'subcategory' => ['subcategory', 'sub-category', 'subcategoryname'],
            'brand' => ['brand', 'brandname', 'manufacturer'],
            'sku' => ['sku', 'productsku', 'itemcode', 'stockcode'],
            'barcode' => ['barcode', 'upc', 'ean'],
            'description' => ['description', 'details'],
            'customer_unit' => ['customerunit', 'saleunit', 'sellingunit', 'unit'],
            'supplier_unit' => ['supplierunit', 'purchaseunit', 'buyingunit'],
            'units_per_supplier' => [
                'customerunitsinonesupplierunit',
                'unitspersupplierunit',
                'unitsperpackage',
                'conversion',
                'packsize',
            ],
            'purchase_price' => ['purchaseprice', 'costprice', 'cost'],
            'selling_price' => ['sellingprice', 'saleprice', 'price'],
            'regular_price' => ['regularprice', 'originalprice', 'compareatprice'],
            'opening_stock' => ['openingsupplierquantity', 'openingsupplierstock', 'openingstock', 'stock', 'quantity'],
            'additional_customer_stock' => [
                'additionalcustomerunitstock',
                'additionalcustomerstock',
                'loosequantity',
                'loosecustomerstock',
            ],
            'low_stock_alert' => ['lowstockalert', 'reorderlevel', 'minimumstock'],
            'status' => ['status', 'active'],
        ];

        $normalizedHeaders = collect($headers)
            ->mapWithKeys(fn ($header, $index) => [$index => $this->normalize((string) $header)]);

        return collect(self::FIELDS)->mapWithKeys(function ($label, $field) use ($aliases, $normalizedHeaders) {
            $index = $normalizedHeaders->search(
                fn ($header) => in_array($header, $aliases[$field] ?? [], true)
            );

            return [$field => $index === false ? '' : (string) $index];
        })->all();
    }

    private function mapRow(array $row, array $mapping): array
    {
        return collect(self::FIELDS)->mapWithKeys(function ($label, $field) use ($row, $mapping) {
            $column = $mapping[$field] ?? '';
            $value = $column === '' ? null : ($row[(int) $column] ?? null);

            return [$field => is_string($value) ? trim($value) : ($value ?? '')];
        })->all();
    }

    private function validateRow(array $row, int $line): void
    {
        foreach (self::REQUIRED_FIELDS as $field) {
            if (($row[$field] ?? '') === '') {
                throw new \RuntimeException(self::FIELDS[$field]." is required on line {$line}.");
            }
        }

        foreach ([
            'purchase_price',
            'selling_price',
            'regular_price',
            'opening_stock',
            'additional_customer_stock',
            'low_stock_alert',
            'units_per_supplier',
        ] as $field) {
            if (($row[$field] ?? '') !== '' && (! is_numeric($row[$field]) || (float) $row[$field] < 0)) {
                throw new \RuntimeException(self::FIELDS[$field]." must be a non-negative number on line {$line}.");
            }
        }

        if (Product::where('sku', $row['sku'])->exists() || ProductVariant::where('sku', $row['sku'])->exists()) {
            throw new \RuntimeException("SKU {$row['sku']} already exists.");
        }

        if ($row['barcode'] && (
            Product::where('barcode', $row['barcode'])->exists()
            || ProductVariant::where('barcode', $row['barcode'])->exists()
        )) {
            throw new \RuntimeException("Barcode {$row['barcode']} already exists.");
        }
    }

    private function validateGroup(array $groupRows): void
    {
        $names = collect($groupRows)->pluck('data.name')->filter()->unique();
        if ($names->count() > 1) {
            throw new \RuntimeException('Rows with the same Product Group SKU must use the same Product Name.');
        }

        $skus = collect($groupRows)->pluck('data.sku')->filter();
        if ($skus->duplicates()->isNotEmpty()) {
            throw new \RuntimeException('Variant SKUs must be unique within the CSV file.');
        }

        $barcodes = collect($groupRows)->pluck('data.barcode')->filter();
        if ($barcodes->duplicates()->isNotEmpty()) {
            throw new \RuntimeException('Variant barcodes must be unique within the CSV file.');
        }
    }

    private function createProductGroup(array $rows, bool $createMissing): void
    {
        $tenantId = auth()->user()->tenant_id;
        app(TenantUsageLimitService::class)->assertCanCreateProduct(auth()->user()->tenant);
        $productRow = $rows[0];

        DB::transaction(function () use ($rows, $productRow, $createMissing, $tenantId): void {
            $category = $this->categoryHierarchy($productRow, $createMissing, $tenantId);

            $brand = null;
            if ($productRow['brand']) {
                $brand = Brand::where('name', $productRow['brand'])->first();
                if (! $brand && $createMissing) {
                    $brand = Brand::create([
                        'tenant_id' => $tenantId,
                        'name' => $productRow['brand'],
                        'slug' => Str::slug($productRow['brand']),
                        'is_active' => true,
                    ]);
                }
                if (! $brand) {
                    throw new \RuntimeException("Brand {$productRow['brand']} does not exist.");
                }
            }

            $variants = collect($rows)->map(function ($row, $index) use ($createMissing, $tenantId, $rows) {
                $customerUnit = $this->unit($row['customer_unit'], $createMissing, $tenantId);
                $supplierUnit = $this->unit(
                    $row['supplier_unit'] ?: $row['customer_unit'],
                    $createMissing,
                    $tenantId
                );
                $factor = max((float) ($row['units_per_supplier'] ?: 1), 0.001);
                $supplierStock = (float) ($row['opening_stock'] ?: 0);
                $additionalCustomerStock = (float) ($row['additional_customer_stock'] ?: 0);
                $customerStock = ($supplierStock * $factor) + $additionalCustomerStock;

                if (floor($customerStock) !== $customerStock) {
                    throw new \RuntimeException('Converted opening stock must result in a whole customer-unit quantity.');
                }

                $attributeValueIds = $this->attributeValueIds($row, $createMissing, $tenantId);
                $variantName = $row['variant_name']
                    ?: $this->variantNameFromAttributes($row)
                    ?: (count($rows) === 1 ? 'Default' : 'Variant '.($index + 1));

                return [
                    'name' => $variantName,
                    'sku' => $row['sku'],
                    'barcode' => $row['barcode'] ?: null,
                    'unit_id' => $customerUnit->id,
                    'purchase_unit_id' => $supplierUnit->id,
                    'purchase_unit_factor' => $factor,
                    'purchase_price' => (float) $row['purchase_price'],
                    'selling_price' => (float) $row['selling_price'],
                    'compare_at_price' => $row['regular_price'] !== '' ? (float) $row['regular_price'] : null,
                    'stock_quantity' => (int) $customerStock,
                    'low_stock_alert' => (int) ($row['low_stock_alert'] ?: 5),
                    'track_stock' => true,
                    'is_active' => ! in_array(Str::lower((string) $row['status']), ['inactive', 'no', '0'], true),
                    'attribute_value_ids' => $attributeValueIds,
                ];
            })->all();

            app(ProductCatalogService::class)->create([
                'category_id' => $category->id,
                'brand_id' => $brand?->id,
                'name' => $productRow['name'],
                'description' => $productRow['description'],
                'sku' => $variants[0]['sku'],
                'barcode' => $variants[0]['barcode'],
                'purchase_price' => $variants[0]['purchase_price'],
                'selling_price' => $variants[0]['selling_price'],
                'stock_quantity' => collect($variants)->sum('stock_quantity'),
                'low_stock_alert' => collect($variants)->sum('low_stock_alert'),
                'is_active' => collect($variants)->contains(fn ($variant) => $variant['is_active']),
                'is_online_enabled' => false,
                'variants' => $variants,
            ]);
        });
    }

    private function unit(string $name, bool $createMissing, int $tenantId): Unit
    {
        $unit = Unit::where(function ($query) use ($name) {
            $query->where('name', $name)->orWhere('symbol', $name);
        })->first();

        if (! $unit && $createMissing) {
            $unit = Unit::create([
                'tenant_id' => $tenantId,
                'name' => $name,
                'symbol' => Str::lower(Str::substr($name, 0, 10)),
                'description' => "Created during product import for {$name}.",
                'is_active' => true,
            ]);
        }

        if (! $unit) {
            throw new \RuntimeException("Unit {$name} does not exist.");
        }

        return $unit;
    }

    private function categoryHierarchy(array $row, bool $createMissing, int $tenantId): Category
    {
        $levels = array_values(array_filter([
            $row['parent_category'] ?? '',
            $row['category'] ?? '',
            $row['subcategory'] ?? '',
        ], fn ($name) => trim((string) $name) !== ''));
        $parent = null;

        foreach ($levels as $name) {
            $category = Category::query()
                ->where('name', $name)
                ->where('parent_id', $parent?->id)
                ->first();

            if (! $category && $createMissing) {
                $category = Category::create([
                    'tenant_id' => $tenantId,
                    'parent_id' => $parent?->id,
                    'name' => $name,
                ]);
            }

            if (! $category) {
                throw new \RuntimeException(
                    "Category path ".implode(' > ', $levels).' does not exist.'
                );
            }

            $parent = $category;
        }

        if (! $parent) {
            throw new \RuntimeException('At least one category level is required.');
        }

        return $parent;
    }

    private function attributeValueIds(array $row, bool $createMissing, int $tenantId): array
    {
        $ids = [];

        for ($number = 1; $number <= 3; $number++) {
            $attributeName = trim((string) ($row["attribute_{$number}_name"] ?? ''));
            $valueName = trim((string) ($row["attribute_{$number}_value"] ?? ''));
            if ($attributeName === '' && $valueName === '') {
                continue;
            }
            if ($attributeName === '' || $valueName === '') {
                throw new \RuntimeException("Attribute {$number} and Option {$number} must both be provided.");
            }

            $attribute = ProductAttribute::where('name', $attributeName)->first();
            if (! $attribute && $createMissing) {
                $attribute = ProductAttribute::create([
                    'tenant_id' => $tenantId,
                    'name' => $attributeName,
                    'slug' => Str::slug($attributeName),
                    'is_active' => true,
                ]);
            }
            if (! $attribute) {
                throw new \RuntimeException("Attribute {$attributeName} does not exist.");
            }

            $value = $attribute->values()->where('value', $valueName)->first();
            if (! $value && $createMissing) {
                $value = $attribute->values()->create([
                    'value' => $valueName,
                    'slug' => Str::slug($valueName),
                    'sort_order' => $attribute->values()->count(),
                ]);
            }
            if (! $value) {
                throw new \RuntimeException("Option {$valueName} does not exist for {$attributeName}.");
            }

            $ids[] = $value->id;
        }

        return $ids;
    }

    private function variantNameFromAttributes(array $row): string
    {
        return collect(range(1, 3))
            ->map(fn ($number) => trim((string) ($row["attribute_{$number}_value"] ?? '')))
            ->filter()
            ->implode(' / ');
    }

    private function normalize(string $value): string
    {
        return preg_replace('/[^a-z0-9]+/', '', Str::lower(trim($value))) ?? '';
    }

    private function errorMessage(Throwable $exception): string
    {
        if ($exception instanceof ValidationException) {
            return collect($exception->errors())->flatten()->first()
                ?? 'The product could not be imported.';
        }

        return $exception->getMessage() ?: 'The product could not be imported.';
    }
}
