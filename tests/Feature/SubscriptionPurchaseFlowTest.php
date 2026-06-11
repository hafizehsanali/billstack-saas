<?php

namespace Tests\Feature;

use App\Models\PlatformSubscriptionInvoice;
use App\Models\PlatformOffer;
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
            ->post(route('subscription.checkout.store'), ['billing_cycle' => 'monthly'])
            ->assertRedirect(route('subscription.checkout'));

        $this->actingAs($owner)->post(route('subscription.checkout.store'), [
            'billing_cycle' => 'monthly',
        ]);

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

    public function test_percentage_offer_reduces_subscription_invoice_once(): void
    {
        [$tenant, $owner, $plan] = $this->scenario();
        $subscription = $tenant->subscriptions()->create([
            'subscription_plan_id' => $plan->id,
            'status' => 'paused',
            'starts_at' => now(),
        ]);
        $offer = $this->offer('SAVE20', 'percent', 20, $plan);

        $this->actingAs($owner)
            ->post(route('subscription.checkout.store'), [
                'billing_cycle' => 'monthly',
                'promo_code' => 'save20',
            ])
            ->assertRedirect(route('subscription.checkout'));

        $this->actingAs($owner)
            ->post(route('subscription.checkout.store'), [
                'billing_cycle' => 'monthly',
                'promo_code' => 'save20',
            ]);

        $invoice = PlatformSubscriptionInvoice::where('tenant_subscription_id', $subscription->id)
            ->firstOrFail();

        $this->assertSame(99980, $invoice->discount_cents);
        $this->assertSame(399920, $invoice->total_cents);
        $this->assertSame('SAVE20', $invoice->offer_code);
        $this->assertSame(1, $offer->fresh()->redeemed_count);
    }

    public function test_fixed_offer_is_capped_at_subscription_price(): void
    {
        [$tenant, $owner, $plan] = $this->scenario();
        $subscription = $tenant->subscriptions()->create([
            'subscription_plan_id' => $plan->id,
            'status' => 'paused',
            'starts_at' => now(),
        ]);
        $this->offer('FULLCREDIT', 'fixed', 900000, $plan);

        $invoice = app(PlatformBillingService::class)
            ->createSubscriptionInvoice($subscription, 'FULLCREDIT');

        $this->assertSame(499900, $invoice->discount_cents);
        $this->assertSame(0, $invoice->total_cents);
        $this->assertSame(0, $invoice->balance_cents);
        $this->assertSame('paid', $invoice->status);
        $this->assertSame('active', $subscription->fresh()->status);
    }

    public function test_offer_for_another_plan_is_rejected(): void
    {
        [$tenant, $owner, $plan] = $this->scenario();
        $tenant->subscriptions()->create([
            'subscription_plan_id' => $plan->id,
            'status' => 'paused',
            'starts_at' => now(),
        ]);
        $otherPlan = SubscriptionPlan::create([
            'name' => 'Other',
            'slug' => 'other',
            'monthly_price_cents' => 100000,
            'annual_price_cents' => 1000000,
            'trial_days' => 0,
        ]);
        $this->offer('OTHERONLY', 'percent', 10, $otherPlan);

        $this->actingAs($owner)
            ->from(route('subscription.checkout'))
            ->post(route('subscription.checkout.store'), [
                'billing_cycle' => 'monthly',
                'promo_code' => 'OTHERONLY',
            ])
            ->assertRedirect(route('subscription.checkout'))
            ->assertSessionHasErrors('promo_code');

        $this->assertDatabaseMissing('platform_subscription_invoices', [
            'tenant_id' => $tenant->id,
        ]);
    }

    public function test_offer_at_redemption_limit_is_rejected(): void
    {
        [$tenant, $owner, $plan] = $this->scenario();
        $tenant->subscriptions()->create([
            'subscription_plan_id' => $plan->id,
            'status' => 'paused',
            'starts_at' => now(),
        ]);
        $offer = $this->offer('LIMITED', 'percent', 10, $plan);
        $offer->update([
            'redemption_limit' => 1,
            'redeemed_count' => 1,
        ]);

        $this->actingAs($owner)
            ->from(route('subscription.checkout'))
            ->post(route('subscription.checkout.store'), [
                'billing_cycle' => 'monthly',
                'promo_code' => 'LIMITED',
            ])
            ->assertRedirect(route('subscription.checkout'))
            ->assertSessionHasErrors('promo_code');

        $this->assertDatabaseMissing('platform_subscription_invoices', [
            'tenant_id' => $tenant->id,
        ]);
    }

    public function test_annual_offer_is_rejected_for_monthly_and_applied_to_annual_price(): void
    {
        [$tenant, $owner, $plan] = $this->scenario();
        $subscription = $tenant->subscriptions()->create([
            'subscription_plan_id' => $plan->id,
            'status' => 'paused',
            'starts_at' => now(),
        ]);
        $this->offer('YEARLYONLY', 'fixed', 500000, $plan, 'annual');

        $this->actingAs($owner)
            ->from(route('subscription.checkout'))
            ->post(route('subscription.checkout.store'), [
                'billing_cycle' => 'monthly',
                'promo_code' => 'YEARLYONLY',
            ])
            ->assertRedirect(route('subscription.checkout'))
            ->assertSessionHasErrors('promo_code');

        $this->actingAs($owner)
            ->post(route('subscription.checkout.store'), [
                'billing_cycle' => 'annual',
                'promo_code' => 'YEARLYONLY',
            ])
            ->assertRedirect(route('subscription.checkout'));

        $invoice = PlatformSubscriptionInvoice::where('tenant_subscription_id', $subscription->id)
            ->firstOrFail();

        $this->assertSame('annual', $invoice->billing_cycle);
        $this->assertSame(4999000, $invoice->subtotal_cents);
        $this->assertSame(500000, $invoice->discount_cents);
        $this->assertSame(4499000, $invoice->total_cents);

        app(PlatformBillingService::class)->recordPayment($invoice, [
            'payment_method' => 'bank_transfer',
            'paid_on' => today()->toDateString(),
        ]);

        $this->assertTrue($subscription->fresh()->ends_at->isAfter(now()->addDays(364)));
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

    private function offer(
        string $code,
        string $type,
        int $value,
        SubscriptionPlan $plan,
        string $billingCycle = 'both'
    ): PlatformOffer {
        $offer = PlatformOffer::create([
            'name' => $code,
            'code' => $code,
            'discount_type' => $type,
            'discount_value' => $value,
            'billing_cycle' => $billingCycle,
            'redemption_limit' => 10,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addWeek(),
            'is_active' => true,
        ]);
        $offer->plans()->attach($plan);

        return $offer;
    }
}
