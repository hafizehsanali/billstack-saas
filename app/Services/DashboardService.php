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

                    // Realized revenue only
                    'today_sales' => Invoice::where('tenant_id', $tenantId)
                        ->whereDate('created_at', Carbon::today())
                        ->whereIn('status', ['paid', 'partial'])
                        ->sum('total'),

                    // Current month revenue
                    'monthly_sales' => Invoice::where('tenant_id', $tenantId)
                        ->whereMonth('created_at', Carbon::now()->month)
                        ->whereIn('status', ['paid', 'partial'])
                        ->sum('total'),

                    // Lifetime revenue
                    'total_sales' => Invoice::where('tenant_id', $tenantId)
                        ->whereIn('status', ['paid', 'partial'])
                        ->sum('total'),

                    // Inventory stats
                    'total_products' => Product::where('tenant_id', $tenantId)->count(),

                    'low_stock' => Product::where('tenant_id', $tenantId)
                        ->whereColumn('stock_quantity', '<=', 'low_stock_alert')
                        ->count(),

                    // CRM stats
                    'total_customers' => Customer::where('tenant_id', $tenantId)->count(),

                    // Ignore cancelled invoices
                    'total_invoices' => Invoice::where('tenant_id', $tenantId)
                        ->where('status', '!=', 'cancelled')
                        ->count(),

                    // Invoice status analytics
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