<?php

namespace App\Http\Controllers;

use App\Services\AnalyticsService;
use App\Services\DashboardService;
use App\Services\TenantModuleService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(
        Request $request,
        DashboardService $dashboardService,
        AnalyticsService $analyticsService,
        TenantModuleService $modules
    ) {
        $tenant = $request->user()->tenant;
        $enabledModuleKeys = $modules->enabledModuleKeys($tenant);
        $hasModule = fn (string $module): bool => in_array($module, $enabledModuleKeys, true);

        $stats = $dashboardService->stats($request);

        $chartData = $analyticsService->monthlyChartData($request);
        $invoiceChart = $analyticsService->invoiceStatusData($request);
        $topProducts = $analyticsService->topProducts($request);
        $recentInvoices = $analyticsService->recentInvoices($request);
        $lowStockProducts = $analyticsService->lowStockProducts();

        return view('dashboard', compact(
            'stats',
            'chartData',
            'invoiceChart',
            'topProducts',
            'recentInvoices',
            'lowStockProducts',
            'enabledModuleKeys',
            'hasModule'
        ));
    }
}
