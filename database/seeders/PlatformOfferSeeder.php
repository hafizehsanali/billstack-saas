<?php

namespace Database\Seeders;

use App\Models\PlatformOffer;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class PlatformOfferSeeder extends Seeder
{
    public function run(): void
    {
        $starter = SubscriptionPlan::where('slug', 'starter')->first();
        $growth = SubscriptionPlan::where('slug', 'growth')->first();

        $offers = [
            [
                'code' => 'LAUNCH25',
                'data' => [
                    'name' => 'Launch Discount',
                    'description' => 'Introductory discount for new businesses moving to a paid plan.',
                    'discount_type' => 'percent',
                    'discount_value' => 25,
                    'trial_days' => 7,
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
            [
                'code' => 'TRY14',
                'data' => [
                    'name' => 'Extended Trial',
                    'description' => 'Extra trial days for businesses evaluating paid features.',
                    'discount_type' => 'percent',
                    'discount_value' => 0,
                    'trial_days' => 14,
                    'redemption_limit' => null,
                    'starts_at' => now()->startOfDay(),
                    'ends_at' => null,
                    'is_active' => true,
                ],
                'plans' => array_filter([$starter?->id, $growth?->id]),
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
