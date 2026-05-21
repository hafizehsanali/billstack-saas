<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Carbon\Carbon;

class AnalyticsService
{
    public function monthlyChartData(): array
    {
        $tenantId = auth()->user()->tenant_id;

        $months = [];
        $salesData = [];
        $profitData = [];

        // Last 12 months analytics
        for ($i = 11; $i >= 0; $i--) {

            $date = Carbon::now()->subMonths($i);

            $monthName = $date->format('M');

            // Monthly sales
            $sales = Invoice::where('tenant_id', $tenantId)
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->whereIn('status', ['paid', 'partial'])
                ->sum('total');

            // Product cost
            $cogs = InvoiceItem::whereHas('invoice', function ($query) use ($tenantId, $date) {

                $query->where('tenant_id', $tenantId)
                    ->whereYear('created_at', $date->year)
                    ->whereMonth('created_at', $date->month)
                    ->whereIn('status', ['paid', 'partial']);

            })->get()->sum(function ($item) {

                return $item->quantity * $item->product->purchase_price;

            });

            // Expenses
            $expenses = Expense::where('tenant_id', $tenantId)
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->sum('amount');

            // Net profit
            $profit = $sales - $cogs - $expenses;

            $months[] = $monthName;
            $salesData[] = round($sales, 2);
            $profitData[] = round($profit, 2);
        }

        return [
            'months' => $months,
            'sales' => $salesData,
            'profits' => $profitData,
        ];
    }
}