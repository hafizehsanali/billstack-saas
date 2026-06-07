<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseService
{
    public function store(array $data): Purchase
    {
        return DB::transaction(function () use ($data) {
            $stockLedger = app(StockLedgerService::class);

            $subtotal = $data['subtotal'];

            $extraExpense = $data['extra_expense'] ?? 0;

            $discount = $data['discount'] ?? 0;

            $paidAmount = $data['paid_amount'] ?? 0;

            $total = ($subtotal + $extraExpense) - $discount;

            $total = max($total, 0);

            if ($paidAmount > $total) {
                throw ValidationException::withMessages([
                    'paid_amount' => 'Paid amount cannot be greater than purchase total.',
                ]);
            }

            $remainingAmount = max($total - $paidAmount, 0);

            $status = 'unpaid';

            if ($remainingAmount <= 0) {
                $status = 'paid';
            } elseif ($paidAmount > 0) {
                $status = 'partial';
            }

            $purchase = Purchase::create([
                'tenant_id' => auth()->user()->tenant_id,

                'supplier_id' => $data['supplier_id'],

                'purchase_no' => $data['purchase_no'],

                'purchase_date' => $data['purchase_date'],

                'subtotal' => $subtotal,

                'extra_expense' => $extraExpense,

                'discount' => $discount,

                'total' => $total,

                'paid_amount' => $paidAmount,

                'remaining_amount' => $remainingAmount,

                'status' => $status,

                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['products'] as $item) {

                $lineTotal = ($item['quantity'] * $item['purchase_price']);

                $purchaseItem = $purchase->items()->create([
                    'product_id' => $item['product_id'],

                    'quantity' => $item['quantity'],

                    'purchase_price' => $item['purchase_price'],

                    'line_total' => $lineTotal,
                ]);

                $product = $this->updateInventory(
                    $item['product_id'],
                    $item['quantity'],
                    $item['purchase_price']
                );

                $stockLedger->record($product, 'purchase', $item['quantity'], [
                    'direction' => 'in',
                    'unit_cost' => $item['purchase_price'],
                    'stock_after' => $product->stock_quantity,
                    'source_type' => PurchaseItem::class,
                    'source_id' => $purchaseItem->id,
                    'reference_no' => $purchase->purchase_no,
                    'movement_date' => $purchase->purchase_date,
                ]);
            }

            if ($paidAmount > 0) {
                $purchase->payments()->create([
                    'tenant_id' => auth()->user()->tenant_id,
                    'supplier_id' => $purchase->supplier_id,
                    'amount' => $paidAmount,
                    'payment_method' => $data['payment_method'] ?? 'cash',
                    'payment_date' => isset($data['payment_date'])
                        ? Carbon::parse($data['payment_date'])->toDateString()
                        : now()->toDateString(),
                    'reference_no' => $data['reference_no'] ?? null,
                    'notes' => $data['payment_notes'] ?? null,
                ]);
            }

            return $purchase;
        });
    }

    private function updateInventory(int $productId, int $newQuantity, float $newPrice): Product
    {

        $product = Product::findOrFail($productId);

        $oldStock = $product->stock_quantity;

        $oldAveragePrice = $product->purchase_price;

        $newStock = $oldStock + $newQuantity;

        // Keep purchase cost aligned with the blended value of existing and new stock.
        $newAveragePrice = (
            ($oldStock * $oldAveragePrice)
            +
            ($newQuantity * $newPrice)
        ) / max($newStock, 1);

        $product->update([
            'stock_quantity' => $newStock,

            'purchase_price' => round(
                $newAveragePrice,
                2
            ),
        ]);

        return $product->refresh();
    }

    public function update(Purchase $purchase, array $data): void
    {
        if (! $purchase->canBeEdited()) {
            throw ValidationException::withMessages([
                'purchase' => 'This purchase cannot be edited after payments, returns, or stock usage.',
            ]);
        }

        DB::transaction(function () use ($purchase, $data) {
            $stockLedger = app(StockLedgerService::class);

            foreach ($purchase->items as $oldItem) {
                $product = $this->reverseInventory($oldItem->product_id, $oldItem->quantity);

                $stockLedger->record($product, 'purchase_reversal', $oldItem->quantity, [
                    'direction' => 'out',
                    'unit_cost' => $oldItem->purchase_price,
                    'stock_after' => $product->stock_quantity,
                    'source_type' => PurchaseItem::class,
                    'source_id' => $oldItem->id,
                    'reference_no' => $purchase->purchase_no,
                    'movement_date' => now()->toDateString(),
                    'notes' => 'Purchase updated: old item reversed.',
                ]);
            }

            $purchase->items()->delete();

            $paidAmount = (float) ($data['paid_amount'] ?? 0);
            $total = (float) ($data['total'] ?? 0);

            if ($paidAmount > $total) {
                throw ValidationException::withMessages([
                    'paid_amount' => 'Paid amount cannot be greater than purchase total.',
                ]);
            }

            $purchase->update([

                'supplier_id' => $data['supplier_id'],

                'purchase_date' => $data['purchase_date'],

                'subtotal' => $data['subtotal'],

                'extra_expense' => $data['extra_expense'] ?? 0,

                'discount' => $data['discount'] ?? 0,

                'total' => $total,

                'paid_amount' => $paidAmount,

                'remaining_amount' => $data['remaining_amount'] ?? 0,

                'status' => $data['status'] ?? 'unpaid',

                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['products'] as $item) {

                $lineTotal =
                    $item['quantity']
                    * $item['purchase_price'];

                $purchaseItem = PurchaseItem::create([

                    'purchase_id' => $purchase->id,

                    'product_id' => $item['product_id'],

                    'quantity' => $item['quantity'],

                    'purchase_price' => $item['purchase_price'],

                    'line_total' => $lineTotal,
                ]);

                $product = $this->updateInventory($item['product_id'], $item['quantity'], $item['purchase_price']);

                $stockLedger->record($product, 'purchase', $item['quantity'], [
                    'direction' => 'in',
                    'unit_cost' => $item['purchase_price'],
                    'stock_after' => $product->stock_quantity,
                    'source_type' => PurchaseItem::class,
                    'source_id' => $purchaseItem->id,
                    'reference_no' => $purchase->purchase_no,
                    'movement_date' => $purchase->purchase_date,
                    'notes' => 'Purchase updated: new item added.',
                ]);
            }
        });
    }

    private function reverseInventory(int $productId, int $quantity): Product
    {
        $product = Product::findOrFail($productId);
        $product->decrement('stock_quantity', $quantity);

        return $product->refresh();
    }

    public function cancel(Purchase $purchase): void
    {

        DB::transaction(function () use ($purchase) {
            $stockLedger = app(StockLedgerService::class);
            $purchase = Purchase::query()
                ->with('items.product')
                ->lockForUpdate()
                ->findOrFail($purchase->id);

            if (! $purchase->canBeCancelled()) {
                throw new \Exception(
                    'Only an unpaid purchase without payments or returns can be cancelled.'
                );
            }

            foreach ($purchase->items as $item) {

                $product = Product::find(
                    $item->product_id
                );

                if (! $product) {
                    continue;
                }

                if (
                    $product->stock_quantity
                    < $item->quantity
                ) {

                    throw new \Exception(
                        'Cannot cancel purchase because stock was already sold.'
                    );
                }

                $product->decrement(
                    'stock_quantity',
                    $item->quantity
                );

                $product->refresh();

                $stockLedger->record($product, 'purchase_cancel', $item->quantity, [
                    'direction' => 'out',
                    'unit_cost' => $item->purchase_price,
                    'stock_after' => $product->stock_quantity,
                    'source_type' => PurchaseItem::class,
                    'source_id' => $item->id,
                    'reference_no' => $purchase->purchase_no,
                    'movement_date' => now()->toDateString(),
                    'notes' => 'Purchase cancelled.',
                ]);
            }

            $purchase->update([

                'status' => 'cancelled',
            ]);
        });
    }
}
