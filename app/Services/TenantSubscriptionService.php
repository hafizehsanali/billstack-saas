<?php

namespace App\Services;

use App\Models\PlatformOffer;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;

class TenantSubscriptionService
{
    public function assignDefaultPlan(Tenant $tenant): TenantSubscription
    {
        $plan = SubscriptionPlan::where('slug', 'starter')->first();

        if (! $plan) {
            $plan = SubscriptionPlan::create([
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'Free plan for small teams starting with core billing and inventory.',
                'monthly_price_cents' => 0,
                'annual_price_cents' => 0,
                'user_limit' => 2,
            ]);
        }

        return TenantSubscription::firstOrCreate(
            [
                'tenant_id' => $tenant->id,
                'status' => 'active',
            ],
            [
                'subscription_plan_id' => $plan->id,
                'starts_at' => now(),
            ]
        );
    }

    public function subscribe(Tenant $tenant, SubscriptionPlan $plan): TenantSubscription
    {
        $trialDays = $this->trialDaysFor($plan);
        $isImmediatelyActive = $plan->monthly_price_cents === 0 || $trialDays > 0;

        return TenantSubscription::create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $plan->id,
            'status' => $isImmediatelyActive ? 'active' : 'paused',
            'starts_at' => now(),
            'trial_ends_at' => $trialDays > 0 ? now()->addDays($trialDays) : null,
        ]);
    }

    public function trialDaysFor(SubscriptionPlan $plan): int
    {
        return PlatformOffer::query()
            ->where('is_active', true)
            ->where('trial_days', '>', 0)
            ->where(function ($query): void {
                $query->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function ($query): void {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now());
            })
            ->where(function ($query): void {
                $query->whereNull('redemption_limit')
                    ->orWhereColumn('redeemed_count', '<', 'redemption_limit');
            })
            ->where(function ($query) use ($plan): void {
                $query->whereDoesntHave('plans')
                    ->orWhereHas('plans', fn ($planQuery) => $planQuery->whereKey($plan->id));
            })
            ->max('trial_days') ?? 0;
    }
}
