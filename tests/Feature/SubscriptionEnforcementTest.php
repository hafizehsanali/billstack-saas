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
