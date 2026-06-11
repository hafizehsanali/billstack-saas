<?php

namespace Tests\Feature;

use App\Models\PlatformSubscriptionInvoice;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PlatformBillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SubscriptionPurchaseFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_expired_trial_is_paused_and_redirected_to_payment(): void
    {
        [$tenant, $owner, $plan] = $this->scenario(trialDays: 14);
        $subscription = $tenant->subscriptions()->create([
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subDays(15),
            'trial_ends_at' => now()->subDay(),
        ]);

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertRedirect(route('subscription.status'));

        $this->assertSame('paused', $subscription->fresh()->status);
    }

    public function test_paid_plan_without_trial_can_create_one_purchase_invoice(): void
    {
        [$tenant, $owner, $plan] = $this->scenario();
        $tenant->subscriptions()->create([
            'subscription_plan_id' => $plan->id,
            'status' => 'paused',
            'starts_at' => now(),
        ]);

        $this->actingAs($owner)
            ->get(route('subscription.checkout'))
            ->assertOk()
            ->assertSee('Purchase Subscription')
            ->assertSee('Create Purchase Invoice');

        $this->actingAs($owner)
            ->post(route('subscription.checkout.store'))
            ->assertRedirect(route('subscription.checkout'));

        $this->actingAs($owner)->post(route('subscription.checkout.store'));

        $this->assertSame(1, PlatformSubscriptionInvoice::where('tenant_id', $tenant->id)->count());
        $this->assertDatabaseHas('platform_subscription_invoices', [
            'tenant_id' => $tenant->id,
            'total_cents' => 499900,
            'status' => 'unpaid',
        ]);
    }

    public function test_full_platform_payment_activates_subscription(): void
    {
        [$tenant, $owner, $plan] = $this->scenario();
        $subscription = $tenant->subscriptions()->create([
            'subscription_plan_id' => $plan->id,
            'status' => 'paused',
            'starts_at' => now(),
        ]);

        $invoice = app(PlatformBillingService::class)->createSubscriptionInvoice($subscription);

        app(PlatformBillingService::class)->recordPayment($invoice, [
            'payment_method' => 'bank_transfer',
            'paid_on' => today()->toDateString(),
            'reference_no' => 'BANK-FULL-001',
        ]);

        $subscription->refresh();
        $this->assertSame('active', $subscription->status);
        $this->assertNull($subscription->trial_ends_at);
        $this->assertTrue($subscription->ends_at->isAfter(now()->addDays(29)));
    }

    private function scenario(int $trialDays = 0): array
    {
        Role::findOrCreate('owner');

        $tenant = Tenant::create([
            'name' => 'Subscription Store',
            'slug' => fake()->unique()->slug(),
        ]);
        $owner = User::factory()->create(['tenant_id' => $tenant->id]);
        $owner->assignRole('owner');

        $plan = SubscriptionPlan::create([
            'name' => 'Professional',
            'slug' => fake()->unique()->slug(),
            'monthly_price_cents' => 499900,
            'annual_price_cents' => 4999000,
            'user_limit' => 15,
            'trial_days' => $trialDays,
            'is_public' => true,
            'is_active' => true,
        ]);

        return [$tenant, $owner, $plan];
    }
}
