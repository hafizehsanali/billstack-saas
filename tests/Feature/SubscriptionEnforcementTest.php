<?php

namespace Tests\Feature;

use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SubscriptionEnforcementTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_tenant_can_access_business_dashboard(): void
    {
        [$tenant, $user, $plan] = $this->tenantUserAndPlan();

        $tenant->subscriptions()->create([
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_inactive_tenant_is_redirected_to_subscription_status(): void
    {
        [$tenant, $user, $plan] = $this->tenantUserAndPlan();

        $tenant->subscriptions()->create([
            'subscription_plan_id' => $plan->id,
            'status' => 'paused',
            'starts_at' => now()->subMonth(),
            'ends_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('subscription.status'));
    }

    public function test_inactive_tenant_can_view_subscription_status_page(): void
    {
        [$tenant, $user, $plan] = $this->tenantUserAndPlan();

        $tenant->subscriptions()->create([
            'subscription_plan_id' => $plan->id,
            'status' => 'cancelled',
            'starts_at' => now()->subMonth(),
            'ends_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('subscription.status'))
            ->assertOk()
            ->assertSee('Subscription Attention Required')
            ->assertSee('cancelled');
    }

    public function test_expired_free_access_is_paused(): void
    {
        [$tenant, $user, $plan] = $this->tenantUserAndPlan();

        $subscription = $tenant->subscriptions()->create([
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subDays(31),
            'ends_at' => now()->subDay(),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('subscription.status'));

        $this->assertSame('paused', $subscription->fresh()->status);
    }

    public function test_pending_upgrade_does_not_remove_access_from_current_active_plan(): void
    {
        [$tenant, $user, $currentPlan] = $this->tenantUserAndPlan();
        $currentSubscription = $tenant->subscriptions()->create([
            'subscription_plan_id' => $currentPlan->id,
            'status' => 'active',
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->addMonth(),
        ]);
        $upgradePlan = SubscriptionPlan::create([
            'name' => 'Growth',
            'slug' => 'growth',
            'monthly_price_cents' => 299900,
            'annual_price_cents' => 2999000,
            'user_limit' => 8,
            'is_public' => true,
            'is_active' => true,
        ]);
        $tenant->subscriptions()->create([
            'subscription_plan_id' => $upgradePlan->id,
            'status' => 'paused',
            'starts_at' => now(),
        ]);

        $tenant->refresh();
        $this->assertSame($currentSubscription->id, $tenant->activeSubscription?->id);
        $this->assertSame($upgradePlan->id, $tenant->currentSubscription?->subscription_plan_id);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_expired_active_plan_is_paused_even_when_newer_upgrade_is_pending(): void
    {
        [$tenant, $user, $currentPlan] = $this->tenantUserAndPlan();
        $currentSubscription = $tenant->subscriptions()->create([
            'subscription_plan_id' => $currentPlan->id,
            'status' => 'active',
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->subDay(),
        ]);
        $upgradePlan = SubscriptionPlan::create([
            'name' => 'Growth',
            'slug' => 'growth-expiry-test',
            'monthly_price_cents' => 299900,
            'annual_price_cents' => 2999000,
        ]);
        $tenant->subscriptions()->create([
            'subscription_plan_id' => $upgradePlan->id,
            'status' => 'paused',
            'starts_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('subscription.status'));

        $this->assertSame('paused', $currentSubscription->fresh()->status);
    }

    public function test_platform_admin_can_still_access_platform_area(): void
    {
        $admin = User::factory()->create([
            'tenant_id' => null,
            'is_platform_admin' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('platform.dashboard'))
            ->assertOk();
    }

    private function tenantUserAndPlan(): array
    {
        $tenant = Tenant::create([
            'name' => 'Demo Store',
            'slug' => fake()->unique()->slug(),
        ]);

        $plan = SubscriptionPlan::create([
            'name' => 'Starter',
            'slug' => fake()->unique()->slug(),
            'monthly_price_cents' => 0,
            'annual_price_cents' => 0,
            'user_limit' => 2,
        ]);

        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        Role::findOrCreate('owner');
        $user->assignRole('owner');

        return [$tenant, $user, $plan];
    }
}
