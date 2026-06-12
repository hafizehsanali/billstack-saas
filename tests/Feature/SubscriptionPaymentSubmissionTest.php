<?php

namespace Tests\Feature;

use App\Models\PlatformSubscriptionInvoice;
use App\Models\SubscriptionPaymentSubmission;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PlatformBillingSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SaasPlanSeeder;
use Database\Seeders\SubscriptionPaymentSubmissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SubscriptionPaymentSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_submit_full_payment_reference_once(): void
    {
        [$owner, $invoice] = $this->scenario();

        $this->actingAs($owner)
            ->post(route('subscription.payment-submissions.store', $invoice), [
                'payment_method' => 'bank_transfer',
                'reference_no' => 'BANK-OWNER-001',
                'paid_on' => today()->toDateString(),
                'notes' => 'Full subscription payment.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('subscription_payment_submissions', [
            'platform_subscription_invoice_id' => $invoice->id,
            'tenant_id' => $owner->tenant_id,
            'reference_no' => 'BANK-OWNER-001',
            'status' => 'pending',
        ]);

        $this->actingAs($owner)
            ->post(route('subscription.payment-submissions.store', $invoice), [
                'payment_method' => 'bank_transfer',
                'reference_no' => 'BANK-DUPLICATE',
                'paid_on' => today()->toDateString(),
            ])
            ->assertSessionHasErrors('payment');

        $this->assertSame(1, SubscriptionPaymentSubmission::count());
    }

    public function test_owner_cannot_submit_payment_for_another_business_invoice(): void
    {
        [$owner] = $this->scenario();
        [, $foreignInvoice] = $this->scenario('Foreign Store');

        $this->actingAs($owner)
            ->post(route('subscription.payment-submissions.store', $foreignInvoice), [
                'payment_method' => 'bank_transfer',
                'reference_no' => 'FOREIGN-REF',
                'paid_on' => today()->toDateString(),
            ])
            ->assertSessionHasErrors('payment');

        $this->assertDatabaseMissing('subscription_payment_submissions', [
            'reference_no' => 'FOREIGN-REF',
        ]);
    }

    public function test_admin_approval_records_full_payment_and_activates_subscription(): void
    {
        [$owner, $invoice, $subscription] = $this->scenario();
        $this->submit($owner, $invoice, 'APPROVE-REF');
        $submission = SubscriptionPaymentSubmission::firstOrFail();
        $admin = $this->platformAdmin();

        $this->actingAs($admin)
            ->post(route('platform.payment-submissions.approve', $submission))
            ->assertRedirect();

        $invoice->refresh();

        $this->assertSame('approved', $submission->fresh()->status);
        $this->assertSame('paid', $invoice->status);
        $this->assertSame($invoice->total_cents, $invoice->paid_cents);
        $this->assertSame(0, $invoice->balance_cents);
        $this->assertSame($invoice->total_cents, $invoice->payments()->first()->amount_cents);
        $this->assertSame('active', $subscription->fresh()->status);
    }

    public function test_rejected_submission_can_be_corrected_and_resubmitted(): void
    {
        [$owner, $invoice] = $this->scenario();
        $this->submit($owner, $invoice, 'WRONG-REF');
        $submission = SubscriptionPaymentSubmission::firstOrFail();
        $admin = $this->platformAdmin();

        $this->actingAs($admin)
            ->post(route('platform.payment-submissions.reject', $submission), [
                'rejection_reason' => 'Reference could not be verified.',
            ])
            ->assertRedirect();

        $this->actingAs($owner)
            ->post(route('subscription.payment-submissions.store', $invoice), [
                'payment_method' => 'bank_transfer',
                'reference_no' => 'CORRECT-REF',
                'paid_on' => today()->toDateString(),
            ])
            ->assertRedirect();

        $submission->refresh();
        $this->assertSame('pending', $submission->status);
        $this->assertSame('CORRECT-REF', $submission->reference_no);
        $this->assertNull($submission->rejection_reason);
    }

    public function test_manual_full_payment_also_closes_pending_submission(): void
    {
        [$owner, $invoice] = $this->scenario();
        $this->submit($owner, $invoice, 'MANUAL-CLOSE-REF');
        $submission = SubscriptionPaymentSubmission::firstOrFail();
        $admin = $this->platformAdmin();

        $this->actingAs($admin)
            ->post(route('platform.billing.payments.store', $invoice), [
                'payment_method' => 'manual',
                'paid_on' => today()->toDateString(),
                'reference_no' => 'ADMIN-MANUAL-REF',
            ])
            ->assertRedirect(route('platform.billing.show', $invoice));

        $this->assertSame('approved', $submission->fresh()->status);
        $this->assertSame($admin->id, $submission->fresh()->reviewed_by);
        $this->assertSame('paid', $invoice->fresh()->status);
    }

    public function test_payment_submission_seeder_is_repeatable(): void
    {
        $tenant = Tenant::create([
            'name' => 'Summit Hardware',
            'slug' => 'demo-store-2',
        ]);
        $this->seed(RolePermissionSeeder::class);
        $this->seed(SaasPlanSeeder::class);
        $owner = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'alpha@example.com',
        ]);
        $owner->assignRole('owner');
        $this->seed(PlatformBillingSeeder::class);
        $this->seed(SubscriptionPaymentSubmissionSeeder::class);
        $this->seed(SubscriptionPaymentSubmissionSeeder::class);

        $this->assertSame(1, SubscriptionPaymentSubmission::count());
        $this->assertSame('pending', SubscriptionPaymentSubmission::first()->status);
    }

    private function scenario(string $name = 'Payment Store'): array
    {
        Role::findOrCreate('owner');
        $tenant = Tenant::create([
            'name' => $name,
            'slug' => fake()->unique()->slug(),
        ]);
        $owner = User::factory()->create(['tenant_id' => $tenant->id]);
        $owner->assignRole('owner');
        $plan = SubscriptionPlan::create([
            'name' => $name.' Plan',
            'slug' => fake()->unique()->slug(),
            'monthly_price_cents' => 299900,
            'annual_price_cents' => 2999000,
        ]);
        $subscription = $tenant->subscriptions()->create([
            'subscription_plan_id' => $plan->id,
            'status' => 'paused',
            'starts_at' => now(),
        ]);
        $invoice = PlatformSubscriptionInvoice::create([
            'tenant_id' => $tenant->id,
            'tenant_subscription_id' => $subscription->id,
            'invoice_no' => 'PLAT-'.fake()->unique()->bothify('????-####'),
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
            'due_on' => today()->addDays(3),
        ]);

        return [$owner, $invoice, $subscription];
    }

    private function submit(User $owner, PlatformSubscriptionInvoice $invoice, string $reference): void
    {
        $this->actingAs($owner)->post(
            route('subscription.payment-submissions.store', $invoice),
            [
                'payment_method' => 'bank_transfer',
                'reference_no' => $reference,
                'paid_on' => today()->toDateString(),
            ]
        );
    }

    private function platformAdmin(): User
    {
        return User::factory()->create([
            'tenant_id' => null,
            'is_platform_admin' => true,
        ]);
    }
}
