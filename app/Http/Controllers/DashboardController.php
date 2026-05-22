<?php

namespace App\Http\Controllers;


use \App\Services\DashboardService;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    
   public function index( Request $request,DashboardService $dashboardService,AnalyticsService $analyticsService) 
   {
        $stats = $dashboardService->stats($request);

        // Analytics
        $chartData = $analyticsService->monthlyChartData($request);

        // Widgets
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
            'lowStockProducts'
        ));
    }
    }