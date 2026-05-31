<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;

class StockLedgerService
{
    public function record(Product $product, string $type, float $quantity, array $data = []): StockMovement
    {
        return StockMovement::create([
            'tenant_id' => $data['tenant_id'] ?? $product->tenant_id,
            'product_id' => $product->id,
            'type' => $type,
            'direction' => $data['direction'] ?? $this->directionFor($type),
            'quantity' => $quantity,
            'unit_cost' => $data['unit_cost'] ?? null,
            'unit_price' => $data['unit_price'] ?? null,
            'stock_after' => $data['stock_after'] ?? $product->stock_quantity,
            'source_type' => $data['source_type'] ?? null,
            'source_id' => $data['source_id'] ?? null,
            'reference_no' => $data['reference_no'] ?? null,
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
