<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Services\CustomerAccountService;

class CustomerAccountController extends Controller
{
    protected $service;

    public function __construct(CustomerAccountService $service)
    {
        $this->service = $service;
    }

    public function show(Customer $customer)
    {
        $data = $this->service->getLedger($customer);

        return view('customer-account.account', [

            'customer' => $customer,

            'ledger' => $data['ledger'],

            'totalSales' => $data['totalSales'],

            'totalReceived' => $data['totalReceived'],

            'totalReturns' => $data['totalReturns'],

            'receivable' => $data['receivable'],

            'advance' => $data['advance'],
        ]);
    }
}
