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
        'platform_offer_id',
        'offer_code',
        'invoice_no',
        'billing_period',
        'billing_cycle',
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

    public function paymentSubmission()
    {
        return $this->hasOne(
            SubscriptionPaymentSubmission::class,
            'platform_subscription_invoice_id'
        );
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(PlatformOffer::class, 'platform_offer_id');
    }
}
