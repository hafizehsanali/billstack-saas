<?php

namespace App\Services;

use App\Models\Supplier;
use App\Models\Purchase;
use App\Models\SupplierPayment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SupplierAccountService
{
    // Complete supplier account summary
    public function getAccount(int $supplierId): array
    {
        $supplier = Supplier::findOrFail($supplierId);

        $purchases = Purchase::where('supplier_id', $supplierId)
            ->where('status', '!=', 'cancelled')
            ->orderBy('purchase_date')
            ->get();

        $payments = SupplierPayment::where('supplier_id', $supplierId)
            ->orderBy('payment_date')
            ->get();

        $totalPurchases = $purchases->sum('total');
        $totalPayments = $payments->sum('amount');
        $remaining = $totalPurchases - $totalPayments;

        $ledger = $this->buildLedger($purchases, $payments);

        return [
            'supplier' => $supplier,
            'total_purchases' => $totalPurchases,
            'total_payments' => $totalPayments,
            'remaining_amount' => $remaining,
            'ledger' => $ledger,
            'purchases' => $purchases,
            'payments' => $payments,
        ];
    }

    protected function buildLedger(Collection $purchases, Collection $payments): Collection
    {
        $ledger = collect();
        // Purchases = Debit
        foreach ($purchases as $p) {
            $ledger->push([
                'date' => $p->purchase_date,
                'type' => 'purchase',
                'ref' => $p->purchase_no,
                'debit' => $p->total,
                'credit' => 0,
                'notes' => $p->notes ?? '',
            ]);
        }
        // Payments = Credit
        foreach ($payments as $pay) {
            $ledger->push([
                'date' => $pay->payment_date,
                'type' => 'payment',
                'ref' => $pay->reference ?? $pay->id,
                'debit' => 0,
                'credit' => $pay->amount,
                'notes' => $pay->note ?? '',
            ]);
        }

        return $ledger->sortBy('date')->values();
    }



    /**
     * Store payment
     */
    public function storePayment(array $validated): SupplierPayment 
    {
        return DB::transaction(
            function () use ($validated) 
            {
                $payment =  SupplierPayment::create([
                        'tenant_id' => $validated['tenant_id'],
                        'supplier_id' => $validated['supplier_id'],
                        'purchase_id' => $validated['purchase_id'] ?? null,
                        'amount' => $validated['amount'],
                        'payment_method' => $validated['payment_method'] ?? null,
                        'payment_date' => $validated['payment_date'] ?? null,
                        'reference_no' =>  $validated['reference_no'] ?? null,
                        'notes' =>  $validated['notes'] ?? null,
                    ]);
                // Refresh Purchase
                if (!empty($validated['purchase_id'])) 
                {
                    $this->refreshPurchasePaymentStatus($validated['purchase_id']);
                }
                return $payment;
            }
        );
    }
    // Delete payment
    public function deletePayment(SupplierPayment $payment): void 
    {
        DB::transaction(function () use ($payment) 
        {
            $purchaseId =  $payment->purchase_id;
            $payment->delete();
            // Refresh Purchase
            if ($purchaseId) 
            {
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

        if (!$purchase) {
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

            'paid_amount' =>
                $paidAmount,

            'remaining_amount' =>
                $remainingAmount,

            'status' =>
                $status,
        ]);
    }
}