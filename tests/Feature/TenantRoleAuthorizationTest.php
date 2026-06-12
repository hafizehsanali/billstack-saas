<?php

namespace Tests\Feature;

use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SaasPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantRoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_can_bill_but_cannot_access_finance_or_reports(): void
    {
        $cashier = $this->createTenantUser('cashier');

        $this->actingAs($cashier)
            ->get(route('invoices.create'))
            ->assertOk()
            ->assertSee('POS Billing')
            ->assertDontSee('Purchases');

        $this->actingAs($cashier)->get(route('purchases.index'))->assertForbidden();
        $this->actingAs($cashier)->get(route('reports.stock'))->assertForbidden();
    }

    public function test_inventory_staff_can_manage_stock_and_purchases_but_cannot_bill(): void
    {
        $inventoryStaff = $this->createTenantUser('inventory_staff');

        $this->actingAs($inventoryStaff)
            ->get(route('purchases.index'))
            ->assertOk()
            ->assertSee('Purchases')
            ->assertDontSee('POS Billing');

        $this->actingAs($inventoryStaff)->get(route('products.create'))->assertOk();
        $this->actingAs($inventoryStaff)->get(route('invoices.pos'))->assertForbidden();
    }

    public function test_accountant_can_access_reports_but_cannot_create_sales(): void
    {
        $accountant = $this->createTenantUser('accountant');

        $this->actingAs($accountant)->get(route('reports.stock'))->assertOk();
        $this->actingAs($accountant)->get(route('invoices.create'))->assertForbidden();
        $this->actingAs($accountant)->get(route('products.create'))->assertForbidden();
    }

    public function test_manager_cannot_access_owner_only_team_or_business_settings(): void
    {
        $manager = $this->createTenantUser('manager');

        $this->actingAs($manager)->get(route('dashboard'))->assertOk();
        $this->actingAs($manager)->get(route('team.index'))->assertForbidden();
        $this->actingAs($manager)->get(route('settings.business'))->assertForbidden();
    }

    private function createTenantUser(string $role): User
    {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(SaasPlanSeeder::class);

        $tenant = Tenant::create([
            'name' => str($role)->replace('_', ' ')->title().' Store',
            'slug' => $role.'-store',
        ]);

        $plan = SubscriptionPlan::where('slug', 'professional')->firstOrFail();
        $tenant->subscriptions()->create([
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now(),
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => true,
        ]);
        $user->assignRole($role);

        return $user;
    }
}
