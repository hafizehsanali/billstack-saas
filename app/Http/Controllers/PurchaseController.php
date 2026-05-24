<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Supplier;
use App\Services\PurchaseService;
use App\Http\Requests\StorePurchaseRequest;

class PurchaseController extends Controller
{
    public function __construct(
        protected PurchaseService $purchaseService
    ) {
    }

    public function index()
    {
        $purchases = \App\Models\Purchase::with('supplier')
            ->latest()
            ->paginate(15);

        return view('purchases.index', compact('purchases'));
    }

    public function create()
    {
        $suppliers = Supplier::latest()->get();

        $products = Product::latest()->get();

        return view('purchases.create', compact(
            'suppliers',
            'products'
        ));
    }

    public function store(StorePurchaseRequest $request)
    {
        $this->purchaseService->store(
            $request->validated()
        );

        return redirect()
            ->route('purchases.index')
            ->with('success', 'Purchase created successfully.');
    }
}