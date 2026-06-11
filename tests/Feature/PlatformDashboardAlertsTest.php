<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\PlatformSubscriptionInvoice;
use App\Models\Product;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformDashboardAlertsTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_dashboard_shows_usage_expiry_and_overdue_alerts(): void
    {
        $tenant = Tenant::create([
            'name' => 'Attention Store',
            'slug' => 'attention-store',
        ]);
        $owner = User::factory()->create(['tenant_id' => $tenant->id]);
        $plan = SubscriptionPlan::create([
            'name' => 'Growth',
            'slug' => 'growth-alerts',
            'monthly_price_cents' => 299900,
            'annual_price_cents' => 2999000,
            'product_limit' => 10,
            'monthly_invoice_limit' => 2,
        ]);
        $subscription = $tenant->subscriptions()->create([
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subMonth(),
            'trial_ends_at' => null,
            'ends_at' => now()->addDays(5),
        ]);

        $this->actingAs($owner);

        $category = Category::create(['name' => 'Alert Products']);

        foreach (range(1, 8) as $number) {
            Product::create([
                'category_id' => $category->id,
                'name' => "Alert Product {$number}",
                'sku' => "ALERT-{$number}",
                'purchase_price' => 100,
                'selling_price' => 150,
                'stock_quantity' => 10,
                'low_stock_alert' => 2,
            ]);
        }

        $customer = Customer::create(['name' => 'Alert Customer']);

        foreach (range(1, 2) as $number) {
            Invoice::create([
                'tenant_id' => $tenant->id,
                'customer_id' => $customer->id,
                'invoice_no' => "ALERT-INV-{$number}",
                'sale_date' => now()->toDateString(),
            ]);
        }

        $platformInvoice = PlatformSubscriptionInvoice::create([
            'tenant_id' => $tenant->id,
            'tenant_subscription_id' => $subscription->id,
            'invoice_no' => 'PLAT-OVERDUE-001',
            'billing_period' => now()->format('F Y'),
            'subtotal_cents' => 5000,
            'discount_cents' => 0,
            'tax_cents' => 0,
            'total_cents' => 5000,
            'paid_cents' => 0,
            'balance_cents' => 5000,
            'status' => 'unpaid',
            'issued_on' => now()->subDays(10),
            'due_on' => now()->subDay(),
        ]);

        $admin = User::factory()->create([
            'tenant_id' => null,
            'is_platform_admin' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('platform.dashboard'))
            ->assertOk()
            ->assertSee('Needs Attention')
            ->assertSee('Products: 8 of 10')
            ->assertSee('Monthly invoices: 2 of 2')
            ->assertSee('Subscription ends')
            ->assertDontSee('Trial ends')
            ->assertSee($platformInvoice->invoice_no)
            ->assertSee('Rs 50.00 due');
    }
}
