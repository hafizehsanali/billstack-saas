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
    public function getAccount(int $supplierId,$from,$to): array
    {
        $supplier = Supplier::findOrFail($supplierId);
       
        $purchases = Purchase::where('supplier_id', $supplierId)
            ->when($from, fn($q) => $q->whereDate('purchase_date', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('purchase_date', '<=', $to))
            ->latest()
            ->get();

        $payments = SupplierPayment::where('supplier_id', $supplierId)
            ->when($from, fn($q) => $q->whereDate('payment_date', '>=', $from))
            ->when($to, fn($q) => $q->whereDate('payment_date', '<=', $to))
            ->latest()
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

   
    protected function buildLedger($purchases, $payments):Collection
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
                 'notes' => $pay->note ?? '',
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
                 'notes' => $pay->note ?? '',
            ]);
        }

        return $ledger->sortBy('date')->values();
    }
    
    public function getLedgerWithBalance(int $supplierId, $from = null, $to = null): array
    {
        $data = $this->getAccount($supplierId,$from,$to );

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
        ]);
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