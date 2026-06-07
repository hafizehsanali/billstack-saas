<?php

namespace Database\Seeders;

use App\Models\Purchase;
use App\Models\SupplierPayment;
use Illuminate\Database\Seeder;

class SupplierPaymentSeeder extends Seeder
{
    public function run(): void
    {
        $purchases = Purchase::query()
            ->where('status', 'unpaid')
            ->get()
            ->groupBy('tenant_id')
            ->flatMap(fn ($tenantPurchases) => $tenantPurchases->take(3));

        foreach ($purchases as $purchase) {
            $amount = $purchase->total / 2;

            SupplierPayment::create([
                'tenant_id' => $purchase->tenant_id,
                'supplier_id' => $purchase->supplier_id,
                'purchase_id' => $purchase->id,
                'payment_date' => now(),
                'amount' => $amount,
                'payment_method' => 'cash',
                'reference_no' => 'PAY-DEMO-'.$purchase->id,
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
