<?php

namespace App\Http\Controllers;


use \App\Services\DashboardService;

class DashboardController extends Controller
{
    public function index(DashboardService $dashboardService)
    {
        $stats = $dashboardService->stats();

        return view('dashboard', compact('stats'));
    }
}