<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AnalyticsService
{
    public function monthlyChartData(Request $request): array
    {
        $tenantId = auth()->user()->tenant_id;

        // Date filters
        $startDate = $request->start_date
            ? Carbon::parse($request->start_date)
            : Carbon::now()->subMonths(11)->startOfMonth();

        $endDate = $request->end_date
            ? Carbon::parse($request->end_date)
            : Carbon::now()->endOfMonth();

        $months = [];
        $salesData = [];
        $profitData = [];

        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {

            $monthName = $currentDate->format('M Y');

            // Sales
            $sales = Invoice::where('tenant_id', $tenantId)
                ->whereYear('created_at', $currentDate->year)
                ->whereMonth('created_at', $currentDate->month)
                ->whereIn('status', ['paid', 'partial'])
                ->sum('total');

            // Product cost
            $cogs = InvoiceItem::whereHas('invoice', function ($query) use ($tenantId, $currentDate) {

                $query->where('tenant_id', $tenantId)
                    ->whereYear('created_at', $currentDate->year)
                    ->whereMonth('created_at', $currentDate->month)
                    ->whereIn('status', ['paid', 'partial']);

            })->get()->sum(function ($item) {

                return $item->quantity * $item->product->purchase_price;

            });

            // Expenses
            $expenses = Expense::where('tenant_id', $tenantId)
                ->whereYear('created_at', $currentDate->year)
                ->whereMonth('created_at', $currentDate->month)
                ->sum('amount');

            // Net profit
            $profit = $sales - $cogs - $expenses;

            $months[] = $monthName;

            $salesData[] = round($sales, 2);

            $profitData[] = round($profit, 2);

            $currentDate->addMonth();
        }

        return [
            'months' => $months,
            'sales' => $salesData,
            'profits' => $profitData,
        ];
    }
    public function invoiceStatusData(Request $request): array
    {
        $tenantId = auth()->user()->tenant_id;

        $startDate = $request->start_date
            ? Carbon::parse($request->start_date)->startOfDay()
            : Carbon::now()->startOfMonth();

        $endDate = $request->end_date
            ? Carbon::parse($request->end_date)->endOfDay()
            : Carbon::now()->endOfMonth();

        $query = Invoice::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate, $endDate]);

        return [

            'paid' => $query->clone()
                ->where('status', 'paid')
                ->count(),

            'partial' => $query->clone()
                ->where('status', 'partial')
                ->count(),

            'unpaid' => $query->clone()
                ->where('status', 'unpaid')
                ->count(),

            'cancelled' => $query->clone()
                ->where('status', 'cancelled')
                ->count(),
        ];
    }

    public function topProducts(Request $request)
    {
        $startDate = $request->start_date
            ? Carbon::parse($request->start_date)->startOfDay()
            : Carbon::now()->startOfMonth();

        $endDate = $request->end_date
            ? Carbon::parse($request->end_date)->endOfDay()
            : Carbon::now()->endOfMonth();

        return InvoiceItem::selectRaw('
                product_id,
                SUM(quantity) as total_qty,
                SUM(total) as total_sales
            ')
            ->whereHas('invoice', function ($query) use ($startDate, $endDate) {

                $query->where('tenant_id', auth()->user()->tenant_id)
                    ->whereBetween('created_at', [$startDate, $endDate]);

            })
            ->with('product')
            ->groupBy('product_id')
            ->orderByDesc('total_qty')
            ->take(5)
            ->get();
    }

    public function recentInvoices(Request $request)
    {
         $tenantId = auth()->user()->tenant_id;

        // Date filters
        $startDate = $request->start_date
            ? Carbon::parse($request->start_date)
            : Carbon::now()->subMonths(11)->startOfMonth();

        $endDate = $request->end_date
            ? Carbon::parse($request->end_date)
            : Carbon::now()->endOfMonth();

         //Recent Invoices
        return Invoice::where('tenant_id', $tenantId)
            ->where('status', '!=', 'cancelled')
            ->when($request->start_date || $request->end_date, function ($query) use ($startDate, $endDate) {

                $query->whereBetween('created_at', [$startDate, $endDate]);

            })
            ->latest()
            ->take(5)
            ->get();
    }

    public function lowStockProducts()
    {
       
        return \App\Models\Product::whereColumn(
                'stock_quantity',
                '<=',
                'low_stock_alert'
            )
            ->latest()
            ->take(5)
            ->get();
    }
}