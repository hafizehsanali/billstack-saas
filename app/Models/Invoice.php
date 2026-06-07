<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'invoice_no',
        'sale_date',
        'subtotal',
        'tax',
        'discount',
        'extra_expense',
        'total',
        'paid_amount',
        'remaining_amount',
        'status',
        'notes',
    ];

    protected static function booted(): void
    {
        static::created(function ($invoice) {
            if (! $invoice->invoice_no) {
                $invoice->updateQuietly([
                    'invoice_no' => 'INV-'.str_pad($invoice->id, 6, '0', STR_PAD_LEFT),
                ]);
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments()
    {
        return $this->hasMany(CustomerPayment::class);
    }

    public function returns(): HasMany
    {
        return $this->hasMany(SalesReturn::class);
    }

    public function canBeCancelled(): bool
    {
        return $this->status === 'unpaid'
            && ! $this->payments()->exists()
            && ! $this->returns()->exists();
    }
}
