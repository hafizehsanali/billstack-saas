<?php

namespace Tests\Feature;

use App\Models\PlatformSetting;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PlatformSettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PlatformSettingManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_update_platform_settings(): void
    {
        $admin = User::factory()->create([
            'tenant_id' => null,
            'is_platform_admin' => true,
        ]);

        $this->actingAs($admin)
            ->put(route('platform.settings.update'), [
                'platform_name' => 'Zephrant ERP Cloud',
                'support_email' => 'help@example.com',
                'support_phone' => '+92 300 1234567',
                'currency_code' => 'usd',
                'payment_instructions' => 'Pay the full invoice by bank transfer.',
                'payment_channels' => [
                    [
                        'key' => 'jazzcash',
                        'label' => 'JazzCash',
                        'account_title' => 'Zephrant Technologies',
                        'account_number' => '03001234567',
                        'instructions' => 'Use the invoice number as reference.',
                        'is_active' => '1',
                    ],
                ],
                'allow_registration' => '0',
            ])
            ->assertRedirect();

        $settings = PlatformSetting::current();

        $this->assertSame('Zephrant ERP Cloud', $settings->platform_name);
        $this->assertSame('USD', $settings->currency_code);
        $this->assertFalse($settings->allow_registration);
        $this->assertSame('jazzcash', $settings->activePaymentChannels()[0]['key']);
    }

    public function test_store_owner_cannot_access_platform_settings(): void
    {
        $tenant = Tenant::create(['name' => 'Demo Store', 'slug' => 'demo-store']);
        $owner = User::factory()->create(['tenant_id' => $tenant->id]);
        Role::findOrCreate('owner');
        $owner->assignRole('owner');

        $this->actingAs($owner)
            ->get(route('platform.settings.edit'))
            ->assertForbidden();
    }

    public function test_configured_platform_name_is_used_across_public_and_guest_pages(): void
    {
        PlatformSetting::current()->update([
            'platform_name' => 'TradeFlow Cloud',
            'support_email' => 'support@tradeflow.test',
        ]);

        foreach ([
            '/',
            route('plans.index'),
            route('legal.terms'),
            route('legal.privacy'),
            route('legal.refunds'),
            route('login'),
        ] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee('TradeFlow Cloud');
        }

        $this->get(route('legal.terms'))
            ->assertSee('support@tradeflow.test');
    }

    public function test_disabled_registration_blocks_form_and_submission(): void
    {
        PlatformSetting::current()->update([
            'platform_name' => 'Business Grower',
            'support_email' => 'support@example.com',
            'support_phone' => '+92 300 1234567',
            'allow_registration' => false,
        ]);

        $this->get(route('register'))
            ->assertOk()
            ->assertSee('New registrations are temporarily unavailable')
            ->assertSee('Business Grower')
            ->assertSee('support@example.com')
            ->assertSee('mailto:support@example.com', false)
            ->assertSee('tel:+923001234567', false)
            ->assertSee(route('login'), false)
            ->assertSee(route('plans.index'), false);

        $this->post(route('register'), [
            'name' => 'Blocked User',
            'business_name' => 'Blocked Store',
            'email' => 'blocked@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertForbidden();

        $this->assertDatabaseMissing('users', ['email' => 'blocked@example.com']);
    }

    public function test_checkout_uses_configured_payment_instructions(): void
    {
        Role::findOrCreate('owner');
        PlatformSetting::current()->update([
            'payment_instructions' => 'Send the complete payment using bank account 123.',
            'payment_channels' => [
                [
                    'key' => 'bank_transfer',
                    'label' => 'Bank Transfer',
                    'account_title' => 'Zephrant Technologies Collections',
                    'account_number' => 'PK00-TEST-123',
                    'instructions' => 'Use the invoice number as reference.',
                    'is_active' => true,
                ],
            ],
            'support_phone' => '+92 300 7654321',
        ]);

        $tenant = Tenant::create(['name' => 'Checkout Store', 'slug' => 'checkout-store']);
        $owner = User::factory()->create(['tenant_id' => $tenant->id]);
        $owner->assignRole('owner');
        $plan = SubscriptionPlan::create([
            'name' => 'Paid Plan',
            'slug' => 'paid-plan',
            'monthly_price_cents' => 100000,
            'annual_price_cents' => 1000000,
        ]);
        $subscription = $tenant->subscriptions()->create([
            'subscription_plan_id' => $plan->id,
            'status' => 'paused',
            'starts_at' => now(),
        ]);
        $subscription->platformInvoices()->create([
            'tenant_id' => $tenant->id,
            'invoice_no' => 'PLAT-SETTINGS-001',
            'billing_period' => now()->format('F Y'),
            'billing_cycle' => 'monthly',
            'subtotal_cents' => 100000,
            'discount_cents' => 0,
            'tax_cents' => 0,
            'total_cents' => 100000,
            'paid_cents' => 0,
            'balance_cents' => 100000,
            'status' => 'unpaid',
            'issued_on' => today(),
            'due_on' => today()->addDays(3),
        ]);

        $this->actingAs($owner)
            ->get(route('subscription.checkout'))
            ->assertOk()
            ->assertSee('Send the complete payment using bank account 123.')
            ->assertSee('Zephrant Technologies Collections')
            ->assertSee('PK00-TEST-123')
            ->assertSee('+92 300 7654321');
    }

    public function test_platform_settings_seeder_is_repeatable(): void
    {
        $this->seed(PlatformSettingSeeder::class);
        $this->seed(PlatformSettingSeeder::class);

        $this->assertSame(1, PlatformSetting::count());
        $this->assertSame('PKR', PlatformSetting::current()->currency_code);
    }
}
