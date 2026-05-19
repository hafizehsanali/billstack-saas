<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;

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

    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required'],
            'selling_price' => ['required'],
        ]);

        Product::create([
            'category_id' => $request->category_id,
            'name' => $request->name,
            'sku' => $request->sku,
            'barcode' => $request->barcode,
            'purchase_price' => $request->purchase_price,
            'selling_price' => $request->selling_price,
            'stock_quantity' => $request->stock_quantity,
            'low_stock_alert' => $request->low_stock_alert,
        ]);

        return redirect()->route('products.index');
    }
}