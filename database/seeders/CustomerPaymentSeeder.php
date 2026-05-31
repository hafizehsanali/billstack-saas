<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Invoice;
use App\Models\CustomerPayment;
use Illuminate\Database\Seeder;

class CustomerPaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $invoices = Invoice::whereIn('status',['paid', 'partial'])->get();
       
        foreach ($invoices as $invoice) {

            if ($invoice->status === 'paid') {

                CustomerPayment::create([

                    'tenant_id' => $invoice->tenant_id,
                    'invoice_id' => $invoice->id,
                    'customer_id' => $invoice->customer_id,
                    'amount' => $invoice->total,
                    'payment_date'=> now(),
                    'reference_no' => 'ADC-123',
                    'payment_method' => 'cash',
                    'notes' => 'ABC',
                ]);

            } else if ($invoice->status === 'partial'){
                 CustomerPayment::create([
                    'tenant_id' => $invoice->tenant_id,
                    'customer_id' => $invoice->customer_id,
                    'invoice_id' => $invoice->id,
                    'amount' => $invoice->total / 2,
                    'payment_date'=> now(),
                    'reference_no' => 'ADC-123',
                    'payment_method' => 'cash',
                    'notes' => 'ABC',
                ]);
            } 
        }
    }
}
