<?php

namespace App\Services;

use App\Models\PlatformSubscriptionInvoice;
use App\Models\SubscriptionPaymentSubmission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubscriptionPaymentSubmissionService
{
    public function submit(
        PlatformSubscriptionInvoice $invoice,
        User $user,
        array $data
    ): SubscriptionPaymentSubmission {
        if ($invoice->tenant_id !== $user->tenant_id || $invoice->status === 'paid') {
            throw ValidationException::withMessages([
                'payment' => 'This subscription invoice cannot accept a payment submission.',
            ]);
        }

        $existing = $invoice->paymentSubmission;

        if ($existing?->status === 'pending') {
            throw ValidationException::withMessages([
                'payment' => 'A payment submission is already waiting for review.',
            ]);
        }

        return SubscriptionPaymentSubmission::updateOrCreate(
            ['platform_subscription_invoice_id' => $invoice->id],
            [
                'tenant_id' => $invoice->tenant_id,
                'submitted_by' => $user->id,
                'payment_method' => $data['payment_method'],
                'reference_no' => $data['reference_no'],
                'paid_on' => $data['paid_on'],
                'notes' => $data['notes'] ?? null,
                'status' => 'pending',
                'reviewed_by' => null,
                'reviewed_at' => null,
                'rejection_reason' => null,
            ]
        );
    }

    public function approve(
        SubscriptionPaymentSubmission $submission,
        User $admin,
        PlatformBillingService $billing
    ): void {
        DB::transaction(function () use ($submission, $admin, $billing): void {
            $submission = SubscriptionPaymentSubmission::lockForUpdate()
                ->with('invoice')
                ->findOrFail($submission->id);

            if ($submission->status !== 'pending') {
                throw ValidationException::withMessages([
                    'payment' => 'Only pending payment submissions can be approved.',
                ]);
            }

            $billing->recordPayment($submission->invoice, [
                'payment_method' => $submission->payment_method,
                'reference_no' => $submission->reference_no,
                'paid_on' => $submission->paid_on->toDateString(),
                'notes' => $submission->notes,
            ]);

            $submission->update([
                'status' => 'approved',
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
                'rejection_reason' => null,
            ]);
        });
    }

    public function reject(
        SubscriptionPaymentSubmission $submission,
        User $admin,
        string $reason
    ): void {
        if ($submission->status !== 'pending') {
            throw ValidationException::withMessages([
                'payment' => 'Only pending payment submissions can be rejected.',
            ]);
        }

        $submission->update([
            'status' => 'rejected',
            'reviewed_by' => $admin->id,
            'reviewed_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }
}
