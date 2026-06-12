<?php

namespace Tests\Feature;

use App\Models\PlatformActivityLog;
use App\Models\PlatformSetting;
use App\Models\PlatformSubscriptionInvoice;
use App\Models\SubscriptionPaymentSubmission;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PlatformActivitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PlatformActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_changes_are_recorded_with_actor_and_tenant_context(): void
    {
        $admin = $this->platformAdmin();
        [$tenant, $plan] = $this->tenantAndPlan();

        $this->actingAs($admin)
            ->put(route('platform.tenants.update', $tenant), [
                'subscription_plan_id' => $plan->id,
                'status' => 'active',
                'trial_ends_at' => null,
                'ends_at' => null,
            ])
            ->assertRedirect(route('platform.tenants.index'));

        $this->assertDatabaseHas('platform_activity_logs', [
            'actor_id' => $admin->id,
            'tenant_id' => $tenant->id,
            'action' => 'tenant.subscription_updated',
        ]);

        $this->actingAs($admin)
            ->put(route('platform.settings.update'), [
                'platform_name' => 'BillStack Cloud',
                'support_email' => 'support@example.com',
                'support_phone' => null,
                'currency_code' => 'PKR',
                'payment_instructions' => 'Pay the complete subscription invoice.',
                'allow_registration' => '1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('platform_activity_logs', [
            'actor_id' => $admin->id,
            'action' => 'settings.updated',
        ]);
    }

    public function test_payment_approval_is_recorded_without_changing_full_payment_rule(): void
    {
        $admin = $this->platformAdmin();
        [$tenant, $plan] = $this->tenantAndPlan();
        $owner = User::factory()->create(['tenant_id' => $tenant->id]);
        Role::findOrCreate('owner');
        $owner->assignRole('owner');
        $subscription = $tenant->subscriptions()->create([
            'subscription_plan_id' => $plan->id,
            'status' => 'paused',
            'starts_at' => now(),
        ]);
        $invoice = PlatformSubscriptionInvoice::create([
            'tenant_id' => $tenant->id,
            'tenant_subscription_id' => $subscription->id,
            'invoice_no' => 'PLAT-ACTIVITY-001',
            'billing_period' => now()->format('F Y'),
            'billing_cycle' => 'monthly',
            'subtotal_cents' => 299900,
            'discount_cents' => 0,
            'tax_cents' => 0,
            'total_cents' => 299900,
            'paid_cents' => 0,
            'balance_cents' => 299900,
            'status' => 'unpaid',
            'issued_on' => today(),
        ]);
        $submission = SubscriptionPaymentSubmission::create([
            'platform_subscription_invoice_id' => $invoice->id,
            'tenant_id' => $tenant->id,
            'submitted_by' => $owner->id,
            'payment_method' => 'bank_transfer',
            'reference_no' => 'ACTIVITY-REF',
            'paid_on' => today(),
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->post(route('platform.payment-submissions.approve', $submission))
            ->assertRedirect();

        $this->assertDatabaseHas('platform_activity_logs', [
            'actor_id' => $admin->id,
            'tenant_id' => $tenant->id,
            'action' => 'payment_submission.approved',
        ]);
        $this->assertSame(299900, $invoice->fresh()->paid_cents);
        $this->assertSame('paid', $invoice->fresh()->status);
    }

    public function test_activity_page_is_admin_only_and_can_filter_actions(): void
    {
        $admin = $this->platformAdmin();
        [$tenant] = $this->tenantAndPlan();
        $owner = User::factory()->create(['tenant_id' => $tenant->id]);

        PlatformActivityLog::create([
            'actor_id' => $admin->id,
            'action' => 'plan.updated',
            'description' => 'Updated Growth plan.',
        ]);
        PlatformActivityLog::create([
            'actor_id' => $admin->id,
            'action' => 'settings.updated',
            'description' => 'Updated platform settings.',
        ]);

        $this->actingAs($admin)
            ->get(route('platform.activities.index', ['action' => 'plan.updated']))
            ->assertOk()
            ->assertSee('Updated Growth plan.')
            ->assertDontSee('Updated platform settings.');

        $this->actingAs($owner)
            ->get(route('platform.activities.index'))
            ->assertForbidden();
    }

    public function test_activity_seeder_is_repeatable(): void
    {
        Tenant::create(['name' => 'Summit Hardware', 'slug' => 'demo-store-2']);
        User::factory()->create([
            'email' => 'platform@test.com',
            'tenant_id' => null,
            'is_platform_admin' => true,
        ]);

        $this->seed(PlatformActivitySeeder::class);
        $this->seed(PlatformActivitySeeder::class);

        $this->assertSame(
            1,
            PlatformActivityLog::where('action', 'demo.platform_review')->count()
        );
    }

    private function platformAdmin(): User
    {
        return User::factory()->create([
            'tenant_id' => null,
            'is_platform_admin' => true,
        ]);
    }

    private function tenantAndPlan(): array
    {
        $tenant = Tenant::create([
            'name' => fake()->company(),
            'slug' => fake()->unique()->slug(),
        ]);
        $plan = SubscriptionPlan::create([
            'name' => 'Growth',
            'slug' => fake()->unique()->slug(),
            'monthly_price_cents' => 299900,
            'annual_price_cents' => 2999000,
        ]);

        return [$tenant, $plan];
    }
}
