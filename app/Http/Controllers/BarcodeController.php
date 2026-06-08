<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BarcodeController extends Controller
{
    public function index(): View
    {
        return view('barcode.index');
    }

    public function lookup(Request $request): JsonResponse
    {
        $data = $request->validate([
            'barcode' => ['required', 'string', 'max:255'],
        ]);

        $product = Product::where('tenant_id', $request->user()->tenant_id)
            ->where('barcode', $data['barcode'])
            ->first();

        if (! $product) {
            return response()->json([
                'message' => 'No product found for this barcode.',
            ], 404);
        }

        return response()->json([
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'barcode' => $product->barcode,
            'selling_price' => (float) $product->selling_price,
            'stock_quantity' => (int) $product->stock_quantity,
            'edit_url' => route('products.edit', $product),
            'stock_ledger_url' => route('products.stock-ledger', $product),
        ]);
    }
}
