<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Http\Requests\StoreProductRequest;

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

    public function store(StoreProductRequest $request)
    {
        $data = $request->validated();

        Product::create([

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

        return redirect()
            ->route('products.index')
            ->with(
                'success',
                'Product created successfully.'
            );
    }
}