<?php

namespace App\Services;

use App\Models\ProductVariant;
use App\Models\ProductBatch;
use App\Models\ProductSerialNumber;
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
            $catalog = app(ProductCatalogService::class);

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
                $variant = $catalog->resolveVariant(
                    $item['product_id'],
                    $item['product_variant_id'] ?? null,
                    true
                );
                $unitFactor = max((float) $variant->purchase_unit_factor, 0.001);
                $baseQuantity = (float) $item['quantity'] * $unitFactor;
                $baseUnitCost = (float) $item['purchase_price'] / $unitFactor;
                $lineTotal = $item['quantity'] * $item['purchase_price'];

                $purchaseItem = $purchase->items()->create([
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $variant->id,
                    'unit_id' => $variant->purchase_unit_id ?: $variant->unit_id,
                    'unit_factor' => $unitFactor,
                    'quantity' => $item['quantity'],
                    'base_quantity' => $baseQuantity,
                    'purchase_price' => $item['purchase_price'],
                    'line_total' => $lineTotal,
                ]);

                if ($variant->product->tracksStock() && $variant->track_stock) {
                    $catalog->adjustStock($variant, $baseQuantity, 'in', $baseUnitCost);
                    $variant->update(['purchase_unit_price' => $item['purchase_price']]);
                    $trackingBatch = $this->recordBatchAndSerials($variant, $purchaseItem, $item, $baseQuantity);

                    $stockLedger->record($variant, 'purchase', $baseQuantity, [
                        'direction' => 'in',
                        'unit_cost' => $baseUnitCost,
                        'stock_after' => $variant->stock_quantity,
                        'source_type' => PurchaseItem::class,
                        'source_id' => $purchaseItem->id,
                        'reference_no' => $purchase->purchase_no,
                        'product_batch_id' => $trackingBatch?->id,
                        'batch_number' => $trackingBatch?->batch_number,
                        'expiry_date' => $trackingBatch?->expiry_date,
                        'movement_date' => $purchase->purchase_date,
                    ]);
                }
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

    public function update(Purchase $purchase, array $data): void
    {
        if (! $purchase->canBeEdited()) {
            throw ValidationException::withMessages([
                'purchase' => 'This purchase cannot be edited after payments, returns, or stock usage.',
            ]);
        }

        DB::transaction(function () use ($purchase, $data) {
            $stockLedger = app(StockLedgerService::class);
            $catalog = app(ProductCatalogService::class);

            foreach ($purchase->items as $oldItem) {
                $variant = $oldItem->variant
                    ?? $catalog->resolveVariant($oldItem->product_id, null, true);
                if ($variant->product->tracksStock() && $variant->track_stock) {
                    $batchMovements = $catalog->consumeBatches($variant, $oldItem->base_quantity ?: $oldItem->quantity);
                    $catalog->adjustStock($variant, $oldItem->base_quantity ?: $oldItem->quantity, 'out');

                    foreach ($batchMovements ?: [['batch' => null, 'quantity' => $oldItem->base_quantity ?: $oldItem->quantity]] as $batchMovement) {
                        $stockLedger->record($variant, 'purchase_reversal', $batchMovement['quantity'], [
                            'direction' => 'out',
                            'unit_cost' => $oldItem->purchase_price / max((float) $oldItem->unit_factor, 0.001),
                            'stock_after' => $variant->stock_quantity,
                            'source_type' => PurchaseItem::class,
                            'source_id' => $oldItem->id,
                            'reference_no' => $purchase->purchase_no,
                            'product_batch_id' => $batchMovement['batch']?->id,
                            'batch_number' => $batchMovement['batch']?->batch_number,
                            'expiry_date' => $batchMovement['batch']?->expiry_date,
                            'movement_date' => now()->toDateString(),
                            'notes' => 'Purchase updated: old item reversed.',
                        ]);
                    }
                }
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
                $variant = $catalog->resolveVariant(
                    $item['product_id'],
                    $item['product_variant_id'] ?? null,
                    true
                );
                $unitFactor = max((float) $variant->purchase_unit_factor, 0.001);
                $baseQuantity = (float) $item['quantity'] * $unitFactor;
                $baseUnitCost = (float) $item['purchase_price'] / $unitFactor;
                $lineTotal = $item['quantity'] * $item['purchase_price'];

                $purchaseItem = PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $variant->id,
                    'unit_id' => $variant->purchase_unit_id ?: $variant->unit_id,
                    'unit_factor' => $unitFactor,
                    'quantity' => $item['quantity'],
                    'base_quantity' => $baseQuantity,
                    'purchase_price' => $item['purchase_price'],
                    'line_total' => $lineTotal,
                ]);

                if ($variant->product->tracksStock() && $variant->track_stock) {
                    $catalog->adjustStock($variant, $baseQuantity, 'in', $baseUnitCost);
                    $variant->update(['purchase_unit_price' => $item['purchase_price']]);
                    $trackingBatch = $this->recordBatchAndSerials($variant, $purchaseItem, $item, $baseQuantity);

                    $stockLedger->record($variant, 'purchase', $baseQuantity, [
                        'direction' => 'in',
                        'unit_cost' => $baseUnitCost,
                        'stock_after' => $variant->stock_quantity,
                        'source_type' => PurchaseItem::class,
                        'source_id' => $purchaseItem->id,
                        'reference_no' => $purchase->purchase_no,
                        'product_batch_id' => $trackingBatch?->id,
                        'batch_number' => $trackingBatch?->batch_number,
                        'expiry_date' => $trackingBatch?->expiry_date,
                        'movement_date' => $purchase->purchase_date,
                        'notes' => 'Purchase updated: new item added.',
                    ]);
                }
            }
        });
    }

    public function cancel(Purchase $purchase): void
    {

        DB::transaction(function () use ($purchase) {
            $stockLedger = app(StockLedgerService::class);
            $purchase = Purchase::query()
                ->with(['items.product', 'items.variant'])
                ->lockForUpdate()
                ->findOrFail($purchase->id);

            if (! $purchase->canBeCancelled()) {
                throw new \Exception(
                    'Only an unpaid purchase without payments or returns can be cancelled.'
                );
            }

            foreach ($purchase->items as $item) {

                $catalog = app(ProductCatalogService::class);
                $variant = $item->variant
                    ?? $catalog->resolveVariant($item->product_id, null, true);

                if ($variant->product->tracksStock() && $variant->track_stock && (
                    $variant->stock_quantity
                    < ($item->base_quantity ?: $item->quantity)
                )) {

                    throw new \Exception(
                        'Cannot cancel purchase because stock was already sold.'
                    );
                }

                if ($variant->product->tracksStock() && $variant->track_stock) {
                    $batchMovements = $catalog->consumeBatches($variant, $item->base_quantity ?: $item->quantity);
                    $catalog->adjustStock($variant, $item->base_quantity ?: $item->quantity, 'out');

                    foreach ($batchMovements ?: [['batch' => null, 'quantity' => $item->base_quantity ?: $item->quantity]] as $batchMovement) {
                        $stockLedger->record($variant, 'purchase_cancel', $batchMovement['quantity'], [
                            'direction' => 'out',
                            'unit_cost' => $item->purchase_price / max((float) $item->unit_factor, 0.001),
                            'stock_after' => $variant->stock_quantity,
                            'source_type' => PurchaseItem::class,
                            'source_id' => $item->id,
                            'reference_no' => $purchase->purchase_no,
                            'product_batch_id' => $batchMovement['batch']?->id,
                            'batch_number' => $batchMovement['batch']?->batch_number,
                            'expiry_date' => $batchMovement['batch']?->expiry_date,
                            'movement_date' => now()->toDateString(),
                            'notes' => 'Purchase cancelled.',
                        ]);
                    }
                }
            }

            $purchase->update([

                'status' => 'cancelled',
            ]);
        });
    }

    private function recordBatchAndSerials(ProductVariant $variant, PurchaseItem $purchaseItem, array $item, float $baseQuantity): ?ProductBatch
    {
        $product = $variant->product;

        $trackingBatch = null;

        if ($product->track_batch && ! empty($item['batch_number'])) {
            $batch = ProductBatch::where([
                'tenant_id' => auth()->user()->tenant_id,
                'product_variant_id' => $variant->id,
                'batch_number' => $item['batch_number'],
            ])->first();

            if ($batch) {
                $batch->increment('quantity', $baseQuantity);
                $batch->update([
                    'manufacturing_date' => $item['manufacturing_date'] ?? $batch->manufacturing_date,
                    'expiry_date' => $item['expiry_date'] ?? $batch->expiry_date,
                    'is_active' => true,
                ]);
            } else {
                $batch = ProductBatch::create([
                    'tenant_id' => auth()->user()->tenant_id,
                    'product_id' => $product->id,
                    'product_variant_id' => $variant->id,
                    'batch_number' => $item['batch_number'],
                    'manufacturing_date' => $item['manufacturing_date'] ?? null,
                    'expiry_date' => $item['expiry_date'] ?? null,
                    'quantity' => $baseQuantity,
                    'is_active' => true,
                ]);
            }

            $trackingBatch = $batch->fresh();
        }

        if ($product->track_serial) {
            foreach ($this->serialNumbers($item['serial_numbers'] ?? '') as $serialNumber) {
                ProductSerialNumber::updateOrCreate(
                    [
                        'tenant_id' => auth()->user()->tenant_id,
                        'serial_number' => $serialNumber,
                    ],
                    [
                        'product_id' => $product->id,
                        'product_variant_id' => $variant->id,
                        'status' => ProductSerialNumber::STATUS_AVAILABLE,
                        'purchase_item_id' => $purchaseItem->id,
                    ]
                );
            }
        }

        return $trackingBatch;
    }

    private function serialNumbers(string|array|null $value): array
    {
        $numbers = is_array($value) ? $value : preg_split('/[\r\n,]+/', (string) $value);

        return collect($numbers)
            ->map(fn ($number) => trim((string) $number))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
