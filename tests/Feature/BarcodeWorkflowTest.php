<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\PlanFeature;
use App\Models\Product;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BarcodeWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_growth_plan_user_can_open_barcode_scanner_and_lookup_product(): void
    {
        [$tenant, $user] = $this->tenantUserWithBarcodeFeature(true);
        $product = $this->productForTenant($tenant, '123456');

        $this->actingAs($user)
            ->get(route('barcode.index'))
            ->assertOk()
            ->assertSee('Barcode Scanner');

        $this->actingAs($user)
            ->postJson(route('barcode.lookup'), ['barcode' => '123456'])
            ->assertOk()
            ->assertJson([
                'id' => $product->id,
                'name' => $product->name,
                'barcode' => '123456',
            ]);
    }

    public function test_starter_plan_user_is_redirected_from_barcode_scanner(): void
    {
        [, $user] = $this->tenantUserWithBarcodeFeature(false);

        $this->actingAs($user)
            ->get(route('barcode.index'))
            ->assertRedirect(route('features.unavailable', ['feature' => 'pro.barcode']));
    }

    public function test_barcode_lookup_only_returns_products_from_current_tenant(): void
    {
        [$tenant, $user] = $this->tenantUserWithBarcodeFeature(true);
        [$otherTenant] = $this->tenantUserWithBarcodeFeature(true);

        $this->productForTenant($otherTenant, 'FOREIGN');

        $this->actingAs($user)
            ->postJson(route('barcode.lookup'), ['barcode' => 'FOREIGN'])
            ->assertNotFound();
    }

    public function test_sidebar_hides_barcode_link_without_feature(): void
    {
        [, $user] = $this->tenantUserWithBarcodeFeature(false);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Barcode Scanner');
    }

    private function tenantUserWithBarcodeFeature(bool $hasBarcode): array
    {
        $this->seed(RolePermissionSeeder::class);

        $tenant = Tenant::create([
            'name' => fake()->company(),
            'slug' => fake()->unique()->slug(),
        ]);

        $barcode = PlanFeature::firstOrCreate(
            ['key' => 'pro.barcode'],
            ['name' => 'Barcode scanning', 'is_paid' => true]
        );

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

    private function productForTenant(Tenant $tenant, string $barcode): Product
    {
        $category = Category::forceCreate([
            'tenant_id' => $tenant->id,
            'name' => 'General',
        ]);

        return Product::forceCreate([
            'tenant_id' => $tenant->id,
            'category_id' => $category->id,
            'name' => 'Barcode Product',
            'sku' => fake()->unique()->bothify('SKU-###'),
            'barcode' => $barcode,
            'purchase_price' => 100,
            'selling_price' => 150,
            'stock_quantity' => 5,
            'low_stock_alert' => 2,
        ]);
    }
}
