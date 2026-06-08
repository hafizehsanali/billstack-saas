<?php

namespace Tests\Feature;

use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SaasPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TenantTeamManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_staff_user_with_role(): void
    {
        $owner = $this->createOwnerWithTenant(userLimit: 3);

        $this->actingAs($owner)
            ->post(route('team.store'), [
                'name' => 'Counter Cashier',
                'email' => 'cashier@example.com',
                'role' => 'cashier',
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
            ->assertRedirect(route('team.index'));

        $staff = User::where('email', 'cashier@example.com')->firstOrFail();

        $this->assertSame($owner->tenant_id, $staff->tenant_id);
        $this->assertTrue($staff->hasRole('cashier'));
    }

    public function test_staff_user_cannot_manage_team(): void
    {
        $owner = $this->createOwnerWithTenant(userLimit: 3);
        $staff = User::factory()->create(['tenant_id' => $owner->tenant_id]);
        $staff->assignRole('cashier');

        $this->actingAs($staff)
            ->get(route('team.index'))
            ->assertForbidden();
    }

    public function test_team_creation_respects_plan_user_limit(): void
    {
        $owner = $this->createOwnerWithTenant(userLimit: 1);

        $this->actingAs($owner)
            ->post(route('team.store'), [
                'name' => 'Extra Staff',
                'email' => 'extra@example.com',
                'role' => 'cashier',
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
            ->assertSessionHasErrors('team');

        $this->assertDatabaseMissing('users', [
            'email' => 'extra@example.com',
        ]);
    }

    public function test_inactive_staff_do_not_count_against_plan_user_limit(): void
    {
        $owner = $this->createOwnerWithTenant(userLimit: 2);

        $inactiveStaff = User::factory()->create([
            'tenant_id' => $owner->tenant_id,
            'email' => 'inactive-staff@example.com',
            'is_active' => false,
        ]);
        $inactiveStaff->assignRole('cashier');

        $this->actingAs($owner)
            ->post(route('team.store'), [
                'name' => 'New Cashier',
                'email' => 'new-cashier@example.com',
                'role' => 'cashier',
                'password' => 'password',
                'password_confirmation' => 'password',
            ])
            ->assertRedirect(route('team.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'new-cashier@example.com',
            'tenant_id' => $owner->tenant_id,
            'is_active' => true,
        ]);
    }

    public function test_owner_cannot_manage_users_from_another_tenant(): void
    {
        $owner = $this->createOwnerWithTenant(userLimit: 3);
        $otherOwner = $this->createOwnerWithTenant('Other Store', 'other@example.com', 3);

        $this->actingAs($owner)
            ->get(route('team.edit', $otherOwner))
            ->assertForbidden();
    }

    public function test_deactivated_user_cannot_login(): void
    {
        $owner = $this->createOwnerWithTenant(userLimit: 3);
        $staff = User::factory()->create([
            'tenant_id' => $owner->tenant_id,
            'email' => 'inactive@example.com',
            'password' => 'password',
            'is_active' => false,
        ]);
        $staff->assignRole('cashier');

        $this->post(route('login'), [
            'email' => 'inactive@example.com',
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    private function createOwnerWithTenant(
        string $storeName = 'Demo Store',
        string $email = 'owner@example.com',
        int $userLimit = 2
    ): User {
        $this->seed(RolePermissionSeeder::class);
        $this->seed(SaasPlanSeeder::class);

        $tenant = Tenant::create([
            'name' => $storeName,
            'slug' => str($storeName)->slug().'-'.fake()->unique()->numberBetween(100, 999),
        ]);

        $plan = SubscriptionPlan::where('slug', 'starter')->firstOrFail();
        $plan->update(['user_limit' => $userLimit]);
        $tenant->subscriptions()->create([
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now(),
        ]);

        $owner = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => $email,
        ]);

        Role::findOrCreate('owner');
        $owner->assignRole('owner');

        return $owner;
    }
}
