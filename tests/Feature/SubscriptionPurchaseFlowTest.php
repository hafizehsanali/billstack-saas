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

        $this->actingAs($owner)
            ->get(route('subscription.checkout'))
            ->assertOk()
            ->assertSee('Package Price')
            ->assertSee('Coupon Discount')
            ->assertSee('Total Payable After Coupon')
            ->assertSee('Rs 4,999.00')
            ->assertSee('Rs 999.80')
            ->assertSee('Rs 3,999.20');
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

    public function test_owner_can_cancel_unpaid_checkout_and_reuse_offer(): void
    {
        [$tenant, $owner, $plan] = $this->scenario();
        $subscription = $tenant->subscriptions()->create([
            'subscription_plan_id' => $plan->id,
            'status' => 'paused',
            'starts_at' => now(),
        ]);
        $offer = $this->offer('RESTART20', 'percent', 20, $plan);
        $invoice = app(PlatformBillingService::class)
            ->createSubscriptionInvoice($subscription, 'RESTART20');

        $this->actingAs($owner)
            ->post(route('subscription.invoices.cancel', $invoice))
            ->assertRedirect(route('subscription.checkout'));

        $this->assertSame('cancelled', $invoice->fresh()->status);
        $this->assertSame(0, $invoice->fresh()->balance_cents);
        $this->assertSame(0, $offer->fresh()->redeemed_count);

        $replacement = app(PlatformBillingService::class)
            ->createSubscriptionInvoice($subscription, 'RESTART20', 'annual');

        $this->assertNotSame($invoice->id, $replacement->id);
        $this->assertSame('annual', $replacement->billing_cycle);
        $this->assertSame(1, $offer->fresh()->redeemed_count);
    }

    public function test_subscription_outcome_reflects_server_side_payment_state(): void
    {
        [$tenant, $owner, $plan] = $this->scenario();
        $subscription = $tenant->subscriptions()->create([
            'subscription_plan_id' => $plan->id,
            'status' => 'paused',
            'starts_at' => now(),
        ]);
        $invoice = app(PlatformBillingService::class)->createSubscriptionInvoice($subscription);

        $this->actingAs($owner)
            ->get(route('subscription.outcome'))
            ->assertOk()
            ->assertSee('Payment details required')
            ->assertDontSee('Subscription activated');

        app(PlatformBillingService::class)->recordPayment($invoice, [
            'payment_method' => 'bank_transfer',
            'paid_on' => today()->toDateString(),
        ]);

        $this->actingAs($owner)
            ->get(route('subscription.outcome'))
            ->assertOk()
            ->assertSee('Subscription activated')
            ->assertSee($invoice->invoice_no);
    }

    public function test_owner_can_select_upgrade_and_old_plan_remains_active_until_full_payment(): void
    {
        [$tenant, $owner, $currentPlan] = $this->scenario();
        $currentSubscription = $tenant->subscriptions()->create([
            'subscription_plan_id' => $currentPlan->id,
            'status' => 'active',
            'starts_at' => now()->subMonth(),
            'ends_at' => now()->addMonth(),
        ]);
        $upgrade = SubscriptionPlan::create([
            'name' => 'Business',
            'slug' => 'business',
            'monthly_price_cents' => 799900,
            'annual_price_cents' => 7999000,
            'is_public' => true,
            'is_active' => true,
        ]);

        $this->actingAs($owner)
            ->post(route('subscription.plans.select', $upgrade), [
                'billing_cycle' => 'annual',
            ])
            ->assertRedirect(route('subscription.checkout', ['billing_cycle' => 'annual']));

        $upgradeSubscription = $tenant->subscriptions()
            ->where('subscription_plan_id', $upgrade->id)
            ->firstOrFail();

        $this->assertSame('active', $currentSubscription->fresh()->status);
        $this->assertSame('paused', $upgradeSubscription->status);

        $invoice = app(PlatformBillingService::class)
            ->createSubscriptionInvoice($upgradeSubscription, billingCycle: 'annual');
        app(PlatformBillingService::class)->recordPayment($invoice, [
            'payment_method' => 'bank_transfer',
            'paid_on' => today()->toDateString(),
        ]);

        $this->assertSame('cancelled', $currentSubscription->fresh()->status);
        $this->assertSame('active', $upgradeSubscription->fresh()->status);
    }

    public function test_owner_can_switch_to_public_free_package_immediately(): void
    {
        [$tenant, $owner, $currentPlan] = $this->scenario();
        $currentSubscription = $tenant->subscriptions()->create([
            'subscription_plan_id' => $currentPlan->id,
            'status' => 'active',
            'starts_at' => now()->subMonth(),
        ]);
        $freePlan = SubscriptionPlan::create([
            'name' => 'Starter',
            'slug' => 'starter-free',
            'monthly_price_cents' => 0,
            'annual_price_cents' => 0,
            'free_access_days' => 30,
            'is_public' => true,
            'is_active' => true,
        ]);

        $this->actingAs($owner)
            ->post(route('subscription.plans.select', $freePlan), [
                'billing_cycle' => 'monthly',
            ])
            ->assertRedirect(route('dashboard'));

        $this->assertSame('cancelled', $currentSubscription->fresh()->status);
        $this->assertDatabaseHas('tenant_subscriptions', [
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $freePlan->id,
            'status' => 'active',
        ]);
    }

    public function test_active_trial_can_open_early_payment_for_same_package(): void
    {
        [$tenant, $owner, $plan] = $this->scenario(trialDays: 14);
        $tenant->subscriptions()->create([
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now(),
            'trial_ends_at' => now()->addDays(14),
        ]);

        $this->actingAs($owner)
            ->post(route('subscription.plans.select', $plan), [
                'billing_cycle' => 'annual',
            ])
            ->assertRedirect(route('subscription.checkout', ['billing_cycle' => 'annual']));

        $this->actingAs($owner)
            ->get(route('subscription.checkout', ['billing_cycle' => 'annual']))
            ->assertOk()
            ->assertSee('Purchase Subscription')
            ->assertSee('Annual - Rs 49,990');
    }

    public function test_early_trial_payment_preserves_unused_trial_days(): void
    {
        [$tenant, , $plan] = $this->scenario(trialDays: 14);
        $subscription = $tenant->subscriptions()->create([
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now(),
            'trial_ends_at' => now()->addDays(10),
        ]);
        $invoice = app(PlatformBillingService::class)->createSubscriptionInvoice($subscription);

        app(PlatformBillingService::class)->recordPayment($invoice, [
            'payment_method' => 'bank_transfer',
            'paid_on' => today()->toDateString(),
        ]);

        $subscription->refresh();
        $this->assertNull($subscription->trial_ends_at);
        $this->assertTrue($subscription->ends_at->isAfter(now()->addDays(39)));
    }

    public function test_selecting_another_package_cancels_abandoned_invoice_and_subscription(): void
    {
        [$tenant, $owner, $firstPlan] = $this->scenario();
        $firstSelection = $tenant->subscriptions()->create([
            'subscription_plan_id' => $firstPlan->id,
            'status' => 'paused',
            'starts_at' => now(),
        ]);
        $invoice = app(PlatformBillingService::class)->createSubscriptionInvoice($firstSelection);
        $secondPlan = SubscriptionPlan::create([
            'name' => 'Second Plan',
            'slug' => 'second-plan',
            'monthly_price_cents' => 699900,
            'annual_price_cents' => 6999000,
            'is_public' => true,
            'is_active' => true,
        ]);

        $this->actingAs($owner)
            ->post(route('subscription.plans.select', $secondPlan), [
                'billing_cycle' => 'monthly',
            ])
            ->assertRedirect(route('subscription.checkout', ['billing_cycle' => 'monthly']));

        $this->assertSame('cancelled', $firstSelection->fresh()->status);
        $this->assertSame('cancelled', $invoice->fresh()->status);
        $this->assertSame(0, $invoice->fresh()->balance_cents);
        $this->assertDatabaseHas('tenant_subscriptions', [
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $secondPlan->id,
            'status' => 'paused',
        ]);
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
