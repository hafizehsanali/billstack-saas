<?php

namespace Tests\Feature;

use App\Models\PlatformOffer;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PlatformOfferManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_create_offer_for_selected_plan(): void
    {
        $admin = $this->platformAdmin();
        $plan = $this->plan();

        $this->actingAs($admin)
            ->post(route('platform.offers.store'), [
                'name' => 'Launch Discount',
                'code' => 'launch25',
                'description' => 'First month launch offer.',
                'discount_type' => 'percent',
                'discount_value' => '25',
                'billing_cycle' => 'monthly',
                'redemption_limit' => '100',
                'starts_at' => now()->format('Y-m-d H:i:s'),
                'ends_at' => now()->addMonth()->format('Y-m-d H:i:s'),
                'is_active' => '1',
                'plans' => [$plan->id],
            ])
            ->assertRedirect(route('platform.offers.index'));

        $offer = PlatformOffer::where('code', 'LAUNCH25')->firstOrFail();

        $this->assertSame(25, $offer->discount_value);
        $this->assertSame('monthly', $offer->billing_cycle);
        $this->assertSame(0, $offer->trial_days);
        $this->assertTrue($offer->plans()->whereKey($plan->id)->exists());
    }

    public function test_fixed_discount_is_stored_in_minor_currency_units(): void
    {
        $admin = $this->platformAdmin();

        $this->actingAs($admin)
            ->post(route('platform.offers.store'), [
                'name' => 'Starter Credit',
                'code' => 'CREDIT500',
                'discount_type' => 'fixed',
                'discount_value' => '500.50',
                'billing_cycle' => 'both',
                'trial_days' => '0',
                'is_active' => '1',
            ])
            ->assertRedirect(route('platform.offers.index'));

        $this->assertDatabaseHas('platform_offers', [
            'code' => 'CREDIT500',
            'discount_value' => 50050,
        ]);
    }

    public function test_percentage_discount_cannot_exceed_one_hundred(): void
    {
        $admin = $this->platformAdmin();

        $this->actingAs($admin)
            ->post(route('platform.offers.store'), [
                'name' => 'Invalid Offer',
                'code' => 'INVALID',
                'discount_type' => 'percent',
                'discount_value' => '125',
                'billing_cycle' => 'both',
                'trial_days' => '0',
                'is_active' => '1',
            ])
            ->assertSessionHasErrors('discount_value');
    }

    public function test_offer_end_date_cannot_be_before_start_date(): void
    {
        $admin = $this->platformAdmin();

        $this->actingAs($admin)
            ->post(route('platform.offers.store'), [
                'name' => 'Invalid Dates',
                'code' => 'DATES',
                'discount_type' => 'percent',
                'discount_value' => '10',
                'billing_cycle' => 'annual',
                'trial_days' => '0',
                'starts_at' => now()->addWeek()->format('Y-m-d H:i:s'),
                'ends_at' => now()->format('Y-m-d H:i:s'),
                'is_active' => '1',
            ])
            ->assertSessionHasErrors('ends_at');
    }

    public function test_store_owner_cannot_access_platform_offers(): void
    {
        $tenant = Tenant::create(['name' => 'Demo Store', 'slug' => 'demo-store']);
        $owner = User::factory()->create(['tenant_id' => $tenant->id]);

        Role::findOrCreate('owner');
        $owner->assignRole('owner');

        $this->actingAs($owner)
            ->get(route('platform.offers.index'))
            ->assertForbidden();
    }

    private function platformAdmin(): User
    {
        return User::factory()->create([
            'tenant_id' => null,
            'is_platform_admin' => true,
        ]);
    }

    private function plan(): SubscriptionPlan
    {
        return SubscriptionPlan::create([
            'name' => 'Growth',
            'slug' => 'growth',
            'monthly_price_cents' => 299900,
            'annual_price_cents' => 2999000,
            'user_limit' => 8,
        ]);
    }
}
