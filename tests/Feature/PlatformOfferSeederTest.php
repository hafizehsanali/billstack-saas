<?php

namespace Tests\Feature;

use App\Models\PlatformOffer;
use App\Models\SubscriptionPlan;
use Database\Seeders\PlatformOfferSeeder;
use Database\Seeders\SaasPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformOfferSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_offer_seeder_creates_repeatable_demo_offers_with_plan_assignments(): void
    {
        $this->seed(SaasPlanSeeder::class);
        $this->seed(PlatformOfferSeeder::class);
        $this->seed(PlatformOfferSeeder::class);

        $this->assertSame(2, PlatformOffer::count());

        $growth = SubscriptionPlan::where('slug', 'growth')->firstOrFail();
        $launchOffer = PlatformOffer::where('code', 'LAUNCH25')->firstOrFail();
        $annualOffer = PlatformOffer::where('code', 'YEARLYSAVE')->firstOrFail();

        $this->assertTrue($launchOffer->plans()->whereKey($growth->id)->exists());
        $this->assertSame(0, $launchOffer->trial_days);
        $this->assertSame('monthly', $launchOffer->billing_cycle);
        $this->assertSame('annual', $annualOffer->billing_cycle);
        $this->assertDatabaseMissing('platform_offers', ['code' => 'TRY14']);
    }
}
