<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;

class SubscriptionLifecycleSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('slug', 'demo-store-3')->first();
        $subscription = $tenant?->currentSubscription;

        if (! $subscription) {
            return;
        }

        $subscription->update([
            'status' => 'active',
            'starts_at' => now()->subDays(31),
            'trial_ends_at' => null,
            'ends_at' => now()->subDay()->endOfDay(),
        ]);
    }
}
