<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Services\StockLedgerService;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with('category')
            ->latest()
            ->get();

        return view('products.index', compact('products'));
    }

    public function create()
    {
        $categories = Category::all();

        return view('products.create', compact('categories'));
    }

    public function edit(Product $product)
    {
        $categories = Category::all();

        return view('products.edit', compact(
            'product',
            'categories'
        ));
    }

    public function stockLedger(Product $product)
    {
        $product->load('category');

        $movements = $product->stockMovements()
            ->latest('movement_date')
            ->latest()
            ->paginate(25);

        return view('products.stock-ledger', compact(
            'product',
            'movements'
        ));
    }

    public function store(StoreProductRequest $request)
    {
        $data = $request->validated();

        $product = Product::create([

            'tenant_id' => auth()->user()->tenant_id,

            'category_id' => $data['category_id'],

            'name' => $data['name'],

            'sku' => $data['sku'],

            'barcode' => $data['barcode'] ?? null,

            'purchase_price' => $data['purchase_price'],

            'selling_price' => $data['selling_price'],

            'stock_quantity' => $data['stock_quantity'],

            'low_stock_alert' => $data['low_stock_alert'],

        ]);

        if ($product->stock_quantity > 0) {
            app(StockLedgerService::class)->record(
                $product,
                'opening_stock',
                $product->stock_quantity,
                [
                    'direction' => 'in',
                    'unit_cost' => $product->purchase_price,
                    'unit_price' => $product->selling_price,
                    'notes' => 'Opening stock from product creation.',
                ]
            );
        }

        return redirect()
            ->route('products.index')
            ->with(
                'success',
                'Product created successfully.'
            );
    }

    public function update(StoreProductRequest $request, Product $product)
    {
        $data = $request->validated();
        $oldStock = $product->stock_quantity;

        $product->update([
            'category_id' => $data['category_id'],
            'name' => $data['name'],
            'sku' => $data['sku'],
            'barcode' => $data['barcode'] ?? null,
            'purchase_price' => $data['purchase_price'],
            'selling_price' => $data['selling_price'],
            'stock_quantity' => $data['stock_quantity'],
            'low_stock_alert' => $data['low_stock_alert'],
        ]);

        $stockDifference = $product->stock_quantity - $oldStock;

        if ($stockDifference !== 0) {
            app(StockLedgerService::class)->record(
                $product,
                $stockDifference > 0 ? 'stock_adjustment_in' : 'stock_adjustment_out',
                abs($stockDifference),
                [
                    'direction' => $stockDifference > 0 ? 'in' : 'out',
                    'unit_cost' => $product->purchase_price,
                    'unit_price' => $product->selling_price,
                    'notes' => 'Manual stock adjustment from product update.',
                ]
            );
        }

        return redirect()
            ->route('products.index')
            ->with(
                'success',
                'Product updated successfully.'
            );
    }
}
