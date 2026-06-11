<?php

namespace Tests\Feature;

use App\Models\PlatformSubscriptionInvoice;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PlatformBillingSeeder;
use Database\Seeders\SaasPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PlatformBillingLedgerTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_view_billing_invoice_list_and_detail(): void
    {
        [$admin, $invoice] = $this->billingScenario();

        $this->actingAs($admin)
            ->get(route('platform.billing.index'))
            ->assertOk()
            ->assertSee('Platform Billing')
            ->assertSee($invoice->invoice_no);

        $this->actingAs($admin)
            ->get(route('platform.billing.show', $invoice))
            ->assertOk()
            ->assertSee($invoice->invoice_no)
            ->assertSee('No payments recorded');
    }

    public function test_store_owner_cannot_access_platform_billing(): void
    {
        [$admin, $invoice, $tenant] = $this->billingScenario();
        $owner = User::factory()->create(['tenant_id' => $tenant->id]);

        Role::findOrCreate('owner');
        $owner->assignRole('owner');

        $this->actingAs($owner)
            ->get(route('platform.billing.index'))
            ->assertForbidden();
    }

    public function test_platform_billing_seeder_creates_repeatable_demo_invoice(): void
    {
        $tenant = Tenant::create([
            'name' => 'Demo Growth Store',
            'slug' => 'demo-store-2',
        ]);

        $this->seed(SaasPlanSeeder::class);
        $this->seed(PlatformBillingSeeder::class);
        $this->seed(PlatformBillingSeeder::class);

        $this->assertSame(2, PlatformSubscriptionInvoice::where('tenant_id', $tenant->id)->count());
        $this->assertDatabaseHas('subscription_plans', [
            'slug' => 'demo-monitoring',
            'is_public' => false,
            'product_limit' => 5,
            'monthly_invoice_limit' => 5,
        ]);
        $this->assertDatabaseHas('platform_subscription_invoices', [
            'tenant_id' => $tenant->id,
            'invoice_no' => 'PLAT-DEMO-OVERDUE',
            'status' => 'partial',
            'balance_cents' => 199900,
        ]);
        $this->assertDatabaseHas('platform_subscription_payments', [
            'tenant_id' => $tenant->id,
            'payment_method' => 'manual',
        ]);
    }

    public function test_platform_admin_can_create_subscription_invoice(): void
    {
        [$admin, $existingInvoice, $tenant] = $this->billingScenario();

        $this->actingAs($admin)
            ->post(route('platform.billing.store'), [
                'tenant_id' => $tenant->id,
                'billing_period' => 'July 2026',
                'subtotal' => '3000.00',
                'discount' => '250.00',
                'tax' => '100.00',
                'issued_on' => '2026-07-01',
                'due_on' => '2026-07-10',
                'notes' => 'Monthly Growth plan.',
            ])
            ->assertRedirect();

        $invoice = PlatformSubscriptionInvoice::where('billing_period', 'July 2026')->firstOrFail();

        $this->assertSame(300000, $invoice->subtotal_cents);
        $this->assertSame(285000, $invoice->total_cents);
        $this->assertSame(285000, $invoice->balance_cents);
        $this->assertSame('unpaid', $invoice->status);
    }

    public function test_platform_admin_records_the_full_subscription_balance(): void
    {
        [$admin, $invoice] = $this->billingScenario();

        $this->actingAs($admin)
            ->post(route('platform.billing.payments.store', $invoice), [
                'payment_method' => 'bank_transfer',
                'paid_on' => '2026-06-08',
                'reference_no' => 'BANK-001',
            ])
            ->assertRedirect(route('platform.billing.show', $invoice));

        $invoice->refresh();
        $this->assertSame(299900, $invoice->paid_cents);
        $this->assertSame(0, $invoice->balance_cents);
        $this->assertSame('paid', $invoice->status);
        $this->assertDatabaseHas('platform_subscription_payments', [
            'platform_subscription_invoice_id' => $invoice->id,
            'amount_cents' => 299900,
        ]);
    }

    public function test_submitted_amount_cannot_override_the_full_subscription_balance(): void
    {
        [$admin, $invoice] = $this->billingScenario();

        $this->actingAs($admin)
            ->post(route('platform.billing.payments.store', $invoice), [
                'amount' => '1.00',
                'payment_method' => 'cash',
                'paid_on' => '2026-06-08',
            ])
            ->assertRedirect(route('platform.billing.show', $invoice));

        $invoice->refresh();
        $this->assertSame(1, $invoice->payments()->count());
        $this->assertSame(299900, $invoice->payments()->first()->amount_cents);
        $this->assertSame('paid', $invoice->status);
    }

    public function test_billing_invoice_requires_a_tenant_subscription(): void
    {
        $admin = User::factory()->create([
            'tenant_id' => null,
            'is_platform_admin' => true,
        ]);
        $tenant = Tenant::create([
            'name' => 'Unsubscribed Tenant',
            'slug' => 'unsubscribed-tenant',
        ]);

        $this->actingAs($admin)
            ->post(route('platform.billing.store'), [
                'tenant_id' => $tenant->id,
                'billing_period' => 'July 2026',
                'subtotal' => '1000.00',
                'discount' => '0.00',
                'tax' => '0.00',
                'issued_on' => '2026-07-01',
            ])
            ->assertSessionHasErrors('tenant_id');

        $this->assertDatabaseMissing('platform_subscription_invoices', [
            'tenant_id' => $tenant->id,
        ]);
    }

    private function billingScenario(): array
    {
        $admin = User::factory()->create([
            'tenant_id' => null,
            'is_platform_admin' => true,
        ]);

        $tenant = Tenant::create([
            'name' => 'Paid Tenant',
            'slug' => 'paid-tenant',
        ]);

        $plan = SubscriptionPlan::create([
            'name' => 'Growth',
            'slug' => 'growth',
            'monthly_price_cents' => 299900,
            'annual_price_cents' => 2999000,
            'user_limit' => 8,
        ]);

        $subscription = $tenant->subscriptions()->create([
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->startOfMonth(),
        ]);

        $invoice = PlatformSubscriptionInvoice::create([
            'tenant_id' => $tenant->id,
            'tenant_subscription_id' => $subscription->id,
            'invoice_no' => 'PLAT-TEST-001',
            'billing_period' => now()->format('F Y'),
            'subtotal_cents' => 299900,
            'discount_cents' => 0,
            'tax_cents' => 0,
            'total_cents' => 299900,
            'paid_cents' => 0,
            'balance_cents' => 299900,
            'status' => 'unpaid',
            'issued_on' => now()->startOfMonth(),
            'due_on' => now()->startOfMonth()->addDays(10),
        ]);

        return [$admin, $invoice, $tenant];
    }
}
