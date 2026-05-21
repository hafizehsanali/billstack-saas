<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Product;
use App\Models\Customer;
use Carbon\Carbon;
use App\Models\Expense;
use App\Models\InvoiceItem;

class DashboardService
{
    public function stats(): array
    {
        $tenantId = auth()->user()->tenant_id;

        // Total revenue from paid + partial invoices
        $totalSales = Invoice::where('tenant_id', $tenantId)
            ->whereIn('status', ['paid', 'partial'])
            ->sum('total');

        // Cost of sold products
        $totalCOGS = InvoiceItem::whereHas('invoice', function ($query) use ($tenantId) {
                           $query->where('tenant_id', $tenantId)->whereIn('status', ['paid', 'partial']);
                     })->get()->sum(function ($item) {
                                    return $item->quantity * $item->product->purchase_price;
                                });

        // Extra business expenses
        $totalExpenses = Expense::where('tenant_id', $tenantId)->sum('amount');

        // Gross profit
        $grossProfit = $totalSales - $totalCOGS;

        // Net profit
        $netProfit = $grossProfit - $totalExpenses;

        return [

            // Sales
            'today_sales' => Invoice::where('tenant_id', $tenantId)
                ->whereDate('created_at', Carbon::today())
                ->whereIn('status', ['paid', 'partial'])
                ->sum('total'),

            'monthly_sales' => Invoice::where('tenant_id', $tenantId)
                ->whereMonth('created_at', Carbon::now()->month)
                ->whereIn('status', ['paid', 'partial'])
                ->sum('total'),

            'total_sales' => $totalSales,

            // Profit metrics
            'total_cogs' => $totalCOGS,
            'gross_profit' => $grossProfit,
            'total_expenses' => $totalExpenses,
            'net_profit' => $netProfit,

            // Inventory
            'total_products' => Product::where('tenant_id', $tenantId)
                ->count(),

            'low_stock' => Product::where('tenant_id', $tenantId)
                ->whereColumn('stock_quantity', '<=', 'low_stock_alert')
                ->count(),

            // Customers
            'total_customers' => Customer::where('tenant_id', $tenantId)
                ->count(),

            // Invoice stats
            'total_invoices' => Invoice::where('tenant_id', $tenantId)
                ->where('status', '!=', 'cancelled')
                ->count(),

            'paid_invoices' => Invoice::where('tenant_id', $tenantId)
                ->where('status', 'paid')
                ->count(),

            'partial_invoices' => Invoice::where('tenant_id', $tenantId)
                ->where('status', 'partial')
                ->count(),

            'unpaid_invoices' => Invoice::where('tenant_id', $tenantId)
                ->where('status', 'unpaid')
                ->count(),

            'cancelled_invoices' => Invoice::where('tenant_id', $tenantId)
                ->where('status', 'cancelled')
                ->count(),

        ];
    }
}