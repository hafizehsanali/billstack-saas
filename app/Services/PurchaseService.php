<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    public function store(array $data): Purchase
    {
        return DB::transaction(function () use ($data) {

            $subtotal = 0;

            /*
            |--------------------------------------------------------------------------
            | Calculate subtotal
            |--------------------------------------------------------------------------
            */

            foreach ($data['products'] as $item) {

                $subtotal += (
                    $item['quantity'] *
                    $item['purchase_price']
                );
            }

            $discount = $data['discount'] ?? 0;
            $extraExpense = $data['extra_expense'] ?? 0;
            $paidAmount = $data['paid_amount'] ?? 0;

            $total = ($subtotal - $discount) + $extraExpense;

            $remainingAmount = $total - $paidAmount;

            /*
            |--------------------------------------------------------------------------
            | Determine payment status
            |--------------------------------------------------------------------------
            */

            $status = 'unpaid';

            if ($paidAmount >= $total) {
                $status = 'paid';
            } elseif ($paidAmount > 0) {
                $status = 'partial';
            }

            /*
            |--------------------------------------------------------------------------
            | Create purchase
            |--------------------------------------------------------------------------
            */

            $purchase = Purchase::create([

                'tenant_id' => auth()->user()->tenant_id,

                'supplier_id' => $data['supplier_id'],

                'purchase_no' => 'PUR-' . now()->timestamp,

                'subtotal' => $subtotal,

                'discount' => $discount,

                'extra_expense' => $extraExpense,

                'total' => $total,

                'paid_amount' => $paidAmount,

                'remaining_amount' => $remainingAmount,

                'purchase_date' => $data['purchase_date'],

                'note' => $data['note'] ?? null,

                'status' => $status,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Save items + update inventory
            |--------------------------------------------------------------------------
            */

            foreach ($data['products'] as $item) {

                $product = Product::findOrFail(
                    $item['product_id']
                );

                $quantity = $item['quantity'];
                $purchasePrice = $item['purchase_price'];

                /*
                |--------------------------------------------------------------------------
                | Save purchase item
                |--------------------------------------------------------------------------
                */

                PurchaseItem::create([

                    'purchase_id' => $purchase->id,

                    'product_id' => $product->id,

                    'quantity' => $quantity,

                    'purchase_price' => $purchasePrice,

                    'total' => (
                        $quantity * $purchasePrice
                    ),
                ]);

                /*
                |--------------------------------------------------------------------------
                | Average Cost Calculation
                |--------------------------------------------------------------------------
                */

                $oldStock = $product->stock_quantity;

                $oldCost = $product->purchase_price;

                $oldStockValue = (
                    $oldStock * $oldCost
                );

                $newStockValue = (
                    $quantity * $purchasePrice
                );

                $totalQuantity = (
                    $oldStock + $quantity
                );

                $newAverageCost = 0;

                if ($totalQuantity > 0) {

                    $newAverageCost = (
                        ($oldStockValue + $newStockValue)
                        / $totalQuantity
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Update inventory
                |--------------------------------------------------------------------------
                */

                $product->update([

                    'stock_quantity' => $totalQuantity,

                    'purchase_price' => $newAverageCost,
                ]);
            }

            return $purchase;
        });
    }
}