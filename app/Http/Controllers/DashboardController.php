<?php

namespace App\Http\Controllers;


use \App\Services\DashboardService;
use App\Services\AnalyticsService;

class DashboardController extends Controller
{
    
   public function index(DashboardService $dashboardService,AnalyticsService $analyticsService) 
   {
        $stats = $dashboardService->stats();

        // Charts
        $chartData = $analyticsService->monthlyChartData();

        // Dashboard widgets
        $invoiceChart = $analyticsService->invoiceStatusData();

        $topProducts = $analyticsService->topProducts();

        $recentInvoices = $analyticsService->recentInvoices();

        $lowStockProducts = $analyticsService->lowStockProducts();

        return view('dashboard', compact(
            'stats',
            'chartData',
            'invoiceChart',
            'topProducts',
            'recentInvoices',
            'lowStockProducts'
        ));
    }
    }