<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'name',
        'slug',
        'email',
        'phone',
        'address',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function brands(): HasMany
    {
        return $this->hasMany(Brand::class);
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(TenantSubscription::class)
            ->ofMany(
                ['id' => 'max'],
                fn ($query) => $query->where('status', 'active')
            );
    }

    public function currentSubscription(): HasOne
    {
        return $this->hasOne(TenantSubscription::class)
            ->latestOfMany();
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(TenantSubscription::class);
    }

    public function platformInvoices(): HasMany
    {
        return $this->hasMany(PlatformSubscriptionInvoice::class);
    }

    public function platformPayments(): HasMany
    {
        return $this->hasMany(PlatformSubscriptionPayment::class);
    }
}
