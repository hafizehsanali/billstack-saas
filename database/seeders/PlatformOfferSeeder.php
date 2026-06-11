<?php

namespace Database\Seeders;

use App\Models\PlatformOffer;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class PlatformOfferSeeder extends Seeder
{
    public function run(): void
    {
        $growth = SubscriptionPlan::where('slug', 'growth')->first();

        PlatformOffer::where('code', 'TRY14')->delete();

        $offers = [
            [
                'code' => 'LAUNCH25',
                'data' => [
                    'name' => 'Launch Discount',
                    'description' => 'Introductory discount for new businesses moving to a paid plan.',
                    'discount_type' => 'percent',
                    'discount_value' => 25,
                    'trial_days' => 0,
                    'redemption_limit' => 100,
                    'starts_at' => now()->startOfDay(),
                    'ends_at' => now()->addMonths(3)->endOfDay(),
                    'is_active' => true,
                ],
                'plans' => array_filter([$growth?->id]),
            ],
            [
                'code' => 'YEARLYSAVE',
                'data' => [
                    'name' => 'Annual Plan Savings',
                    'description' => 'Fixed discount for customers choosing annual billing.',
                    'discount_type' => 'fixed',
                    'discount_value' => 500000,
                    'trial_days' => 0,
                    'redemption_limit' => null,
                    'starts_at' => now()->startOfDay(),
                    'ends_at' => null,
                    'is_active' => true,
                ],
                'plans' => array_filter([$growth?->id]),
            ],
        ];

        foreach ($offers as $offer) {
            $platformOffer = PlatformOffer::updateOrCreate(
                ['code' => $offer['code']],
                $offer['data'] + ['code' => $offer['code']]
            );

            $platformOffer->plans()->sync($offer['plans']);
        }
    }
}
