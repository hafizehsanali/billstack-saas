<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Purchase;
use App\Models\SupplierPayment;

class SupplierPaymentSeeder extends Seeder
{
    public function run(): void
    {
        $purchases = Purchase::take(3)->get();

        foreach ($purchases as $purchase) {

            $amount = $purchase->total / 2;

            SupplierPayment::create([
                'tenant_id' => 1,
                'supplier_id' => $purchase->supplier_id,
                'purchase_id' => $purchase->id,
                'payment_date' => now(),
                'amount' => $amount,
                'payment_method' => 'cash',
                'reference_no' => 'PAY-' . rand(1000, 9999),
                'notes' => 'Demo supplier payment',
            ]);

            $purchase->update([
                'paid_amount' => $amount,
                'remaining_amount' => $purchase->total - $amount,
                'status' => 'partial',
            ]);
        }
    }
}