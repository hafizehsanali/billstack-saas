<?php

namespace App\Services;

use App\Models\TenantSubscription;

class SubscriptionLifecycleService
{
    public function expireEndedSubscriptions(): int
    {
        $expiredCount = 0;

        TenantSubscription::query()
            ->where('status', 'active')
            ->where(function ($query) {
                $query->where('trial_ends_at', '<', now())
                    ->orWhere('ends_at', '<', now());
            })
            ->chunkById(100, function ($subscriptions) use (&$expiredCount): void {
                foreach ($subscriptions as $subscription) {
                    if ($this->pauseIfExpired($subscription)) {
                        $expiredCount++;
                    }
                }
            });

        return $expiredCount;
    }

    public function pauseIfExpired(?TenantSubscription $subscription): bool
    {
        if (
            ! $subscription
            || $subscription->status !== 'active'
            || ! $this->hasExpired($subscription)
        ) {
            return false;
        }

        $subscription->update([
            'status' => 'paused',
            'ends_at' => $subscription->ends_at ?? now(),
        ]);

        return true;
    }

    private function hasExpired(TenantSubscription $subscription): bool
    {
        return ($subscription->trial_ends_at?->isPast() ?? false)
            || ($subscription->ends_at?->isPast() ?? false);
    }
}
