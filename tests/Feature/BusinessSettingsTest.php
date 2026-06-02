<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BusinessSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_settings_can_be_updated(): void
    {
        $tenant = Tenant::create([
            'name' => 'Old Store',
            'slug' => 'old-store',
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        Role::create(['name' => 'owner']);
        $user->assignRole('owner');

        $this->actingAs($user);

        $this
            ->put(route('settings.business.update'), [
                'name' => 'Updated Store',
                'email' => 'store@example.com',
                'phone' => '03000000000',
                'address' => 'Main Market',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'name' => 'Updated Store',
            'email' => 'store@example.com',
            'phone' => '03000000000',
            'address' => 'Main Market',
        ]);
    }
}
