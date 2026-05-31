<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Tenant;
use App\Models\InvoiceItem;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class InvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            'unpaid',
            'partial',
            'paid',
            'cancelled',
        ];

        foreach (Tenant::all() as $tenant) {

            $customer = Customer::where('tenant_id', $tenant->id)->first();
            $products = Product::where('tenant_id', $tenant->id)->take(3)->get();

            if (!$customer || $products->isEmpty()) {
                continue;
            }

            foreach ($statuses as $index => $status) {

                $subtotal = 0;

                foreach ($products as $product) {
                    $subtotal += $product->selling_price * 2;
                }

                $discount = 0;
                $tax = 0;
                $total = $subtotal - $discount + $tax;

                // Ledger logic
                $paidAmount = match ($status) {
                    'paid' => $total,
                    'partial' => round($total * 0.4, 2),
                    'unpaid', 'cancelled' => 0,
                };

                $remainingAmount = $total - $paidAmount;

                $invoice = Invoice::create([

                    'tenant_id' => $tenant->id,
                    'customer_id' => $customer->id,
                    'invoice_no' => 'INV-' . str_pad(Invoice::max('id') + 1, 6, '0', STR_PAD_LEFT),

                    'status' => $status,

                    'sale_date' => Carbon::now()->subDays($index),

                    'subtotal' => $subtotal,
                    'discount' => $discount,
                    'tax' => $tax,
                    'total' => $total,

                    // IMPORTANT FOR LEDGER
                    'paid_amount' => $paidAmount,
                    'remaining_amount' => $remainingAmount,
                ]);

                foreach ($products as $product) {

                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'product_id' => $product->id,
                        'quantity' => 2,
                        'price' => $product->selling_price,
                        'total' => $product->selling_price * 2,
                    ]);

                    // stock deduction only if not cancelled
                    if ($status !== 'cancelled') {
                        $product->decrement('stock_quantity', 2);
                    }
                }
            }
        }
    }
}
