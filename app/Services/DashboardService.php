<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Product;
use App\Models\Customer;
use Carbon\Carbon;

class DashboardService
{
    public function stats(): array
    {
        $tenantId = auth()->user()->tenant_id;

        return [

            'today_sales' => Invoice::where('tenant_id', $tenantId)
                ->whereDate('created_at', Carbon::today())
                ->sum('total'),

            'monthly_sales' => Invoice::where('tenant_id', $tenantId)
                ->whereMonth('created_at', Carbon::now()->month)
                ->sum('total'),

            'total_products' => Product::where('tenant_id', $tenantId)->count(),

            'low_stock' => Product::where('tenant_id', $tenantId)
                ->whereColumn('stock_quantity', '<=', 'low_stock_alert')
                ->count(),

            'total_customers' => Customer::where('tenant_id', $tenantId)->count(),

            'total_invoices' => Invoice::where('tenant_id', $tenantId)->count(),

        ];
    }
}