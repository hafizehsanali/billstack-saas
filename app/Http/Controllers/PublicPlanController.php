<?php

namespace App\Http\Controllers;

use App\Models\SubscriptionPlan;
use Illuminate\View\View;

class PublicPlanController extends Controller
{
    public function index(): View
    {
        $plans = SubscriptionPlan::with('features')
            ->where('is_public', true)
            ->where('is_active', true)
            ->orderBy('monthly_price_cents')
            ->orderBy('name')
            ->get();

        return view('plans.index', compact('plans'));
    }
}
