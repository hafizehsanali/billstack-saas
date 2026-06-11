<?php

namespace Database\Seeders;

use App\Models\PlanFeature;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Services\TenantSubscriptionService;
use Illuminate\Database\Seeder;

class SaasPlanSeeder extends Seeder
{
    public function run(): void
    {
        $features = collect([
            ['name' => 'Core POS and invoices', 'key' => 'core.pos', 'is_paid' => false],
            ['name' => 'Inventory and stock alerts', 'key' => 'core.inventory', 'is_paid' => false],
            ['name' => 'Customer and supplier ledgers', 'key' => 'core.accounts', 'is_paid' => false],
            ['name' => 'Profit and stock reports', 'key' => 'core.reports', 'is_paid' => false],
            ['name' => 'Barcode scanning', 'key' => 'pro.barcode', 'is_paid' => true],
            ['name' => 'AI business insights', 'key' => 'pro.ai_insights', 'is_paid' => true],
            ['name' => 'Advanced team permissions', 'key' => 'pro.team_permissions', 'is_paid' => true],
        ])->map(fn (array $feature) => PlanFeature::updateOrCreate(
            ['key' => $feature['key']],
            $feature
        ));

        $starter = SubscriptionPlan::updateOrCreate(
            ['slug' => 'starter'],
            [
                'name' => 'Starter',
                'description' => 'Free plan for small teams starting with core billing and inventory.',
                'monthly_price_cents' => 0,
                'annual_price_cents' => 0,
                'user_limit' => 2,
                'trial_days' => 0,
                'free_access_days' => 30,
                'product_limit' => 100,
                'monthly_invoice_limit' => 100,
                'is_public' => true,
                'is_active' => true,
            ]
        );

        $growth = SubscriptionPlan::updateOrCreate(
            ['slug' => 'growth'],
            [
                'name' => 'Growth',
                'description' => 'Paid plan for stores that need larger teams and faster checkout tools.',
                'monthly_price_cents' => 299900,
                'annual_price_cents' => 2999000,
                'user_limit' => 8,
                'trial_days' => 14,
                'free_access_days' => null,
                'product_limit' => 1000,
                'monthly_invoice_limit' => 1000,
                'is_public' => true,
                'is_active' => true,
            ]
        );

        $professional = SubscriptionPlan::updateOrCreate(
            ['slug' => 'professional'],
            [
                'name' => 'Professional',
                'description' => 'Full paid package for established businesses ready to activate immediately.',
                'monthly_price_cents' => 499900,
                'annual_price_cents' => 4999000,
                'user_limit' => 15,
                'trial_days' => 0,
                'free_access_days' => null,
                'product_limit' => null,
                'monthly_invoice_limit' => null,
                'is_public' => true,
                'is_active' => true,
            ]
        );

        $starter->features()->sync($features->where('is_paid', false)->pluck('id')->all());
        $growth->features()->sync($features->pluck('id')->all());
        $professional->features()->sync($features->pluck('id')->all());

        $subscriptions = app(TenantSubscriptionService::class);

        Tenant::query()
            ->doesntHave('activeSubscription')
            ->each(fn (Tenant $tenant) => $subscriptions->assignDefaultPlan($tenant));
    }
}
