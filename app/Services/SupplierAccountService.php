<?php

namespace App\Services;

use App\Models\Supplier;
use App\Models\Purchase;
use App\Models\SupplierPayment;

class SupplierAccountService
{
    public function getAccount(int $supplierId): array
    {
        // Get supplier basic info
        $supplier = Supplier::findOrFail($supplierId);

        // Total purchases (liability created)
        $totalPurchases = Purchase::where('supplier_id', $supplierId)->sum('total');

        // Total payments (money already paid to supplier)
        $totalPayments = SupplierPayment::where('supplier_id', $supplierId)->sum('amount');

        // Remaining payable amount (what we still owe)
        $remaining_amount = $totalPurchases - $totalPayments;

        // Purchase history (latest first)
        $purchases = Purchase::where('supplier_id', $supplierId)->latest()->get();

        // Payment history (latest first)
        $payments = SupplierPayment::where('supplier_id', $supplierId)->latest()->get();

        return [
            'supplier' => $supplier,
            'total_purchases' => $totalPurchases,
            'total_payments' => $totalPayments,
            'remaining_amount' => $remaining_amount,
            'purchases' => $purchases,
            'payments' => $payments,
        ];
    }
}