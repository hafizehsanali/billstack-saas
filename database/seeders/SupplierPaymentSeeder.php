<?php

namespace Database\Seeders;

use App\Models\SupplierPayment;
use Illuminate\Database\Seeder;

class SupplierPaymentSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Tenant::all() as $tenant) {
            SupplierPayment::create([

                'tenant_id' => $tenant->id,

                'supplier_id' => 1,

                'amount' => 500,

                'payment_date' => now(),

                'payment_method' => 'cash',

                'notes' => 'Advance payment',
            ]);
        }
    }
}