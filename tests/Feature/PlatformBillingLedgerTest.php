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
            ->assertSee('Manual');
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

        $this->assertSame(1, PlatformSubscriptionInvoice::where('tenant_id', $tenant->id)->count());
        $this->assertDatabaseHas('platform_subscription_payments', [
            'tenant_id' => $tenant->id,
            'payment_method' => 'manual',
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
            'paid_cents' => 100000,
            'balance_cents' => 199900,
            'status' => 'partial',
            'issued_on' => now()->startOfMonth(),
            'due_on' => now()->startOfMonth()->addDays(10),
        ]);

        $invoice->payments()->create([
            'tenant_id' => $tenant->id,
            'amount_cents' => 100000,
            'payment_method' => 'manual',
            'reference_no' => 'PAY-TEST-001',
            'paid_on' => now()->startOfMonth()->addDays(2),
        ]);

        return [$admin, $invoice, $tenant];
    }
}
