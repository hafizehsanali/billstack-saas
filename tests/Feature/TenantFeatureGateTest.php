<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\BusinessPreset;
use App\Models\PlanFeature;
use App\Models\Product;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantFeatureService;
use Database\Seeders\BusinessPresetSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TenantFeatureGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_feature_service_detects_features_on_active_plan(): void
    {
        [$tenant] = $this->tenantWithPlan(hasBarcode: true);

        $this->assertTrue(app(TenantFeatureService::class)->hasFeature($tenant, 'pro.barcode'));
        $this->assertFalse(app(TenantFeatureService::class)->hasFeature($tenant, 'pro.ai_insights'));
    }

    public function test_unavailable_feature_page_is_shown_for_missing_feature(): void
    {
        [, $user] = $this->tenantWithPlan(hasBarcode: false);

        $this->actingAs($user)
            ->get(route('features.unavailable', ['feature' => 'pro.barcode']))
            ->assertOk()
            ->assertSee('Feature Not Available')
            ->assertSee('Barcode scanning');
    }

    public function test_product_index_shows_scanner_status_for_plan_feature(): void
    {
        [$tenant, $user] = $this->tenantWithPlan(hasBarcode: true);

        $category = Category::forceCreate([
            'tenant_id' => $tenant->id,
            'name' => 'General',
        ]);

        Product::forceCreate([
            'tenant_id' => $tenant->id,
            'category_id' => $category->id,
            'name' => 'Scanner Product',
            'sku' => 'SCAN-001',
            'barcode' => '123456',
            'purchase_price' => 100,
            'selling_price' => 150,
            'stock_quantity' => 5,
            'low_stock_alert' => 2,
        ]);

        $this->actingAs($user)
            ->get(route('products.index'))
            ->assertOk()
            ->assertSee('Scanner Enabled');
    }

    public function test_product_index_links_missing_scanner_feature_to_upgrade_notice(): void
    {
        [, $user] = $this->tenantWithPlan(hasBarcode: false);

        $this->actingAs($user)
            ->get(route('products.index'))
            ->assertOk()
            ->assertSee('Barcode Scanner')
            ->assertSee(route('features.unavailable', ['feature' => 'pro.barcode']), false);
    }

    public function test_pending_upgrade_does_not_change_features_before_payment(): void
    {
        [$tenant, $user, $currentPlan] = $this->tenantWithPlan(hasBarcode: true);
        $pendingPlan = SubscriptionPlan::create([
            'name' => 'Pending Basic',
            'slug' => 'pending-basic',
            'monthly_price_cents' => 199900,
            'annual_price_cents' => 1999000,
        ]);
        $tenant->subscriptions()->create([
            'subscription_plan_id' => $pendingPlan->id,
            'status' => 'paused',
            'starts_at' => now(),
        ]);

        $tenant->refresh();

        $this->assertSame($currentPlan->id, $tenant->activeSubscription?->subscription_plan_id);
        $this->assertTrue(app(TenantFeatureService::class)->hasFeature($tenant, 'pro.barcode'));

        $this->actingAs($user)
            ->get(route('barcode.index'))
            ->assertOk();
    }

    private function tenantWithPlan(bool $hasBarcode): array
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(BusinessPresetSeeder::class);
        $generalStore = BusinessPreset::where('slug', BusinessPreset::GENERAL_STORE)->firstOrFail();

        $tenant = Tenant::create([
            'name' => fake()->company(),
            'slug' => fake()->unique()->slug(),
            'business_preset_id' => $generalStore->id,
        ]);

        $barcode = PlanFeature::create([
            'name' => 'Barcode scanning',
            'key' => 'pro.barcode',
            'is_paid' => true,
        ]);

        PlanFeature::create([
            'name' => 'AI business insights',
            'key' => 'pro.ai_insights',
            'is_paid' => true,
        ]);

        $plan = SubscriptionPlan::create([
            'name' => $hasBarcode ? 'Growth' : 'Starter',
            'slug' => fake()->unique()->slug(),
            'monthly_price_cents' => $hasBarcode ? 299900 : 0,
            'annual_price_cents' => $hasBarcode ? 2999000 : 0,
            'user_limit' => $hasBarcode ? 8 : 2,
        ]);

        if ($hasBarcode) {
            $plan->features()->sync([$barcode->id]);
        }

        $tenant->subscriptions()->create([
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now(),
        ]);

        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        Role::findOrCreate('owner');
        $user->assignRole('owner');

        return [$tenant, $user, $plan];
    }
}
