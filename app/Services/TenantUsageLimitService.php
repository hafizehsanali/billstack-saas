<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Validation\ValidationException;

class TenantUsageLimitService
{
    public function summary(Tenant $tenant): array
    {
        $plan = $tenant->activeSubscription?->plan;

        return [
            'products' => $this->usageItem(
                $this->productCount($tenant),
                $plan?->product_limit
            ),
            'monthly_invoices' => $this->usageItem(
                $this->monthlyInvoiceCount($tenant),
                $plan?->monthly_invoice_limit
            ),
        ];
    }

    public function assertCanCreateProduct(Tenant $tenant): void
    {
        $limit = $tenant->activeSubscription?->plan?->product_limit;

        if ($limit === null) {
            return;
        }

        $currentCount = $this->productCount($tenant);

        if ($currentCount >= $limit) {
            throw ValidationException::withMessages([
                'plan_limit' => "Your plan allows up to {$limit} products. Upgrade the package to add more.",
            ]);
        }
    }

    public function assertCanCreateInvoice(Tenant $tenant): void
    {
        $limit = $tenant->activeSubscription?->plan?->monthly_invoice_limit;

        if ($limit === null) {
            return;
        }

        $currentCount = $this->monthlyInvoiceCount($tenant);

        if ($currentCount >= $limit) {
            throw ValidationException::withMessages([
                'plan_limit' => "Your plan allows up to {$limit} invoices per month. Upgrade the package to continue billing.",
            ]);
        }
    }

    private function productCount(Tenant $tenant): int
    {
        return Product::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)
            ->count();
    }

    private function monthlyInvoiceCount(Tenant $tenant): int
    {
        return Invoice::withoutGlobalScope('tenant')
            ->where('tenant_id', $tenant->id)
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();
    }

    private function usageItem(int $used, ?int $limit): array
    {
        $percentage = $limit === null
            ? 0
            : min((int) round(($used / $limit) * 100), 100);

        return [
            'used' => $used,
            'limit' => $limit,
            'percentage' => $percentage,
            'status' => match (true) {
                $limit !== null && $used >= $limit => 'limit',
                $limit !== null && $percentage >= 80 => 'warning',
                default => 'normal',
            },
        ];
    }
}
