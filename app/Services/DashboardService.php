<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Product;
use App\Models\Customer;
use Carbon\Carbon;
use App\Models\Expense;
use App\Models\InvoiceItem;
use Illuminate\Http\Request;

class DashboardService
{
    public function stats(Request $request): array
    {
        $tenantId = auth()->user()->tenant_id;

        // Date filters
        $startDate = $request->start_date
            ? Carbon::parse($request->start_date)->startOfDay()
            : Carbon::now()->startOfMonth();

        $endDate = $request->end_date
            ? Carbon::parse($request->end_date)->endOfDay()
            : Carbon::now()->endOfMonth();

        // Base invoice query
        $invoiceQuery = Invoice::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate, $endDate]);

        // Total revenue from paid + partial invoices
        $totalSales = $invoiceQuery->clone()
            ->whereIn('status', ['paid', 'partial'])
            ->sum('total');

        // Cost of sold products
        $totalCOGS = InvoiceItem::whereHas('invoice', function ($query) use (
            $tenantId,
            $startDate,
            $endDate
        ) {
            $query->where('tenant_id', $tenantId)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->whereIn('status', ['paid', 'partial']);

        })->get()->sum(function ($item) {

            return $item->quantity * $item->product->purchase_price;

        });

        // Extra business expenses
        $totalExpenses = Expense::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount');

        // Gross profit
        $grossProfit = $totalSales - $totalCOGS;

        // Net profit
        $netProfit = $grossProfit - $totalExpenses;

        return [

            // Today's sales
            'today_sales' => Invoice::where('tenant_id', $tenantId)
                ->whereDate('created_at', Carbon::today())
                ->whereIn('status', ['paid', 'partial'])
                ->sum('total'),

            // Filtered sales
            'monthly_sales' => $invoiceQuery->clone()
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
                ->whereBetween('created_at', [$startDate, $endDate])
                ->count(),

            'low_stock' => Product::where('tenant_id', $tenantId)
                ->whereColumn('stock_quantity', '<=', 'low_stock_alert')
                ->count(),
        
            // Products
            'total_products' => Product::where('tenant_id', $tenantId)
                    ->when($request->start_date || $request->end_date, function ($query) use ($startDate, $endDate) {

                        $query->whereBetween('created_at', [$startDate, $endDate]);

                    })
                    ->count(),
            // Customers
            'total_customers' => Customer::where('tenant_id', $tenantId)
                            ->when($request->start_date || $request->end_date, function ($query) use ($startDate, $endDate) {
                                $query->whereBetween('created_at', [$startDate, $endDate]);
                            })
                            ->count(),
            // Invoice stats
            'total_invoices' => Invoice::where('tenant_id', $tenantId)
                ->where('status', '!=', 'cancelled')
                ->when($request->start_date || $request->end_date, function ($query) use ($startDate, $endDate) {

                    $query->whereBetween('created_at', [$startDate, $endDate]);

                })
                ->count(),
            // Paid invoices
            'paid_invoices' => Invoice::where('tenant_id', $tenantId)
                ->where('status', 'paid')
                ->when($request->start_date || $request->end_date, function ($query) use ($startDate, $endDate) {

                    $query->whereBetween('created_at', [$startDate, $endDate]);

                })
                ->count(),

            // Partial invoices
            'partial_invoices' => Invoice::where('tenant_id', $tenantId)
                ->where('status', 'partial')
                ->when($request->start_date || $request->end_date, function ($query) use ($startDate, $endDate) {

                    $query->whereBetween('created_at', [$startDate, $endDate]);

                })
                ->count(),

            // Unpaid invoices
            'unpaid_invoices' => Invoice::where('tenant_id', $tenantId)
                ->where('status', 'unpaid')
                ->when($request->start_date || $request->end_date, function ($query) use ($startDate, $endDate) {

                    $query->whereBetween('created_at', [$startDate, $endDate]);

                })
                ->count(),

            // Cancelled invoices
            'cancelled_invoices' => Invoice::where('tenant_id', $tenantId)
                ->where('status', 'cancelled')
                ->when($request->start_date || $request->end_date, function ($query) use ($startDate, $endDate) {

                    $query->whereBetween('created_at', [$startDate, $endDate]);

                })
                ->count(),
           
        ];
    }
    public function stats2(Request $request): array
    {
        
        // Base invoice query
        $invoiceQuery = Invoice::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate, $endDate]);

        return [

            // Sales
            'today_sales' => Invoice::where('tenant_id', $tenantId)
                ->whereDate('created_at', Carbon::today())
                ->whereIn('status', ['paid', 'partial'])
                ->sum('total'),

            'monthly_sales' => $invoiceQuery
                ->clone()
                ->whereIn('status', ['paid', 'partial'])
                ->sum('total'),

            'totalSales' => $invoiceQuery
                ->clone()
                ->whereIn('status', ['paid', 'partial'])
                ->sum('total'),

            // Products
            'total_products' => Product::where('tenant_id', $tenantId)->count(),

            'low_stock' => Product::where('tenant_id', $tenantId)
                ->whereColumn('stock_quantity', '<=', 'low_stock_alert')
                ->count(),

            // Customers
            'total_customers' => Customer::where('tenant_id', $tenantId)->count(),
            

            // Invoice counts
            'total_invoices' => $invoiceQuery
                ->clone()
                ->where('status', '!=', 'cancelled')
                ->count(),

            'paid_invoices' => $invoiceQuery
                ->clone()
                ->where('status', 'paid')
                ->count(),

            'partial_invoices' => $invoiceQuery
                ->clone()
                ->where('status', 'partial')
                ->count(),

            'unpaid_invoices' => $invoiceQuery
                ->clone()
                ->where('status', 'unpaid')
                ->count(),

            'cancelled_invoices' => $invoiceQuery
                ->clone()
                ->where('status', 'cancelled')
                ->count(),
        ];
    }

}