<?php

namespace Database\Seeders;

use App\Models\PlatformSubscriptionInvoice;
use App\Models\SubscriptionPaymentSubmission;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class SubscriptionPaymentSubmissionSeeder extends Seeder
{
    public function run(): void
    {
        $invoice = PlatformSubscriptionInvoice::where('invoice_no', 'PLAT-DEMO-OVERDUE')->first();
        $owner = User::where('email', 'alpha@example.com')->first();

        if (! $invoice || ! $owner) {
            return;
        }

        SubscriptionPaymentSubmission::updateOrCreate(
            ['platform_subscription_invoice_id' => $invoice->id],
            [
                'tenant_id' => $invoice->tenant_id,
                'submitted_by' => $owner->id,
                'payment_method' => 'bank_transfer',
                'reference_no' => 'DEMO-BANK-2026',
                'paid_on' => today(),
                'notes' => 'Demo payment reference awaiting platform review.',
                'status' => 'pending',
                'reviewed_by' => null,
                'reviewed_at' => null,
                'rejection_reason' => null,
            ]
        );

        $rejectedTenant = Tenant::where('slug', 'demo-store-3')->first();
        $rejectedOwner = User::where('email', 'beta@example.com')->first();
        $admin = User::where('email', 'platform@test.com')->first();
        $subscription = $rejectedTenant?->currentSubscription;

        if (! $rejectedTenant || ! $rejectedOwner || ! $subscription) {
            return;
        }

        $rejectedInvoice = PlatformSubscriptionInvoice::updateOrCreate(
            ['invoice_no' => 'PLAT-DEMO-REJECTED'],
            [
                'tenant_id' => $rejectedTenant->id,
                'tenant_subscription_id' => $subscription->id,
                'billing_period' => now()->format('F Y'),
                'billing_cycle' => 'monthly',
                'subtotal_cents' => 499900,
                'discount_cents' => 0,
                'tax_cents' => 0,
                'total_cents' => 499900,
                'paid_cents' => 0,
                'balance_cents' => 499900,
                'status' => 'unpaid',
                'issued_on' => today()->subDays(3),
                'due_on' => today(),
                'notes' => 'Demo invoice with a rejected payment reference.',
            ]
        );

        SubscriptionPaymentSubmission::updateOrCreate(
            ['platform_subscription_invoice_id' => $rejectedInvoice->id],
            [
                'tenant_id' => $rejectedTenant->id,
                'submitted_by' => $rejectedOwner->id,
                'payment_method' => 'bank_transfer',
                'reference_no' => 'DEMO-REJECTED-REF',
                'paid_on' => today()->subDays(2),
                'notes' => 'Demo rejected payment reference.',
                'status' => 'rejected',
                'reviewed_by' => $admin?->id,
                'reviewed_at' => now()->subDay(),
                'rejection_reason' => 'The transaction reference could not be verified.',
            ]
        );
    }
}
