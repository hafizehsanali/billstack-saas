<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\ProductCatalogService;
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

        $variant = ProductVariant::with('product')
            ->where('tenant_id', $request->user()->tenant_id)
            ->where('barcode', $data['barcode'])
            ->first();

        if (! $variant) {
            $legacyProduct = Product::where('tenant_id', $request->user()->tenant_id)
                ->where('barcode', $data['barcode'])
                ->first();

            if (! $legacyProduct) {
                return response()->json([
                    'message' => 'No product found for this barcode.',
                ], 404);
            }

            $variant = app(ProductCatalogService::class)->resolveVariant($legacyProduct->id);
        }

        $product = $variant->product;

        return response()->json([
            'id' => $product->id,
            'product_variant_id' => $variant->id,
            'name' => $variant->display_name,
            'sku' => $variant->sku,
            'barcode' => $variant->barcode,
            'selling_price' => (float) $variant->selling_price,
            'stock_quantity' => (int) $variant->stock_quantity,
            'edit_url' => route('products.edit', $product),
            'stock_ledger_url' => route('products.stock-ledger', $product),
        ]);
    }
}
