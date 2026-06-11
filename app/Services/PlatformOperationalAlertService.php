<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\PlatformSubscriptionInvoice;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use Illuminate\Support\Collection;

class PlatformOperationalAlertService
{
    public function usageAlerts(): Collection
    {
        $tenants = Tenant::with('activeSubscription.plan')
            ->whereHas('activeSubscription.plan', function ($query) {
                $query->whereNotNull('product_limit')
                    ->orWhereNotNull('monthly_invoice_limit');
            })
            ->get();

        if ($tenants->isEmpty()) {
            return collect();
        }

        $tenantIds = $tenants->pluck('id');
        $productCounts = Product::withoutGlobalScope('tenant')
            ->whereIn('tenant_id', $tenantIds)
            ->selectRaw('tenant_id, COUNT(*) as aggregate')
            ->groupBy('tenant_id')
            ->pluck('aggregate', 'tenant_id');
        $invoiceCounts = Invoice::withoutGlobalScope('tenant')
            ->whereIn('tenant_id', $tenantIds)
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->selectRaw('tenant_id, COUNT(*) as aggregate')
            ->groupBy('tenant_id')
            ->pluck('aggregate', 'tenant_id');

        return $tenants
            ->flatMap(function (Tenant $tenant) use ($productCounts, $invoiceCounts) {
                $plan = $tenant->activeSubscription->plan;

                return collect([
                    $this->usageAlert(
                        $tenant,
                        'Products',
                        (int) ($productCounts[$tenant->id] ?? 0),
                        $plan->product_limit
                    ),
                    $this->usageAlert(
                        $tenant,
                        'Monthly invoices',
                        (int) ($invoiceCounts[$tenant->id] ?? 0),
                        $plan->monthly_invoice_limit
                    ),
                ])->filter();
            })
            ->sortByDesc('percentage')
            ->take(8)
            ->values();
    }

    public function expiringSubscriptions(): Collection
    {
        $deadline = now()->addDays(7)->endOfDay();

        return TenantSubscription::with(['tenant', 'plan'])
            ->where('status', 'active')
            ->where(function ($query) use ($deadline) {
                $query->whereBetween('trial_ends_at', [now(), $deadline])
                    ->orWhereBetween('ends_at', [now(), $deadline]);
            })
            ->get()
            ->map(function (TenantSubscription $subscription) use ($deadline) {
                $dates = collect([
                    'Trial' => $subscription->trial_ends_at,
                    'Subscription' => $subscription->ends_at,
                ])
                    ->filter()
                    ->filter(fn ($date) => $date->between(now(), $deadline))
                    ->sort();

                return [
                    'subscription' => $subscription,
                    'label' => $dates->keys()->first(),
                    'expires_at' => $dates->first(),
                ];
            })
            ->sortBy('expires_at')
            ->take(8)
            ->values();
    }

    public function overdueInvoices(): Collection
    {
        return PlatformSubscriptionInvoice::with('tenant')
            ->where('status', '!=', 'paid')
            ->where('balance_cents', '>', 0)
            ->whereDate('due_on', '<', today())
            ->oldest('due_on')
            ->take(8)
            ->get();
    }

    private function usageAlert(
        Tenant $tenant,
        string $metric,
        int $used,
        ?int $limit
    ): ?array {
        if ($limit === null || $limit < 1) {
            return null;
        }

        $percentage = (int) round(($used / $limit) * 100);

        if ($percentage < 80) {
            return null;
        }

        return [
            'tenant' => $tenant,
            'metric' => $metric,
            'used' => $used,
            'limit' => $limit,
            'percentage' => min($percentage, 100),
            'at_limit' => $used >= $limit,
        ];
    }
}
