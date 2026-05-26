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

                $lineTotal = ($item['quantity'] * $item['purchase_price']);

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

    private function updateInventory(int $productId,int $newQuantity,float $newPrice): void 
    {

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

    public function update( Purchase $purchase,array $data): void 
    {

        DB::transaction(function () use ($purchase,$data) 
        {
            // STEP 1:
            foreach ($purchase->items as $oldItem) 
            {
                 $this->reverseInventory($oldItem->product_id,$oldItem->quantity);
            }

            /*
            |--------------------------------------------------------------------------
            | STEP 2:
            | DELETE OLD ITEMS
            |--------------------------------------------------------------------------
            */

            $purchase->items()->delete();

            /*
            |--------------------------------------------------------------------------
            | STEP 3:
            | UPDATE PURCHASE
            |--------------------------------------------------------------------------
            */

            $purchase->update([

                'supplier_id' => $data['supplier_id'],

                'purchase_date' => $data['purchase_date'],

                'subtotal' => $data['subtotal'],

                'extra_expense' => $data['extra_expense'] ?? 0,

                'discount' => $data['discount'] ?? 0,

                'total' => $data['total'],

                'paid_amount' => $data['paid_amount'] ?? 0,

                'remaining_amount' => $data['remaining_amount'] ?? 0,

                'status' => $data['status'],

                'notes' => $data['notes'] ?? null,
            ]);

            /*
            |--------------------------------------------------------------------------
            | STEP 4:
            | ADD NEW ITEMS + STOCK
            |--------------------------------------------------------------------------
            */

            foreach ($data['products'] as $item) {

                $lineTotal =
                    $item['quantity']
                    * $item['purchase_price'];

                PurchaseItem::create([

                    'purchase_id' => $purchase->id,

                    'product_id' => $item['product_id'],

                    'quantity' => $item['quantity'],

                    'purchase_price' => $item['purchase_price'],

                    'line_total' => $lineTotal,
                ]);
                
                $this->updateInventory($item['product_id'], $item['quantity'],$item['purchase_price']);
                // $product = Product::find($item['product_id']);
                // if ($product) 
                // {   
                //     // WEIGHTED AVERAGE COST
                //     $oldStock = $product->stock_quantity;
                //     $oldCost = $product->purchase_price;
                //     $newQty = $item['quantity'];
                //     $newCost = $item['purchase_price'];
                //     $totalOldValue = $oldStock * $oldCost;
                //     $totalNewValue = $newQty * $newCost;
                //     $finalQty = $oldStock + $newQty;
                //     $averageCost =$finalQty > 0 ? (($totalOldValue + $totalNewValue) / $finalQty) : $newCost;
                //     $product->update([
                //         'purchase_price' => round($averageCost, 2),
                //         'stock_quantity' => $finalQty,
                //     ]);
                // }
            }
        });
    }
     
    private function reverseInventory(int $productId,int $quantity): void 
    {
        $product = Product::findOrFail($productId);
        $product->decrement('stock_quantity',$quantity);
    }

    public function cancel(Purchase $purchase): void 
    {

        DB::transaction(function () use ($purchase) {

            if ($purchase->status === 'cancelled') {

                throw new \Exception(
                    'Purchase already cancelled.'
                );
            }

            foreach ($purchase->items as $item) {

                $product = Product::find(
                    $item->product_id
                );

                if (!$product) {
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | SAFETY CHECK
                |--------------------------------------------------------------------------
                */

                if (
                    $product->stock_quantity
                    < $item->quantity
                ) {

                    throw new \Exception(
                        'Cannot cancel purchase because stock was already sold.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | REVERSE STOCK
                |--------------------------------------------------------------------------
                */

                $product->decrement(
                    'stock_quantity',
                    $item->quantity
                );
            }

            /*
            |--------------------------------------------------------------------------
            | UPDATE STATUS
            |--------------------------------------------------------------------------
            */

            $purchase->update([

                'status' => 'cancelled',
            ]);
        });
    }
}