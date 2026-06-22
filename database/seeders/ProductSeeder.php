<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductAttribute;
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
            $categories = Category::where('tenant_id', $tenant->id)->get();

            if ($categories->isEmpty()) {
                continue;
            }

            $brand = Brand::updateOrCreate(
                ['tenant_id' => $tenant->id, 'name' => 'Demo Essentials'],
                ['slug' => 'demo-essentials', 'description' => 'Seeded catalog brand.', 'is_active' => true]
            );

            $color = $this->attribute($tenant->id, 'Color', ['Black', 'Blue']);
            $pack = $this->attribute($tenant->id, 'Pack Size', ['Single', 'Pack of 3']);

            $this->product($tenant->id, $categories->get(0), $brand->id, [
                'name' => 'Wireless Mouse',
                'slug' => 'wireless-mouse',
                'variants' => [
                    ['name' => 'Black', 'sku' => "CAT-$tenant->id-MOU-BLK", 'barcode' => "CAT-$tenant->id-1001", 'cost' => 1000, 'price' => 1800, 'stock' => 30, 'values' => [$color['Black']]],
                    ['name' => 'Blue', 'sku' => "CAT-$tenant->id-MOU-BLU", 'barcode' => "CAT-$tenant->id-1002", 'cost' => 1050, 'price' => 1850, 'stock' => 18, 'values' => [$color['Blue']]],
                ],
            ]);

            $this->product($tenant->id, $categories->get(1) ?? $categories->first(), $brand->id, [
                'name' => 'Utility Notebook',
                'slug' => 'utility-notebook',
                'variants' => [
                    ['name' => 'Single', 'sku' => "CAT-$tenant->id-NOTE-001", 'barcode' => "CAT-$tenant->id-2001", 'cost' => 100, 'price' => 180, 'stock' => 50, 'values' => [$pack['Single']]],
                    ['name' => 'Pack of 3', 'sku' => "CAT-$tenant->id-NOTE-003", 'barcode' => "CAT-$tenant->id-2002", 'cost' => 280, 'price' => 500, 'stock' => 20, 'values' => [$pack['Pack of 3']]],
                ],
            ]);
        }
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

            return [$value => $model->id];
        })->all();
    }

    private function product(int $tenantId, Category $category, int $brandId, array $data): void
    {
        $pieceUnitId = Unit::where('tenant_id', $tenantId)->where('name', 'Piece')->value('id');
        $first = $data['variants'][0];
        $product = Product::updateOrCreate(
            ['tenant_id' => $tenantId, 'slug' => $data['slug']],
            [
                'category_id' => $category->id,
                'brand_id' => $brandId,
                'name' => $data['name'],
                'sku' => $first['sku'],
                'barcode' => $first['barcode'],
                'purchase_price' => $first['cost'],
                'selling_price' => $first['price'],
                'stock_quantity' => collect($data['variants'])->sum('stock'),
                'low_stock_alert' => 5,
                'has_variants' => count($data['variants']) > 1,
                'is_active' => true,
                'is_online_enabled' => true,
            ]
        );

        foreach ($data['variants'] as $index => $row) {
            $variant = ProductVariant::where('tenant_id', $tenantId)
                ->where(fn ($query) => $query
                    ->where('sku', $row['sku'])
                    ->orWhere('barcode', $row['barcode']))
                ->first() ?? new ProductVariant(['tenant_id' => $tenantId]);

            $variant->fill([
                'product_id' => $product->id,
                'name' => $row['name'],
                'sku' => $row['sku'],
                'barcode' => $row['barcode'],
                'unit_id' => $pieceUnitId,
                'purchase_unit_id' => $pieceUnitId,
                'purchase_unit_factor' => 1,
                'purchase_unit_price' => $row['cost'],
                'purchase_price' => $row['cost'],
                'selling_price' => $row['price'],
                'stock_quantity' => $row['stock'],
                'low_stock_alert' => 5,
                'is_default' => $index === 0,
                'is_active' => true,
            ])->save();
            $variant->attributeValues()->sync($row['values']);
        }
    }
}
