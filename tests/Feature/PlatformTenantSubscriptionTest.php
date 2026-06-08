<?php

namespace Tests\Feature;

use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PlatformTenantSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_change_tenant_subscription_plan(): void
    {
        [$admin, $tenant, $starter, $growth] = $this->subscriptionScenario();

        $tenant->subscriptions()->create([
            'subscription_plan_id' => $starter->id,
            'status' => 'active',
            'starts_at' => now(),
        ]);

        $this->actingAs($admin)
            ->put(route('platform.tenants.update', $tenant), [
                'subscription_plan_id' => $growth->id,
                'status' => 'active',
                'trial_ends_at' => now()->addDays(14)->format('Y-m-d'),
                'ends_at' => null,
            ])
            ->assertRedirect(route('platform.tenants.index'));

        $subscription = $tenant->fresh()->currentSubscription;

        $this->assertSame($growth->id, $subscription->subscription_plan_id);
        $this->assertSame('active', $subscription->status);
        $this->assertNotNull($subscription->trial_ends_at);
    }

    public function test_platform_admin_can_pause_tenant_subscription(): void
    {
        [$admin, $tenant, $starter] = $this->subscriptionScenario();

        $tenant->subscriptions()->create([
            'subscription_plan_id' => $starter->id,
            'status' => 'active',
            'starts_at' => now(),
        ]);

        $this->actingAs($admin)
            ->put(route('platform.tenants.update', $tenant), [
                'subscription_plan_id' => $starter->id,
                'status' => 'paused',
                'trial_ends_at' => null,
                'ends_at' => now()->format('Y-m-d'),
            ])
            ->assertRedirect(route('platform.tenants.index'));

        $this->assertSame('paused', $tenant->fresh()->currentSubscription->status);
        $this->assertNull($tenant->fresh()->activeSubscription);
    }

    public function test_store_user_cannot_manage_tenant_subscriptions(): void
    {
        [$admin, $tenant, $starter] = $this->subscriptionScenario();
        $storeUser = User::factory()->create(['tenant_id' => $tenant->id]);

        Role::findOrCreate('owner');
        $storeUser->assignRole('owner');

        $this->actingAs($storeUser)
            ->get(route('platform.tenants.index'))
            ->assertForbidden();
    }

    private function subscriptionScenario(): array
    {
        $admin = User::factory()->create([
            'tenant_id' => null,
            'is_platform_admin' => true,
        ]);

        $tenant = Tenant::create([
            'name' => 'Demo Store',
            'slug' => 'demo-store',
        ]);

        $starter = SubscriptionPlan::create([
            'name' => 'Starter',
            'slug' => 'starter',
            'monthly_price_cents' => 0,
            'annual_price_cents' => 0,
            'user_limit' => 2,
        ]);

        $growth = SubscriptionPlan::create([
            'name' => 'Growth',
            'slug' => 'growth',
            'monthly_price_cents' => 299900,
            'annual_price_cents' => 2999000,
            'user_limit' => 8,
        ]);

        return [$admin, $tenant, $starter, $growth];
    }
}
