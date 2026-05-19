<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'category_id',
        'name',
        'sku',
        'barcode',
        'purchase_price',
        'selling_price',
        'stock_quantity',
        'low_stock_alert',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}