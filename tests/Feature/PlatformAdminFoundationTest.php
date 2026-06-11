<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\PlanFeature;
use App\Models\Product;
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

    public function test_platform_tenant_list_shows_usage_for_each_business(): void
    {
        Role::findOrCreate('owner');

        $tenant = Tenant::create(['name' => 'Usage Store', 'slug' => 'usage-store']);
        $owner = User::factory()->create(['tenant_id' => $tenant->id]);
        $owner->assignRole('owner');
        $plan = SubscriptionPlan::create([
            'name' => 'Usage Plan',
            'slug' => 'usage-plan',
            'monthly_price_cents' => 1000,
            'annual_price_cents' => 10000,
            'product_limit' => 10,
            'monthly_invoice_limit' => 20,
        ]);
        $tenant->subscriptions()->create([
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now(),
        ]);

        $this->actingAs($owner);
        $category = Category::create(['name' => 'Platform Usage Category']);
        Product::create([
            'category_id' => $category->id,
            'name' => 'Platform Usage Product',
            'sku' => 'PLATFORM-USAGE-001',
            'purchase_price' => 100,
            'selling_price' => 150,
            'stock_quantity' => 10,
            'low_stock_alert' => 2,
        ]);
        $customer = Customer::create(['name' => 'Platform Usage Customer']);
        Invoice::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'invoice_no' => 'PLATFORM-USAGE-INV-001',
            'sale_date' => now()->toDateString(),
        ]);

        $admin = User::factory()->create([
            'tenant_id' => null,
            'is_platform_admin' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('platform.tenants.index'))
            ->assertOk()
            ->assertSee('Products:')
            ->assertSee('1/10')
            ->assertSee('Invoices this month:')
            ->assertSee('1/20');
    }
}
