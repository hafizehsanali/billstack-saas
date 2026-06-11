<?php

namespace Tests\Feature;

use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use Database\Seeders\SaasPlanSeeder;
use Database\Seeders\SubscriptionLifecycleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionLifecycleCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_pauses_expired_trials_and_subscriptions(): void
    {
        $plan = $this->plan();
        $expiredTrial = $this->subscription($plan, [
            'trial_ends_at' => now()->subMinute(),
            'ends_at' => null,
        ]);
        $expiredAccess = $this->subscription($plan, [
            'trial_ends_at' => null,
            'ends_at' => now()->subMinute(),
        ]);
        $valid = $this->subscription($plan, [
            'trial_ends_at' => null,
            'ends_at' => now()->addDay(),
        ]);

        $this->artisan('subscriptions:expire')
            ->expectsOutput('Paused 2 expired subscription(s).')
            ->assertSuccessful();

        $this->assertSame('paused', $expiredTrial->fresh()->status);
        $this->assertSame('paused', $expiredAccess->fresh()->status);
        $this->assertSame('active', $valid->fresh()->status);
    }

    public function test_lifecycle_demo_seeder_is_repeatable(): void
    {
        $tenant = Tenant::create([
            'name' => 'Greenline Pharmacy',
            'slug' => 'demo-store-3',
        ]);

        $this->seed(SaasPlanSeeder::class);
        $this->seed(SubscriptionLifecycleSeeder::class);
        $this->seed(SubscriptionLifecycleSeeder::class);

        $this->assertSame(1, TenantSubscription::where('tenant_id', $tenant->id)->count());

        $subscription = $tenant->fresh()->currentSubscription;
        $this->assertSame('active', $subscription->status);
        $this->assertTrue($subscription->ends_at->isPast());

        $this->artisan('subscriptions:expire')->assertSuccessful();

        $this->assertSame('paused', $subscription->fresh()->status);
    }

    private function plan(): SubscriptionPlan
    {
        return SubscriptionPlan::create([
            'name' => 'Lifecycle Plan',
            'slug' => 'lifecycle-plan',
            'monthly_price_cents' => 1000,
            'annual_price_cents' => 10000,
        ]);
    }

    private function subscription(SubscriptionPlan $plan, array $dates): TenantSubscription
    {
        $tenant = Tenant::create([
            'name' => fake()->company(),
            'slug' => fake()->unique()->slug(),
        ]);

        return $tenant->subscriptions()->create($dates + [
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subMonth(),
        ]);
    }
}
