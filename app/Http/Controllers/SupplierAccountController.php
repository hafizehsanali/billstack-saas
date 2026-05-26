<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Services\SupplierAccountService;

class SupplierAccountController extends Controller
{
    protected SupplierAccountService
        $supplierAccountService;

    public function __construct( SupplierAccountService $supplierAccountService) 
    {
        $this->service    = $supplierAccountService;
    }

    /**
     * Supplier account statement
     */
    public function show(Supplier $supplier)
    {
        $account = $this->service->getAccount($supplier->id);

        return view('supplier-account.account', $account);
    }
}