<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Purchase extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'supplier_id',
        'purchase_no',
        'purchase_date',
        'subtotal',
        'discount',
        'extra_expense',
        'total',
        'paid_amount',
        'remaining_amount',
        'status',
        'notes',
    ];

    protected $casts = [
        'purchase_date' => 'date',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function payments()
    {
        return $this->hasMany(SupplierPayment::class);
    }

    public function returns()
    {
        return $this->hasMany(PurchaseReturn::class);
    }

    public function canBeEdited(): bool
    {
        if (! $this->canBeCancelled()) {
            return false;
        }

        $this->loadMissing('items.product');

        return $this->items->every(
            fn (PurchaseItem $item) => $item->product
                && $item->product->stock_quantity >= $item->quantity
        );
    }

    public function canBeCancelled(): bool
    {
        return $this->status === 'unpaid'
            && ! $this->payments()->exists()
            && ! $this->returns()->exists();
    }
}
