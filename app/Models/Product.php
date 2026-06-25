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

    public const SALE_MODE_LOOSE = 'loose';
    public const SALE_MODE_PACKED = 'packed';
    public const SALE_MODE_HYBRID = 'hybrid';
    public const SALE_MODE_SERVICE = 'service';
    public const SALE_MODE_SERIALIZED = 'serialized';
    public const SALE_MODE_BATCH_TRACKED = 'batch_tracked';

    public const SALE_MODES = [
        self::SALE_MODE_LOOSE,
        self::SALE_MODE_PACKED,
        self::SALE_MODE_HYBRID,
        self::SALE_MODE_SERVICE,
        self::SALE_MODE_SERIALIZED,
        self::SALE_MODE_BATCH_TRACKED,
    ];

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
        'product_sale_mode',
        'allow_loose_sale',
        'base_stock_unit_id',
        'default_purchase_unit_id',
        'default_purchase_unit_factor',
        'track_expiry',
        'track_batch',
        'track_serial',
    ];

    protected function casts(): array
    {
        return [
            'has_variants' => 'boolean',
            'is_active' => 'boolean',
            'is_online_enabled' => 'boolean',
            'allow_loose_sale' => 'boolean',
            'default_purchase_unit_factor' => 'decimal:3',
            'track_expiry' => 'boolean',
            'track_batch' => 'boolean',
            'track_serial' => 'boolean',
        ];
    }

    public function getStockQuantityAttribute(mixed $value): int|float
    {
        $number = (float) $value;

        return floor($number) === $number ? (int) $number : $number;
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

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function salesReturnItems(): HasMany
    {
        return $this->hasMany(SalesReturnItem::class);
    }

    public function purchaseReturnItems(): HasMany
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function channelListings(): HasMany
    {
        return $this->hasMany(ProductChannelListing::class);
    }

    public function batches(): HasMany
    {
        return $this->hasMany(ProductBatch::class);
    }

    public function serialNumbers(): HasMany
    {
        return $this->hasMany(ProductSerialNumber::class);
    }

    public function tracksStock(): bool
    {
        return $this->product_sale_mode !== self::SALE_MODE_SERVICE;
    }

    public function allowsDecimalQuantity(): bool
    {
        return in_array($this->product_sale_mode, [self::SALE_MODE_LOOSE, self::SALE_MODE_HYBRID], true);
    }

    public function canBeDeleted(): bool
    {
        if (isset($this->invoice_items_count)
            || isset($this->purchase_items_count)
            || isset($this->sales_return_items_count)
            || isset($this->purchase_return_items_count)
            || isset($this->stock_movements_count)
            || isset($this->channel_listings_count)
        ) {
            return ($this->invoice_items_count ?? 0) === 0
                && ($this->purchase_items_count ?? 0) === 0
                && ($this->sales_return_items_count ?? 0) === 0
                && ($this->purchase_return_items_count ?? 0) === 0
                && ($this->stock_movements_count ?? 0) === 0
                && ($this->channel_listings_count ?? 0) === 0;
        }

        return ! $this->invoiceItems()->exists()
            && ! $this->purchaseItems()->exists()
            && ! $this->salesReturnItems()->exists()
            && ! $this->purchaseReturnItems()->exists()
            && ! $this->stockMovements()->exists()
            && ! $this->channelListings()->exists();
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
            'stock_quantity' => (float) $variants->sum('stock_quantity'),
            'low_stock_alert' => (int) $variants->sum('low_stock_alert'),
            'has_variants' => $variants->count() > 1,
        ]);
    }
}
