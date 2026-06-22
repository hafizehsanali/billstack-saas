<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    protected $fillable = [
        'purchase_id',
        'product_id',
        'product_variant_id',
        'unit_id',
        'unit_factor',
        'quantity',
        'base_quantity',
        'purchase_price',
        'line_total',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function returnItems()
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }

    public function returnedQuantity(): int
    {
        return (int) $this->returnItems()->sum('quantity');
    }

    public function returnableQuantity(): int
    {
        return max($this->quantity - $this->returnedQuantity(), 0);
    }
}
