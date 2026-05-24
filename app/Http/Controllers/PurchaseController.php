<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Supplier;
use App\Models\Purchase;
use App\Services\PurchaseService;
use App\Http\Requests\StorePurchaseRequest;

class PurchaseController extends Controller
{
    public function __construct(
        private PurchaseService $purchaseService
    ) {}

    public function index()
    {
        $purchases = Purchase::with('supplier')
            ->latest()
            ->paginate(20);

        return view('purchases.index', compact('purchases'));
    }

    public function create()
    {
        $suppliers = Supplier::orderBy('name')->get();

        $products = Product::orderBy('name')->get();

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
            ->with(
                'success',
                'Purchase created successfully.'
            );
    }
}