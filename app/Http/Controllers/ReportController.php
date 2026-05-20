<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Product;

class ReportController extends Controller
{
    public function dailySales()
    {
        $invoices = Invoice::whereDate(
            'created_at',
            today()
        )->latest()->get();

        $totalSales = $invoices->sum('total');

        return view(
            'reports.daily-sales',
            compact('invoices', 'totalSales')
        );
    }

    public function monthlySales()
    {
        $invoices = Invoice::whereMonth(
            'created_at',
            now()->month
        )->whereYear(
            'created_at',
            now()->year
        )->latest()->get();

        $totalSales = $invoices->sum('total');

        return view(
            'reports.monthly-sales',
            compact('invoices', 'totalSales')
        );
    }

    public function stock()
    {
        $products = Product::latest()->get();

        return view(
            'reports.stock',
            compact('products')
        );
    }

    public function lowStock()
    {
        $products = Product::whereColumn(
            'stock_quantity',
            '<=',
            'low_stock_alert'
        )->get();

        return view(
            'reports.low-stock',
            compact('products')
        );
    }
}