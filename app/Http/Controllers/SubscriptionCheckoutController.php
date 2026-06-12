<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSubscriptionCheckoutRequest;
use App\Models\PlatformOffer;
use App\Models\PlatformSetting;
use App\Models\PlatformSubscriptionInvoice;
use App\Services\PlatformBillingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SubscriptionCheckoutController extends Controller
{
    public function show(): View
    {
        $tenant = auth()->user()->tenant()->with('currentSubscription.plan')->firstOrFail();
        $subscription = $tenant->currentSubscription;

        abort_if(! $subscription?->plan || $subscription->plan->monthly_price_cents === 0, 404);

        $offers = PlatformOffer::with('plans')
            ->where('is_active', true)
            ->get()
            ->filter(fn (PlatformOffer $offer) => $offer->isCurrentlyAvailable()
                && $offer->appliesTo($subscription->plan))
            ->values();

        return view('subscription.checkout', [
            'tenant' => $tenant,
            'subscription' => $subscription,
            'invoice' => $this->openInvoice($subscription->id),
            'platformSettings' => PlatformSetting::current(),
            'offers' => $offers,
            'offerPreviews' => $offers->map(fn (PlatformOffer $offer) => [
                'code' => $offer->code,
                'type' => $offer->discount_type,
                'value' => (int) $offer->discount_value,
                'cycle' => $offer->billing_cycle,
            ])->values(),
        ]);
    }

    public function store(
        StoreSubscriptionCheckoutRequest $request,
        PlatformBillingService $billing
    ): RedirectResponse
    {
        $tenant = auth()->user()->tenant()->with('currentSubscription.plan')->firstOrFail();
        $subscription = $tenant->currentSubscription;

        abort_if(! $subscription?->plan || $subscription->plan->monthly_price_cents === 0, 404);

        $billing->createSubscriptionInvoice(
            $subscription,
            $request->validated('promo_code'),
            $request->validated('billing_cycle')
        );

        return redirect()
            ->route('subscription.checkout')
            ->with('success', 'Subscription invoice created. Complete the full payment to activate the plan.');
    }

    private function openInvoice(int $subscriptionId): ?PlatformSubscriptionInvoice
    {
        return PlatformSubscriptionInvoice::with(['payments', 'paymentSubmission'])
            ->where('tenant_subscription_id', $subscriptionId)
            ->where('status', '!=', 'paid')
            ->latest()
            ->first();
    }
}
