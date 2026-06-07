<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class PurchaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Tenant::all() as $tenant) {
            $supplier = Supplier::where('tenant_id', $tenant->id)->first();
            $products = Product::where('tenant_id', $tenant->id)->take(3)->get();

            if (! $supplier || $products->isEmpty()) {
                continue;
            }

            for ($i = 1; $i <= 5; $i++) {
                $subtotal = 0;

                $purchase = Purchase::create([
                    'tenant_id' => $tenant->id,
                    'supplier_id' => $supplier->id,
                    'purchase_no' => 'PUR-DEMO-'.$tenant->id.'-'.$i,
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
                    $quantity = random_int(5, 20);
                    $price = random_int(100, 500);
                    $lineTotal = $quantity * $price;

                    PurchaseItem::create([
                        'purchase_id' => $purchase->id,
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'purchase_price' => $price,
                        'line_total' => $lineTotal,
                    ]);

                    $product->increment('stock_quantity', $quantity);
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
}
