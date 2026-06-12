<?php

namespace App\Http\Controllers;

use App\Models\PlatformOffer;
use App\Models\SubscriptionPlan;
use Illuminate\View\View;

class PublicPlanController extends Controller
{
    public function index(): View
    {
        $offers = PlatformOffer::with('plans')
            ->where('is_active', true)
            ->get()
            ->filter->isCurrentlyAvailable();

        $plans = SubscriptionPlan::with('features')
            ->where('is_public', true)
            ->where('is_active', true)
            ->orderBy('monthly_price_cents')
            ->orderBy('name')
            ->get()
            ->each(function (SubscriptionPlan $plan) use ($offers): void {
                $plan->setRelation(
                    'availableOffers',
                    $offers->filter(fn (PlatformOffer $offer) => $offer->appliesTo($plan))->values()
                );
            });

        $comparisonFeatures = $plans
            ->flatMap->features
            ->unique('id')
            ->sortBy('name')
            ->values();

        return view('plans.index', compact('plans', 'comparisonFeatures'));
    }
}
