<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\PlanFeature;
use App\Models\PlatformSubscriptionInvoice;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $tenants = Tenant::with(['activeSubscription.plan', 'users'])
            ->latest()
            ->take(10)
            ->get();

        return view('platform.dashboard', [
            'tenantCount' => Tenant::count(),
            'activeTenantCount' => Tenant::whereHas('activeSubscription')->count(),
            'planCount' => SubscriptionPlan::where('is_active', true)->count(),
            'paidFeatureCount' => PlanFeature::where('is_paid', true)->count(),
            'platformDueCents' => PlatformSubscriptionInvoice::sum('balance_cents'),
            'platformAdminCount' => User::where('is_platform_admin', true)->count(),
            'tenants' => $tenants,
            'plans' => SubscriptionPlan::withCount('features')->orderBy('monthly_price_cents')->get(),
        ]);
    }
}
