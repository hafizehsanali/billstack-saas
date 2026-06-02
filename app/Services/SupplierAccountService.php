<?php

namespace App\Services;

use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SupplierAccountService
{
    // Complete supplier account summary
    public function getAccount(int $supplierId, $from, $to): array
    {
        $supplier = Supplier::findOrFail($supplierId);

        $purchases = Purchase::where('supplier_id', $supplierId)
            ->when($from, fn ($q) => $q->whereDate('purchase_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('purchase_date', '<=', $to))
            ->latest()
            ->get();

        $payments = SupplierPayment::where('supplier_id', $supplierId)
            ->when($from, fn ($q) => $q->whereDate('payment_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('payment_date', '<=', $to))
            ->latest()
            ->get();

        $returns = PurchaseReturn::where('supplier_id', $supplierId)
            ->when($from, fn ($q) => $q->whereDate('return_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('return_date', '<=', $to))
            ->latest()
            ->get();

        $totalPurchases = $purchases->sum('total');
        $totalPayments = $payments->sum('amount');
        $totalReturns = $returns->sum('total_amount');
        $remaining = $totalPurchases - $totalPayments - $totalReturns;

        $ledger = $this->buildLedger($purchases, $payments, $returns);

        return [
            'supplier' => $supplier,
            'total_purchases' => $totalPurchases,
            'total_payments' => $totalPayments,
            'total_returns' => $totalReturns,
            'remaining_amount' => $remaining,
            'ledger' => $ledger,
            'purchases' => $purchases,
            'payments' => $payments,
        ];
    }

    protected function buildLedger($purchases, $payments, $returns): Collection
    {
        $ledger = collect();
        // Purchases = Debit
        foreach ($purchases as $purchase) {
            $ledger->push([
                'date' => $purchase->purchase_date,
                'type' => 'Purchase',
                'reference' => $purchase->purchase_no,
                'reference_id' => $purchase->id,
                'debit' => $purchase->total,
                'credit' => 0,
                'description' => 'Purchase Invoice',
                'notes' => $purchase->notes ?? '',
            ]);
        }
        // Payments = Credit
        foreach ($payments as $payment) {
            $ledger->push([
                'date' => $payment->payment_date,
                'type' => 'Payment',
                'reference' => $payment->reference_no,
                'reference_id' => $payment->id,
                'debit' => 0,
                'credit' => $payment->amount,
                'description' => 'Supplier Payment',
                'notes' => $payment->notes ?? '',
            ]);
        }

        foreach ($returns as $purchaseReturn) {
            $ledger->push([
                'date' => $purchaseReturn->return_date,
                'type' => 'Supplier Return',
                'reference' => $purchaseReturn->return_no,
                'reference_id' => $purchaseReturn->purchase_id,
                'debit' => 0,
                'credit' => $purchaseReturn->total_amount,
                'description' => 'Supplier Return',
                'notes' => $purchaseReturn->notes ?? '',
            ]);
        }

        return $ledger->sortBy('date')->values();
    }

    public function getLedgerWithBalance(int $supplierId, $from = null, $to = null): array
    {
        $data = $this->getAccount($supplierId, $from, $to);

        $openingBalance = $data['supplier']->opening_balance ?? 0;

        $balance = $openingBalance;

        $ledger = collect();

        // Opening row (Tally style)
        $ledger->push([
            'date' => null,
            'type' => 'Opening',
            'reference' => '-',
            'reference_id' => '-1',
            'description' => 'Opening Balance',
            'debit' => 0,
            'credit' => 0,
            'balance' => $balance,
        ]);

        foreach ($data['ledger'] as $row) {

            $balance += $row['debit'] - $row['credit'];

            $ledger->push([
                'date' => $row['date'],
                'type' => $row['type'],
                'reference' => $row['reference'],
                'reference_id' => $row['reference_id'],
                'description' => $row['description'],
                'debit' => $row['debit'],
                'credit' => $row['credit'],
                'balance' => $balance,
            ]);
        }

        return array_merge($data, [
            'ledger' => $ledger,
            'closing_balance' => $balance,
            'opening_balance' => $openingBalance,
            'outstanding_payable' => Purchase::where('supplier_id', $supplierId)
                ->where('tenant_id', $data['supplier']->tenant_id)
                ->where('status', '!=', 'cancelled')
                ->sum('remaining_amount'),
        ]);
    }

    /**
     * Store payment
     */
    public function storePayment(array $validated): SupplierPayment
    {
        return DB::transaction(function () use ($validated) {
            if (! empty($validated['purchase_id'])) {
                return $this->storePaymentForPurchase($validated);
            }

            return $this->allocatePaymentToOldestPurchases($validated);
        });
    }

    private function storePaymentForPurchase(array $validated): SupplierPayment
    {
        $purchase = Purchase::where('tenant_id', $validated['tenant_id'])
            ->where('supplier_id', $validated['supplier_id'])
            ->lockForUpdate()
            ->findOrFail($validated['purchase_id']);

        if ($purchase->status === 'cancelled') {
            throw ValidationException::withMessages([
                'purchase_id' => 'Cannot record payment against a cancelled purchase.',
            ]);
        }

        if ((float) $validated['amount'] > (float) $purchase->remaining_amount) {
            throw ValidationException::withMessages([
                'amount' => 'Payment amount cannot be greater than purchase remaining balance.',
            ]);
        }

        $payment = $this->createPayment($validated, $purchase->id, (float) $validated['amount']);
        $this->refreshPurchasePaymentStatus($purchase->id);

        return $payment;
    }

    private function allocatePaymentToOldestPurchases(array $validated): SupplierPayment
    {
        $purchases = Purchase::where('tenant_id', $validated['tenant_id'])
            ->where('supplier_id', $validated['supplier_id'])
            ->where('status', '!=', 'cancelled')
            ->where('remaining_amount', '>', 0)
            ->orderBy('purchase_date')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $outstanding = (float) $purchases->sum('remaining_amount');
        $paymentAmount = (float) $validated['amount'];

        if ($outstanding <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'This supplier has no outstanding purchase balance.',
            ]);
        }

        if ($paymentAmount > $outstanding) {
            throw ValidationException::withMessages([
                'amount' => 'Payment amount cannot be greater than supplier outstanding balance.',
            ]);
        }

        $remainingPayment = $paymentAmount;
        $firstPayment = null;

        // Apply payments to older purchases first so supplier ageing remains predictable.
        foreach ($purchases as $purchase) {
            if ($remainingPayment <= 0) {
                break;
            }

            $allocatedAmount = min($remainingPayment, (float) $purchase->remaining_amount);
            $payment = $this->createPayment($validated, $purchase->id, $allocatedAmount);
            $firstPayment ??= $payment;

            $this->refreshPurchasePaymentStatus($purchase->id);
            $remainingPayment -= $allocatedAmount;
        }

        return $firstPayment;
    }

    private function createPayment(array $validated, int $purchaseId, float $amount): SupplierPayment
    {
        return SupplierPayment::create([
            'tenant_id' => $validated['tenant_id'],
            'supplier_id' => $validated['supplier_id'],
            'purchase_id' => $purchaseId,
            'amount' => $amount,
            'payment_method' => $validated['payment_method'] ?? null,
            'payment_date' => $validated['payment_date'] ?? null,
            'reference_no' => $validated['reference_no'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);
    }

    // Delete payment
    public function deletePayment(SupplierPayment $payment): void
    {
        DB::transaction(function () use ($payment) {
            $purchaseId = $payment->purchase_id;
            $payment->delete();
            // Refresh Purchase
            if ($purchaseId) {
                $this->refreshPurchasePaymentStatus($purchaseId);
            }
        });
    }

    /**
     * Refresh purchase balance
     */
    public function refreshPurchasePaymentStatus(
        int $purchaseId
    ): void {

        $purchase = Purchase::find(
            $purchaseId
        );

        if (! $purchase) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Paid Amount
        |--------------------------------------------------------------------------
        */

        $paidAmount = SupplierPayment::query()

            ->where(
                'purchase_id',
                $purchase->id
            )

            ->sum('amount');

        /*
        |--------------------------------------------------------------------------
        | Remaining Amount
        |--------------------------------------------------------------------------
        */

        $remainingAmount =
            $purchase->total - $paidAmount;

        /*
        |--------------------------------------------------------------------------
        | Status
        |--------------------------------------------------------------------------
        */

        $status = 'unpaid';

        if (
            $paidAmount >= $purchase->total
        ) {

            $status = 'paid';

            $remainingAmount = 0;

        } elseif ($paidAmount > 0) {

            $status = 'partial';
        }

        /*
        |--------------------------------------------------------------------------
        | Update Purchase
        |--------------------------------------------------------------------------
        */

        $purchase->update([

            'paid_amount' => $paidAmount,

            'remaining_amount' => $remainingAmount,

            'status' => $status,
        ]);
    }
}
