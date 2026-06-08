<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlatformSubscriptionInvoice extends Model
{
    protected $fillable = [
        'tenant_id',
        'tenant_subscription_id',
        'invoice_no',
        'billing_period',
        'subtotal_cents',
        'discount_cents',
        'tax_cents',
        'total_cents',
        'paid_cents',
        'balance_cents',
        'status',
        'issued_on',
        'due_on',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'issued_on' => 'date',
            'due_on' => 'date',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(TenantSubscription::class, 'tenant_subscription_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PlatformSubscriptionPayment::class);
    }
}
