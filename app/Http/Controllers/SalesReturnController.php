<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\ProductCatalogService;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\ProductSerialNumber;
use App\Services\StockLedgerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SalesReturnController extends Controller
{
    public function store(Request $request, Invoice $invoice): RedirectResponse
    {
        if ($invoice->status === 'cancelled') {
            return back()->withErrors([
                'return' => 'Cannot return items from a cancelled invoice.',
            ]);
        }

        $validated = $request->validate([
            'return_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($invoice, $validated) {
            $stockLedger = app(StockLedgerService::class);
            $invoice->load('items.returnItems', 'items.variant', 'payments');

            $selectedItems = collect($validated['items'])
                ->map(fn ($item, $invoiceItemId) => [
                    'invoice_item_id' => (int) $invoiceItemId,
                    'quantity' => (float) ($item['quantity'] ?? 0),
                ])
                ->filter(fn ($item) => $item['quantity'] > 0)
                ->values();

            if ($selectedItems->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'Enter a return quantity for at least one item.',
                ]);
            }

            $invoiceItems = $invoice->items->keyBy('id');
            $totalAmount = 0;

            foreach ($selectedItems as $item) {
                $invoiceItem = $invoiceItems->get($item['invoice_item_id']);

                if (! $invoiceItem) {
                    throw ValidationException::withMessages([
                        'items' => 'Invalid invoice item selected.',
                    ]);
                }

                if ($item['quantity'] > $invoiceItem->returnableQuantity()) {
                    throw ValidationException::withMessages([
                        'items' => $invoiceItem->product?->name.' return quantity exceeds sold quantity.',
                    ]);
                }

                $totalAmount += $item['quantity'] * $invoiceItem->price;
            }

            $salesReturn = SalesReturn::create([
                'tenant_id' => auth()->user()->tenant_id,
                'invoice_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'return_date' => $validated['return_date'],
                'total_amount' => $totalAmount,
                'notes' => $validated['notes'] ?? null,
            ]);
            $salesReturn->refresh();

            foreach ($selectedItems as $item) {
                $invoiceItem = $invoiceItems->get($item['invoice_item_id']);
                $lineTotal = $item['quantity'] * $invoiceItem->price;

                $returnItem = SalesReturnItem::create([
                    'sales_return_id' => $salesReturn->id,
                    'invoice_item_id' => $invoiceItem->id,
                    'product_id' => $invoiceItem->product_id,
                    'product_variant_id' => $invoiceItem->product_variant_id,
                    'quantity' => $item['quantity'],
                    'price' => $invoiceItem->price,
                    'total' => $lineTotal,
                ]);

                $catalog = app(ProductCatalogService::class);
                $variant = $invoiceItem->variant
                    ?? $catalog->resolveVariant($invoiceItem->product_id, null, true);

                if (! $invoiceItem->product_variant_id && $invoiceItem->product) {
                    $invoiceItem->product->update([
                        'stock_quantity' => (float) $invoiceItem->product->stock_quantity + (float) $item['quantity'],
                    ]);

                    $stockLedger->record($invoiceItem->product, 'sales_return', $item['quantity'], [
                        'direction' => 'in',
                        'unit_cost' => $invoiceItem->product->purchase_price,
                        'unit_price' => $invoiceItem->price,
                        'stock_after' => $invoiceItem->product->stock_quantity,
                        'source_type' => SalesReturnItem::class,
                        'source_id' => $returnItem->id,
                        'reference_no' => $salesReturn->return_no,
                        'movement_date' => $salesReturn->return_date,
                    ]);

                    continue;
                }

                if ($variant && $variant->product->tracksStock() && $variant->track_stock) {
                    $batchMovements = $catalog->restoreBatchesFromInvoiceItem($invoiceItem, $item['quantity']);
                    $catalog->adjustStock($variant, $item['quantity'], 'in');

                    if ($batchMovements === []) {
                        $stockLedger->record($variant, 'sales_return', $item['quantity'], [
                            'direction' => 'in',
                            'unit_cost' => $variant->purchase_price,
                            'unit_price' => $invoiceItem->price,
                            'stock_after' => $variant->stock_quantity,
                            'source_type' => SalesReturnItem::class,
                            'source_id' => $returnItem->id,
                            'reference_no' => $salesReturn->return_no,
                            'movement_date' => $salesReturn->return_date,
                        ]);
                    } else {
                        foreach ($batchMovements as $batchMovement) {
                            $stockLedger->record($variant, 'sales_return', $batchMovement['quantity'], [
                                'direction' => 'in',
                                'unit_cost' => $variant->purchase_price,
                                'unit_price' => $invoiceItem->price,
                                'stock_after' => $variant->stock_quantity,
                                'source_type' => SalesReturnItem::class,
                                'source_id' => $returnItem->id,
                                'reference_no' => $salesReturn->return_no,
                                'product_batch_id' => $batchMovement['batch']->id,
                                'batch_number' => $batchMovement['batch']->batch_number,
                                'expiry_date' => $batchMovement['batch']->expiry_date,
                                'movement_date' => $salesReturn->return_date,
                            ]);
                        }
                    }
                }

                if ($variant?->product?->track_serial) {
                    ProductSerialNumber::query()
                        ->where('invoice_item_id', $invoiceItem->id)
                        ->where('status', ProductSerialNumber::STATUS_SOLD)
                        ->limit((int) $item['quantity'])
                        ->get()
                        ->each(function (ProductSerialNumber $serial): void {
                            $serial->update([
                                'status' => ProductSerialNumber::STATUS_AVAILABLE,
                                'invoice_item_id' => null,
                            ]);
                        });
                }
            }

            $this->refreshInvoiceTotals($invoice, $totalAmount);
        });

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('success', 'Sales return recorded successfully.');
    }

    private function refreshInvoiceTotals(Invoice $invoice, float $returnAmount): void
    {
        $newSubtotal = max($invoice->subtotal - $returnAmount, 0);
        $newTotal = max($invoice->total - $returnAmount, 0);
        $paidAmount = $invoice->payments()->sum('amount');
        $remainingAmount = max($newTotal - $paidAmount, 0);

        $status = 'unpaid';

        if ($newTotal <= 0) {
            $status = 'returned';
        } elseif ($paidAmount >= $newTotal) {
            $status = 'paid';
        } elseif ($paidAmount > 0) {
            $status = 'partial';
        }

        $invoice->update([
            'subtotal' => $newSubtotal,
            'total' => $newTotal,
            'paid_amount' => $paidAmount,
            'remaining_amount' => $remainingAmount,
            'status' => $status,
        ]);
    }
}
