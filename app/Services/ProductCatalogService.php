<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Unit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductCatalogService
{
    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data): Product {
            $product = Product::create([
                'tenant_id' => auth()->user()->tenant_id,
                'category_id' => $data['category_id'],
                'brand_id' => $data['brand_id'] ?? null,
                'name' => $data['name'],
                'slug' => $this->uniqueSlug($data['name']),
                'description' => $data['description'] ?? null,
                'sku' => $data['sku'],
                'barcode' => $data['barcode'] ?? null,
                'purchase_price' => $data['purchase_price'],
                'selling_price' => $data['selling_price'],
                'stock_quantity' => $data['stock_quantity'],
                'low_stock_alert' => $data['low_stock_alert'],
                'has_variants' => count($data['variants'] ?? []) > 1,
                'is_active' => $data['is_active'] ?? true,
                'is_online_enabled' => $data['is_online_enabled'] ?? false,
            ]);

            $this->syncVariants($product, $data);
            $this->storeImages($product, $data['images'] ?? []);

            return $product->fresh(['variants', 'brand', 'category']);
        });
    }

    public function update(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data): Product {
            $product->update([
                'category_id' => $data['category_id'],
                'brand_id' => $data['brand_id'] ?? null,
                'name' => $data['name'],
                'slug' => $product->slug ?: $this->uniqueSlug($data['name'], $product->id),
                'description' => $data['description'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'is_online_enabled' => $data['is_online_enabled'] ?? false,
            ]);

            $this->syncVariants($product, $data);
            $this->storeImages($product, $data['images'] ?? []);

            return $product->fresh(['variants', 'brand', 'category']);
        });
    }

    public function resolveVariant(int $productId, ?int $variantId = null, bool $lock = false): ProductVariant
    {
        $query = ProductVariant::query()
            ->with('product')
            ->where('product_id', $productId)
            ->where('is_active', true);

        if ($variantId) {
            $query->whereKey($variantId);
        } else {
            $query->orderByDesc('is_default')->orderBy('id');
        }

        if ($lock) {
            $query->lockForUpdate();
        }

        $variant = $query->first();

        if (! $variant) {
            $product = Product::findOrFail($productId);
            $variant = $this->createDefaultVariant($product);
        }

        return $variant;
    }

    public function adjustStock(
        ProductVariant $variant,
        float $quantity,
        string $direction,
        ?float $purchasePrice = null
    ): ProductVariant {
        if ($direction === 'out' && $variant->stock_quantity < $quantity) {
            throw ValidationException::withMessages([
                'stock' => $variant->display_name.' does not have enough stock.',
            ]);
        }

        if ($direction === 'in' && $purchasePrice !== null) {
            $newStock = $variant->stock_quantity + $quantity;
            $averagePrice = (
                ($variant->stock_quantity * $variant->purchase_price)
                + ($quantity * $purchasePrice)
            ) / max($newStock, 1);

            $variant->update([
                'stock_quantity' => $newStock,
                'purchase_price' => round($averagePrice, 2),
            ]);
        } else {
            $variant->{$direction === 'in' ? 'increment' : 'decrement'}(
                'stock_quantity',
                $quantity
            );
        }

        $variant->refresh();
        $variant->product->syncFromVariants();

        return $variant;
    }

    public function createDefaultVariant(Product $product): ProductVariant
    {
        $unitId = $this->defaultUnitId($product->tenant_id);

        return $product->variants()->create([
            'tenant_id' => $product->tenant_id,
            'name' => 'Default',
            'sku' => $product->sku ?: 'PRODUCT-'.$product->id,
            'barcode' => $product->barcode,
            'unit_id' => $unitId,
            'purchase_unit_id' => $unitId,
            'purchase_unit_factor' => 1,
            'purchase_unit_price' => $product->purchase_price,
            'purchase_price' => $product->purchase_price,
            'selling_price' => $product->selling_price,
            'stock_quantity' => $product->stock_quantity,
            'low_stock_alert' => $product->low_stock_alert,
            'is_default' => true,
            'is_active' => true,
        ]);
    }

    private function syncVariants(Product $product, array $data): void
    {
        $defaultUnitId = $this->defaultUnitId($product->tenant_id);
        $rows = $data['variants'] ?? [[
            'name' => 'Default',
            'sku' => $data['sku'],
            'barcode' => $data['barcode'] ?? null,
            'unit_id' => $data['unit_id'] ?? $defaultUnitId,
            'purchase_unit_id' => $data['purchase_unit_id'] ?? $data['unit_id'] ?? $defaultUnitId,
            'purchase_unit_factor' => $data['purchase_unit_factor'] ?? 1,
            'purchase_price' => $data['purchase_price'],
            'selling_price' => $data['selling_price'],
            'stock_quantity' => $data['stock_quantity'],
            'low_stock_alert' => $data['low_stock_alert'],
            'is_default' => true,
            'is_active' => true,
        ]];
        $keptIds = [];

        foreach (array_values($rows) as $index => $row) {
            $variant = ! empty($row['id'])
                ? $product->variants()->findOrFail($row['id'])
                : new ProductVariant([
                    'tenant_id' => $product->tenant_id,
                    'product_id' => $product->id,
                ]);
            $oldStock = (int) ($variant->exists ? $variant->stock_quantity : 0);
            $purchaseUnitFactor = max((float) ($row['purchase_unit_factor'] ?? 1), 0.001);
            $purchaseUnitPrice = (float) $row['purchase_price'];

            $variant->fill([
                'name' => $row['name'] ?: ($index === 0 ? 'Default' : 'Variant '.($index + 1)),
                'sku' => $row['sku'],
                'barcode' => $row['barcode'] ?? null,
                'unit_id' => $row['unit_id'] ?? $defaultUnitId,
                'purchase_unit_id' => ($row['purchase_unit_id'] ?? null) ?: ($row['unit_id'] ?? $defaultUnitId),
                'purchase_unit_factor' => $purchaseUnitFactor,
                'purchase_unit_price' => $purchaseUnitPrice,
                'purchase_price' => round($purchaseUnitPrice / $purchaseUnitFactor, 2),
                'selling_price' => $row['selling_price'],
                'compare_at_price' => $row['compare_at_price'] ?? null,
                'stock_quantity' => $row['stock_quantity'],
                'low_stock_alert' => $row['low_stock_alert'],
                'track_stock' => $row['track_stock'] ?? true,
                'is_default' => $index === 0,
                'is_active' => $row['is_active'] ?? true,
            ]);
            $variant->save();
            $variant->attributeValues()->sync($row['attribute_value_ids'] ?? []);
            $keptIds[] = $variant->id;

            $difference = (int) $variant->stock_quantity - $oldStock;

            if ($difference !== 0) {
                $isOpeningStock = ! $variant->wasRecentlyCreated ? false : $oldStock === 0;

                app(StockLedgerService::class)->record(
                    $variant,
                    $isOpeningStock ? 'opening_stock' : ($difference > 0 ? 'stock_adjustment_in' : 'stock_adjustment_out'),
                    abs($difference),
                    [
                        'direction' => $difference > 0 ? 'in' : 'out',
                        'unit_cost' => $variant->purchase_price,
                        'unit_price' => $variant->selling_price,
                        'notes' => $oldStock === 0 ? 'Opening variant stock.' : 'Variant stock adjustment.',
                    ]
                );
            }
        }

        $product->variants()
            ->whereNotIn('id', $keptIds)
            ->whereDoesntHave('stockMovements', fn ($query) => $query->whereNotIn('type', [
                'opening_stock',
                'stock_adjustment_in',
                'stock_adjustment_out',
            ]))
            ->delete();

        $product->syncFromVariants();
    }

    private function defaultUnitId(int $tenantId): int
    {
        return Unit::firstOrCreate(
            ['tenant_id' => $tenantId, 'name' => 'Piece'],
            [
                'symbol' => 'pc',
                'description' => 'For items sold individually.',
                'is_active' => true,
            ]
        )->id;
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'product';
        $slug = $base;
        $suffix = 2;

        while (Product::withoutGlobalScope('tenant')
            ->where('tenant_id', auth()->user()->tenant_id)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    private function storeImages(Product $product, array $images): void
    {
        $nextOrder = (int) $product->images()->max('sort_order') + 1;

        foreach ($images as $image) {
            $path = $image->store("products/{$product->tenant_id}/{$product->id}", 'public');

            $product->images()->create([
                'tenant_id' => $product->tenant_id,
                'path' => $path,
                'alt_text' => $product->name,
                'sort_order' => $nextOrder,
                'is_primary' => ! $product->images()->exists(),
            ]);

            $nextOrder++;
        }
    }
}
