<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;

class TenantFeatureService
{
    public function hasFeature(?Tenant $tenant, string $featureKey): bool
    {
        if (! $tenant?->activeSubscription?->plan) {
            return false;
        }

        return $tenant->activeSubscription
            ->plan
            ->features()
            ->where('key', $featureKey)
            ->exists();
    }

    public function userHasFeature(?User $user, string $featureKey): bool
    {
        if (! $user || $user->isPlatformAdmin()) {
            return true;
        }

        return $this->hasFeature($user->tenant, $featureKey);
    }
}
