<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Validation\ValidationException;

class TenantUsageLimitService
{
    public function assertCanCreateProduct(Tenant $tenant): void
    {
        $limit = $tenant->activeSubscription?->plan?->product_limit;

        if ($limit === null) {
            return;
        }

        $currentCount = Product::where('tenant_id', $tenant->id)->count();

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

        $currentCount = Invoice::where('tenant_id', $tenant->id)
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();

        if ($currentCount >= $limit) {
            throw ValidationException::withMessages([
                'plan_limit' => "Your plan allows up to {$limit} invoices per month. Upgrade the package to continue billing.",
            ]);
        }
    }
}
