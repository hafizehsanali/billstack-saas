<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSubscriptionCheckoutRequest;
use App\Models\PlatformOffer;
use App\Models\PlatformSetting;
use App\Models\PlatformSubscriptionInvoice;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Services\PlatformBillingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SubscriptionCheckoutController extends Controller
{
    public function show(Request $request): View
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

        $platformSettings = PlatformSetting::current();

        $selectedCycle = in_array($request->string('billing_cycle')->toString(), ['monthly', 'annual'], true)
            ? $request->string('billing_cycle')->toString()
            : 'monthly';

        if ($selectedCycle === 'annual' && $subscription->plan->annual_price_cents <= 0) {
            $selectedCycle = 'monthly';
        }

        return view('subscription.checkout', [
            'tenant' => $tenant,
            'subscription' => $subscription,
            'invoice' => $this->openInvoice($subscription->id),
            'platformSettings' => $platformSettings,
            'paymentChannels' => $platformSettings->activePaymentChannels(),
            'offers' => $offers,
            'offerPreviews' => $offers->map(fn (PlatformOffer $offer) => [
                'code' => $offer->code,
                'type' => $offer->discount_type,
                'value' => (int) $offer->discount_value,
                'cycle' => $offer->billing_cycle,
            ])->values(),
            'selectedCycle' => $selectedCycle,
            'selectedPromo' => strtoupper($request->string('promo_code')->toString()),
        ]);
    }

    public function selectPlan(Request $request, SubscriptionPlan $plan): RedirectResponse
    {
        abort_unless($plan->is_public && $plan->is_active, 404);

        $request->validate([
            'billing_cycle' => ['required', 'in:monthly,annual'],
        ]);

        $tenant = $request->user()->tenant()->with([
            'currentSubscription.platformInvoices.paymentSubmission',
            'activeSubscription',
        ])->firstOrFail();
        $current = $tenant->currentSubscription;

        if ($current?->subscription_plan_id === $plan->id) {
            $isActiveTrial = $current->status === 'active'
                && $current->trial_ends_at?->isFuture()
                && $plan->monthly_price_cents > 0;

            return redirect()->route(
                $current->status === 'active' && ! $isActiveTrial ? 'billing.index' : 'subscription.checkout',
                $current->status === 'active' && ! $isActiveTrial
                    ? []
                    : ['billing_cycle' => $request->string('billing_cycle')->toString()]
            );
        }

        $hasPendingReview = $current?->platformInvoices
            ->contains(fn (PlatformSubscriptionInvoice $invoice) => $invoice->paymentSubmission?->status === 'pending');

        if ($hasPendingReview) {
            throw ValidationException::withMessages([
                'plan' => 'Wait for the pending payment review before selecting another package.',
            ]);
        }

        if ($plan->monthly_price_cents === 0) {
            DB::transaction(function () use ($tenant, $plan): void {
                $this->cancelReplaceableSelections($tenant);
                $tenant->subscriptions()->where('status', 'active')->update([
                    'status' => 'cancelled',
                    'ends_at' => now(),
                ]);
                $tenant->subscriptions()->create([
                    'subscription_plan_id' => $plan->id,
                    'status' => 'active',
                    'starts_at' => now(),
                    'ends_at' => $plan->free_access_days ? now()->addDays($plan->free_access_days) : null,
                ]);
            });

            return redirect()->route('dashboard')
                ->with('success', "{$plan->name} is now active.");
        }

        DB::transaction(function () use ($tenant, $plan): void {
            $this->cancelReplaceableSelections($tenant);
            $tenant->subscriptions()->create([
                'subscription_plan_id' => $plan->id,
                'status' => 'paused',
                'starts_at' => now(),
            ]);
        });

        return redirect()->route('subscription.checkout', [
            'billing_cycle' => $request->string('billing_cycle')->toString(),
        ])->with('success', "{$plan->name} selected. Complete full payment to activate it.");
    }

    public function store(
        StoreSubscriptionCheckoutRequest $request,
        PlatformBillingService $billing
    ): RedirectResponse
    {
        $tenant = auth()->user()->tenant()->with('currentSubscription.plan')->firstOrFail();
        $subscription = $tenant->currentSubscription;

        abort_if(! $subscription?->plan || $subscription->plan->monthly_price_cents === 0, 404);

        $invoice = $billing->createSubscriptionInvoice(
            $subscription,
            $request->validated('promo_code'),
            $request->validated('billing_cycle')
        );

        if ($invoice->status === 'paid') {
            return redirect()
                ->route('subscription.outcome')
                ->with('success', 'Your promotion covered the full amount and the package is now active.');
        }

        return redirect()
            ->route('subscription.checkout')
            ->with('success', 'Subscription invoice created. Complete the full payment to activate the plan.');
    }

    public function cancel(
        PlatformSubscriptionInvoice $invoice
    ): RedirectResponse {
        abort_unless($invoice->tenant_id === auth()->user()->tenant_id, 403);

        if ($invoice->status === 'paid' || $invoice->paymentSubmission?->status === 'pending') {
            throw ValidationException::withMessages([
                'invoice' => 'A paid invoice or payment awaiting review cannot be cancelled.',
            ]);
        }

        $invoice->update([
            'status' => 'cancelled',
            'balance_cents' => 0,
            'notes' => trim(($invoice->notes ? $invoice->notes.PHP_EOL : '').'Cancelled by the business owner before payment.'),
        ]);

        if ($invoice->offer && $invoice->offer->redeemed_count > 0) {
            $invoice->offer->decrement('redeemed_count');
        }

        return redirect()
            ->route('subscription.checkout')
            ->with('success', 'Checkout cancelled. You can now choose a different billing cycle or promotion.');
    }

    private function openInvoice(int $subscriptionId): ?PlatformSubscriptionInvoice
    {
        return PlatformSubscriptionInvoice::with(['payments', 'paymentSubmission'])
            ->where('tenant_subscription_id', $subscriptionId)
            ->whereIn('status', ['unpaid', 'overdue'])
            ->latest()
            ->first();
    }

    private function cancelReplaceableSelections(Tenant $tenant): void
    {
        $tenant->subscriptions()
            ->where('status', 'paused')
            ->with(['platformInvoices.offer', 'platformInvoices.paymentSubmission'])
            ->get()
            ->reject(fn ($subscription) => $subscription->platformInvoices
                ->contains(fn (PlatformSubscriptionInvoice $invoice) => $invoice->paymentSubmission?->status === 'pending'))
            ->each(function ($subscription): void {
                $subscription->platformInvoices
                    ->whereIn('status', ['unpaid', 'overdue'])
                    ->each(function (PlatformSubscriptionInvoice $invoice): void {
                        $invoice->update([
                            'status' => 'cancelled',
                            'balance_cents' => 0,
                            'notes' => trim(($invoice->notes ? $invoice->notes.PHP_EOL : '')
                                .'Cancelled after another package was selected.'),
                        ]);

                        if ($invoice->offer && $invoice->offer->redeemed_count > 0) {
                            $invoice->offer->decrement('redeemed_count');
                        }
                    });

                $subscription->update([
                    'status' => 'cancelled',
                    'ends_at' => now(),
                ]);
            });
    }
}
