<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $invoices = Invoice::whereIn('status',['paid', 'partial'])->get();

        foreach ($invoices as $invoice) {

            if ($invoice->status === 'paid') {

                Payment::create([

                    'tenant_id' => $invoice->tenant_id,

                    'invoice_id' => $invoice->id,

                    'customer_id' => $invoice->customer_id,

                    'amount' => $invoice->total,

                    'method' => 'cash',
                ]);

            } else {

                Payment::create([

                    'tenant_id' => $invoice->tenant_id,

                    'invoice_id' => $invoice->id,

                    'customer_id' => $invoice->customer_id,

                    'amount' => $invoice->total / 2,

                    'method' => 'cash',
                ]);
            }
        }
    }
}
