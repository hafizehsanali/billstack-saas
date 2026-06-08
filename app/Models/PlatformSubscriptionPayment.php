<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformSubscriptionPayment extends Model
{
    protected $fillable = [
        'platform_subscription_invoice_id',
        'tenant_id',
        'amount_cents',
        'payment_method',
        'reference_no',
        'paid_on',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'paid_on' => 'date',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(PlatformSubscriptionInvoice::class, 'platform_subscription_invoice_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
