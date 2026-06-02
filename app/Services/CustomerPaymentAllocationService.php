<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerPayment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomerPaymentAllocationService
{
    public function record(Customer $customer, array $data): void
    {
        DB::transaction(function () use ($customer, $data) {
            $invoices = $customer->invoices()
                ->where('status', '!=', 'cancelled')
                ->where('remaining_amount', '>', 0)
                ->orderBy('sale_date')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $outstanding = (float) $invoices->sum('remaining_amount');
            $paymentAmount = (float) $data['amount'];

            if ($outstanding <= 0) {
                throw ValidationException::withMessages([
                    'amount' => 'This customer has no outstanding balance.',
                ]);
            }

            if ($paymentAmount > $outstanding) {
                throw ValidationException::withMessages([
                    'amount' => 'Payment amount cannot be greater than customer outstanding balance.',
                ]);
            }

            $remainingPayment = $paymentAmount;

            // Apply receipts to older invoices first to keep customer ageing predictable.
            foreach ($invoices as $invoice) {
                if ($remainingPayment <= 0) {
                    break;
                }

                $allocatedAmount = min($remainingPayment, (float) $invoice->remaining_amount);

                CustomerPayment::create([
                    'tenant_id' => $customer->tenant_id,
                    'customer_id' => $customer->id,
                    'invoice_id' => $invoice->id,
                    'amount' => $allocatedAmount,
                    'payment_method' => $data['payment_method'],
                    'payment_date' => isset($data['payment_date'])
                        ? Carbon::parse($data['payment_date'])->toDateString()
                        : now()->toDateString(),
                    'reference_no' => $data['reference_no'] ?? null,
                    'notes' => $data['notes'] ?? null,
                ]);

                $newPaidAmount = $invoice->payments()->sum('amount');
                $newRemainingAmount = max($invoice->total - $newPaidAmount, 0);

                $invoice->update([
                    'paid_amount' => $newPaidAmount,
                    'remaining_amount' => $newRemainingAmount,
                    'status' => $newRemainingAmount <= 0 ? 'paid' : 'partial',
                ]);

                $remainingPayment -= $allocatedAmount;
            }
        });
    }
}
