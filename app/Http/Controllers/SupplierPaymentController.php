<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Http\Requests\StoreSupplierPaymentRequest;

class SupplierPaymentController extends Controller
{
    public function index(Supplier $supplier)
    {
        $payments = SupplierPayment::where('tenant_id', auth()->user()->tenant_id)
            ->where('supplier_id', $supplier->id)
            ->latest()
            ->get();

        return view('supplier-payments.index', compact('supplier', 'payments'));
    }

    public function create(Supplier $supplier)
    {
        return view('supplier-payments.create', compact('supplier'));
    }

    public function store(StoreSupplierPaymentRequest $request)
    {
        $data = $request->validated();

        SupplierPayment::create([
            'tenant_id' => auth()->user()->tenant_id,
            'supplier_id' => $data['supplier_id'],
            'amount' => $data['amount'],
            'payment_method' => $data['payment_method'] ?? null,
            'reference' => $data['reference'] ?? null,
            'note' => $data['note'] ?? null,
        ]);

        return redirect()->route('suppliers.index')
            ->with('success', 'Payment added successfully.');
    }

    public function destroy(SupplierPayment $payment)
    {
        $payment->delete();

        return back()->with('success', 'Payment deleted successfully.');
    }
}