<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;

class AlertService
{
    public function summary(): array
    {
        $lowStockProducts = $this->lowStockProducts();

        return [
            'total' => $lowStockProducts->count(),
            'out_of_stock' => $lowStockProducts
                ->where('stock_quantity', '<=', 0)
                ->count(),
            'low_stock' => $lowStockProducts
                ->where('stock_quantity', '>', 0)
                ->count(),
        ];
    }

    public function lowStockProducts(): Collection
    {
        return Product::with('category')
            ->whereColumn('stock_quantity', '<=', 'low_stock_alert')
            ->orderBy('stock_quantity')
            ->orderBy('name')
            ->get();
    }
}
