<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use App\Services\TenantSubscriptionService;
use Illuminate\View\View;

class PublicPlanController extends Controller
{
    public function index(TenantSubscriptionService $subscriptions): View
    {
        $plans = SubscriptionPlan::with(['features', 'offers'])
            ->where('is_public', true)
            ->where('is_active', true)
            ->orderBy('monthly_price_cents')
            ->orderBy('name')
            ->get()
            ->each(function (SubscriptionPlan $plan) use ($subscriptions): void {
                $plan->setAttribute('available_trial_days', $subscriptions->trialDaysFor($plan));
            });

        return view('plans.index', compact('plans'));
    }
}
