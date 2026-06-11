<?php

namespace App\Services;

use App\Models\PlatformSubscriptionInvoice;
use App\Models\PlatformSubscriptionPayment;
use App\Models\Tenant;
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

    public function recordPayment(PlatformSubscriptionInvoice $invoice, array $data): PlatformSubscriptionPayment
    {
        return DB::transaction(function () use ($invoice, $data): PlatformSubscriptionPayment {
            $lockedInvoice = PlatformSubscriptionInvoice::query()
                ->lockForUpdate()
                ->findOrFail($invoice->id);

            $paidCents = (int) $lockedInvoice->payments()->sum('amount_cents');
            $balanceCents = max($lockedInvoice->total_cents - $paidCents, 0);
            $amountCents = $this->toCents($data['amount']);

            if ($balanceCents === 0) {
                throw ValidationException::withMessages([
                    'amount' => 'This invoice is already fully paid.',
                ]);
            }

            if ($amountCents > $balanceCents) {
                throw ValidationException::withMessages([
                    'amount' => 'Payment cannot be greater than the invoice balance.',
                ]);
            }

            $payment = $lockedInvoice->payments()->create([
                'tenant_id' => $lockedInvoice->tenant_id,
                'amount_cents' => $amountCents,
                'payment_method' => $data['payment_method'],
                'reference_no' => $data['reference_no'] ?? null,
                'paid_on' => $data['paid_on'],
                'notes' => $data['notes'] ?? null,
            ]);

            $newPaidCents = $paidCents + $amountCents;
            $newBalanceCents = max($lockedInvoice->total_cents - $newPaidCents, 0);

            $lockedInvoice->update([
                'paid_cents' => $newPaidCents,
                'balance_cents' => $newBalanceCents,
                'status' => $newBalanceCents === 0 ? 'paid' : 'partial',
            ]);

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

    private function toCents(int|float|string $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }
}
