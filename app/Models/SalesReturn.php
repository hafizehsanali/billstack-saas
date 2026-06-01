<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesReturn extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'invoice_id',
        'customer_id',
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
        static::created(function ($salesReturn) {
            if (! $salesReturn->return_no) {
                $salesReturn->updateQuietly([
                    'return_no' => 'SR-'.str_pad($salesReturn->id, 6, '0', STR_PAD_LEFT),
                ]);
            }
        });
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesReturnItem::class);
    }
}
