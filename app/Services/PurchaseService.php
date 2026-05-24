<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Purchase;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    public function store(array $data): Purchase
    {
        return DB::transaction(function () use ($data) {

            $subtotal = $data['subtotal'];

            $extraExpense = $data['extra_expense'] ?? 0;

            $discount = $data['discount'] ?? 0;

            $paidAmount = $data['paid_amount'] ?? 0;

            $total = ($subtotal + $extraExpense) - $discount;

            $remainingAmount = $total - $paidAmount;

            // Purchase status
            $status = 'unpaid';

            if ($remainingAmount <= 0) {
                $status = 'paid';
            } elseif ($paidAmount > 0) {
                $status = 'partial';
            }

            // Create purchase
            $purchase = Purchase::create([
                'tenant_id' => auth()->user()->tenant_id,

                'supplier_id' => $data['supplier_id'],

                'purchase_no' => $data['purchase_no'],

                'purchase_date' => $data['purchase_date'],

                'subtotal' => $subtotal,

                'extra_expense' => $extraExpense,

                'discount' => $discount,

                'total' => $total,

                'paid_amount' => $paidAmount,

                'remaining_amount' => $remainingAmount,

                'status' => $status,

                'notes' => $data['notes'] ?? null,

                'created_by' => auth()->id(),
            ]);

            // Save items + inventory update
            foreach ($data['products'] as $item) {

                $lineTotal = (
                    $item['quantity']
                    * $item['purchase_price']
                );

                $purchase->items()->create([
                    'product_id' => $item['product_id'],

                    'quantity' => $item['quantity'],

                    'purchase_price' => $item['purchase_price'],

                    'line_total' => $lineTotal,
                ]);

                $this->updateInventory(
                    $item['product_id'],
                    $item['quantity'],
                    $item['purchase_price']
                );
            }

            return $purchase;
        });
    }

    private function updateInventory(
        int $productId,
        int $newQuantity,
        float $newPrice
    ): void {

        $product = Product::findOrFail($productId);

        $oldStock = $product->stock_quantity;

        $oldAveragePrice = $product->purchase_price;

        // New stock
        $newStock = $oldStock + $newQuantity;

        // Weighted average
        $newAveragePrice = (
            ($oldStock * $oldAveragePrice)
            +
            ($newQuantity * $newPrice)
        ) / max($newStock, 1);

        $product->update([
            'stock_quantity' => $newStock,

            'purchase_price' => round(
                $newAveragePrice,
                2
            ),
        ]);
    }
}