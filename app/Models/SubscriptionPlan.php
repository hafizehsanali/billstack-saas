<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'monthly_price_cents',
        'annual_price_cents',
        'user_limit',
        'is_public',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function features(): BelongsToMany
    {
        return $this->belongsToMany(PlanFeature::class, 'feature_plan')
            ->withPivot('limits')
            ->withTimestamps();
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(TenantSubscription::class);
    }

    public function offers(): BelongsToMany
    {
        return $this->belongsToMany(PlatformOffer::class, 'offer_subscription_plan')
            ->withTimestamps();
    }
}
