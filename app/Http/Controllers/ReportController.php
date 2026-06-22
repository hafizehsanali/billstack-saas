<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function dailySales()
    {
        $invoices = Invoice::whereIn('status', ['paid', 'partial'])
            ->whereDate('sale_date', today())
            ->latest()
            ->get();

        $totalSales = $invoices->sum('total');
        $totalReceived = $invoices->sum('paid_amount');
        $totalReceivable = $invoices->sum('remaining_amount');

        return view(
            'reports.daily-sales',
            compact('invoices', 'totalSales', 'totalReceived', 'totalReceivable')
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
        $totalReceived = $invoices->sum('paid_amount');
        $totalReceivable = $invoices->sum('remaining_amount');

        return view(
            'reports.monthly-sales',
            compact('invoices', 'totalSales', 'totalReceived', 'totalReceivable')
        );
    }

    public function stock()
    {
        $products = Product::latest()->get();
        $stockValue = $products->sum(fn ($product) => $product->stock_quantity * $product->purchase_price);
        $lowStockCount = $products
            ->filter(fn ($product) => $product->stock_quantity > 0 && $product->stock_quantity <= $product->low_stock_alert)
            ->count();
        $outOfStockCount = $products->where('stock_quantity', '<=', 0)->count();

        return view(
            'reports.stock',
            compact('products', 'stockValue', 'lowStockCount', 'outOfStockCount')
        );
    }

    public function lowStock()
    {
        $products = Product::whereColumn(
            'stock_quantity',
            '<=',
            'low_stock_alert'
        )->get();
        $outOfStockCount = $products->where('stock_quantity', '<=', 0)->count();

        return view(
            'reports.low-stock',
            compact('products', 'outOfStockCount')
        );
    }

    public function profitLoss(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;
        $startDate = $request->start_date
            ? Carbon::parse($request->start_date)->startOfDay()
            : now()->startOfMonth();
        $endDate = $request->end_date
            ? Carbon::parse($request->end_date)->endOfDay()
            : now()->endOfMonth();

        $invoiceQuery = Invoice::where('tenant_id', $tenantId)
            ->whereIn('status', ['paid', 'partial'])
            ->whereBetween('sale_date', [$startDate, $endDate]);

        $sales = (clone $invoiceQuery)
            ->sum('total');

        $cogs = InvoiceItem::whereHas('invoice', function ($query) use ($tenantId, $startDate, $endDate) {
            $query->where('tenant_id', $tenantId)
                ->whereIn('status', ['paid', 'partial'])
                ->whereBetween('sale_date', [$startDate, $endDate]);
        })
            ->with(['product', 'variant'])
            ->get()
            ->sum(fn ($item) => $item->quantity * ($item->variant?->purchase_price ?? $item->product?->purchase_price ?? 0));

        $expenses = Expense::where('tenant_id', $tenantId)
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->sum('amount');

        $grossProfit = $sales - $cogs;
        $netProfit = $grossProfit - $expenses;
        $invoiceCount = (clone $invoiceQuery)->count();

        return view(
            'reports.profit-loss',
            compact(
                'startDate',
                'endDate',
                'sales',
                'cogs',
                'grossProfit',
                'expenses',
                'netProfit',
                'invoiceCount'
            )
        );
    }
}
