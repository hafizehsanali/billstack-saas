<?php

namespace Tests\Feature;

use App\Models\PlanFeature;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PlatformPlanManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_admin_can_create_feature_and_plan(): void
    {
        $admin = User::factory()->create([
            'tenant_id' => null,
            'is_platform_admin' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('platform.features.store'), [
                'name' => 'Barcode Scanner',
                'key' => 'pro.barcode_scanner',
                'description' => 'Fast checkout scanning.',
                'is_paid' => '1',
            ])
            ->assertRedirect(route('platform.features.index'));

        $feature = PlanFeature::where('key', 'pro.barcode_scanner')->firstOrFail();

        $this->actingAs($admin)
            ->post(route('platform.plans.store'), [
                'name' => 'Growth Plus',
                'slug' => 'growth-plus',
                'description' => 'Paid plan for larger shops.',
                'monthly_price' => '4999.00',
                'annual_price' => '49990.00',
                'user_limit' => '10',
                'trial_days' => '14',
                'is_public' => '1',
                'is_active' => '1',
                'features' => [$feature->id],
            ])
            ->assertRedirect(route('platform.plans.index'));

        $plan = SubscriptionPlan::where('slug', 'growth-plus')->firstOrFail();

        $this->assertSame(499900, $plan->monthly_price_cents);
        $this->assertSame(4999000, $plan->annual_price_cents);
        $this->assertSame(14, $plan->trial_days);
        $this->assertTrue($plan->features()->whereKey($feature->id)->exists());
    }

    public function test_platform_admin_can_update_plan_features(): void
    {
        $admin = User::factory()->create([
            'tenant_id' => null,
            'is_platform_admin' => true,
        ]);

        $plan = SubscriptionPlan::create([
            'name' => 'Starter',
            'slug' => 'starter',
            'monthly_price_cents' => 0,
            'annual_price_cents' => 0,
            'user_limit' => 2,
        ]);

        $feature = PlanFeature::create([
            'name' => 'AI Insights',
            'key' => 'pro.ai_insights',
            'is_paid' => true,
        ]);

        $this->actingAs($admin)
            ->put(route('platform.plans.update', $plan), [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'Free plan',
                'monthly_price' => '0.00',
                'annual_price' => '0.00',
                'user_limit' => '3',
                'trial_days' => '30',
                'is_public' => '1',
                'is_active' => '1',
                'features' => [$feature->id],
            ])
            ->assertRedirect(route('platform.plans.index'));

        $this->assertSame(3, $plan->fresh()->user_limit);
        $this->assertSame(0, $plan->fresh()->trial_days);
        $this->assertTrue($plan->features()->whereKey($feature->id)->exists());
    }

    public function test_store_user_cannot_access_platform_plan_management(): void
    {
        $tenant = Tenant::create(['name' => 'Demo Store', 'slug' => 'demo-store']);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        Role::findOrCreate('owner');
        $user->assignRole('owner');

        $this->actingAs($user)
            ->get(route('platform.plans.index'))
            ->assertForbidden();
    }
}
