<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\ProductVariant;
use App\Models\StockMovement;
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
                'product_sale_mode' => $data['product_sale_mode'] ?? Product::SALE_MODE_PACKED,
                'allow_loose_sale' => $data['allow_loose_sale'] ?? false,
                'base_stock_unit_id' => $data['base_stock_unit_id'] ?? null,
                'default_purchase_unit_id' => $data['default_purchase_unit_id'] ?? null,
                'default_purchase_unit_factor' => $data['default_purchase_unit_factor'] ?? null,
                'track_expiry' => $data['track_expiry'] ?? false,
                'track_batch' => $data['track_batch'] ?? false,
                'track_serial' => $data['track_serial'] ?? false,
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
                'product_sale_mode' => $data['product_sale_mode'] ?? $product->product_sale_mode ?? Product::SALE_MODE_PACKED,
                'allow_loose_sale' => $data['allow_loose_sale'] ?? false,
                'base_stock_unit_id' => $data['base_stock_unit_id'] ?? null,
                'default_purchase_unit_id' => $data['default_purchase_unit_id'] ?? null,
                'default_purchase_unit_factor' => $data['default_purchase_unit_factor'] ?? null,
                'track_expiry' => $data['track_expiry'] ?? false,
                'track_batch' => $data['track_batch'] ?? false,
                'track_serial' => $data['track_serial'] ?? false,
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
        $variant->loadMissing('product');

        if (! $variant->product->tracksStock() || ! $variant->track_stock) {
            return $variant;
        }

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
            'conversion_to_base_unit' => 1,
            'purchase_price' => $product->purchase_price,
            'selling_price' => $product->selling_price,
            'stock_quantity' => $product->stock_quantity,
            'low_stock_alert' => $product->low_stock_alert,
            'is_default' => true,
            'is_active' => true,
        ]);
    }

    public function consumeBatches(ProductVariant $variant, float $quantity, ?string $batchNumber = null): array
    {
        $variant->loadMissing('product');

        if (! $variant->product->track_batch) {
            return [];
        }

        $remaining = $quantity;
        $consumed = [];
        $query = ProductBatch::query()
            ->where('product_variant_id', $variant->id)
            ->where('quantity', '>', 0)
            ->where('is_active', true)
            ->lockForUpdate();

        if ($batchNumber) {
            $query->where('batch_number', $batchNumber);
        } else {
            $query->orderByRaw('expiry_date IS NULL')
                ->orderBy('expiry_date')
                ->orderBy('id');
        }

        foreach ($query->get() as $batch) {
            if ($remaining <= 0) {
                break;
            }

            $take = min((float) $batch->quantity, $remaining);
            $batch->decrement('quantity', $take);
            $batch->refresh();

            if ((float) $batch->quantity <= 0) {
                $batch->update(['is_active' => false]);
            }

            $consumed[] = ['batch' => $batch, 'quantity' => $take];
            $remaining = round($remaining - $take, 3);
        }

        if ($remaining > 0) {
            throw ValidationException::withMessages([
                'stock' => $batchNumber
                    ? "Batch {$batchNumber} does not have enough stock."
                    : $variant->display_name.' does not have enough batch stock.',
            ]);
        }

        return $consumed;
    }

    public function restoreBatchesFromInvoiceItem(\App\Models\InvoiceItem $invoiceItem, float $quantity): array
    {
        $remaining = $quantity;
        $restored = [];

        $movements = StockMovement::query()
            ->with('variant')
            ->where('source_type', \App\Models\InvoiceItem::class)
            ->where('source_id', $invoiceItem->id)
            ->whereNotNull('product_batch_id')
            ->orderBy('id')
            ->get();

        foreach ($movements as $movement) {
            if ($remaining <= 0) {
                break;
            }

            $batch = ProductBatch::find($movement->product_batch_id);
            if (! $batch) {
                continue;
            }

            $restore = min((float) $movement->quantity, $remaining);
            $batch->increment('quantity', $restore);
            $batch->update(['is_active' => true]);
            $batch->refresh();

            $restored[] = ['batch' => $batch, 'quantity' => $restore];
            $remaining = round($remaining - $restore, 3);
        }

        return $restored;
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
            $oldStock = (float) ($variant->exists ? $variant->stock_quantity : 0);
            $purchaseUnitFactor = max((float) ($row['purchase_unit_factor'] ?? 1), 0.001);
            $purchaseUnitPrice = (float) $row['purchase_price'];
            $tracksStock = ($row['track_stock'] ?? true) && $product->tracksStock();

            $variant->fill([
                'name' => $row['name'] ?: ($index === 0 ? 'Default' : 'Variant '.($index + 1)),
                'sku' => $row['sku'],
                'barcode' => $row['barcode'] ?? null,
                'unit_id' => $row['unit_id'] ?? $defaultUnitId,
                'purchase_unit_id' => ($row['purchase_unit_id'] ?? null) ?: ($row['unit_id'] ?? $defaultUnitId),
                'purchase_unit_factor' => $purchaseUnitFactor,
                'purchase_unit_price' => $purchaseUnitPrice,
                'conversion_to_base_unit' => $row['conversion_to_base_unit'] ?? 1,
                'purchase_price' => round($purchaseUnitPrice / $purchaseUnitFactor, 2),
                'selling_price' => $row['selling_price'],
                'compare_at_price' => $row['compare_at_price'] ?? null,
                'stock_quantity' => $tracksStock ? $row['stock_quantity'] : 0,
                'low_stock_alert' => $row['low_stock_alert'],
                'track_stock' => $tracksStock,
                'is_default' => $index === 0,
                'is_active' => $row['is_active'] ?? true,
            ]);
            $variant->save();
            $variant->attributeValues()->sync($row['attribute_value_ids'] ?? []);
            $keptIds[] = $variant->id;

            $difference = (float) $variant->stock_quantity - $oldStock;

            if ($tracksStock && abs($difference) > 0.0001) {
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
