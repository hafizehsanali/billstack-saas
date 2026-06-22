<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'category_id',
        'brand_id',
        'name',
        'slug',
        'description',
        'sku',
        'barcode',
        'purchase_price',
        'selling_price',
        'stock_quantity',
        'low_stock_alert',
        'has_variants',
        'is_active',
        'is_online_enabled',
    ];

    protected function casts(): array
    {
        return [
            'has_variants' => 'boolean',
            'is_active' => 'boolean',
            'is_online_enabled' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function activeVariants(): HasMany
    {
        return $this->variants()->where('is_active', true);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function defaultVariant()
    {
        return $this->hasOne(ProductVariant::class)->where('is_default', true);
    }

    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function syncFromVariants(): void
    {
        $variants = $this->variants()->where('is_active', true)->get();

        if ($variants->isEmpty()) {
            return;
        }

        $default = $variants->firstWhere('is_default', true) ?? $variants->first();

        $this->update([
            'sku' => $default->sku,
            'barcode' => $default->barcode,
            'purchase_price' => $default->purchase_price,
            'selling_price' => $default->selling_price,
            'stock_quantity' => (int) $variants->sum('stock_quantity'),
            'low_stock_alert' => (int) $variants->sum('low_stock_alert'),
            'has_variants' => $variants->count() > 1,
        ]);
    }
}
