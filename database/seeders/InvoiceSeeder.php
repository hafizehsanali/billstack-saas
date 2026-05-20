<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Tenant;
use App\Models\InvoiceItem;
use Illuminate\Database\Seeder;

class InvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (Tenant::all() as $tenant) {

            $customer = Customer::where(
                'tenant_id',
                $tenant->id
            )->first();

            $products = Product::where(
                'tenant_id',
                $tenant->id
            )->take(2)->get();

            $subtotal = 0;

            foreach ($products as $product) {
                $subtotal += $product->selling_price * 2;
            }

            $invoice = Invoice::create([
                'tenant_id' => $tenant->id,
                'customer_id' => $customer->id,
                'invoice_number' => 'INV-' . str_pad(
                    $tenant->id,
                    6,
                    '0',
                    STR_PAD_LEFT
                ),
                'status' => 'completed',
                'subtotal' => $subtotal,
                'tax' => 0,
                'discount' => 0,
                'total' => $subtotal,
            ]);

            foreach ($products as $product) {

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'price' => $product->selling_price,
                    'total' => $product->selling_price * 2,
                ]);

                $product->decrement('stock_quantity', 2);
            }
        }
    }
}
