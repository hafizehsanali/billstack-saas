<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Services\SupplierAccountService;
use Illuminate\Http\Request;

class SupplierAccountController extends Controller
{
    public function index(Supplier $supplier, SupplierAccountService $service)
    {
        $account = $service->getAccount($supplier->id);

        return view('supplier-account.index', compact('supplier', 'account'));
    }
}