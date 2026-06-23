<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\ProductAttributeValue;
use App\Models\ProductVariant;
use App\Models\Tenant;
use App\Models\Unit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Tenant::all() as $tenant) {
            $units = $this->units($tenant->id);
            $brands = $this->brands($tenant->id);
            $attributes = [
                'Flavor' => $this->attribute($tenant->id, 'Flavor', ['Cola', 'Orange', 'Lemon']),
                'Pack Size' => $this->attribute($tenant->id, 'Pack Size', ['250g', '500g', '100g', '200g']),
            ];

            foreach ($this->catalog() as $item) {
                $category = $this->categoryPath($tenant->id, $item['category']);
                $brand = $brands[$item['brand']];
                $this->product($tenant->id, $category, $brand, $units, $attributes, $item);
            }
        }
    }

    private function catalog(): array
    {
        return [
            [
                'name' => 'Basmati Rice',
                'slug' => 'basmati-rice',
                'brand' => 'Zephrant Essentials',
                'category' => ['Groceries', 'Rice & Grains'],
                'description' => 'Premium basmati rice sold by kilogram and purchased by supplier bag.',
                'variants' => [
                    ['name' => 'Default', 'sku' => 'RICE-BAS-001', 'barcode' => '890100000001', 'customer_unit' => 'Kilogram', 'supplier_unit' => 'Bag', 'units_per_supplier_unit' => 50, 'supplier_price' => 12000, 'selling_price' => 280, 'regular_price' => 300, 'stock' => 160, 'low_stock' => 20],
                ],
            ],
            [
                'name' => 'Sugar',
                'slug' => 'sugar',
                'brand' => 'Zephrant Essentials',
                'category' => ['Groceries', 'Rice & Grains'],
                'description' => 'Loose sugar managed in kilograms and purchased in supplier bags.',
                'variants' => [
                    ['name' => 'Default', 'sku' => 'SUGAR-001', 'barcode' => '890100000002', 'customer_unit' => 'Kilogram', 'supplier_unit' => 'Bag', 'units_per_supplier_unit' => 50, 'supplier_price' => 7000, 'selling_price' => 165, 'regular_price' => 175, 'stock' => 125, 'low_stock' => 25],
                ],
            ],
            [
                'name' => 'Cooking Oil',
                'slug' => 'cooking-oil-1-litre',
                'brand' => 'Pure Harvest',
                'category' => ['Groceries', 'Oil & Ghee'],
                'description' => 'One litre cooking oil bottles purchased in cartons.',
                'variants' => [
                    ['name' => '1 Litre Bottle', 'sku' => 'OIL-1L-001', 'barcode' => '890100000003', 'customer_unit' => 'Bottle', 'supplier_unit' => 'Carton', 'units_per_supplier_unit' => 12, 'supplier_price' => 6000, 'selling_price' => 560, 'regular_price' => 585, 'stock' => 48, 'low_stock' => 12],
                ],
            ],
            [
                'name' => 'Eggs',
                'slug' => 'eggs',
                'brand' => 'Daily Farm',
                'category' => ['Fresh Items', 'Dairy & Eggs'],
                'description' => 'Eggs sold by piece and received in supplier trays.',
                'variants' => [
                    ['name' => 'Default', 'sku' => 'EGGS-001', 'barcode' => '890100000004', 'customer_unit' => 'Piece', 'supplier_unit' => 'Tray', 'units_per_supplier_unit' => 30, 'supplier_price' => 900, 'selling_price' => 38, 'regular_price' => 40, 'stock' => 180, 'low_stock' => 30],
                ],
            ],
            [
                'name' => 'Detergent Powder',
                'slug' => 'detergent-powder-1kg',
                'brand' => 'CleanPro',
                'category' => ['Household', 'Cleaning'],
                'description' => 'Household detergent packs purchased in supplier cartons.',
                'variants' => [
                    ['name' => '1KG Pack', 'sku' => 'DETERGENT-1KG', 'barcode' => '890100000005', 'customer_unit' => 'Piece', 'supplier_unit' => 'Carton', 'units_per_supplier_unit' => 12, 'supplier_price' => 4800, 'selling_price' => 465, 'regular_price' => 500, 'stock' => 36, 'low_stock' => 10],
                ],
            ],
            [
                'name' => 'Paracetamol Strip',
                'slug' => 'paracetamol-strip',
                'brand' => 'MediCare',
                'category' => ['Pharmacy', 'Pain Relief'],
                'description' => 'Medicine strips purchased in boxes for pharmacy-style inventory.',
                'variants' => [
                    ['name' => 'Default', 'sku' => 'MED-PARA-STRIP', 'barcode' => '890100000006', 'customer_unit' => 'Strip', 'supplier_unit' => 'Box', 'units_per_supplier_unit' => 25, 'supplier_price' => 1250, 'selling_price' => 70, 'regular_price' => 75, 'stock' => 75, 'low_stock' => 15],
                ],
            ],
            [
                'name' => 'Soft Drink',
                'slug' => 'soft-drink-500ml',
                'brand' => 'RefreshCo',
                'category' => ['Beverages', 'Cold Drinks'],
                'description' => 'Cold drink variants sold as bottles and purchased in cartons.',
                'variants' => [
                    ['name' => '500ml Cola', 'sku' => 'DRINK-COLA-500', 'barcode' => '890100000101', 'customer_unit' => 'Bottle', 'supplier_unit' => 'Carton', 'units_per_supplier_unit' => 24, 'supplier_price' => 2160, 'selling_price' => 110, 'regular_price' => 120, 'stock' => 72, 'low_stock' => 24, 'values' => ['Flavor' => 'Cola']],
                    ['name' => '500ml Orange', 'sku' => 'DRINK-ORANGE-500', 'barcode' => '890100000102', 'customer_unit' => 'Bottle', 'supplier_unit' => 'Carton', 'units_per_supplier_unit' => 24, 'supplier_price' => 2160, 'selling_price' => 110, 'regular_price' => 120, 'stock' => 48, 'low_stock' => 24, 'values' => ['Flavor' => 'Orange']],
                    ['name' => '500ml Lemon', 'sku' => 'DRINK-LEMON-500', 'barcode' => '890100000103', 'customer_unit' => 'Bottle', 'supplier_unit' => 'Carton', 'units_per_supplier_unit' => 24, 'supplier_price' => 2160, 'selling_price' => 110, 'regular_price' => 120, 'stock' => 48, 'low_stock' => 24, 'values' => ['Flavor' => 'Lemon']],
                ],
            ],
            [
                'name' => 'Tea Pack',
                'slug' => 'tea-pack',
                'brand' => 'Morning Leaf',
                'category' => ['Groceries', 'Tea & Coffee'],
                'description' => 'Tea packs with common retail pack-size variants.',
                'variants' => [
                    ['name' => '250g', 'sku' => 'TEA-250G', 'barcode' => '890100000201', 'customer_unit' => 'Pack', 'supplier_unit' => 'Carton', 'units_per_supplier_unit' => 24, 'supplier_price' => 8400, 'selling_price' => 420, 'regular_price' => 450, 'stock' => 48, 'low_stock' => 12, 'values' => ['Pack Size' => '250g']],
                    ['name' => '500g', 'sku' => 'TEA-500G', 'barcode' => '890100000202', 'customer_unit' => 'Pack', 'supplier_unit' => 'Carton', 'units_per_supplier_unit' => 12, 'supplier_price' => 7800, 'selling_price' => 760, 'regular_price' => 800, 'stock' => 24, 'low_stock' => 8, 'values' => ['Pack Size' => '500g']],
                ],
            ],
            [
                'name' => 'Toothpaste',
                'slug' => 'toothpaste',
                'brand' => 'SmileCare',
                'category' => ['Personal Care', 'Oral Care'],
                'description' => 'Toothpaste variants by pack size.',
                'variants' => [
                    ['name' => '100g', 'sku' => 'TOOTHPASTE-100G', 'barcode' => '890100000301', 'customer_unit' => 'Piece', 'supplier_unit' => 'Carton', 'units_per_supplier_unit' => 48, 'supplier_price' => 5760, 'selling_price' => 150, 'regular_price' => 165, 'stock' => 96, 'low_stock' => 24, 'values' => ['Pack Size' => '100g']],
                    ['name' => '200g', 'sku' => 'TOOTHPASTE-200G', 'barcode' => '890100000302', 'customer_unit' => 'Piece', 'supplier_unit' => 'Carton', 'units_per_supplier_unit' => 24, 'supplier_price' => 6000, 'selling_price' => 300, 'regular_price' => 320, 'stock' => 48, 'low_stock' => 12, 'values' => ['Pack Size' => '200g']],
                ],
            ],
        ];
    }

    private function product(int $tenantId, Category $category, Brand $brand, array $units, array $attributes, array $data): void
    {
        $first = $data['variants'][0];
        $product = Product::updateOrCreate(
            ['tenant_id' => $tenantId, 'slug' => $data['slug']],
            [
                'category_id' => $category->id,
                'brand_id' => $brand->id,
                'name' => $data['name'],
                'description' => $data['description'],
                'sku' => $this->sku($tenantId, $first['sku']),
                'barcode' => $this->barcode($tenantId, $first['barcode']),
                'purchase_price' => $this->customerUnitCost($first),
                'selling_price' => $first['selling_price'],
                'stock_quantity' => collect($data['variants'])->sum('stock'),
                'low_stock_alert' => collect($data['variants'])->sum('low_stock'),
                'has_variants' => count($data['variants']) > 1,
                'is_active' => true,
                'is_online_enabled' => true,
            ]
        );

        foreach ($data['variants'] as $index => $row) {
            $variant = ProductVariant::where('tenant_id', $tenantId)
                ->where('sku', $this->sku($tenantId, $row['sku']))
                ->first() ?? new ProductVariant(['tenant_id' => $tenantId]);

            $variant->fill([
                'product_id' => $product->id,
                'name' => $row['name'],
                'sku' => $this->sku($tenantId, $row['sku']),
                'barcode' => $this->barcode($tenantId, $row['barcode']),
                'unit_id' => $units[$row['customer_unit']]->id,
                'purchase_unit_id' => $units[$row['supplier_unit']]->id,
                'purchase_unit_factor' => $row['units_per_supplier_unit'],
                'purchase_unit_price' => $row['supplier_price'],
                'purchase_price' => $this->customerUnitCost($row),
                'selling_price' => $row['selling_price'],
                'compare_at_price' => $row['regular_price'],
                'stock_quantity' => $row['stock'],
                'low_stock_alert' => $row['low_stock'],
                'track_stock' => true,
                'is_default' => $index === 0,
                'is_active' => true,
            ])->save();

            $variant->attributeValues()->sync($this->attributeValueIds($attributes, $row['values'] ?? []));
        }

        $product->syncFromVariants();
    }

    private function units(int $tenantId): array
    {
        return Unit::where('tenant_id', $tenantId)->get()->keyBy('name')->all();
    }

    private function brands(int $tenantId): array
    {
        return collect(['Zephrant Essentials', 'Pure Harvest', 'Daily Farm', 'CleanPro', 'MediCare', 'RefreshCo', 'Morning Leaf', 'SmileCare'])
            ->mapWithKeys(function (string $name) use ($tenantId) {
                $brand = Brand::updateOrCreate(
                    ['tenant_id' => $tenantId, 'name' => $name],
                    ['slug' => Str::slug($name), 'description' => "Default catalog brand: $name.", 'is_active' => true]
                );

                return [$name => $brand];
            })
            ->all();
    }

    private function categoryPath(int $tenantId, array $path): Category
    {
        $parent = null;

        foreach ($path as $name) {
            $parent = Category::updateOrCreate(
                ['tenant_id' => $tenantId, 'parent_id' => $parent?->id, 'name' => $name],
                []
            );
        }

        return $parent;
    }

    private function attribute(int $tenantId, string $name, array $values): array
    {
        $attribute = ProductAttribute::updateOrCreate(
            ['tenant_id' => $tenantId, 'name' => $name],
            ['slug' => Str::slug($name), 'is_active' => true]
        );

        return collect($values)->mapWithKeys(function (string $value, int $index) use ($attribute) {
            $model = $attribute->values()->updateOrCreate(
                ['value' => $value],
                ['slug' => Str::slug($value), 'sort_order' => $index]
            );

            return [$value => $model];
        })->all();
    }

    private function attributeValueIds(array $attributes, array $values): array
    {
        return collect($values)
            ->map(fn (string $value, string $attribute): ?ProductAttributeValue => $attributes[$attribute][$value] ?? null)
            ->filter()
            ->pluck('id')
            ->all();
    }

    private function customerUnitCost(array $row): float
    {
        return round($row['supplier_price'] / max((float) $row['units_per_supplier_unit'], 1), 2);
    }

    private function sku(int $tenantId, string $sku): string
    {
        return "DEMO-$tenantId-$sku";
    }

    private function barcode(int $tenantId, string $barcode): string
    {
        return "$tenantId$barcode";
    }
}
