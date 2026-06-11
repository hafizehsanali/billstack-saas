<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlatformOffer extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'discount_type',
        'discount_value',
        'billing_cycle',
        'trial_days',
        'redemption_limit',
        'redeemed_count',
        'starts_at',
        'ends_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(SubscriptionPlan::class, 'offer_subscription_plan')
            ->withTimestamps();
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(PlatformSubscriptionInvoice::class);
    }

    public function appliesTo(SubscriptionPlan $plan): bool
    {
        return $this->plans->isEmpty() || $this->plans->contains('id', $plan->id);
    }

    public function appliesToBillingCycle(string $billingCycle): bool
    {
        return $this->billing_cycle === 'both' || $this->billing_cycle === $billingCycle;
    }

    public function discountFor(int $subtotalCents): int
    {
        $discount = $this->discount_type === 'percent'
            ? (int) round($subtotalCents * ($this->discount_value / 100))
            : $this->discount_value;

        return min($discount, $subtotalCents);
    }

    public function isCurrentlyAvailable(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->ends_at && $this->ends_at->isPast()) {
            return false;
        }

        return $this->redemption_limit === null || $this->redeemed_count < $this->redemption_limit;
    }
}
