<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Invoice;
use App\Models\Customer;
use \App\Services\DashboardService;

class DashboardController extends Controller
{
    public function index(\App\Services\DashboardService $dashboardService)
    {
        $stats = $dashboardService->stats();

        return view('dashboard', compact('stats'));
    }
}