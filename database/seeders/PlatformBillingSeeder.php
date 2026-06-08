<?php

namespace Database\Seeders;

use App\Models\PlatformSubscriptionInvoice;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class PlatformBillingSeeder extends Seeder
{
    public function run(): void
    {
        $growthPlan = SubscriptionPlan::where('slug', 'growth')->first();
        $demoGrowthTenant = Tenant::where('slug', 'demo-store-2')->first();

        if ($growthPlan && $demoGrowthTenant) {
            $demoGrowthTenant->subscriptions()->updateOrCreate(
                [
                    'subscription_plan_id' => $growthPlan->id,
                    'status' => 'active',
                ],
                [
                    'starts_at' => now()->startOfMonth(),
                    'trial_ends_at' => null,
                    'ends_at' => null,
                ]
            );
        }

        Tenant::with('currentSubscription.plan')
            ->get()
            ->each(function (Tenant $tenant): void {
                $subscription = $tenant->currentSubscription;

                if (! $subscription?->plan || $subscription->plan->monthly_price_cents === 0) {
                    return;
                }

                $total = $subscription->plan->monthly_price_cents;
                $paid = (int) floor($total / 2);
                $balance = $total - $paid;

                $invoice = PlatformSubscriptionInvoice::updateOrCreate(
                    [
                        'invoice_no' => 'PLAT-'.$tenant->id.'-'.now()->format('Ym'),
                    ],
                    [
                        'tenant_id' => $tenant->id,
                        'tenant_subscription_id' => $subscription->id,
                        'billing_period' => now()->format('F Y'),
                        'subtotal_cents' => $total,
                        'discount_cents' => 0,
                        'tax_cents' => 0,
                        'total_cents' => $total,
                        'paid_cents' => $paid,
                        'balance_cents' => $balance,
                        'status' => $balance > 0 ? 'partial' : 'paid',
                        'issued_on' => now()->startOfMonth()->toDateString(),
                        'due_on' => now()->startOfMonth()->addDays(10)->toDateString(),
                        'notes' => 'Demo platform subscription invoice.',
                    ]
                );

                $invoice->payments()->updateOrCreate(
                    ['reference_no' => 'PLAT-PAY-'.$tenant->id.'-'.now()->format('Ym')],
                    [
                        'tenant_id' => $tenant->id,
                        'amount_cents' => $paid,
                        'payment_method' => 'manual',
                        'paid_on' => now()->startOfMonth()->addDays(2)->toDateString(),
                        'notes' => 'Demo partial subscription payment.',
                    ]
                );
            });
    }
}
