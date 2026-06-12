<?php

namespace Database\Seeders;

use App\Models\PlatformSubscriptionInvoice;
use App\Models\SubscriptionPaymentSubmission;
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
    }
}
