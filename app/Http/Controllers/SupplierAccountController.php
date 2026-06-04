<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Services\SupplierAccountService;

class SupplierAccountController extends Controller
{
    protected SupplierAccountService $service;

    public function __construct(SupplierAccountService $supplierAccountService)
    {
        $this->service = $supplierAccountService;
    }
    
    public function show(Supplier $supplier)
    {
        $from = request('from');
        $to = request('to');

        $account = $this->service->getLedgerWithBalance($supplier->id, $from, $to);

        return view('supplier-account.account', $account);
    }
}
