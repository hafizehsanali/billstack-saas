<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseRequest;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Services\PurchaseService;
use Barryvdh\DomPDF\Facade\Pdf;

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

    public function edit(Purchase $purchase)
    {
        abort_unless(
            $purchase->canBeEdited(),
            403,
            'This purchase cannot be edited after payments, returns, or stock usage.'
        );

        $purchase->load('items');

        $suppliers = Supplier::all();

        $products = Product::all();

        return view('purchases.edit', compact(
            'purchase',
            'suppliers',
            'products'
        ));
    }

    public function update(
        StorePurchaseRequest $request,
        Purchase $purchase,
        PurchaseService $purchaseService
    ) {
        abort_unless(
            $purchase->canBeEdited(),
            403,
            'This purchase cannot be edited after payments, returns, or stock usage.'
        );

        $purchaseService->update(
            $purchase,
            $request->validated()
        );

        return redirect()
            ->route('purchases.index')
            ->with(
                'success',
                'Purchase updated successfully.'
            );
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

    public function show(Purchase $purchase)
    {
        $purchase->load([
            'supplier',
            'items.product',
            'items.returnItems',
            'payments',
            'returns.items.product',
        ]);

        return view('purchases.show', compact('purchase'));
    }

    public function cancel(Purchase $purchase, PurchaseService $purchaseService)
    {
        try {

            $purchaseService->cancel($purchase);

            return redirect()
                ->route('purchases.index')
                ->with(
                    'success',
                    'Purchase cancelled successfully.'
                );

        } catch (\Exception $e) {

            return redirect()
                ->back()
                ->with(
                    'error',
                    $e->getMessage()
                );
        }
    }

    public function print(Purchase $purchase)
    {
        $purchase->load([
            'supplier',
            'items.product',
        ]);

        return view('purchases.print', compact(
            'purchase'
        ));
    }

    public function pdf(Purchase $purchase)
    {
        if ($purchase->status === 'cancelled') {
            abort(403, 'Cancelled purchase cannot be downloaded.');
        }

        $purchase->load([
            'supplier',
            'items.product',
        ]);

        $tenant = auth()->user()->tenant;

        $pdf = Pdf::loadView(
            'purchases.pdf',
            compact('purchase', 'tenant')
        );

        return $pdf->download(
            $purchase->purchase_no.'.pdf'
        );
    }
}
