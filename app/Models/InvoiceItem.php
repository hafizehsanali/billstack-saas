<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id',
        'product_id',
        'quantity',
        'price',
        'total',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function returnItems(): HasMany
    {
        return $this->hasMany(SalesReturnItem::class);
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
