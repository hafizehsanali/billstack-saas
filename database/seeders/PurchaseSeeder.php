<?php

namespace Database\Seeders;

use App\Models\Purchase;
use App\Models\PurchaseItem;
use Illuminate\Database\Seeder;

class PurchaseSeeder extends Seeder
{
    public function run(): void
    {
         foreach (Tenant::all() as $tenant) {
            $purchase = Purchase::create([
                'tenant_id' => $tenant->id,
                'supplier_id' => 1,
                'purchase_no' => 'PUR-1001',
                'purchase_date' => now(),
                'subtotal' => 1000,
                'extra_expense' => 100,
                'discount' => 50,
                'total' => 1050,
                'paid_amount' => 500,
                'remaining_amount' => 550,
                'status' => 'partial',
                'notes' => 'Demo purchase',
                'created_by' => 1,
            ]);

            PurchaseItem::create([
                'purchase_id' => $purchase->id,
                'product_id' => 1,
                'quantity' => 10,
                'purchase_price' => 980,
                'line_total' => 9800,
            ]);

            $purchase2 = Purchase::create([
                'tenant_id' => $tenant->id,
                'supplier_id' => 2,
                'purchase_no' => 'PUR-1002',
                'purchase_date' => now(),
                'subtotal' => 10000,
                'extra_expense' => 500,
                'discount' => 0,
                'total' => 10500,
                'paid_amount' => 0,
                'remaining_amount' => 10500,
                'status' => 'pending',
                'notes' => 'Demo purchase ',
                'created_by' => 2,
            ]);

            PurchaseItem::create([
                'purchase_id' => $purchase2->id,
                'product_id' => 2,
                'quantity' => 10,
                'purchase_price' => 1500,
                'line_total' => 15000,
            ]);
         }
    }
}