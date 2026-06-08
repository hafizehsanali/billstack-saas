<?php

namespace Tests\Feature;

use App\Models\PlanFeature;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SaasPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PlatformAdminFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_assigns_owner_role_and_starter_subscription(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(SaasPlanSeeder::class);

        $this->post('/register', [
            'name' => 'Store Owner',
            'business_name' => 'New Store',
            'email' => 'owner@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertRedirect(route('dashboard', absolute: false));

        $user = User::where('email', 'owner@example.com')->firstOrFail();
        $tenant = $user->tenant()->firstOrFail();

        $this->assertTrue($user->hasRole('owner'));
        $this->assertDatabaseHas('tenant_subscriptions', [
            'tenant_id' => $tenant->id,
            'status' => 'active',
        ]);
        $this->assertSame('starter', $tenant->activeSubscription->plan->slug);
    }

    public function test_platform_dashboard_is_only_available_to_platform_admins(): void
    {
        $tenant = Tenant::create(['name' => 'Demo Store', 'slug' => 'demo-store']);
        Role::findOrCreate('owner');

        $storeUser = User::factory()->create(['tenant_id' => $tenant->id]);
        $storeUser->assignRole('owner');

        $platformAdmin = User::factory()->create([
            'tenant_id' => null,
            'is_platform_admin' => true,
        ]);

        $this->actingAs($storeUser)
            ->get(route('platform.dashboard'))
            ->assertForbidden();

        $this->actingAs($platformAdmin)
            ->get(route('platform.dashboard'))
            ->assertOk()
            ->assertSee('Platform Admin')
            ->assertSee('Demo Store');
    }

    public function test_platform_admin_is_sent_to_platform_dashboard_after_login(): void
    {
        $admin = User::factory()->create([
            'email' => 'platform@example.com',
            'password' => 'password',
            'tenant_id' => null,
            'is_platform_admin' => true,
        ]);

        $this->post(route('login'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('platform.dashboard', absolute: false));
    }

    public function test_saas_plan_seeder_creates_default_features_and_subscriptions(): void
    {
        $tenant = Tenant::create(['name' => 'Seeded Store', 'slug' => 'seeded-store']);

        $this->seed(SaasPlanSeeder::class);

        $this->assertDatabaseHas('subscription_plans', ['slug' => 'starter']);
        $this->assertDatabaseHas('subscription_plans', ['slug' => 'growth']);
        $this->assertGreaterThanOrEqual(7, PlanFeature::count());
        $this->assertSame(1, TenantSubscription::where('tenant_id', $tenant->id)->count());

        $starter = SubscriptionPlan::where('slug', 'starter')->firstOrFail();
        $this->assertTrue($starter->features()->where('is_paid', false)->exists());
        $this->assertFalse($starter->features()->where('is_paid', true)->exists());
    }
}
