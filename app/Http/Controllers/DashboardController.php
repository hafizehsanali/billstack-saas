<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Invoice;
use App\Models\Customer;

class DashboardController extends Controller
{
    public function index()
    {
        $todaySales = Invoice::where('status','completed')
                      ->whereDate('created_at',today())->sum('total');

        $totalProducts = Product::count();

        $totalCustomers = Customer::count();

        $totalInvoices = Invoice::count();

        $lowStockProducts = Product::whereColumn(
            'stock_quantity',
            '<=',
            'low_stock_alert'
        )->get();

        return view('dashboard', compact(
            'todaySales',
            'totalProducts',
            'totalCustomers',
            'totalInvoices',
            'lowStockProducts'
        ));
    }
}