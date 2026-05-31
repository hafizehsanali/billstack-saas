<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvoiceRequest;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Services\StockLedgerService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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

    public function store(StoreInvoiceRequest $request)
    {
        $data = $request->validated();

        DB::transaction(function () use ($data) {
            $stockLedger = app(StockLedgerService::class);

            $subtotal = 0;
            $requestedQuantities = [];

            foreach ($data['products'] as $item) {
                $productId = $item['product_id'];
                $quantity = (int) $item['quantity'];
                $price = (float) $item['price'];

                $requestedQuantities[$productId] =
                    ($requestedQuantities[$productId] ?? 0) + $quantity;

                $subtotal += $quantity * $price;
            }

            $products = Product::whereIn(
                'id',
                array_keys($requestedQuantities)
            )->lockForUpdate()->get()->keyBy('id');

            foreach ($requestedQuantities as $productId => $quantity) {
                $product = $products[$productId];

                if ($quantity > $product->stock_quantity) {
                    throw ValidationException::withMessages([
                        'stock' => $product->name.' does not have enough stock.',
                    ]);
                }
            }

            $tax = (float) ($data['tax'] ?? 0);
            $discount = (float) ($data['discount'] ?? 0);
            $extraExpense = (float) ($data['extra_expense'] ?? 0);
            $paidAmount = (float) ($data['paid_amount'] ?? 0);
            $total = max(($subtotal + $tax + $extraExpense) - $discount, 0);

            if ($paidAmount > $total) {
                throw ValidationException::withMessages([
                    'paid_amount' => 'Paid amount cannot be greater than invoice total.',
                ]);
            }

            $remainingAmount = max($total - $paidAmount, 0);
            $status = 'unpaid';

            if ($paidAmount >= $total && $total > 0) {
                $status = 'paid';
            } elseif ($paidAmount > 0) {
                $status = 'partial';
            }

            $invoice = Invoice::create([
                'tenant_id' => auth()->user()->tenant_id,
                'customer_id' => $data['customer_id'],
                'invoice_no' => $data['invoice_no'],
                'sale_date' => $data['sale_date'],
                'subtotal' => $subtotal,
                'tax' => $tax,
                'discount' => $discount,
                'extra_expense' => $extraExpense,
                'total' => $total,
                'paid_amount' => $paidAmount,
                'remaining_amount' => $remainingAmount,
                'status' => $status,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['products'] as $item) {
                $product = $products[$item['product_id']];
                $quantity = (int) $item['quantity'];
                $price = (float) $item['price'];
                $lineTotal = $quantity * $price;

                $invoiceItem = InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'price' => $price,
                    'total' => $lineTotal,
                ]);

                $product->decrement('stock_quantity', $quantity);
                $product->refresh();

                $stockLedger->record($product, 'sale', $quantity, [
                    'direction' => 'out',
                    'unit_cost' => $product->purchase_price,
                    'unit_price' => $price,
                    'stock_after' => $product->stock_quantity,
                    'source_type' => InvoiceItem::class,
                    'source_id' => $invoiceItem->id,
                    'reference_no' => $invoice->invoice_no,
                    'movement_date' => $invoice->sale_date,
                ]);
            }

            if ($paidAmount > 0) {
                CustomerPayment::create([
                    'tenant_id' => auth()->user()->tenant_id,
                    'customer_id' => $invoice->customer_id,
                    'invoice_id' => $invoice->id,
                    'amount' => $paidAmount,
                    'payment_method' => $data['payment_method'] ?? 'cash',
                    'payment_date' => isset($data['payment_date'])
                        ? Carbon::parse($data['payment_date'])->toDateString()
                        : now()->toDateString(),
                    'reference_no' => $data['reference_no'] ?? null,
                    'notes' => $data['payment_notes'] ?? null,
                ]);
            }
        });

        return redirect()
            ->route('invoices.index')
            ->with(
                'success',
                'Invoice created successfully.'
            );
    }

    public function show(Invoice $invoice)
    {
        $invoice->load([
            'customer',
            'items.product',
            'payments',
        ]);

        return view('invoices.show', compact('invoice'));
    }

    public function destroy(Invoice $invoice)
    {
        if ($invoice->status === 'cancelled') {
            $invoice->delete();

            return redirect()
                ->route('invoices.index')
                ->with('success', 'Invoice deleted.');
        }

        $stockLedger = app(StockLedgerService::class);

        foreach ($invoice->items as $item) {

            if ($item->product) {

                $item->product->increment(
                    'stock_quantity',
                    $item->quantity
                );

                $item->product->refresh();

                $stockLedger->record($item->product, 'sale_cancel', $item->quantity, [
                    'direction' => 'in',
                    'unit_cost' => $item->product->purchase_price,
                    'unit_price' => $item->price,
                    'stock_after' => $item->product->stock_quantity,
                    'source_type' => InvoiceItem::class,
                    'source_id' => $item->id,
                    'reference_no' => $invoice->invoice_no,
                    'movement_date' => now()->toDateString(),
                    'notes' => 'Invoice deleted.',
                ]);
            }
        }

        $invoice->delete();

        return redirect()
            ->route('invoices.index')
            ->with('success', 'Invoice deleted.');
    }

    public function cancel(Invoice $invoice)
    {
        if ($invoice->status === 'cancelled') {

            return back()->withErrors([
                'invoice' => 'Invoice already cancelled.',
            ]);
        }

        $stockLedger = app(StockLedgerService::class);

        foreach ($invoice->items as $item) {

            if ($item->product) {

                $item->product->increment(
                    'stock_quantity',
                    $item->quantity
                );

                $item->product->refresh();

                $stockLedger->record($item->product, 'sale_cancel', $item->quantity, [
                    'direction' => 'in',
                    'unit_cost' => $item->product->purchase_price,
                    'unit_price' => $item->price,
                    'stock_after' => $item->product->stock_quantity,
                    'source_type' => InvoiceItem::class,
                    'source_id' => $item->id,
                    'reference_no' => $invoice->invoice_no,
                    'movement_date' => now()->toDateString(),
                    'notes' => 'Invoice cancelled.',
                ]);
            }
        }

        $invoice->update([
            'status' => 'cancelled',
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
            'items.product',
        ]);

        $pdf = Pdf::loadView(
            'invoices.pdf',
            compact('invoice')
        );

        return $pdf->download(
            $invoice->invoice_no.'.pdf'
        );
    }
}
