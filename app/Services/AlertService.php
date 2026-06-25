<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Purchase;
use Illuminate\Support\Collection;

class AlertService
{
    public function summary(): array
    {
        $lowStockProducts = $this->lowStockProducts();
        $expiryBatches = $this->expiryBatches();
        $customerPaymentDues = $this->customerPaymentDues();
        $supplierPaymentDues = $this->supplierPaymentDues();

        return [
            'total' => $lowStockProducts->count()
                + $expiryBatches->count()
                + $customerPaymentDues->count()
                + $supplierPaymentDues->count(),
            'out_of_stock' => $lowStockProducts
                ->where('stock_quantity', '<=', 0)
                ->count(),
            'low_stock' => $lowStockProducts
                ->where('stock_quantity', '>', 0)
                ->count(),
            'customer_payment_due' => $customerPaymentDues->count(),
            'supplier_payment_due' => $supplierPaymentDues->count(),
            'expiry' => $expiryBatches->count(),
            'expired' => $expiryBatches
                ->filter(fn (ProductBatch $batch) => $batch->expiry_date?->isPast())
                ->count(),
        ];
    }

    public function lowStockProducts(): Collection
    {
        return Product::with('category')
            ->whereColumn('stock_quantity', '<=', 'low_stock_alert')
            ->orderBy('stock_quantity')
            ->orderBy('name')
            ->get();
    }

    public function expiryBatches(int $days = 30): Collection
    {
        return ProductBatch::with(['product', 'variant.unit'])
            ->where('quantity', '>', 0)
            ->whereNotNull('expiry_date')
            ->whereDate('expiry_date', '<=', now()->addDays($days)->toDateString())
            ->orderBy('expiry_date')
            ->orderBy('batch_number')
            ->get();
    }

    public function customerPaymentDues(): Collection
    {
        return Invoice::with('customer')
            ->whereIn('status', ['unpaid', 'partial'])
            ->where('remaining_amount', '>', 0)
            ->orderBy('sale_date')
            ->orderBy('id')
            ->get()
            ->groupBy('customer_id')
            ->map(function (Collection $invoices) {
                $firstInvoice = $invoices->first();

                return [
                    'customer' => $firstInvoice->customer,
                    'open_invoices' => $invoices->count(),
                    'remaining_amount' => $invoices->sum('remaining_amount'),
                    'oldest_date' => $invoices->min('sale_date'),
                    'latest_date' => $invoices->max('sale_date'),
                ];
            })
            ->sortByDesc('remaining_amount')
            ->values();
    }

    public function supplierPaymentDues(): Collection
    {
        return Purchase::with('supplier')
            ->whereIn('status', ['unpaid', 'partial'])
            ->where('remaining_amount', '>', 0)
            ->orderBy('purchase_date')
            ->orderBy('id')
            ->get()
            ->groupBy('supplier_id')
            ->map(function (Collection $purchases) {
                $firstPurchase = $purchases->first();

                return [
                    'supplier' => $firstPurchase->supplier,
                    'open_purchases' => $purchases->count(),
                    'remaining_amount' => $purchases->sum('remaining_amount'),
                    'oldest_date' => $purchases->min('purchase_date'),
                    'latest_date' => $purchases->max('purchase_date'),
                ];
            })
            ->sortByDesc('remaining_amount')
            ->values();
    }
}
