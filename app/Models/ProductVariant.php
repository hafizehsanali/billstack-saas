<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'product_id',
        'name',
        'sku',
        'barcode',
        'unit_id',
        'purchase_unit_id',
        'purchase_unit_factor',
        'purchase_unit_price',
        'conversion_to_base_unit',
        'purchase_price',
        'selling_price',
        'compare_at_price',
        'stock_quantity',
        'low_stock_alert',
        'track_stock',
        'is_default',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'track_stock' => 'boolean',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'purchase_unit_factor' => 'decimal:3',
            'purchase_unit_price' => 'decimal:2',
            'conversion_to_base_unit' => 'float',
        ];
    }

    public function getStockQuantityAttribute(mixed $value): int|float
    {
        $number = (float) $value;

        return floor($number) === $number ? (int) $number : $number;
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function purchaseUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'purchase_unit_id');
    }

    public function attributeValues(): BelongsToMany
    {
        return $this->belongsToMany(ProductAttributeValue::class, 'product_variant_values')
            ->with('attribute');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(ProductBatch::class, 'product_variant_id');
    }

    public function serialNumbers(): HasMany
    {
        return $this->hasMany(ProductSerialNumber::class, 'product_variant_id');
    }

    public function getDisplayNameAttribute(): string
    {
        if (! $this->name || $this->name === 'Default') {
            return $this->product?->name ?? $this->name ?? 'Product';
        }

        return ($this->product?->name ? $this->product->name.' - ' : '').$this->name;
    }
}
