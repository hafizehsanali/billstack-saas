<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\Purchase;
use App\Models\SupplierPayment;
use App\Services\SupplierAccountService;
use App\Http\Requests\StoreSupplierPaymentRequest;

class SupplierPaymentController extends Controller
{
    protected SupplierAccountService $supplierAccountService;

    public function __construct(SupplierAccountService $supplierAccountService) 
    {
        $this->supplierAccountService =
            $supplierAccountService;
    }

    /**
     * Supplier payment list
     */
    public function index(Supplier $supplier)
    {
        $payments = SupplierPayment::query()

            ->where(
                'tenant_id',
                auth()->user()->tenant_id
            )

            ->where(
                'supplier_id',
                $supplier->id
            )

            ->latest()

            ->get();

        return view(
            'supplier-payments.index',
            compact('supplier', 'payments')
        );
    }

    /**
     * Create payment form
     */
    public function create(Supplier $supplier, ?Purchase $purchase = null)
    {
        $purchases = Purchase::where('supplier_id', $supplier->id)
            ->where('remaining_amount', '>', 0)
            ->where('status', '!=', 'cancelled')
            ->latest()
            ->get();
        return view('supplier-payments.create', [
            'supplier' => $supplier,
            'purchases' => $purchases,
            'selectedPurchase' => $purchase,
        ]);
    }

    /**
     * Store supplier payment
     */
    public function store(StoreSupplierPaymentRequest $request) 
    {
       
        $validated = $request->validated();
         //dd($validated);
        $validated['tenant_id'] = auth()->user()->tenant_id;
        $this->supplierAccountService->storePayment($validated);
        return redirect()->route('supplier-payments.index',$validated['supplier_id'])
               ->with('success','Payment added successfully.' );
    }

    /**
     * Delete supplier payment
     */
    public function destroy(
        SupplierPayment $payment
    ) {

        $this->supplierAccountService
            ->deletePayment($payment);

        return back()->with(
            'success',
            'Payment deleted successfully.'
        );
    }
}