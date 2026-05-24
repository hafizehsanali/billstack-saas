<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Purchase extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'supplier_id',
        'purchase_no',
        'subtotal',
        'discount',
        'extra_expense',
        'total',
        'paid_amount',
        'remaining_amount',
        'purchase_date',
        'note',
        'status',
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
}