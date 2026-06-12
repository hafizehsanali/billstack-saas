<?php

namespace Tests\Feature;

use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SaasPlanSeeder;
use Database\Seeders\TenantTeamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TenantTeamManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_staff_user_with_role(): void
    {
        Notification::fake();
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
        $this->assertTrue($staff->requires_password_setup);
        Notification::assertSentTo($staff, ResetPassword::class);
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

    public function test_owner_can_deactivate_and_reactivate_staff_user(): void
    {
        $owner = $this->createOwnerWithTenant(userLimit: 2);
        $staff = User::factory()->create([
            'tenant_id' => $owner->tenant_id,
            'is_active' => true,
        ]);
        $staff->assignRole('cashier');

        $this->actingAs($owner)
            ->patch(route('team.deactivate', $staff))
            ->assertSessionHas('success');

        $this->assertFalse($staff->fresh()->is_active);

        $this->actingAs($owner)
            ->patch(route('team.activate', $staff))
            ->assertSessionHas('success');

        $this->assertTrue($staff->fresh()->is_active);
    }

    public function test_owner_cannot_manage_another_owner_as_staff(): void
    {
        $owner = $this->createOwnerWithTenant(userLimit: 3);
        $otherOwner = User::factory()->create(['tenant_id' => $owner->tenant_id]);
        $otherOwner->assignRole('owner');

        $this->actingAs($owner)
            ->get(route('team.edit', $otherOwner))
            ->assertForbidden();
    }

    public function test_team_index_explains_staff_role_access(): void
    {
        $owner = $this->createOwnerWithTenant(userLimit: 3);

        $this->actingAs($owner)
            ->get(route('team.index'))
            ->assertOk()
            ->assertSee('Role Access Guide')
            ->assertSee('Can handle POS billing, customers, and invoice payments.');
    }

    public function test_tenant_team_seeder_creates_repeatable_demo_staff_accounts(): void
    {
        $this->seed(RolePermissionSeeder::class);

        Tenant::create([
            'name' => 'Northstar General Store',
            'slug' => 'demo-store-1',
        ]);

        $this->seed(TenantTeamSeeder::class);
        $this->seed(TenantTeamSeeder::class);

        $this->assertSame(1, User::where('email', 'cashier@test.com')->count());
        $cashier = User::where('email', 'cashier@test.com')->firstOrFail();
        $this->assertTrue($cashier->hasRole('cashier'));
        $this->assertTrue($cashier->hasVerifiedEmail());
        $this->assertDatabaseHas('users', [
            'email' => 'manager@test.com',
            'is_active' => false,
        ]);
    }

    public function test_owner_can_resend_staff_password_setup_invitation(): void
    {
        Notification::fake();
        $owner = $this->createOwnerWithTenant(userLimit: 3);
        $staff = User::factory()->create(['tenant_id' => $owner->tenant_id]);
        $staff->assignRole('cashier');

        $this->actingAs($owner)
            ->post(route('team.resend-invitation', $staff))
            ->assertSessionHas('success');

        Notification::assertSentTo($staff, ResetPassword::class);
    }

    public function test_owner_cannot_resend_invitation_to_user_from_another_tenant(): void
    {
        Notification::fake();
        $owner = $this->createOwnerWithTenant(userLimit: 3);
        $otherOwner = $this->createOwnerWithTenant('Other Store', 'other-owner@example.com', 3);

        $this->actingAs($owner)
            ->post(route('team.resend-invitation', $otherOwner))
            ->assertForbidden();

        Notification::assertNothingSent();
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
