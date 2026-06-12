<?php

namespace App\Services;

use App\Models\PlatformSubscriptionInvoice;
use App\Models\PlatformSubscriptionPayment;
use App\Models\PlatformOffer;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PlatformBillingService
{
    public function createInvoice(Tenant $tenant, array $data): PlatformSubscriptionInvoice
    {
        $subscription = $tenant->currentSubscription()->with('plan')->first();

        if (! $subscription) {
            throw ValidationException::withMessages([
                'tenant_id' => 'Assign a subscription plan before creating an invoice.',
            ]);
        }

        $subtotalCents = $this->toCents($data['subtotal']);
        $discountCents = $this->toCents($data['discount'] ?? 0);
        $taxCents = $this->toCents($data['tax'] ?? 0);
        $totalCents = $subtotalCents - $discountCents + $taxCents;

        return PlatformSubscriptionInvoice::create([
            'tenant_id' => $tenant->id,
            'tenant_subscription_id' => $subscription->id,
            'invoice_no' => $this->nextInvoiceNumber(),
            'billing_period' => $data['billing_period'],
            'subtotal_cents' => $subtotalCents,
            'discount_cents' => $discountCents,
            'tax_cents' => $taxCents,
            'total_cents' => $totalCents,
            'paid_cents' => 0,
            'balance_cents' => $totalCents,
            'status' => 'unpaid',
            'issued_on' => $data['issued_on'],
            'due_on' => $data['due_on'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    public function createSubscriptionInvoice(
        TenantSubscription $subscription,
        ?string $promoCode = null,
        string $billingCycle = 'monthly'
    ): PlatformSubscriptionInvoice
    {
        return DB::transaction(function () use (
            $subscription,
            $promoCode,
            $billingCycle
        ): PlatformSubscriptionInvoice {
            $subscription->loadMissing(['tenant', 'plan']);

            $existingInvoice = PlatformSubscriptionInvoice::query()
                ->where('tenant_subscription_id', $subscription->id)
                ->whereIn('status', ['unpaid', 'overdue'])
                ->lockForUpdate()
                ->latest()
                ->first();

            if ($existingInvoice) {
                return $existingInvoice;
            }

            $subtotalCents = $billingCycle === 'annual'
                ? $subscription->plan->annual_price_cents
                : $subscription->plan->monthly_price_cents;

            if ($subtotalCents <= 0) {
                throw ValidationException::withMessages([
                    'billing_cycle' => 'The selected billing cycle is not available for this package.',
                ]);
            }

            $offer = $this->validatedOffer($subscription, $promoCode, $billingCycle);
            $discountCents = $offer?->discountFor($subtotalCents) ?? 0;
            $totalCents = $subtotalCents - $discountCents;

            $invoice = PlatformSubscriptionInvoice::create([
                'tenant_id' => $subscription->tenant_id,
                'tenant_subscription_id' => $subscription->id,
                'platform_offer_id' => $offer?->id,
                'offer_code' => $offer?->code,
                'invoice_no' => $this->nextInvoiceNumber(),
                'billing_period' => $billingCycle === 'annual'
                    ? now()->format('M Y').' - '.now()->addYear()->subDay()->format('M Y')
                    : now()->format('F Y'),
                'billing_cycle' => $billingCycle,
                'subtotal_cents' => $subtotalCents,
                'discount_cents' => $discountCents,
                'tax_cents' => 0,
                'total_cents' => $totalCents,
                'paid_cents' => 0,
                'balance_cents' => $totalCents,
                'status' => $totalCents === 0 ? 'paid' : 'unpaid',
                'issued_on' => today(),
                'due_on' => today()->addDays(3),
                'notes' => 'Subscription purchase invoice.',
            ]);

            if ($offer) {
                $offer->increment('redeemed_count');
            }

            if ($totalCents === 0) {
                $paidAccessStartsAt = $subscription->trial_ends_at?->isFuture()
                    ? $subscription->trial_ends_at->copy()
                    : now();
                $subscription->tenant->subscriptions()
                    ->where('id', '!=', $subscription->id)
                    ->where('status', 'active')
                    ->update([
                        'status' => 'cancelled',
                        'ends_at' => now(),
                    ]);
                $subscription->update([
                    'status' => 'active',
                    'starts_at' => now(),
                    'trial_ends_at' => null,
                    'ends_at' => $billingCycle === 'annual'
                        ? $paidAccessStartsAt->copy()->addYear()
                        : $paidAccessStartsAt->copy()->addMonth(),
                ]);
            }

            return $invoice;
        });
    }

    public function recordPayment(PlatformSubscriptionInvoice $invoice, array $data): PlatformSubscriptionPayment
    {
        return DB::transaction(function () use ($invoice, $data): PlatformSubscriptionPayment {
            $lockedInvoice = PlatformSubscriptionInvoice::query()
                ->lockForUpdate()
                ->findOrFail($invoice->id);

            $paidCents = (int) $lockedInvoice->payments()->sum('amount_cents');
            $balanceCents = max($lockedInvoice->total_cents - $paidCents, 0);

            if ($balanceCents === 0) {
                throw ValidationException::withMessages([
                    'payment' => 'This invoice is already fully paid.',
                ]);
            }

            $payment = $lockedInvoice->payments()->create([
                'tenant_id' => $lockedInvoice->tenant_id,
                'amount_cents' => $balanceCents,
                'payment_method' => $data['payment_method'],
                'reference_no' => $data['reference_no'] ?? null,
                'paid_on' => $data['paid_on'],
                'notes' => $data['notes'] ?? null,
            ]);

            $lockedInvoice->update([
                'paid_cents' => $lockedInvoice->total_cents,
                'balance_cents' => 0,
                'status' => 'paid',
            ]);

            $lockedInvoice->paymentSubmission()
                ->where('status', 'pending')
                ->update([
                    'status' => 'approved',
                    'reviewed_by' => auth()->id(),
                    'reviewed_at' => now(),
                    'rejection_reason' => null,
                ]);

            if ($lockedInvoice->subscription) {
                $paidAccessStartsAt = $lockedInvoice->subscription->trial_ends_at?->isFuture()
                    ? $lockedInvoice->subscription->trial_ends_at->copy()
                    : now();
                $lockedInvoice->subscription->tenant->subscriptions()
                    ->where('id', '!=', $lockedInvoice->subscription->id)
                    ->where('status', 'active')
                    ->update([
                        'status' => 'cancelled',
                        'ends_at' => now(),
                    ]);
                $lockedInvoice->subscription->update([
                    'status' => 'active',
                    'starts_at' => now(),
                    'trial_ends_at' => null,
                    'ends_at' => $lockedInvoice->billing_cycle === 'annual'
                        ? $paidAccessStartsAt->copy()->addYear()
                        : $paidAccessStartsAt->copy()->addMonth(),
                ]);
            }

            return $payment;
        });
    }

    private function nextInvoiceNumber(): string
    {
        do {
            $invoiceNumber = 'PLAT-'.now()->format('Ym').'-'.Str::upper(Str::random(6));
        } while (PlatformSubscriptionInvoice::where('invoice_no', $invoiceNumber)->exists());

        return $invoiceNumber;
    }

    private function validatedOffer(
        TenantSubscription $subscription,
        ?string $promoCode,
        string $billingCycle
    ): ?PlatformOffer {
        if (! $promoCode) {
            return null;
        }

        $offer = PlatformOffer::with('plans')
            ->where('code', Str::upper($promoCode))
            ->lockForUpdate()
            ->first();

        if (! $offer || ! $offer->isCurrentlyAvailable()) {
            throw ValidationException::withMessages([
                'promo_code' => 'This promotion code is invalid or no longer available.',
            ]);
        }

        if (! $offer->appliesTo($subscription->plan)) {
            throw ValidationException::withMessages([
                'promo_code' => 'This promotion code does not apply to the selected package.',
            ]);
        }

        if (! $offer->appliesToBillingCycle($billingCycle)) {
            throw ValidationException::withMessages([
                'promo_code' => 'This promotion code does not apply to the selected billing cycle.',
            ]);
        }

        return $offer;
    }

    private function toCents(int|float|string $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }
}
