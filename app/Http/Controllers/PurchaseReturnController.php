<?php

namespace App\Http\Controllers;

use App\Services\ProductCatalogService;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Services\StockLedgerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseReturnController extends Controller
{
    public function store(Request $request, Purchase $purchase): RedirectResponse
    {
        if ($purchase->status === 'cancelled') {
            return back()->withErrors([
                'return' => 'Cannot return items from a cancelled purchase.',
            ]);
        }

        $validated = $request->validate([
            'return_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($purchase, $validated) {
            $stockLedger = app(StockLedgerService::class);
            $purchase->load('items.returnItems', 'items.variant', 'payments');

            $selectedItems = collect($validated['items'])
                ->map(fn ($item, $purchaseItemId) => [
                    'purchase_item_id' => (int) $purchaseItemId,
                    'quantity' => (float) ($item['quantity'] ?? 0),
                ])
                ->filter(fn ($item) => $item['quantity'] > 0)
                ->values();

            if ($selectedItems->isEmpty()) {
                throw ValidationException::withMessages([
                    'items' => 'Enter a return quantity for at least one item.',
                ]);
            }

            $purchaseItems = $purchase->items->keyBy('id');
            $totalAmount = 0;

            foreach ($selectedItems as $item) {
                $purchaseItem = $purchaseItems->get($item['purchase_item_id']);

                if (! $purchaseItem) {
                    throw ValidationException::withMessages([
                        'items' => 'Invalid purchase item selected.',
                    ]);
                }

                if ($item['quantity'] > $purchaseItem->returnableQuantity()) {
                    throw ValidationException::withMessages([
                        'items' => $purchaseItem->product?->name.' return quantity exceeds purchased quantity.',
                    ]);
                }

                $catalog = app(ProductCatalogService::class);
                $variant = $purchaseItem->variant
                    ?? $catalog->resolveVariant($purchaseItem->product_id, null, true);
                $baseReturnQuantity = $item['quantity'] * max((float) $purchaseItem->unit_factor, 0.001);

                if ($variant->product->tracksStock() && $variant->track_stock && $variant->stock_quantity < $baseReturnQuantity) {
                    throw ValidationException::withMessages([
                        'items' => $purchaseItem->product?->name.' does not have enough stock to return.',
                    ]);
                }

                $totalAmount += $item['quantity'] * $purchaseItem->purchase_price;
            }

            $purchaseReturn = PurchaseReturn::create([
                'tenant_id' => auth()->user()->tenant_id,
                'purchase_id' => $purchase->id,
                'supplier_id' => $purchase->supplier_id,
                'return_date' => $validated['return_date'],
                'total_amount' => $totalAmount,
                'notes' => $validated['notes'] ?? null,
            ]);
            $purchaseReturn->refresh();

            foreach ($selectedItems as $item) {
                $purchaseItem = $purchaseItems->get($item['purchase_item_id']);
                $lineTotal = $item['quantity'] * $purchaseItem->purchase_price;

                $returnItem = PurchaseReturnItem::create([
                    'purchase_return_id' => $purchaseReturn->id,
                    'purchase_item_id' => $purchaseItem->id,
                    'product_id' => $purchaseItem->product_id,
                    'product_variant_id' => $purchaseItem->product_variant_id,
                    'quantity' => $item['quantity'],
                    'purchase_price' => $purchaseItem->purchase_price,
                    'total' => $lineTotal,
                ]);

                $catalog = app(ProductCatalogService::class);
                $variant = $purchaseItem->variant
                    ?? $catalog->resolveVariant($purchaseItem->product_id, null, true);
                $baseReturnQuantity = $item['quantity'] * max((float) $purchaseItem->unit_factor, 0.001);
                $baseUnitCost = $purchaseItem->purchase_price / max((float) $purchaseItem->unit_factor, 0.001);

                if ($variant->product->tracksStock() && $variant->track_stock) {
                    $batchMovements = $catalog->consumeBatches($variant, $baseReturnQuantity);
                    $catalog->adjustStock($variant, $baseReturnQuantity, 'out');

                    foreach ($batchMovements ?: [['batch' => null, 'quantity' => $baseReturnQuantity]] as $batchMovement) {
                        $stockLedger->record($variant, 'purchase_return', $batchMovement['quantity'], [
                            'direction' => 'out',
                            'unit_cost' => $baseUnitCost,
                            'stock_after' => $variant->stock_quantity,
                            'source_type' => PurchaseReturnItem::class,
                            'source_id' => $returnItem->id,
                            'reference_no' => $purchaseReturn->return_no,
                            'product_batch_id' => $batchMovement['batch']?->id,
                            'batch_number' => $batchMovement['batch']?->batch_number,
                            'expiry_date' => $batchMovement['batch']?->expiry_date,
                            'movement_date' => $purchaseReturn->return_date,
                        ]);
                    }
                }
            }

            $this->refreshPurchaseTotals($purchase, $totalAmount);
        });

        return redirect()
            ->route('purchases.show', $purchase)
            ->with('success', 'Supplier return recorded successfully.');
    }

    private function refreshPurchaseTotals(Purchase $purchase, float $returnAmount): void
    {
        $newSubtotal = max($purchase->subtotal - $returnAmount, 0);
        $newTotal = max($purchase->total - $returnAmount, 0);
        $paidAmount = $purchase->payments()->sum('amount');
        $remainingAmount = max($newTotal - $paidAmount, 0);

        $status = 'unpaid';

        if ($newTotal <= 0) {
            $status = 'returned';
        } elseif ($paidAmount >= $newTotal) {
            $status = 'paid';
        } elseif ($paidAmount > 0) {
            $status = 'partial';
        }

        $purchase->update([
            'subtotal' => $newSubtotal,
            'total' => $newTotal,
            'paid_amount' => $paidAmount,
            'remaining_amount' => $remainingAmount,
            'status' => $status,
        ]);
    }
}
