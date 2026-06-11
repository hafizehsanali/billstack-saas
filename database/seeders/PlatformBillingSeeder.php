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
        $monitoringPlan = SubscriptionPlan::updateOrCreate(
            ['slug' => 'demo-monitoring'],
            [
                'name' => 'Demo Monitoring',
                'description' => 'Internal demo plan used to display platform usage and renewal alerts.',
                'monthly_price_cents' => 299900,
                'annual_price_cents' => 2999000,
                'user_limit' => 8,
                'trial_days' => 0,
                'free_access_days' => null,
                'product_limit' => 5,
                'monthly_invoice_limit' => 5,
                'is_public' => false,
                'is_active' => true,
            ]
        );
        $monitoredTenant = Tenant::where('slug', 'demo-store-2')->first();

        if ($monitoredTenant) {
            $monitoredTenant->subscriptions()
                ->where('status', 'active')
                ->where('subscription_plan_id', '!=', $monitoringPlan->id)
                ->update([
                    'status' => 'cancelled',
                    'ends_at' => now(),
                ]);

            $monitoredSubscription = $monitoredTenant->subscriptions()->updateOrCreate(
                [
                    'subscription_plan_id' => $monitoringPlan->id,
                    'status' => 'active',
                ],
                [
                    'starts_at' => now()->startOfMonth(),
                    'trial_ends_at' => null,
                    'ends_at' => now()->addDays(5)->endOfDay(),
                ]
            );

            PlatformSubscriptionInvoice::updateOrCreate(
                ['invoice_no' => 'PLAT-DEMO-OVERDUE'],
                [
                    'tenant_id' => $monitoredTenant->id,
                    'tenant_subscription_id' => $monitoredSubscription->id,
                    'billing_period' => now()->subMonth()->format('F Y'),
                    'billing_cycle' => 'monthly',
                    'subtotal_cents' => 299900,
                    'discount_cents' => 0,
                    'tax_cents' => 0,
                    'total_cents' => 299900,
                    'paid_cents' => 0,
                    'balance_cents' => 299900,
                    'status' => 'unpaid',
                    'issued_on' => now()->subMonth()->startOfMonth()->toDateString(),
                    'due_on' => now()->subDays(7)->toDateString(),
                    'notes' => 'Demo overdue subscription invoice awaiting full payment.',
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
                        'paid_cents' => $total,
                        'balance_cents' => 0,
                        'status' => 'paid',
                        'issued_on' => now()->startOfMonth()->toDateString(),
                        'due_on' => now()->startOfMonth()->addDays(10)->toDateString(),
                        'notes' => 'Demo platform subscription invoice.',
                    ]
                );

                $invoice->payments()->updateOrCreate(
                    ['reference_no' => 'PLAT-PAY-'.$tenant->id.'-'.now()->format('Ym')],
                    [
                        'tenant_id' => $tenant->id,
                        'amount_cents' => $total,
                        'payment_method' => 'manual',
                        'paid_on' => now()->startOfMonth()->addDays(2)->toDateString(),
                        'notes' => 'Demo full subscription payment.',
                    ]
                );
            });
    }
}
