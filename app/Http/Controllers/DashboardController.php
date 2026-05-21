<?php

namespace App\Http\Controllers;


use \App\Services\DashboardService;
use App\Services\AnalyticsService;

class DashboardController extends Controller
{
    
    public function index(DashboardService $dashboardService, AnalyticsService $analyticsService) 
    {
        $stats = $dashboardService->stats();

        // Chart analytics
        $chartData = $analyticsService->monthlyChartData();

        return view('dashboard', compact('stats','chartData'));
    }
}