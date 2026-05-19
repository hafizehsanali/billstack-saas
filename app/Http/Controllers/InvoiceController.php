<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Invoice;
use App\Models\Customer;
use App\Models\InvoiceItem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class InvoiceController extends Controller
{
    public function index()
    {
        $invoices = Invoice::with('customer')
            ->latest()
            ->get();

        return view('invoices.index', compact('invoices'));
    }

    public function create()
    {
        $customers = Customer::all();

        $products = Product::all();

        return view('invoices.create', compact(
            'customers',
            'products'
        ));
    }

    public function store(Request $request)
    {
        $subtotal = 0;

        foreach ($request->products as $index => $productId) {

            $product = Product::findOrFail($productId);

            $quantity = $request->quantities[$index];

            $lineTotal = $product->selling_price * $quantity;

            $subtotal += $lineTotal;
        }

        $invoice = Invoice::create([
            'customer_id' => $request->customer_id,
            'invoice_number' => 'INV-' . strtoupper(Str::random(8)),
            'subtotal' => $subtotal,
            'tax' => 0,
            'discount' => 0,
            'total' => $subtotal,
        ]);

        foreach ($request->products as $index => $productId) {

            $product = Product::findOrFail($productId);

            $quantity = $request->quantities[$index];

            $lineTotal = $product->selling_price * $quantity;

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'price' => $product->selling_price,
                'total' => $lineTotal,
            ]);

            // Reduce stock
            $product->decrement('stock_quantity', $quantity);
        }

        return redirect()->route('invoices.index');
    }
}