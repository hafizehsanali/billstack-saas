<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Product;
use App\Models\Supplier;

class PurchaseSeeder extends Seeder
{
    public function run(): void
    {
        $supplier = Supplier::first();
        $products = Product::take(3)->get();

        if (!$supplier || $products->count() == 0) {
            return;
        }

        for ($i = 1; $i <= 5; $i++) {

            $subtotal = 0;

            $purchase = Purchase::create([
                'tenant_id' => 1,
                'supplier_id' => $supplier->id,
                'purchase_no' => 'PUR-DEMO-' . $i,
                'purchase_date' => now()->subDays($i),
                'subtotal' => 0,
                'extra_expense' => 0,
                'discount' => 0,
                'total' => 0,
                'paid_amount' => 0,
                'remaining_amount' => 0,
                'status' => 'unpaid',
            ]);

            foreach ($products as $product) {

                $qty = rand(5, 20);
                $price = rand(100, 500);
                $lineTotal = $qty * $price;

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'purchase_price' => $price,
                    'line_total' => $lineTotal,
                ]);

                $product->increment('stock_quantity', $qty);

                $subtotal += $lineTotal;
            }

            $purchase->update([
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'remaining_amount' => $subtotal,
            ]);
        }
    }
}