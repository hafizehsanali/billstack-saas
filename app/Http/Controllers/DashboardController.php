<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Invoice;
use App\Models\Customer;
use \App\Services\DashboardService;
use App\Models\Expense;

class DashboardController extends Controller
{
    public function index(DashboardService $dashboardService)
    {
        $stats = $dashboardService->stats();
        // Expenses
        $totalExpenses = Expense::where('tenant_id', auth()->user()->tenant_id)->sum('amount');
        // Profit
        $totalProfit = $stats['total_sales'] - $totalExpenses;
        return view('dashboard', compact('stats','totalExpenses', 'totalProfit'));
    }
}