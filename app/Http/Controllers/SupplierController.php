<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use App\Http\Requests\StoreSupplierRequest;

class SupplierController extends Controller
{
    public function index()
    {
        $suppliers = Supplier::where('tenant_id', auth()->user()->tenant_id)
            ->latest()
            ->get();

        return view('suppliers.index', compact('suppliers'));
    }

    public function create()
    {
        return view('suppliers.create');
    }

    public function store(StoreSupplierRequest $request)
    {
        $data = $request->validated();

        Supplier::create([
            'tenant_id' => auth()->user()->tenant_id,
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'opening_balance' => $data['opening_balance'] ?? 0,
            
        ]);

        return redirect()->route('suppliers.index')
            ->with('success', 'Supplier created successfully.');
    }

    public function edit(Supplier $supplier)
    {
        return view('suppliers.edit', compact('supplier'));
    }

    public function show(Supplier $supplier)
    {
        // // Total purchases
        // $totalPurchases = Purchase::where('supplier_id', $supplier->id)
        //     ->sum('total');

        // // Total paid amount
        // $totalPayments = SupplierPayment::where('supplier_id', $supplier->id)
        //     ->sum('amount');

        // // Remaining payable amount
        // $remainingAmount = $totalPurchases - $totalPayments;
        // // Purchase history
        // $purchases = Purchase::where('supplier_id', $supplier->id)
        //     ->latest()
        //     ->get();

        // // Payment history
        // $payments = SupplierPayment::where('supplier_id', $supplier->id)
        //     ->latest()
        //     ->get();

        return view('supplier-account.index', 
        //compact(
           // 'supplier',
            // 'totalPurchases',
            // 'totalPayments',
            // 'remainingAmount',
            // 'purchases',
            // 'payments'
       // )
        );

    }

    public function update(StoreSupplierRequest $request, Supplier $supplier)
    {
        $data = $request->validated();

        $supplier->update([
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'opening_balance' => $data['opening_balance'] ?? 0,
        ]);

        return redirect()->route('suppliers.index')
            ->with('success', 'Supplier updated successfully.');
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete(); // soft delete supported

        return redirect()->route('suppliers.index')
            ->with('success', 'Supplier deleted successfully.');
    }
}