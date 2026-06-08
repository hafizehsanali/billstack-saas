<?php

namespace Tests\Feature;

use App\Models\PlatformSubscriptionInvoice;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TenantBillingPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_own_subscription_billing(): void
    {
        [$owner, $invoice] = $this->tenantBillingScenario();

        $this->actingAs($owner)
            ->get(route('billing.index'))
            ->assertOk()
            ->assertSee('Plan &amp; Billing', false)
            ->assertSee($invoice->invoice_no)
            ->assertSee('Rs 1,999.00');
    }

    public function test_tenant_billing_page_only_shows_current_tenant_invoices(): void
    {
        [$owner] = $this->tenantBillingScenario();

        $otherTenant = Tenant::create([
            'name' => 'Other Store',
            'slug' => 'other-store',
        ]);

        PlatformSubscriptionInvoice::create([
            'tenant_id' => $otherTenant->id,
            'invoice_no' => 'PLAT-OTHER-001',
            'billing_period' => now()->format('F Y'),
            'subtotal_cents' => 500000,
            'discount_cents' => 0,
            'tax_cents' => 0,
            'total_cents' => 500000,
            'paid_cents' => 0,
            'balance_cents' => 500000,
            'status' => 'unpaid',
            'issued_on' => now(),
        ]);

        $this->actingAs($owner)
            ->get(route('billing.index'))
            ->assertOk()
            ->assertDontSee('PLAT-OTHER-001');
    }

    public function test_cashier_cannot_access_tenant_billing(): void
    {
        [$owner] = $this->tenantBillingScenario();

        $cashier = User::factory()->create(['tenant_id' => $owner->tenant_id]);
        Role::findOrCreate('cashier');
        $cashier->assignRole('cashier');

        $this->actingAs($cashier)
            ->get(route('billing.index'))
            ->assertForbidden();
    }

    private function tenantBillingScenario(): array
    {
        Role::findOrCreate('owner');

        $tenant = Tenant::create([
            'name' => 'Tenant Store',
            'slug' => 'tenant-store',
        ]);

        $owner = User::factory()->create(['tenant_id' => $tenant->id]);
        $owner->assignRole('owner');

        $plan = SubscriptionPlan::create([
            'name' => 'Growth',
            'slug' => 'growth',
            'monthly_price_cents' => 199900,
            'annual_price_cents' => 1999000,
            'user_limit' => 5,
        ]);

        $subscription = $tenant->subscriptions()->create([
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->startOfMonth(),
        ]);

        $invoice = PlatformSubscriptionInvoice::create([
            'tenant_id' => $tenant->id,
            'tenant_subscription_id' => $subscription->id,
            'invoice_no' => 'PLAT-TENANT-001',
            'billing_period' => now()->format('F Y'),
            'subtotal_cents' => 199900,
            'discount_cents' => 0,
            'tax_cents' => 0,
            'total_cents' => 199900,
            'paid_cents' => 100000,
            'balance_cents' => 99900,
            'status' => 'partial',
            'issued_on' => now()->startOfMonth(),
            'due_on' => now()->startOfMonth()->addDays(10),
        ]);

        $invoice->payments()->create([
            'tenant_id' => $tenant->id,
            'amount_cents' => 100000,
            'payment_method' => 'manual',
            'reference_no' => 'TENANT-PAY-001',
            'paid_on' => now()->startOfMonth()->addDays(2),
        ]);

        return [$owner, $invoice, $tenant];
    }
}
