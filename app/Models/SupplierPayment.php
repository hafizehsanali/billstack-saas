<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierPayment extends Model
{
    use BelongsToTenant, SoftDeletes;
    protected $fillable = [
        'tenant_id',
        'supplier_id',
        'purchase_id',
        'amount',
        'payment_method',
        'payment_date',
        'reference_no',
        'notes',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }
}