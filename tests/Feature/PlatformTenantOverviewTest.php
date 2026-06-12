<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\PlatformSubscriptionInvoice;
use App\Models\Product;
use App\Models\SubscriptionPaymentSubmission;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PlatformTenantOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_review_complete_tenant_overview(): void
    {
        [$tenant, $owner, $plan, $subscription] = $this->tenantScenario('Overview Store');

        $this->actingAs($owner);
        $category = Category::create(['name' => 'Overview Category']);
        Product::create([
            'category_id' => $category->id,
            'name' => 'Overview Product',
            'sku' => 'OVERVIEW-001',
            'purchase_price' => 100,
            'selling_price' => 150,
            'stock_quantity' => 10,
            'low_stock_alert' => 2,
        ]);
        $customer = Customer::create(['name' => 'Overview Customer']);
        Invoice::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'invoice_no' => 'OVERVIEW-SALE-001',
            'sale_date' => today(),
        ]);

        $platformInvoice = PlatformSubscriptionInvoice::create([
            'tenant_id' => $tenant->id,
            'tenant_subscription_id' => $subscription->id,
            'invoice_no' => 'OVERVIEW-PLAT-001',
            'billing_period' => now()->format('F Y'),
            'billing_cycle' => 'monthly',
            'subtotal_cents' => 299900,
            'discount_cents' => 0,
            'tax_cents' => 0,
            'total_cents' => 299900,
            'paid_cents' => 0,
            'balance_cents' => 299900,
            'status' => 'unpaid',
            'issued_on' => today(),
            'due_on' => today()->addDays(3),
        ]);
        SubscriptionPaymentSubmission::create([
            'platform_subscription_invoice_id' => $platformInvoice->id,
            'tenant_id' => $tenant->id,
            'submitted_by' => $owner->id,
            'payment_method' => 'bank_transfer',
            'reference_no' => 'OVERVIEW-REF-001',
            'paid_on' => today(),
            'status' => 'pending',
        ]);

        [, $otherOwner, , $otherSubscription] = $this->tenantScenario('Other Store');
        PlatformSubscriptionInvoice::create([
            'tenant_id' => $otherOwner->tenant_id,
            'tenant_subscription_id' => $otherSubscription->id,
            'invoice_no' => 'OTHER-PLAT-001',
            'billing_period' => now()->format('F Y'),
            'billing_cycle' => 'monthly',
            'subtotal_cents' => 1000,
            'discount_cents' => 0,
            'tax_cents' => 0,
            'total_cents' => 1000,
            'paid_cents' => 0,
            'balance_cents' => 1000,
            'status' => 'unpaid',
            'issued_on' => today(),
        ]);

        $admin = User::factory()->create([
            'tenant_id' => null,
            'is_platform_admin' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('platform.tenants.show', $tenant))
            ->assertOk()
            ->assertSee('Overview Store')
            ->assertSee($plan->name)
            ->assertSee('1 of 10')
            ->assertSee('1 of 20')
            ->assertSee('OVERVIEW-PLAT-001')
            ->assertSee('OVERVIEW-REF-001')
            ->assertSee($owner->email)
            ->assertDontSee('OTHER-PLAT-001')
            ->assertDontSee($otherOwner->email);
    }

    public function test_store_owner_cannot_view_platform_tenant_overview(): void
    {
        [$tenant, $owner] = $this->tenantScenario('Protected Store');

        $this->actingAs($owner)
            ->get(route('platform.tenants.show', $tenant))
            ->assertForbidden();
    }

    private function tenantScenario(string $name): array
    {
        Role::findOrCreate('owner');
        $tenant = Tenant::create([
            'name' => $name,
            'slug' => fake()->unique()->slug(),
        ]);
        $owner = User::factory()->create(['tenant_id' => $tenant->id]);
        $owner->assignRole('owner');
        $plan = SubscriptionPlan::create([
            'name' => $name.' Plan',
            'slug' => fake()->unique()->slug(),
            'monthly_price_cents' => 299900,
            'annual_price_cents' => 2999000,
            'user_limit' => 5,
            'product_limit' => 10,
            'monthly_invoice_limit' => 20,
        ]);
        $subscription = $tenant->subscriptions()->create([
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now(),
        ]);

        return [$tenant, $owner, $plan, $subscription];
    }
}
