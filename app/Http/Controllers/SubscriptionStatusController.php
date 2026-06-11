<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class SubscriptionStatusController extends Controller
{
    public function show(): View
    {
        $tenant = auth()->user()->tenant?->load('currentSubscription.plan');

        return view('subscription.status', [
            'tenant' => $tenant,
            'subscription' => $tenant?->currentSubscription,
            'requiresPayment' => $tenant?->currentSubscription?->plan?->monthly_price_cents > 0,
        ]);
    }
}
