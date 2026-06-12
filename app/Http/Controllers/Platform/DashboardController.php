<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\PlanFeature;
use App\Models\PlatformActivityLog;
use App\Models\PlatformSubscriptionInvoice;
use App\Models\SubscriptionPaymentSubmission;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PlatformOperationalAlertService;
use Illuminate\View\View;
use Illuminate\Support\Facades\File;

class DashboardController extends Controller
{
    public function index(PlatformOperationalAlertService $alerts): View
    {
        $tenants = Tenant::with(['activeSubscription.plan', 'users'])
            ->latest()
            ->take(10)
            ->get();

        return view('platform.dashboard', [
            'tenantCount' => Tenant::count(),
            'activeTenantCount' => Tenant::whereHas('activeSubscription')->count(),
            'inactiveTenantCount' => Tenant::whereDoesntHave('activeSubscription')->count(),
            'pendingPaymentCount' => SubscriptionPaymentSubmission::where('status', 'pending')->count(),
            'planCount' => SubscriptionPlan::where('is_active', true)->count(),
            'paidFeatureCount' => PlanFeature::where('is_paid', true)->count(),
            'platformDueCents' => PlatformSubscriptionInvoice::sum('balance_cents'),
            'platformAdminCount' => User::where('is_platform_admin', true)->count(),
            'tenants' => $tenants,
            'plans' => SubscriptionPlan::withCount('features')->orderBy('monthly_price_cents')->get(),
            'usageAlerts' => $alerts->usageAlerts(),
            'expiringSubscriptions' => $alerts->expiringSubscriptions(),
            'overdueInvoices' => $alerts->overdueInvoices(),
            'recentActivities' => PlatformActivityLog::with(['actor', 'tenant'])
                ->latest()
                ->take(6)
                ->get(),
            'backupStatus' => $this->backupStatus(),
        ]);
    }

    private function backupStatus(): ?array
    {
        $path = config('backup.path').DIRECTORY_SEPARATOR.'status.json';

        if (! File::exists($path)) {
            return null;
        }

        return json_decode(File::get($path), true);
    }
}
