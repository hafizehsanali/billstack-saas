<?php

namespace App\Http\Controllers;

use App\Services\AlertService;

class AlertController extends Controller
{
    public function index(AlertService $alerts)
    {
        return view('alerts.index', [
            'summary' => $alerts->summary(),
            'lowStockProducts' => $alerts->lowStockProducts(),
            'customerPaymentDues' => $alerts->customerPaymentDues(),
            'supplierPaymentDues' => $alerts->supplierPaymentDues(),
        ]);
    }
}
