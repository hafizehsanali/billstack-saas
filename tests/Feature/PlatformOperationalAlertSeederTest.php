<?php

namespace Tests\Feature;

use App\Models\PlatformSubscriptionInvoice;
use App\Models\Tenant;
use Database\Seeders\PlatformBillingSeeder;
use Database\Seeders\SaasPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformOperationalAlertSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_operational_alert_demo_data_is_repeatable(): void
    {
        $tenant = Tenant::create([
            'name' => 'Summit Hardware',
            'slug' => 'demo-store-2',
        ]);

        $this->seed(SaasPlanSeeder::class);
        $this->seed(PlatformBillingSeeder::class);
        $this->seed(PlatformBillingSeeder::class);

        $subscription = $tenant->fresh()->activeSubscription;

        $this->assertSame('demo-monitoring', $subscription->plan->slug);
        $this->assertTrue($subscription->ends_at->isBetween(
            now()->addDays(4),
            now()->addDays(6)
        ));
        $this->assertSame(
            1,
            PlatformSubscriptionInvoice::where('invoice_no', 'PLAT-DEMO-OVERDUE')->count()
        );
        $this->assertDatabaseHas('platform_subscription_invoices', [
            'tenant_id' => $tenant->id,
            'invoice_no' => 'PLAT-DEMO-OVERDUE',
            'paid_cents' => 0,
            'balance_cents' => 299900,
            'status' => 'unpaid',
        ]);
    }
}
