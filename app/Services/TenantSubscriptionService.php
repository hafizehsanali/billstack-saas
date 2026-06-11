<?php

namespace App\Services;

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
                'trial_days' => 0,
                'free_access_days' => 30,
                'product_limit' => 100,
                'monthly_invoice_limit' => 100,
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
                'ends_at' => $plan->free_access_days
                    ? now()->addDays($plan->free_access_days)
                    : null,
            ]
        );
    }

    public function subscribe(Tenant $tenant, SubscriptionPlan $plan): TenantSubscription
    {
        $trialDays = $plan->monthly_price_cents > 0 ? $plan->trial_days : 0;
        $freeAccessDays = $plan->monthly_price_cents === 0 ? $plan->free_access_days : null;
        $isImmediatelyActive = $plan->monthly_price_cents === 0 || $trialDays > 0;

        return TenantSubscription::create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $plan->id,
            'status' => $isImmediatelyActive ? 'active' : 'paused',
            'starts_at' => now(),
            'trial_ends_at' => $trialDays > 0 ? now()->addDays($trialDays) : null,
            'ends_at' => $freeAccessDays ? now()->addDays($freeAccessDays) : null,
        ]);
    }
}
