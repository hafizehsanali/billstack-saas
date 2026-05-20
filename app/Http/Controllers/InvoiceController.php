<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Invoice;
use App\Models\Customer;
use App\Models\InvoiceItem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;

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
            $quantity = $request->quantities[$productId];
            if ($quantity > $product->stock_quantity) {
                return back()->withErrors([
                    'stock' => $product->name .
                    ' does not have enough stock.'
                ]);
            }
            $lineTotal = $product->selling_price * $quantity;
            
            $subtotal += $lineTotal;
        }

        $invoice = Invoice::create([
            'customer_id' => $request->customer_id,
            'invoice_number' => 'INV-' . str_pad(Invoice::max('id') + 1,6,'0',STR_PAD_LEFT),
            'subtotal' => $subtotal,
            'tax' => 0,
            'discount' => 0,
            'total' => $subtotal,
        ]);

        foreach ($request->products as $index => $productId) {

            $product = Product::findOrFail($productId);
            $quantity = $request->quantities[$productId];
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
    public function show(Invoice $invoice)
    {
        $invoice->load([
            'customer',
            'items.product'
        ]);

        return view('invoices.show', compact('invoice'));
    }
    public function destroy(Invoice $invoice)
    {
            foreach ($invoice->items as $item) {

                if ($item->product) {

                    $item->product->increment(
                        'stock_quantity',
                        $item->quantity
                    );
                }
            }

            $invoice->delete();

            return redirect()
                ->route('invoices.index')
                ->with('success', 'Invoice deleted.');
    }

    public function cancel(Invoice $invoice)
    {
        // Prevent double cancellation
        if ($invoice->status === 'cancelled') {

            return back()->withErrors([
                'invoice' => 'Invoice already cancelled.'
            ]);
        }

        // Restore stock
        foreach ($invoice->items as $item) {

            if ($item->product) {

                $item->product->increment(
                    'stock_quantity',
                    $item->quantity
                );
            }
        }

        // Update status
        $invoice->update([
            'status' => 'cancelled'
        ]);

        return redirect()
            ->route('invoices.index')
            ->with(
                'success',
                'Invoice cancelled successfully.'
            );
    }

    public function pdf(Invoice $invoice)
    {
        if ($invoice->status === 'cancelled') {

            abort(403, 'Cancelled invoice cannot be downloaded.');
        }
        $invoice->load([
            'customer',
            'items.product'
        ]);

        $pdf = Pdf::loadView(
            'invoices.pdf',
            compact('invoice')
        );

        return $pdf->download(
            $invoice->invoice_number . '.pdf'
        );
    }
}