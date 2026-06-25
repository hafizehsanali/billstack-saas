<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;

class StockLedgerService
{
    public function record(
        Product|ProductVariant $stockItem,
        string $type,
        float $quantity,
        array $data = []
    ): StockMovement
    {
        $variant = $stockItem instanceof ProductVariant
            ? $stockItem
            : ($data['variant'] ?? $stockItem->defaultVariant);
        $product = $stockItem instanceof ProductVariant
            ? $stockItem->product
            : $stockItem;

        return StockMovement::create([
            'tenant_id' => $data['tenant_id'] ?? $product->tenant_id,
            'product_id' => $product->id,
            'product_variant_id' => $variant?->id,
            'product_batch_id' => $data['product_batch_id'] ?? null,
            'product_serial_number_id' => $data['product_serial_number_id'] ?? null,
            'type' => $type,
            'direction' => $data['direction'] ?? $this->directionFor($type),
            'quantity' => $quantity,
            'unit_cost' => $data['unit_cost'] ?? null,
            'unit_price' => $data['unit_price'] ?? null,
            'stock_after' => $data['stock_after'] ?? $variant?->stock_quantity ?? $product->stock_quantity,
            'source_type' => $data['source_type'] ?? null,
            'source_id' => $data['source_id'] ?? null,
            'reference_no' => $data['reference_no'] ?? null,
            'batch_number' => $data['batch_number'] ?? null,
            'expiry_date' => $data['expiry_date'] ?? null,
            'movement_date' => $data['movement_date'] ?? now()->toDateString(),
            'notes' => $data['notes'] ?? null,
        ]);
    }

    private function directionFor(string $type): string
    {
        return in_array($type, [
            'sale',
            'purchase_reversal',
            'purchase_cancel',
            'stock_adjustment_out',
        ], true) ? 'out' : 'in';
    }
}
