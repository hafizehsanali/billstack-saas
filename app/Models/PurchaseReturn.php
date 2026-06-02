<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseReturn extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'purchase_id',
        'supplier_id',
        'return_no',
        'return_date',
        'total_amount',
        'notes',
    ];

    protected $casts = [
        'return_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::created(function ($purchaseReturn) {
            if (! $purchaseReturn->return_no) {
                $purchaseReturn->updateQuietly([
                    'return_no' => 'PR-'.str_pad($purchaseReturn->id, 6, '0', STR_PAD_LEFT),
                ]);
            }
        });
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }
}
