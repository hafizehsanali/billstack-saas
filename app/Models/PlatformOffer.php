<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PlatformOffer extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'discount_type',
        'discount_value',
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
