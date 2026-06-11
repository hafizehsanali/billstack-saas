<?php

namespace Tests\Feature;

use App\Models\PlatformOffer;
use App\Models\PlanFeature;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPlanSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_page_lists_only_active_public_packages(): void
    {
        $publicPlan = $this->plan('Starter', 'starter', 0);
        $feature = PlanFeature::create([
            'name' => 'Core POS',
            'key' => 'core.pos',
            'is_paid' => false,
        ]);
        $publicPlan->features()->attach($feature);

        $this->plan('Private Plan', 'private-plan', 100000, false);
        $inactivePlan = $this->plan('Inactive Plan', 'inactive-plan', 100000);
        $inactivePlan->update(['is_active' => false]);

        $this->get(route('plans.index'))
            ->assertOk()
            ->assertSee('Choose the package that fits your store')
            ->assertSee('Starter')
            ->assertSee('Core POS')
            ->assertDontSee('Private Plan')
            ->assertDontSee('Inactive Plan');
    }

    public function test_registration_preserves_selected_package(): void
    {
        $plan = $this->plan('Starter', 'starter', 0);

        $this->get(route('register', ['plan' => $plan->slug]))
            ->assertOk()
            ->assertSee('Selected package')
            ->assertSee('Starter')
            ->assertSee('name="plan" value="starter"', false);
    }

    public function test_free_package_is_activated_during_registration(): void
    {
        $plan = $this->plan('Starter', 'starter', 0);

        $this->post(route('register'), $this->registrationData($plan))
            ->assertRedirect(route('dashboard', absolute: false));

        $tenant = Tenant::where('name', 'Public Test Store')->firstOrFail();
        $this->assertDatabaseHas('tenant_subscriptions', [
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
        ]);
    }

    public function test_paid_package_with_offer_starts_a_trial(): void
    {
        $plan = $this->plan('Growth', 'growth', 299900);
        $offer = PlatformOffer::create([
            'name' => 'Trial',
            'code' => 'TRIAL14',
            'discount_type' => 'percent',
            'discount_value' => 0,
            'trial_days' => 14,
            'is_active' => true,
        ]);
        $offer->plans()->attach($plan);

        $this->post(route('register'), $this->registrationData($plan))
            ->assertRedirect(route('dashboard', absolute: false));

        $tenant = Tenant::where('name', 'Public Test Store')->firstOrFail();
        $subscription = $tenant->subscriptions()->firstOrFail();

        $this->assertSame('active', $subscription->status);
        $this->assertNotNull($subscription->trial_ends_at);
        $this->assertTrue($subscription->trial_ends_at->isAfter(now()->addDays(13)));
    }

    public function test_paid_package_without_trial_waits_for_full_payment(): void
    {
        $plan = $this->plan('Professional', 'professional', 499900);

        $this->post(route('register'), $this->registrationData($plan))
            ->assertRedirect(route('subscription.status', absolute: false));

        $tenant = Tenant::where('name', 'Public Test Store')->firstOrFail();
        $this->assertDatabaseHas('tenant_subscriptions', [
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'paused',
        ]);
    }

    private function plan(
        string $name,
        string $slug,
        int $monthlyPrice,
        bool $isPublic = true
    ): SubscriptionPlan {
        return SubscriptionPlan::create([
            'name' => $name,
            'slug' => $slug,
            'description' => $name.' package description.',
            'monthly_price_cents' => $monthlyPrice,
            'annual_price_cents' => $monthlyPrice * 10,
            'user_limit' => 5,
            'is_public' => $isPublic,
            'is_active' => true,
        ]);
    }

    private function registrationData(SubscriptionPlan $plan): array
    {
        return [
            'name' => 'Public Test Owner',
            'business_name' => 'Public Test Store',
            'email' => 'public-owner@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'plan' => $plan->slug,
        ];
    }
}
