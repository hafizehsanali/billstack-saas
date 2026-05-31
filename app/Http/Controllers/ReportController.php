<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Product;

class ReportController extends Controller
{
    public function dailySales()
    {
        $invoices = Invoice::whereIn('status', ['paid', 'partial'])
            ->whereDate('sale_date', today())
            ->latest()
            ->get();

        $totalSales = $invoices->sum('total');

        return view(
            'reports.daily-sales',
            compact('invoices', 'totalSales')
        );
    }

    public function monthlySales()
    {
        $invoices = Invoice::whereIn('status', ['paid', 'partial'])
            ->whereMonth('sale_date', now()->month)
            ->whereYear('sale_date', now()->year)
            ->latest()
            ->get();

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

    public function profitLoss()
    {
        $tenantId = auth()->user()->tenant_id;

        // Paid + partial invoices only
        $sales = Invoice::where(
            'tenant_id',
            $tenantId
        )
            ->whereIn('status', [
            'paid',
            'partial',
        ])
            ->sum('total');

        // Total expenses
        $expenses = Expense::where(
            'tenant_id',
            $tenantId
        )
            ->sum('amount');

        // Net profit
        $profit = $sales - $expenses;

        // Monthly analytics
        $monthlySales = Invoice::where(
            'tenant_id',
            $tenantId
        )
            ->whereMonth(
                'created_at',
                now()->month
            )
            ->whereIn('status', [
                'paid',
                'partial',
            ])
            ->sum('total');

        $monthlyExpenses = Expense::where(
            'tenant_id',
            $tenantId
        )
            ->whereMonth(
                'expense_date',
                now()->month
            )
            ->sum('amount');

        $monthlyProfit =
            $monthlySales - $monthlyExpenses;

        return view(
            'reports.profit-loss',
            compact(
                'sales',
                'expenses',
                'profit',
                'monthlySales',
                'monthlyExpenses',
                'monthlyProfit'
            )
        );
    }
}
