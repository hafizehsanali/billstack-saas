<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PlanUsageLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_creation_is_blocked_when_the_plan_limit_is_reached(): void
    {
        [$tenant, $user] = $this->createSubscribedUser(productLimit: 1);

        $this->actingAs($user);

        $category = Category::create(['name' => 'Hardware']);
        Product::create([
            'category_id' => $category->id,
            'name' => 'Existing Product',
            'sku' => 'LIMIT-001',
            'purchase_price' => 100,
            'selling_price' => 150,
            'stock_quantity' => 10,
            'low_stock_alert' => 2,
        ]);

        $response = $this->from(route('products.create'))->post(route('products.store'), [
            'category_id' => $category->id,
            'name' => 'Limit Test Product',
            'sku' => 'LIMIT-002',
            'purchase_price' => 100,
            'selling_price' => 150,
            'stock_quantity' => 10,
            'low_stock_alert' => 2,
        ]);

        $response
            ->assertRedirect(route('products.create'))
            ->assertSessionHasErrors('plan_limit');

        $this->assertDatabaseMissing('products', [
            'tenant_id' => $tenant->id,
            'sku' => 'LIMIT-002',
        ]);
    }

    public function test_invoice_creation_is_blocked_when_the_monthly_limit_is_reached(): void
    {
        [$tenant, $user] = $this->createSubscribedUser(monthlyInvoiceLimit: 1);

        $this->actingAs($user);

        $customer = Customer::create(['name' => 'Invoice Limit Customer']);
        $category = Category::create(['name' => 'Hardware']);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Invoice Product',
            'sku' => 'INV-PRODUCT-001',
            'purchase_price' => 100,
            'stock_quantity' => 10,
            'selling_price' => 150,
            'low_stock_alert' => 2,
        ]);
        Invoice::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'invoice_no' => 'LIMIT-INV-001',
            'sale_date' => now()->toDateString(),
            'created_at' => now(),
        ]);

        $response = $this->from(route('invoices.create'))->post(
            route('invoices.store'),
            $this->invoicePayload($customer, $product, 'LIMIT-INV-002')
        );

        $response
            ->assertRedirect(route('invoices.create'))
            ->assertSessionHasErrors('plan_limit');

        $this->assertDatabaseMissing('invoices', [
            'tenant_id' => $tenant->id,
            'invoice_no' => 'LIMIT-INV-002',
        ]);
    }

    public function test_previous_month_invoices_do_not_count_toward_the_current_month_limit(): void
    {
        [$tenant, $user] = $this->createSubscribedUser(monthlyInvoiceLimit: 1);

        $this->actingAs($user);

        $customer = Customer::create(['name' => 'Monthly Reset Customer']);
        $category = Category::create(['name' => 'Hardware']);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Monthly Reset Product',
            'sku' => 'INV-PRODUCT-RESET',
            'purchase_price' => 100,
            'stock_quantity' => 10,
            'selling_price' => 150,
            'low_stock_alert' => 2,
        ]);
        $previousInvoice = Invoice::create([
            'tenant_id' => $tenant->id,
            'customer_id' => $customer->id,
            'invoice_no' => 'LIMIT-INV-PREVIOUS',
            'sale_date' => now()->subMonthNoOverflow()->toDateString(),
        ]);
        $previousInvoice->forceFill([
            'created_at' => now()->subMonthNoOverflow()->startOfMonth(),
            'updated_at' => now()->subMonthNoOverflow()->startOfMonth(),
        ])->saveQuietly();

        $response = $this->post(
            route('invoices.store'),
            $this->invoicePayload($customer, $product, 'LIMIT-INV-CURRENT')
        );

        $response->assertRedirect();

        $this->assertDatabaseHas('invoices', [
            'tenant_id' => $tenant->id,
            'invoice_no' => 'LIMIT-INV-CURRENT',
        ]);
    }

    private function createSubscribedUser(
        ?int $productLimit = null,
        ?int $monthlyInvoiceLimit = null
    ): array {
        $tenant = Tenant::create([
            'name' => 'Usage Limit Store',
            'slug' => fake()->unique()->slug(),
        ]);
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);
        $plan = SubscriptionPlan::create([
            'name' => 'Limited Plan',
            'slug' => fake()->unique()->slug(),
            'monthly_price_cents' => 1000,
            'annual_price_cents' => 10000,
            'user_limit' => 2,
            'product_limit' => $productLimit,
            'monthly_invoice_limit' => $monthlyInvoiceLimit,
        ]);

        TenantSubscription::create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
        ]);

        Role::findOrCreate('owner');
        $user->assignRole('owner');

        return [$tenant, $user];
    }

    private function invoicePayload(
        Customer $customer,
        Product $product,
        string $invoiceNumber
    ): array {
        return [
            'invoice_no' => $invoiceNumber,
            'sale_date' => now()->toDateString(),
            'customer_id' => $customer->id,
            'tax' => 0,
            'discount' => 0,
            'extra_expense' => 0,
            'paid_amount' => 0,
            'products' => [[
                'product_id' => $product->id,
                'quantity' => 1,
                'price' => 150,
            ]],
        ];
    }
}
