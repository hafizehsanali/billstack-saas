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
}
