<?php

namespace Tests\Feature;

use App\Models\BusinessModule;
use App\Models\BusinessPreset;
use App\Models\Category;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\Unit;
use App\Models\User;
use App\Services\TenantModuleService;
use Database\Seeders\BusinessPresetSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BusinessPresetModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_preset_seeder_creates_default_modules(): void
    {
        $this->seed(BusinessPresetSeeder::class);

        $generalStore = BusinessPreset::where('slug', BusinessPreset::GENERAL_STORE)->firstOrFail();
        $pharmacy = BusinessPreset::where('slug', BusinessPreset::PHARMACY)->firstOrFail();

        $this->assertTrue($generalStore->modules()->where('key', BusinessModule::BILLING)->exists());
        $this->assertFalse($generalStore->modules()->where('key', BusinessModule::BATCH_EXPIRY)->exists());
        $this->assertTrue($pharmacy->modules()->where('key', BusinessModule::BATCH_EXPIRY)->exists());
    }

    public function test_tenant_module_service_combines_preset_and_manual_overrides(): void
    {
        $this->seed(BusinessPresetSeeder::class);

        $generalStore = BusinessPreset::where('slug', BusinessPreset::GENERAL_STORE)->firstOrFail();
        $batchExpiry = BusinessModule::where('key', BusinessModule::BATCH_EXPIRY)->firstOrFail();
        $billing = BusinessModule::where('key', BusinessModule::BILLING)->firstOrFail();
        $tenant = Tenant::create([
            'name' => 'Module Store',
            'slug' => 'module-store',
            'business_preset_id' => $generalStore->id,
        ]);

        $this->assertFalse(app(TenantModuleService::class)->hasModule($tenant, BusinessModule::BATCH_EXPIRY));
        $this->assertTrue(app(TenantModuleService::class)->hasModule($tenant, BusinessModule::BILLING));

        $tenant->businessModuleOverrides()->create([
            'business_module_id' => $batchExpiry->id,
            'is_enabled' => true,
        ]);
        $tenant->businessModuleOverrides()->create([
            'business_module_id' => $billing->id,
            'is_enabled' => false,
        ]);

        $tenant->refresh();

        $this->assertTrue(app(TenantModuleService::class)->hasModule($tenant, BusinessModule::BATCH_EXPIRY));
        $this->assertFalse(app(TenantModuleService::class)->hasModule($tenant, BusinessModule::BILLING));
    }

    public function test_business_owner_cannot_update_business_type_from_settings(): void
    {
        $this->seed(BusinessPresetSeeder::class);

        $generalStore = BusinessPreset::where('slug', BusinessPreset::GENERAL_STORE)->firstOrFail();
        $hardware = BusinessPreset::where('slug', BusinessPreset::HARDWARE)->firstOrFail();
        $tenant = Tenant::create([
            'name' => 'Old Store',
            'slug' => 'old-store',
            'business_preset_id' => $generalStore->id,
        ]);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        Role::findOrCreate('owner');
        $user->assignRole('owner');

        $this->actingAs($user)
            ->put(route('settings.business.update'), [
                'name' => 'Updated Store',
                'business_preset_id' => $hardware->id,
                'email' => 'store@example.com',
                'phone' => '03000000000',
                'address' => 'Main Market',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'business_preset_id' => $generalStore->id,
        ]);
    }

    public function test_platform_admin_can_adjust_tenant_modules(): void
    {
        $this->seed(BusinessPresetSeeder::class);

        $generalStore = BusinessPreset::where('slug', BusinessPreset::GENERAL_STORE)->firstOrFail();
        $pharmacy = BusinessPreset::where('slug', BusinessPreset::PHARMACY)->firstOrFail();
        $batchExpiry = BusinessModule::where('key', BusinessModule::BATCH_EXPIRY)->firstOrFail();
        $billing = BusinessModule::where('key', BusinessModule::BILLING)->firstOrFail();
        $tenant = Tenant::create([
            'name' => 'Platform Store',
            'slug' => 'platform-store',
            'business_preset_id' => $generalStore->id,
        ]);
        $plan = SubscriptionPlan::create([
            'name' => 'Starter',
            'slug' => 'starter',
        ]);
        $tenant->subscriptions()->create([
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now(),
        ]);
        $admin = User::factory()->create([
            'tenant_id' => null,
            'is_platform_admin' => true,
        ]);

        $this->actingAs($admin)
            ->put(route('platform.tenants.update', $tenant), [
                'subscription_plan_id' => $plan->id,
                'business_preset_id' => $pharmacy->id,
                'enabled_module_ids' => [$billing->id, $batchExpiry->id],
                'status' => 'active',
            ])
            ->assertRedirect(route('platform.tenants.index'));

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'business_preset_id' => $pharmacy->id,
        ]);
        $this->assertDatabaseHas('tenant_business_modules', [
            'tenant_id' => $tenant->id,
            'business_module_id' => $batchExpiry->id,
            'is_enabled' => true,
        ]);
    }

    public function test_product_form_only_shows_modes_enabled_for_business_type(): void
    {
        [$generalTenant, $generalUser] = $this->tenantForPreset(BusinessPreset::GENERAL_STORE);
        [$pharmacyTenant, $pharmacyUser] = $this->tenantForPreset(BusinessPreset::PHARMACY);
        Category::create(['tenant_id' => $generalTenant->id, 'name' => 'General']);
        Category::create(['tenant_id' => $pharmacyTenant->id, 'name' => 'Medicine']);
        Unit::create(['tenant_id' => $generalTenant->id, 'name' => 'Piece', 'symbol' => 'pc']);
        Unit::create(['tenant_id' => $pharmacyTenant->id, 'name' => 'Piece', 'symbol' => 'pc']);

        $this->actingAs($generalUser)
            ->get(route('products.create'))
            ->assertOk()
            ->assertSee('Loose item')
            ->assertSee('Packed item')
            ->assertDontSee('Batch/expiry item')
            ->assertDontSee('Track expiry date');

        $this->actingAs($pharmacyUser)
            ->get(route('products.create'))
            ->assertOk()
            ->assertSee('Batch/expiry item')
            ->assertSee('Track expiry date');
    }

    public function test_sidebar_hides_module_specific_links(): void
    {
        [, $generalUser] = $this->tenantForPreset(BusinessPreset::GENERAL_STORE);
        [, $pharmacyUser] = $this->tenantForPreset(BusinessPreset::PHARMACY);

        $this->actingAs($generalUser)
            ->get(route('products.index'))
            ->assertOk()
            ->assertDontSee('Expiring Stock');

        $this->actingAs($pharmacyUser)
            ->get(route('products.index'))
            ->assertOk()
            ->assertSee('Expiring Stock');
    }

    public function test_disabled_module_direct_urls_are_forbidden(): void
    {
        [, $serviceUser] = $this->tenantForPreset(BusinessPreset::SERVICES);
        [, $generalUser] = $this->tenantForPreset(BusinessPreset::GENERAL_STORE);

        $this->actingAs($serviceUser)
            ->get(route('products.index'))
            ->assertForbidden();

        $this->actingAs($generalUser)
            ->get(route('reports.expiring-stock'))
            ->assertForbidden();
    }

    public function test_team_management_module_controls_team_urls(): void
    {
        [$tenant, $user] = $this->tenantForPreset(BusinessPreset::GENERAL_STORE);
        $teamManagement = BusinessModule::where('key', BusinessModule::TEAM_MANAGEMENT)->firstOrFail();

        $this->actingAs($user)
            ->get(route('team.index'))
            ->assertOk()
            ->assertSee('Team Users');

        $tenant->businessModuleOverrides()->create([
            'business_module_id' => $teamManagement->id,
            'is_enabled' => false,
        ]);
        $user->setRelation('tenant', $tenant->fresh());

        $this->actingAs($user)
            ->get(route('team.index'))
            ->assertForbidden();
    }

    private function tenantForPreset(string $presetSlug): array
    {
        $this->seed(BusinessPresetSeeder::class);
        Role::findOrCreate('owner');

        $preset = BusinessPreset::where('slug', $presetSlug)->firstOrFail();
        $tenant = Tenant::create([
            'name' => str($presetSlug)->headline().' Store',
            'slug' => fake()->unique()->slug(),
            'business_preset_id' => $preset->id,
        ]);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->assignRole('owner');
        $plan = SubscriptionPlan::create([
            'name' => str($presetSlug)->headline().' Plan',
            'slug' => fake()->unique()->slug(),
        ]);
        $tenant->subscriptions()->create([
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now(),
        ]);

        return [$tenant, $user];
    }
}
