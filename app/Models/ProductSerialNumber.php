<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductSerialNumber extends Model
{
    use BelongsToTenant;

    public const STATUS_AVAILABLE = 'available';
    public const STATUS_SOLD = 'sold';

    protected $fillable = [
        'tenant_id',
        'product_id',
        'product_variant_id',
        'serial_number',
        'status',
        'purchase_item_id',
        'invoice_item_id',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
